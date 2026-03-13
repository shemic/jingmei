from __future__ import annotations

import json
import time
from typing import Any, Dict, List, Optional

import httpx

from dever.client import Client
from dever.error import WorkerError
from dever.result import ResultFactory
from llm.core.provider import Provider


class Gemini(Provider):
    PROVIDER_NAME = "gemini"
    REQUIRED_KEYS = ("api_key", "model")

    def init(self) -> None:
        configured = self.get_base_url()
        self.base_url = configured or "https://generativelanguage.googleapis.com/v1beta"
        self.base_url = self.base_url.rstrip("/")
        self.client = Client.get(
            name=f"llm-{self.PROVIDER_NAME}",
            timeout=self.get_timeout(60),
            headers={"Content-Type": "application/json"},
        )

    @staticmethod
    def _content_text(content: Any) -> str:
        if content is None:
            return ""
        if isinstance(content, str):
            return content
        if isinstance(content, list):
            out: List[str] = []
            for part in content:
                if isinstance(part, str):
                    out.append(part)
                    continue
                if isinstance(part, dict):
                    if isinstance(part.get("text"), str):
                        out.append(part["text"])
                        continue
                    if isinstance(part.get("content"), str):
                        out.append(part["content"])
                        continue
                out.append(str(part))
            return "".join(out)
        return str(content)

    def _to_gemini_payload(self, messages: List[Any]) -> Dict[str, Any]:
        contents: List[Dict[str, Any]] = []
        system_texts: List[str] = []

        for m in messages:
            if isinstance(m, dict):
                role = str(m.get("role") or "user").strip().lower()
                text = self._content_text(m.get("content"))
            else:
                role = str(getattr(m, "type", "user") or "user").strip().lower()
                text = self._content_text(getattr(m, "content", ""))

            if not text:
                continue

            if role in ("system",):
                system_texts.append(text)
                continue

            gem_role = "model" if role in ("assistant", "ai") else "user"
            contents.append({"role": gem_role, "parts": [{"text": text}]})

        if not contents:
            contents = [{"role": "user", "parts": [{"text": ""}]}]

        payload: Dict[str, Any] = {"contents": contents}
        if system_texts:
            payload["systemInstruction"] = {
                "parts": [{"text": "\n".join(system_texts)}],
            }

        policy = self.get_policy()
        if isinstance(policy, dict) and policy:
            if isinstance(policy.get("generationConfig"), dict):
                payload["generationConfig"] = policy["generationConfig"]
            else:
                gc: Dict[str, Any] = {}
                for k in (
                    "temperature",
                    "topP",
                    "topK",
                    "maxOutputTokens",
                    "candidateCount",
                    "stopSequences",
                    "responseMimeType",
                    "responseSchema",
                    "presencePenalty",
                    "frequencyPenalty",
                ):
                    if k in policy:
                        gc[k] = policy[k]
                if gc:
                    payload["generationConfig"] = gc

            if isinstance(policy.get("safetySettings"), list):
                payload["safetySettings"] = policy["safetySettings"]
            elif isinstance(policy.get("safety_settings"), list):
                payload["safetySettings"] = policy["safety_settings"]

            if isinstance(policy.get("tools"), list):
                payload["tools"] = policy["tools"]
            if isinstance(policy.get("toolConfig"), dict):
                payload["toolConfig"] = policy["toolConfig"]

        return payload

    def _generate_url(self, *, stream: bool) -> str:
        suffix = ":streamGenerateContent?alt=sse" if stream else ":generateContent"
        sep = "&" if "?" in suffix else "?"
        key = self.get_api_key()
        return f"{self.base_url}/models/{self.get_model()}{suffix}{sep}key={key}"

    @staticmethod
    def _extract_text_from_response(body: Dict[str, Any]) -> str:
        out: List[str] = []
        candidates = body.get("candidates")
        if not isinstance(candidates, list):
            return ""

        for c in candidates:
            if not isinstance(c, dict):
                continue
            content = c.get("content")
            if not isinstance(content, dict):
                continue
            parts = content.get("parts")
            if not isinstance(parts, list):
                continue
            for p in parts:
                if isinstance(p, dict) and isinstance(p.get("text"), str):
                    out.append(p["text"])
        return "".join(out)

    @staticmethod
    def _usage_from_body(body: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        um = body.get("usageMetadata") if isinstance(body, dict) else None
        if not isinstance(um, dict):
            return None
        pt = um.get("promptTokenCount")
        ct = um.get("candidatesTokenCount")
        tt = um.get("totalTokenCount")
        return {
            "prompt_tokens": pt,
            "completion_tokens": ct,
            "total_tokens": tt,
        }

    def _request_impl(self, rf: ResultFactory, messages: List[Any]) -> Dict[str, Any]:
        try:
            if self._is_cancelled():
                raise WorkerError("调用已取消", retryable=False)

            payload = self._to_gemini_payload(messages)
            retries = max(self.get_max_retries(1), 1)
            retry_delay = float(self.config.get("retry_delay", 0.8) or 0.8)
            last_exc: Optional[Exception] = None

            for attempt in range(retries):
                try:
                    resp = self.client.post(self._generate_url(stream=False), json=payload, timeout=self.get_timeout(60))
                    if resp.status_code >= 400:
                        body = resp.text
                        retryable = resp.status_code >= 500 or resp.status_code in (408, 429)
                        if retryable and attempt + 1 < retries:
                            time.sleep(retry_delay * (2 ** attempt))
                            continue
                        raise WorkerError(f"Gemini HTTP {resp.status_code}: {body}", retryable=retryable)

                    body = resp.json()
                    if isinstance(body, dict) and isinstance(body.get("error"), dict):
                        err = body["error"]
                        raise WorkerError(
                            f"Gemini 错误: {err.get('message') or body}",
                            retryable=False,
                        )

                    return rf.final(
                        content=self._extract_text_from_response(body),
                        usage=self._usage_from_body(body),
                        raw=body,
                    )
                except WorkerError:
                    raise
                except (httpx.TimeoutException, httpx.ConnectError, httpx.ReadError) as e:
                    last_exc = e
                    if attempt + 1 >= retries:
                        break
                    time.sleep(retry_delay * (2 ** attempt))

            raise WorkerError(f"Gemini 请求失败: {last_exc}", retryable=True, cause=last_exc)

        except WorkerError:
            raise
        except Exception as e:
            raise WorkerError(f"LLM 调用异常: {e}", retryable=True, cause=e)

    def _stream_impl(self, rf: ResultFactory, messages: List[Any]) -> Dict[str, Any]:
        parts: List[str] = []
        usage: Optional[Dict[str, Any]] = None
        last_raw: Any = None

        payload = self._to_gemini_payload(messages)
        retries = max(self.get_max_retries(1), 1)
        retry_delay = float(self.config.get("retry_delay", 0.8) or 0.8)
        last_exc: Optional[Exception] = None

        for attempt in range(retries):
            started = False
            try:
                with self.client.stream(
                    "POST",
                    self._generate_url(stream=True),
                    json=payload,
                    timeout=None,
                ) as resp:
                    if resp.status_code >= 400:
                        body = resp.text
                        retryable = resp.status_code >= 500 or resp.status_code in (408, 429)
                        err = WorkerError(f"Gemini HTTP {resp.status_code}: {body}", retryable=retryable)
                        if retryable and not started and attempt + 1 < retries:
                            time.sleep(retry_delay * (2 ** attempt))
                            continue
                        raise err

                    for line in resp.iter_lines():
                        if self._is_cancelled():
                            raise WorkerError("调用已取消", retryable=False)
                        if not line:
                            continue
                        started = True

                        raw_line = line.decode("utf-8", errors="replace") if isinstance(line, bytes) else str(line)
                        if not raw_line.startswith("data:"):
                            continue

                        data = raw_line[5:].strip()
                        if not data:
                            continue
                        if data == "[DONE]":
                            break

                        try:
                            chunk = json.loads(data)
                        except Exception:
                            continue

                        if isinstance(chunk, dict) and isinstance(chunk.get("error"), dict):
                            err = chunk["error"]
                            raise WorkerError(f"Gemini 错误: {err.get('message') or chunk}", retryable=False)

                        last_raw = chunk
                        delta = self._extract_text_from_response(chunk) if isinstance(chunk, dict) else ""
                        emitted = False
                        if delta:
                            parts.append(delta)
                            self.emit(rf.delta(delta))
                            emitted = True

                        u = self._usage_from_body(chunk) if isinstance(chunk, dict) else None
                        if u:
                            usage = u

                    final = rf.final(content="".join(parts), usage=usage, raw=last_raw)
                    self.emit(final)
                    return final

            except WorkerError as e:
                last_exc = e
                if (not e.retryable) or started or attempt + 1 >= retries:
                    err_evt = rf.error(
                        message=str(e),
                        code="error",
                        raw=getattr(e, "__dict__", None),
                        retryable=e.retryable,
                    )
                    self.emit(err_evt)
                    raise
                time.sleep(retry_delay * (2 ** attempt))

            except (httpx.TimeoutException, httpx.ConnectError, httpx.ReadError) as e:
                last_exc = e
                if started or attempt + 1 >= retries:
                    err_evt = rf.error(
                        message=str(e),
                        code="error",
                        raw=getattr(e, "__dict__", None),
                        retryable=True,
                    )
                    self.emit(err_evt)
                    raise WorkerError(f"LLM 流式调用异常: {e}", retryable=True, cause=e)
                time.sleep(retry_delay * (2 ** attempt))

            except Exception as e:
                err_evt = rf.error(
                    message=str(e),
                    code="error",
                    raw=getattr(e, "__dict__", None),
                    retryable=True,
                )
                self.emit(err_evt)
                raise WorkerError(f"LLM 流式调用异常: {e}", retryable=True, cause=e)

        err = WorkerError(f"LLM 流式请求失败: {last_exc}", retryable=True, cause=last_exc)
        self.emit(rf.error(message=str(err), code="error", retryable=True))
        raise err
