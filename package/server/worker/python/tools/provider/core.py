from __future__ import annotations
from typing import Any, Callable, Dict, List, Optional, Set
import time
from urllib.parse import urlparse
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

    def collect_video_urls(self, payload: Any) -> List[str]:
        result: List[str] = []
        self._collect_video_urls_recursive(payload, result, parent_key="")
        return result

    def extract_task_id(self, body: Dict[str, Any]) -> str:
        for key in ("taskId", "task_id", "id"):
            task_id = body.get(key)
            if isinstance(task_id, (str, int)) and str(task_id).strip():
                return str(task_id).strip()
        data = body.get("data")
        if isinstance(data, dict):
            for key in ("taskId", "task_id", "id"):
                task_id = data.get(key)
                if isinstance(task_id, (str, int)) and str(task_id).strip():
                    return str(task_id).strip()
        return ""

    @staticmethod
    def _pick_status(value: Dict[str, Any]) -> str:
        for key in ("status", "state", "task_status", "taskStatus"):
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
                if key_lower in {"url", "fileurl", "downloadurl"} or key_lower.endswith("_url"):
                    self._append_urls(raw, result)
                elif key_lower.endswith("_urls") or key_lower == "imageurls":
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

    def _collect_video_urls_recursive(self, value: Any, result: List[str], parent_key: str) -> None:
        if isinstance(value, str):
            raw = value.strip()
            if raw.startswith(("http://", "https://")) and self._looks_like_video_url(raw, parent_key):
                if raw not in result:
                    result.append(raw)
            return
        if isinstance(value, list):
            for item in value:
                self._collect_video_urls_recursive(item, result, parent_key)
            return
        if isinstance(value, dict):
            for key, item in value.items():
                self._collect_video_urls_recursive(item, result, str(key))

    @staticmethod
    def _looks_like_video_url(url: str, key: str) -> bool:
        key_l = str(key or "").lower()
        if key_l in {"video_url", "video", "url"} or key_l.endswith("video_url") or key_l.endswith("video_urls"):
            return True
        lower = urlparse(url).path.lower()
        return lower.endswith((".mp4", ".mov", ".avi", ".mkv", ".wmv", ".flv", ".webm", ".m4v", ".3gp", ".mpeg", ".mpg"))

    @staticmethod
    def uniq_strings(values: List[str]) -> List[str]:
        out: List[str] = []
        seen = set()
        for item in values:
            value = str(item).strip()
            if not value or value in seen:
                continue
            seen.add(value)
            out.append(value)
        return out

    @classmethod
    def _normalize_media_values(cls, raw: Any) -> List[str]:
        if raw is None:
            return []
        if isinstance(raw, str):
            value = raw.strip()
            return [value] if value else []
        if isinstance(raw, dict):
            out: List[str] = []
            for value in raw.values():
                out.extend(cls._normalize_media_values(value))
            return cls.uniq_strings(out)
        if isinstance(raw, (list, tuple, set)):
            out: List[str] = []
            for item in raw:
                out.extend(cls._normalize_media_values(item))
            return cls.uniq_strings(out)
        return []

    @classmethod
    def _normalize_media_target_value(cls, values: Any, target_key: str) -> Any:
        cleaned = cls.uniq_strings(cls._normalize_media_values(values))
        if not cleaned:
            return None
        lowered = str(target_key or "").strip().lower()
        if lowered.endswith("urls") or lowered in {"images", "videos", "audios"}:
            return cleaned
        return cleaned[0]

    @classmethod
    def _merge_mapped_field_value(cls, current: Any, new_value: Any) -> Any:
        values = cls._normalize_media_values(current) + cls._normalize_media_values(new_value)
        merged = cls.uniq_strings(values)
        if not merged:
            return current
        if isinstance(current, list) or isinstance(new_value, list):
            return merged
        return merged[0]

    @staticmethod
    def _is_empty_field_value(value: Any) -> bool:
        if value is None:
            return True
        if isinstance(value, str):
            return not value.strip()
        if isinstance(value, (list, dict, tuple, set)):
            return len(value) == 0
        return False

    @staticmethod
    def to_positive_int(value: Any, default: int, field_name: str) -> int:
        if value in (None, ""):
            return default
        try:
            parsed = int(value)
        except Exception as exc:
            raise WorkerError(f"{field_name} 必须是整数") from exc
        if parsed <= 0:
            raise WorkerError(f"{field_name} 必须大于 0")
        return parsed

    @staticmethod
    def to_non_negative_float(value: Any, default: float, field_name: str) -> float:
        if value in (None, ""):
            return default
        try:
            parsed = float(value)
        except Exception as exc:
            raise WorkerError(f"{field_name} 必须是数字") from exc
        if parsed < 0:
            raise WorkerError(f"{field_name} 必须大于等于 0")
        return parsed
