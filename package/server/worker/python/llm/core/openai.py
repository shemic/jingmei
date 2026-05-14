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
    RESPONSE_API_MODES = {"response", "responses"}
    CHAT_API_MODES = {"chat", "chat_completion", "chat_completions", "chat/completions"}

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

    def _responses_url(self) -> str:
        return f"{self.base_url.rstrip('/')}/responses"

    def _api_url(self, api_mode: str) -> str:
        return self._responses_url() if api_mode == "responses" else self._chat_url()

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

    @staticmethod
    def _message_role(message: Any) -> str:
        if isinstance(message, dict):
            return str(message.get("role") or "user").strip().lower()
        return str(getattr(message, "type", "user") or "user").strip().lower()

    @staticmethod
    def _message_content(message: Any) -> Any:
        if isinstance(message, dict):
            return message.get("content")
        return getattr(message, "content", "")

    @staticmethod
    def _extract_url(value: Any) -> str:
        if isinstance(value, str):
            return value.strip()
        if isinstance(value, dict):
            for key in ("url", "image_url", "video_url", "audio_url", "src"):
                url = str(value.get(key) or "").strip()
                if url:
                    return url
        return ""

    @classmethod
    def _to_chat_content_part(cls, part: Any) -> Optional[Dict[str, Any]]:
        if isinstance(part, str):
            text = part.strip()
            return {"type": "text", "text": text} if text else None
        if not isinstance(part, dict):
            text = cls._content_text(part).strip()
            return {"type": "text", "text": text} if text else None

        part_type = str(part.get("type") or "").strip()
        normalized_type = part_type.lower()
        if normalized_type in {"text", "input_text"}:
            text = cls._content_text(part.get("text") or part.get("content")).strip()
            return {"type": "text", "text": text} if text else None

        if normalized_type in {"image_url", "input_image"} or "image_url" in part:
            image_url = cls._extract_url(part.get("image_url") or part.get("url"))
            if not image_url:
                return None
            return {"type": "image_url", "image_url": {"url": image_url}}

        text = cls._content_text(part).strip()
        return {"type": "text", "text": text} if text else None

    @classmethod
    def _to_chat_content(cls, content: Any) -> Any:
        if not isinstance(content, list):
            return cls._content_text(content)

        parts: List[Dict[str, Any]] = []
        for part in content:
            normalized = cls._to_chat_content_part(part)
            if normalized:
                parts.append(normalized)
        return parts if parts else cls._content_text(content)

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
            role = self._message_role(m)
            content = self._message_content(m)
            out.append({"role": role_map.get(role, "user"), "content": self._to_chat_content(content)})
        return out

    @classmethod
    def _to_responses_content_part(cls, part: Any) -> Optional[Dict[str, Any]]:
        if isinstance(part, str):
            text = part.strip()
            return {"type": "input_text", "text": text} if text else None
        if not isinstance(part, dict):
            text = cls._content_text(part).strip()
            return {"type": "input_text", "text": text} if text else None

        part_type = str(part.get("type") or "").strip().lower()
        if part_type in {"text", "input_text"}:
            text = cls._content_text(part.get("text") or part.get("content")).strip()
            return {"type": "input_text", "text": text} if text else None

        if part_type in {"image_url", "input_image"} or "image_url" in part:
            image_url = cls._extract_url(part.get("image_url") or part.get("url"))
            if not image_url:
                return None
            return {"type": "input_image", "image_url": image_url}

        text = cls._content_text(part).strip()
        return {"type": "input_text", "text": text} if text else None

    @classmethod
    def _to_responses_content(cls, content: Any) -> List[Dict[str, Any]]:
        if not isinstance(content, list):
            text = cls._content_text(content).strip()
            return [{"type": "input_text", "text": text}] if text else []

        parts: List[Dict[str, Any]] = []
        for part in content:
            normalized = cls._to_responses_content_part(part)
            if normalized:
                parts.append(normalized)
        return parts

    def _to_responses_input(self, messages: List[Any]) -> List[Dict[str, Any]]:
        role_map = {
            "system": "system",
            "human": "user",
            "user": "user",
            "assistant": "assistant",
            "ai": "assistant",
        }

        out: List[Dict[str, Any]] = []
        for m in messages:
            role = self._message_role(m)
            content = self._to_responses_content(self._message_content(m))
            if not content:
                continue
            out.append({"role": role_map.get(role, "user"), "content": content})
        return out or [{"role": "user", "content": [{"type": "input_text", "text": ""}]}]

    @classmethod
    def _content_has_media(cls, content: Any) -> bool:
        if isinstance(content, dict):
            part_type = str(content.get("type") or "").strip().lower()
            if part_type in {"input_image", "image_url", "input_video", "video_url", "input_audio", "audio_url"}:
                return True
            return any(cls._content_has_media(value) for value in content.values())
        if isinstance(content, list):
            return any(cls._content_has_media(part) for part in content)
        return False

    def _messages_have_media(self, messages: List[Any]) -> bool:
        return any(self._content_has_media(self._message_content(message)) for message in messages)

    def _configured_api_mode(self) -> str:
        policy = self.get_policy()
        candidates = [
            self.config.get("api_type"),
            self.config.get("api_mode"),
            self.config.get("endpoint"),
        ]
        if isinstance(policy, dict):
            candidates.extend([policy.get("api_type"), policy.get("api_mode"), policy.get("endpoint")])

        for raw in candidates:
            value = str(raw or "").strip().lower()
            if value in self.RESPONSE_API_MODES:
                return "responses"
            if value in self.CHAT_API_MODES:
                return "chat"
        return ""

    def _should_auto_use_responses_api(self, messages: List[Any]) -> bool:
        if not self._messages_have_media(messages):
            return False
        model = self.get_model().lower()
        base_url = self.base_url.lower()
        return self.PROVIDER_NAME == "doubao" or "ark.cn-beijing.volces.com" in base_url or model.startswith("doubao-seed-")

    def _resolve_api_mode(self, messages: List[Any]) -> str:
        configured = self._configured_api_mode()
        if configured:
            return configured
        return "responses" if self._should_auto_use_responses_api(messages) else "chat"

    @staticmethod
    def _payload_policy(policy: Any) -> Dict[str, Any]:
        if isinstance(policy, dict) and policy:
            return {
                key: value
                for key, value in policy.items()
                if key not in {"api_type", "api_mode", "endpoint"}
            }
        return {}

    def _build_payload(self, messages: List[Any], *, stream: bool, api_mode: str) -> Dict[str, Any]:
        if api_mode == "responses":
            payload: Dict[str, Any] = {
                "model": self.get_model(),
                "input": self._to_responses_input(messages),
                "stream": stream,
            }
        else:
            payload = {
                "model": self.get_model(),
                "messages": self._to_openai_messages(messages),
                "stream": stream,
            }
        payload.update(self._payload_policy(self.get_policy()))
        return payload

    def _post_with_retry(self, *, payload: Dict[str, Any], timeout: Optional[float], api_mode: str) -> httpx.Response:
        retries = max(self.get_max_retries(1), 1)
        retry_delay = float(self.config.get("retry_delay", 0.8) or 0.8)
        last_exc: Optional[Exception] = None

        for attempt in range(retries):
            try:
                resp = self.client.post(self._api_url(api_mode), json=payload, timeout=timeout)
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

    @classmethod
    def _responses_text(cls, body: Any) -> str:
        if not isinstance(body, dict):
            return ""

        output_text = body.get("output_text")
        if isinstance(output_text, str) and output_text:
            return output_text

        out: List[str] = []

        def collect(value: Any) -> None:
            if isinstance(value, list):
                for item in value:
                    collect(item)
                return
            if not isinstance(value, dict):
                return

            value_type = str(value.get("type") or "").strip().lower()
            text = value.get("text")
            if value_type in {"output_text", "text"} and isinstance(text, str):
                out.append(text)
                return

            content = value.get("content")
            if isinstance(content, list):
                collect(content)
            elif isinstance(content, str) and value_type in {"message", "output_message"}:
                out.append(content)

        collect(body.get("output"))
        return "".join(out)

    @classmethod
    def _response_stream_delta(cls, chunk: Any) -> str:
        if not isinstance(chunk, dict):
            return ""
        event_type = str(chunk.get("type") or "").strip().lower()
        if event_type in {"response.output_text.delta", "response.refusal.delta"}:
            return cls._content_text(chunk.get("delta"))
        if event_type == "response.output_text.done":
            return ""
        return ""

    @classmethod
    def _response_stream_usage(cls, chunk: Any) -> Optional[Dict[str, Any]]:
        if not isinstance(chunk, dict):
            return None
        usage = chunk.get("usage")
        if isinstance(usage, dict):
            return usage
        response = chunk.get("response")
        if isinstance(response, dict) and isinstance(response.get("usage"), dict):
            return response["usage"]
        return None

    @classmethod
    def _response_stream_final_text(cls, chunk: Any) -> str:
        if not isinstance(chunk, dict):
            return ""
        response = chunk.get("response")
        if isinstance(response, dict):
            return cls._responses_text(response)
        return cls._responses_text(chunk)

    def _request_impl(self, rf: ResultFactory, messages: List[Any]) -> Dict[str, Any]:
        try:
            if self._is_cancelled():
                raise WorkerError("调用已取消", retryable=False)

            api_mode = self._resolve_api_mode(messages)
            payload = self._build_payload(messages, stream=False, api_mode=api_mode)
            resp = self._post_with_retry(payload=payload, timeout=self.get_timeout(60), api_mode=api_mode)
            body = resp.json()

            text = ""
            if api_mode == "responses":
                text = self._responses_text(body)
            else:
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

        api_mode = self._resolve_api_mode(messages)
        payload = self._build_payload(messages, stream=True, api_mode=api_mode)
        if api_mode != "responses":
            payload.setdefault("stream_options", {"include_usage": True})

        retries = max(self.get_max_retries(1), 1)
        retry_delay = float(self.config.get("retry_delay", 0.8) or 0.8)
        last_exc: Optional[Exception] = None

        for attempt in range(retries):
            started = False
            try:
                with self.client.stream(
                    "POST",
                    self._api_url(api_mode),
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

                        if api_mode == "responses":
                            delta = self._response_stream_delta(chunk)
                            if delta:
                                parts.append(delta)
                                self.emit(rf.delta(delta))
                                emitted = True
                            u = self._response_stream_usage(chunk)
                        else:
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

                    content = "".join(parts)
                    if api_mode == "responses" and not content:
                        content = self._response_stream_final_text(last_raw)
                    final = rf.final(content=content, usage=usage, raw=last_raw)
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
