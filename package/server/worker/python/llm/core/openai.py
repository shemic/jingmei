from __future__ import annotations

import json
import time
from typing import Any, Dict, List, Optional

import httpx

from dever.client import Client
from dever.error import WorkerError
from dever.result import ResultFactory
from llm.core.provider import Provider


class Openai(Provider):
    PROVIDER_NAME = "openai"
    REQUIRED_KEYS = ("api_key", "model", "host")

    def init(self) -> None:
        self.base_url = self.get_base_url()
        if not self.base_url:
            raise WorkerError(f"{self.PROVIDER_NAME}.host 未配置", retryable=False)

        self.headers = {
            "Authorization": f"Bearer {self.get_api_key()}",
            "Content-Type": "application/json",
        }
        self.client = Client.get(
            name=f"llm-{self.PROVIDER_NAME}",
            timeout=self.get_timeout(60),
            headers=self.headers,
        )

    def _chat_url(self) -> str:
        return f"{self.base_url.rstrip('/')}/chat/completions"

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

    def _to_openai_messages(self, messages: List[Any]) -> List[Dict[str, Any]]:
        role_map = {
            "system": "system",
            "human": "user",
            "user": "user",
            "assistant": "assistant",
            "ai": "assistant",
        }

        out: List[Dict[str, Any]] = []
        for m in messages:
            if isinstance(m, dict):
                role = str(m.get("role") or "user").strip().lower()
                content = self._content_text(m.get("content"))
            else:
                role = str(getattr(m, "type", "user") or "user").strip().lower()
                content = self._content_text(getattr(m, "content", ""))
            out.append({"role": role_map.get(role, "user"), "content": content})
        return out

    def _build_payload(self, messages: List[Any], *, stream: bool) -> Dict[str, Any]:
        payload: Dict[str, Any] = {
            "model": self.get_model(),
            "messages": self._to_openai_messages(messages),
            "stream": stream,
        }
        policy = self.get_policy()
        if isinstance(policy, dict) and policy:
            payload.update(policy)
        return payload

    def _post_with_retry(self, *, payload: Dict[str, Any], timeout: Optional[float]) -> httpx.Response:
        retries = max(self.get_max_retries(1), 1)
        retry_delay = float(self.config.get("retry_delay", 0.8) or 0.8)
        last_exc: Optional[Exception] = None

        for attempt in range(retries):
            try:
                resp = self.client.post(self._chat_url(), json=payload, timeout=timeout)
                if resp.status_code >= 400:
                    body = resp.text
                    retryable = resp.status_code >= 500 or resp.status_code in (408, 429)
                    err = WorkerError(
                        f"OpenAI HTTP {resp.status_code}: {body}",
                        retryable=retryable,
                    )
                    if retryable and attempt + 1 < retries:
                        time.sleep(retry_delay * (2 ** attempt))
                        continue
                    raise err
                return resp
            except (httpx.TimeoutException, httpx.ConnectError, httpx.ReadError) as e:
                last_exc = e
                if attempt + 1 >= retries:
                    break
                time.sleep(retry_delay * (2 ** attempt))

        raise WorkerError(f"OpenAI 请求失败: {last_exc}", retryable=True, cause=last_exc)

    def _request_impl(self, rf: ResultFactory, messages: List[Any]) -> Dict[str, Any]:
        try:
            if self._is_cancelled():
                raise WorkerError("调用已取消", retryable=False)

            payload = self._build_payload(messages, stream=False)
            resp = self._post_with_retry(payload=payload, timeout=self.get_timeout(60))
            body = resp.json()

            text = ""
            choices = body.get("choices") if isinstance(body, dict) else None
            if isinstance(choices, list) and choices:
                msg = choices[0].get("message") if isinstance(choices[0], dict) else None
                if isinstance(msg, dict):
                    text = self._content_text(msg.get("content"))

            usage = body.get("usage") if isinstance(body, dict) else None
            return rf.final(content=text, usage=usage, raw=body)
        except WorkerError:
            raise
        except Exception as e:
            raise WorkerError(f"LLM 调用异常: {e}", retryable=True, cause=e)

    def _stream_impl(self, rf: ResultFactory, messages: List[Any]) -> Dict[str, Any]:
        parts: List[str] = []
        usage: Optional[Dict[str, Any]] = None
        last_raw: Any = None

        payload = self._build_payload(messages, stream=True)
        payload.setdefault("stream_options", {"include_usage": True})

        retries = max(self.get_max_retries(1), 1)
        retry_delay = float(self.config.get("retry_delay", 0.8) or 0.8)
        last_exc: Optional[Exception] = None

        for attempt in range(retries):
            started = False
            try:
                with self.client.stream(
                    "POST",
                    self._chat_url(),
                    json=payload,
                    timeout=None,
                ) as resp:
                    if resp.status_code >= 400:
                        body = resp.text
                        retryable = resp.status_code >= 500 or resp.status_code in (408, 429)
                        err = WorkerError(
                            f"OpenAI HTTP {resp.status_code}: {body}",
                            retryable=retryable,
                        )
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

                        last_raw = chunk
                        emitted = False

                        choices = chunk.get("choices") if isinstance(chunk, dict) else None
                        if isinstance(choices, list) and choices:
                            delta_obj = choices[0].get("delta") if isinstance(choices[0], dict) else None
                            if isinstance(delta_obj, dict):
                                delta = self._content_text(delta_obj.get("content"))
                                if delta:
                                    parts.append(delta)
                                    self.emit(rf.delta(delta))
                                    emitted = True

                        u = chunk.get("usage") if isinstance(chunk, dict) else None
                        if isinstance(u, dict):
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
