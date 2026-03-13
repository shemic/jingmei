from __future__ import annotations
from dever.core import Dever
from dever.error import WorkerError, WorkerHttpError
from dever.client import Client
from dever.result import Result
from dever.publisher import Publisher, RedisPublisher

import httpx
import time
from typing import Any, Dict, Iterator, Optional, Tuple, Union


class Openai(object):
    config: Dict[str, Any] = {}
    headers: Dict[str, str] = {}
    publisher: Optional[Publisher] = None
    publish_meta: Dict[str, Any] = {}
    model: str = ""

    def __init__(self, config = {}):
        self.config = config or {}
        api_key = (self.config.get("api_key") or "").strip()
        if not api_key:
            raise WorkerError("llm.api_key 未配置", retryable=False)

        self.headers = {
            "Authorization": "Bearer " + api_key,
            "Content-Type": "application/json",
        }
        self.model = str(self.config.get("model") or "").strip()
        if not self.model:
            raise WorkerError("llm.model 未配置", retryable=False)
        
        self.client = Client.get(name="openai")

        # 发布
        publish_cfg = self.config.get("publish")
        self.publisher = None
        self.publish_meta = None
        if isinstance(publish_cfg, dict) and publish_cfg.get("channel"):
            self.publisher = RedisPublisher(channel=publish_cfg["channel"])
            self.publish_meta = publish_cfg

    def payload(self, type: str, msg: Dict[str, Any]) -> Tuple[Dict[str, Any], str]:
        payload: Dict[str, Any] = {}
        if type == "responses":
            uri = "responses"
            payload["input"] = msg
        else:
            uri = "chat/completions"
            payload["messages"] = msg
        return payload, uri

    def _build_url(self, uri: str) -> str:
        base = (self.config.get("host") or "").strip().rstrip("/")
        if not base:
            raise WorkerError("llm.host 未配置", retryable=False)
        return f"{base}/{uri.lstrip('/')}"

    def _retry_conf(self) -> Tuple[int, float]:
        retry_max = int(self.config.get("retry_max", 1))
        retry_delay = float(self.config.get("retry_delay", 1))
        return retry_max, retry_delay

    @staticmethod
    def _is_retryable_status(code: int) -> bool:
        return code >= 500 or code in (408, 429)

    # 上游统一调用 request()
    def request(
        self,
        type: str,
        msg: Dict[str, Any],
        stream: bool = False,
        timeout: Optional[float] = 60,
    ) -> Union[httpx.Response, Iterator[bytes]]:
        payload, uri = self.payload(type, msg)
        payload["model"] = self.model
        if stream:
            payload["stream"] = True

        url = self._build_url(uri)
        retry_max, retry_delay = self._retry_conf()

        if stream:
            return self._stream_impl(url, payload, retry_max, retry_delay)
        return self._request_impl(url, payload, retry_max, retry_delay, timeout)

    def _request_impl(
        self,
        url: str,
        payload: Dict[str, Any],
        retry_max: int,
        retry_delay: float,
        timeout: Optional[float],
    ) -> httpx.Response:
        last_exc: Optional[BaseException] = None

        for attempt in range(retry_max):
            try:
                resp = self.client.post(url, headers=self.headers, json=payload, timeout=timeout)

                if resp.status_code >= 400:
                    body = resp.text or ""
                    retryable = self._is_retryable_status(resp.status_code)
                    raise WorkerHttpError(resp.status_code, body, retryable=retryable)

                return Result.normal(resp.json())

            except WorkerHttpError as e:
                last_exc = e
                if (not e.retryable) or (attempt + 1 >= retry_max):
                    raise
                time.sleep(retry_delay * (2 ** attempt))

            except (httpx.TimeoutException, httpx.ConnectError, httpx.ReadError) as e:
                last_exc = e
                if attempt + 1 >= retry_max:
                    break
                time.sleep(retry_delay * (2 ** attempt))

            except Exception as e:
                raise WorkerError(f"LLM 调用异常: {e}", retryable=False, cause=e)

        raise WorkerError(f"LLM 请求失败: {last_exc}", retryable=True, cause=last_exc)

    def _stream_impl(
        self,
        url: str,
        payload: Dict[str, Any],
        retry_max: int,
        retry_delay: float,
    ) -> Dict[str, Any]:
        last_exc: Optional[BaseException] = None

        stream_cfg = self.config.get("stream") or {}
        flush_chars = int(stream_cfg.get("flush_chars", 10))
        flush_ms = int(stream_cfg.get("flush_ms", 120))

        for attempt in range(retry_max):
            yielded_any = False
            try:
                with self.client.stream("POST", url, headers=self.headers, json=payload, timeout=None) as resp:
                    if resp.status_code >= 400:
                        body = resp.text or ""
                        retryable = self._is_retryable_status(resp.status_code)
                        raise WorkerHttpError(resp.status_code, body, retryable=retryable)

                    final: Optional[Dict[str, Any]] = None
                    for evt in Result.sse(resp.iter_lines(), flush_chars=flush_chars, flush_ms=flush_ms):
                        yielded_any = True
                        if evt.get("type") == "final":
                            final = evt
                        if self.publisher:
                            try:
                                self.publisher.emit(evt, meta=self.publish_meta)
                            except Exception:
                                pass

                    return Result.normal(final)

            except WorkerHttpError as e:
                last_exc = e
                if yielded_any or (not e.retryable) or (attempt + 1 >= retry_max):
                    raise
                time.sleep(retry_delay * (2 ** attempt))

            except (httpx.TimeoutException, httpx.ConnectError, httpx.ReadError) as e:
                last_exc = e
                if yielded_any or (attempt + 1 >= retry_max):
                    raise WorkerError(f"LLM 流式请求失败: {e}", retryable=False, cause=e)
                time.sleep(retry_delay * (2 ** attempt))

            except Exception as e:
                raise WorkerError(f"LLM 流式调用异常: {e}", retryable=False, cause=e)

        raise WorkerError(f"LLM 流式请求失败: {last_exc}", retryable=True, cause=last_exc)
