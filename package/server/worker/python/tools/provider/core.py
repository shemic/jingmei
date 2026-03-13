from __future__ import annotations
from typing import Any, Callable, Dict, List, Optional, Set
import time
import requests
from dever.error import WorkerError

class Provider(object):
    token = ""
    host = ""

    def __init__(self, config: Optional[Dict[str, Any]] = None):
        self.config: Dict[str, Any] = config or {}
        self.host = str(self.config.get("host", "")).rstrip("/")
        self.token = str(self.config.get("token", ""))
        if not self.host:
            raise WorkerError("媒体服务配置缺少 host")
        if not self.token:
            raise WorkerError("媒体服务配置缺少 token")
        self.header = {
            "Content-Type": "application/json",
            "Authorization": f"Bearer {self.token}",
        }

    def request_json(
        self,
        method: str,
        url: str,
        payload: Optional[Dict[str, Any]] = None,
        timeout: int = 60,
    ) -> Dict[str, Any]:
        method_upper = method.upper()
        if method_upper == "GET":
            res = requests.get(url, headers=self.header, timeout=timeout)
        elif method_upper == "POST":
            res = requests.post(url, headers=self.header, json=payload, timeout=timeout)
        else:
            res = requests.request(method_upper, url, headers=self.header, json=payload, timeout=timeout)

        if not res.ok:
            preview = (res.text or "").strip()[:500]
            raise WorkerError(f"媒体服务HTTP错误: {res.status_code}, body={preview}")

        try:
            body = res.json()
        except Exception as exc:
            preview = (res.text or "").strip()[:500]
            raise WorkerError(f"媒体服务返回的JSON无效: {preview}") from exc

        if not isinstance(body, dict):
            raise WorkerError("媒体服务返回必须是JSON对象")
        return body

    def poll_until_done(
        self,
        fetcher: Callable[[], Dict[str, Any]],
        timeout: int = 600,
        interval: float = 5,
        success_states: Optional[Set[str]] = None,
        failed_states: Optional[Set[str]] = None,
    ) -> Dict[str, Any]:
        success = self._lower_set(success_states or {"succeeded", "success", "done", "completed"})
        failed = self._lower_set(failed_states or {"failed", "error", "canceled", "cancelled"})
        start = time.monotonic()

        while True:
            if time.monotonic() - start > timeout:
                raise WorkerError(f"媒体任务轮询超时（{timeout}秒）")

            payload = fetcher()
            status = self.extract_status(payload)
            if status:
                status_lower = status.lower()
                if status_lower in success:
                    return payload
                if status_lower in failed:
                    raise WorkerError(f"媒体任务失败，状态={status}")

            if interval > 0:
                time.sleep(interval)

    def extract_status(self, body: Dict[str, Any]) -> str:
        status = self._pick_status(body)
        if status:
            return status
        data = body.get("data")
        if isinstance(data, dict):
            status = self._pick_status(data)
            if status:
                return status
        task = body.get("task")
        if isinstance(task, dict):
            status = self._pick_status(task)
            if status:
                return status
        return ""

    def collect_urls(self, payload: Any) -> List[str]:
        result: List[str] = []
        self._collect_urls_recursive(payload, result)
        return result

    @staticmethod
    def _pick_status(value: Dict[str, Any]) -> str:
        for key in ("status", "state", "task_status"):
            raw = value.get(key)
            if isinstance(raw, str) and raw:
                return raw
        return ""

    @staticmethod
    def _lower_set(values: Set[str]) -> Set[str]:
        normalized: Set[str] = set()
        for value in values:
            normalized.add(str(value).lower())
        return normalized

    def _collect_urls_recursive(self, value: Any, result: List[str]) -> None:
        if isinstance(value, dict):
            for key, raw in value.items():
                key_lower = str(key).lower()
                if key_lower == "url" or key_lower.endswith("_url"):
                    self._append_urls(raw, result)
                elif key_lower.endswith("_urls"):
                    self._append_urls(raw, result)
                else:
                    self._collect_urls_recursive(raw, result)
        elif isinstance(value, list):
            for item in value:
                self._collect_urls_recursive(item, result)

    def _append_urls(self, value: Any, result: List[str]) -> None:
        if isinstance(value, str):
            if value.startswith("http://") or value.startswith("https://"):
                if value not in result:
                    result.append(value)
            return
        if isinstance(value, dict):
            self._collect_urls_recursive(value, result)
            return
        if isinstance(value, list):
            for item in value:
                self._append_urls(item, result)
