from __future__ import annotations

import math
import time
from typing import Any, Dict, List, Optional, Tuple

from dever.error import WorkerError
from dever.redis import Redis
from tools.provider.core import Provider


class RunningHubAPI(Provider):
    DEFAULT_HOST = "https://www.runninghub.cn"
    OPENAPI_PREFIX = "/openapi/v2"
    OUTPUTS_PATH = "/openapi/v2/query"
    DEFAULT_ASPECT_RATIO = "1:1"
    DEFAULT_RESOLUTION = "1K"
    TASK_CACHE_TTL = 24 * 60 * 60

    def image(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        _ = meta
        if not isinstance(input, dict):
            raise WorkerError("RunningHubAPI 图片入参必须是对象")

        prompt = str(input.get("prompt", "")).strip()
        if not prompt:
            raise WorkerError("RunningHubAPI prompt 不能为空")

        images = self._normalize_images(input.get("files"))
        option = input.get("option") if isinstance(input.get("option"), dict) else {}
        task_id = self._load_cached_task_id(input)
        if task_id:
            final_body = self._poll_result(task_id, {"taskId": task_id}, option, input, resource_name="图片")
            return self._normalize_response(task_id, final_body)

        submit_paths = self._resolve_submit_paths(input)
        payload = self.build_image_payload(prompt=prompt, images=images, option=option)
        created = self._submit_with_fallback(submit_paths, payload, "提交 RunningHubAPI 图片任务失败")

        task_id = self.extract_task_id(created)
        self._cache_task_id(input, task_id)
        wait = bool(input.get("wait", True))
        if not wait:
            return self._normalize_response(task_id, created)

        if self.collect_urls(created):
            return self._normalize_response(task_id, created)

        final_body = self._poll_result(task_id, created, option, input, resource_name="图片")
        return self._normalize_response(task_id, final_body)

    def video(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        _ = meta
        if not isinstance(input, dict):
            raise WorkerError("RunningHubAPI 视频入参必须是对象")

        prompt = str(input.get("prompt", "")).strip()
        if not prompt:
            raise WorkerError("RunningHubAPI 视频 prompt 不能为空")

        images = self._normalize_images(input.get("files"))
        option = input.get("option") if isinstance(input.get("option"), dict) else {}
        task_id = self._load_cached_task_id(input)
        if task_id:
            final_body = self._poll_result(task_id, {"taskId": task_id}, option, input, resource_name="视频")
            return self._normalize_video_response(task_id, final_body)

        submit_paths = self._resolve_video_submit_paths(input)
        payload = self.build_video_payload(prompt=prompt, images=images, option=option)
        created = self._submit_with_fallback(submit_paths, payload, "提交 RunningHubAPI 视频任务失败")

        task_id = self.extract_task_id(created)
        self._cache_task_id(input, task_id)
        wait = bool(input.get("wait", True))
        if not wait:
            return self._normalize_video_response(task_id, created)

        if self._collect_video_urls(created):
            return self._normalize_video_response(task_id, created)

        final_body = self._poll_result(task_id, created, option, input, resource_name="视频")
        return self._normalize_video_response(task_id, final_body)

    def query_outputs(self, task_id: str) -> Dict[str, Any]:
        payload = {"taskId": task_id}
        return self.request_json("POST", f"{self.host}{self.OUTPUTS_PATH}", payload=payload, timeout=60)

    def build_image_payload(self, prompt: str, images: List[str], option: Dict[str, Any]) -> Dict[str, Any]:
        payload: Dict[str, Any] = {"prompt": prompt}
        if images:
            payload["imageUrls"] = images

        for key, value in option.items():
            if key in {"model", "timeout", "interval", "size", "file"}:
                continue
            payload[key] = value

        payload["aspectRatio"] = self._resolve_aspect_ratio(payload, option)
        payload["resolution"] = self._resolve_resolution(payload, option)
        return payload

    def build_video_payload(self, prompt: str, images: List[str], option: Dict[str, Any]) -> Dict[str, Any]:
        payload: Dict[str, Any] = {"prompt": prompt}
        if images:
            payload["imageUrl"] = images[0]

        for key, value in option.items():
            if key in {"model", "timeout", "interval", "file", "image", "video", "audio", "ratio", "radio"}:
                continue
            payload[key] = value

        payload["aspectRatio"] = self._resolve_video_aspect_ratio(payload, option)
        if "duration" in payload and payload["duration"] not in (None, ""):
            payload["duration"] = str(payload["duration"]).strip()
        if "storyboard" not in payload:
            payload["storyboard"] = False
        return payload

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

    def _normalize_response(self, task_id: str, body: Dict[str, Any]) -> Dict[str, Any]:
        urls = self.collect_urls(body)
        data = [{"url": url} for url in urls]
        status = self.extract_status(body) or ("succeeded" if urls else "submitted")
        return {
            "task_id": task_id,
            "status": status,
            "result": body,
            "data": data,
        }

    def _normalize_video_response(self, task_id: str, body: Dict[str, Any]) -> Dict[str, Any]:
        urls = self._collect_video_urls(body)
        data = [{"url": url} for url in urls]
        status = self.extract_status(body) or ("succeeded" if urls else "submitted")
        return {
            "task_id": task_id,
            "status": status,
            "result": body,
            "data": data,
        }

    def _raise_for_error(self, body: Dict[str, Any], prefix: str) -> None:
        code = body.get("code")
        if code not in (None, 0, "0"):
            msg = str(body.get("msg") or body.get("message") or body.get("errorMessage") or "").strip()
            raise WorkerError(f"{prefix}: code={code}, message={msg}")

        status = self.extract_status(body).lower()
        if status in {"failed", "error", "cancelled", "canceled"}:
            message = str(body.get("errorMessage") or body.get("msg") or body.get("message") or "").strip()
            raise WorkerError(f"{prefix}: status={status}, message={message}")

        for key in ("errorMessage", "failedReason"):
            value = body.get(key)
            if isinstance(value, str) and value.strip():
                raise WorkerError(f"{prefix}: {value.strip()}")
            if isinstance(value, dict) and value:
                raise WorkerError(f"{prefix}: {value}")

        msg = str(body.get("msg") or body.get("message") or "").strip()
        if msg:
            msg_lower = msg.lower()
            if "失败" in msg or "错误" in msg or "error" in msg_lower or "failed" in msg_lower:
                raise WorkerError(f"{prefix}: {msg}")

    def _parse_image_path_candidates(self, raw: str) -> List[Tuple[str, str]]:
        return self._parse_path_candidates(raw, "图片")

    def _resolve_submit_paths(self, input_data: Dict[str, Any]) -> List[str]:
        return self._resolve_submit_path_list(
            self._parse_image_path_candidates(str(input_data.get("model", "")).strip()),
            bool(input_data.get("is_edit")),
        )

    def _parse_video_path_candidates(self, raw: str) -> List[Tuple[str, str]]:
        return self._parse_path_candidates(raw, "视频")

    def _resolve_video_submit_paths(self, input_data: Dict[str, Any]) -> List[str]:
        return self._resolve_submit_path_list(
            self._parse_video_path_candidates(str(input_data.get("model", "")).strip()),
            bool(input_data.get("is_edit")),
        )

    def _parse_path_candidates(self, raw: str, resource_name: str) -> List[Tuple[str, str]]:
        value = raw.strip().strip("/")
        if not value:
            raise WorkerError(f"RunningHubAPI {resource_name}请求缺少接口 path")
        lines = [part.strip().strip("/") for part in value.splitlines() if part.strip()]
        if not lines:
            raise WorkerError(f"RunningHubAPI {resource_name}请求缺少接口 path")

        candidates: List[Tuple[str, str]] = []
        if len(lines) == 2 and all("||" not in line for line in lines):
            candidates.append(self._split_candidate_pair(lines[0], lines[1], resource_name))
            return candidates

        for line in lines:
            pair = line.split("||", 1)
            text_path = pair[0].strip().strip("/")
            edit_path = pair[1].strip().strip("/") if len(pair) > 1 else text_path
            candidates.append(self._split_candidate_pair(text_path, edit_path, resource_name))
        return candidates

    @staticmethod
    def _split_candidate_pair(text_path: str, edit_path: str, resource_name: str) -> Tuple[str, str]:
        text_value = str(text_path or "").strip().strip("/")
        edit_value = str(edit_path or "").strip().strip("/") or text_value
        if not text_value:
            raise WorkerError(f"RunningHubAPI 文生{resource_name}接口 path 不能为空")
        return text_value, edit_value

    @staticmethod
    def _resolve_submit_path_list(candidates: List[Tuple[str, str]], is_edit: bool) -> List[str]:
        paths: List[str] = []
        for text_path, edit_path in candidates:
            path = edit_path if is_edit else text_path
            normalized = str(path or "").strip().strip("/")
            if normalized:
                paths.append(normalized)
        if not paths:
            raise WorkerError("RunningHubAPI 请求缺少可用接口 path")
        return paths

    def _submit_with_fallback(self, submit_paths: List[str], payload: Dict[str, Any], prefix: str) -> Dict[str, Any]:
        last_error: Optional[WorkerError] = None
        for index, submit_path in enumerate(submit_paths):
            try:
                created = self.request_json("POST", self._openapi_url(submit_path), payload=payload, timeout=180)
                self._raise_for_error(created, prefix)
                return created
            except WorkerError as exc:
                last_error = exc
                if index >= len(submit_paths) - 1:
                    raise
        if last_error is not None:
            raise last_error
        raise WorkerError(prefix)

    def _poll_result(
        self,
        task_id: str,
        created: Dict[str, Any],
        option: Dict[str, Any],
        input_data: Dict[str, Any],
        resource_name: str = "图片",
    ) -> Dict[str, Any]:
        timeout = self._to_positive_int(option.get("timeout", input_data.get("timeout")), default=600, field_name="timeout")
        interval = self._to_non_negative_float(option.get("interval", input_data.get("interval")), default=5.0, field_name="interval")
        start = time.monotonic()
        last_body = created
        while True:
            if task_id and time.monotonic() - start > timeout:
                raise WorkerError(f"RunningHubAPI {resource_name}任务轮询超时（{timeout}秒）")
            if not task_id:
                if self.collect_urls(last_body):
                    return last_body
                raise WorkerError("RunningHubAPI 返回缺少 taskId，且未直接返回结果")
            try:
                last_body = self.query_outputs(task_id)
            except WorkerError:
                if interval > 0:
                    time.sleep(interval)
                continue
            if self.collect_urls(last_body):
                return last_body
            self._raise_for_terminal_query_error(last_body, f"查询 RunningHubAPI {resource_name}结果失败")
            if interval > 0:
                time.sleep(interval)

    @staticmethod
    def _is_running_response(body: Dict[str, Any]) -> bool:
        code = str(body.get("code", "")).strip()
        if code != "804":
            return False
        msg = str(body.get("msg") or body.get("message") or "").strip().upper()
        return msg == "APIKEY_TASK_IS_RUNNING"

    def _raise_for_terminal_query_error(self, body: Dict[str, Any], prefix: str) -> None:
        status = self.extract_status(body).lower()
        if status in {"failed", "error", "cancelled", "canceled"}:
            message = str(body.get("errorMessage") or body.get("msg") or body.get("message") or "").strip()
            raise WorkerError(f"{prefix}: status={status}, message={message}", retryable=False)

        for key in ("errorMessage", "failedReason"):
            value = body.get(key)
            if isinstance(value, str) and value.strip():
                raise WorkerError(f"{prefix}: {value.strip()}", retryable=False)
            if isinstance(value, dict) and value:
                raise WorkerError(f"{prefix}: {value}", retryable=False)

    def _load_cached_task_id(self, input_data: Dict[str, Any]) -> str:
        task_id = str(input_data.get("task_id", "")).strip()
        if task_id:
            return task_id

        task_key = self._normalize_task_key(input_data.get("task_key"))
        if not task_key:
            return ""
        client = Redis.get()
        if client is None:
            return ""
        try:
            return str(client.get(Redis.key(task_key)) or "").strip()
        except Exception:
            return ""

    def _cache_task_id(self, input_data: Dict[str, Any], task_id: str) -> None:
        task_key = self._normalize_task_key(input_data.get("task_key"))
        task_value = str(task_id or "").strip()
        if not task_key or not task_value:
            return
        client = Redis.get()
        if client is None:
            return
        try:
            client.set(Redis.key(task_key), task_value, ex=self.TASK_CACHE_TTL)
        except Exception:
            return

    def clear_cached_task_id(self, task_key: Any) -> None:
        key = self._normalize_task_key(task_key)
        if not key:
            return
        client = Redis.get()
        if client is None:
            return
        try:
            client.delete(Redis.key(key))
        except Exception:
            return

    @staticmethod
    def _normalize_task_key(task_key: Any) -> str:
        return str(task_key or "").strip()

    def _resolve_aspect_ratio(self, payload: Dict[str, Any], option: Dict[str, Any]) -> str:
        aspect_ratio = str(payload.get("aspectRatio", "")).strip()
        if aspect_ratio:
            return aspect_ratio
        return self._size_to_aspect_ratio(str(option.get("size", "")).strip()) or self.DEFAULT_ASPECT_RATIO

    def _resolve_video_aspect_ratio(self, payload: Dict[str, Any], option: Dict[str, Any]) -> str:
        aspect_ratio = str(payload.get("aspectRatio", "")).strip()
        if aspect_ratio:
            return aspect_ratio
        for key in ("aspectRatio", "ratio", "radio"):
            value = str(option.get(key, "")).strip()
            if value:
                return value
        return self._size_to_aspect_ratio(str(option.get("size", "")).strip()) or self.DEFAULT_ASPECT_RATIO

    def _resolve_resolution(self, payload: Dict[str, Any], option: Dict[str, Any]) -> str:
        resolution = str(payload.get("resolution", "")).strip()
        if resolution:
            return resolution
        return self._size_to_resolution(str(option.get("size", "")).strip()) or self.DEFAULT_RESOLUTION

    def _openapi_url(self, path: str) -> str:
        return f"{self.host}{self.OPENAPI_PREFIX}/{path.lstrip('/')}"

    @staticmethod
    def _normalize_images(raw: Any) -> List[str]:
        if not isinstance(raw, list):
            return []
        return [str(item).strip() for item in raw if str(item).strip()]

    def _collect_video_urls(self, payload: Any) -> List[str]:
        result: List[str] = []
        self._collect_video_urls_recursive(payload, result, parent_key="")
        return result

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
        lower = url.lower().split("?", 1)[0]
        return lower.endswith((".mp4", ".mov", ".avi", ".mkv", ".wmv", ".flv", ".webm", ".m4v", ".3gp", ".mpeg", ".mpg"))

    @staticmethod
    def _size_to_aspect_ratio(size: str) -> str:
        if "x" not in size:
            return ""
        try:
            width_raw, height_raw = size.lower().split("x", 1)
            width = int(width_raw.strip())
            height = int(height_raw.strip())
        except Exception:
            return ""
        if width <= 0 or height <= 0:
            return ""
        gcd = math.gcd(width, height)
        return f"{width // gcd}:{height // gcd}"

    @staticmethod
    def _size_to_resolution(size: str) -> str:
        if "x" not in size:
            return ""
        try:
            width_raw, height_raw = size.lower().split("x", 1)
            width = int(width_raw.strip())
            height = int(height_raw.strip())
        except Exception:
            return ""
        longest = max(width, height)
        if longest >= 4096:
            return "4K"
        if longest >= 2048:
            return "2K"
        return "1K"

    @staticmethod
    def _to_positive_int(value: Any, default: int, field_name: str) -> int:
        if value in (None, ""):
            return default
        try:
            parsed = int(value)
        except Exception as exc:
            raise WorkerError(f"RunningHubAPI {field_name} 必须是整数") from exc
        if parsed <= 0:
            raise WorkerError(f"RunningHubAPI {field_name} 必须大于 0")
        return parsed

    @staticmethod
    def _to_non_negative_float(value: Any, default: float, field_name: str) -> float:
        if value in (None, ""):
            return default
        try:
            parsed = float(value)
        except Exception as exc:
            raise WorkerError(f"RunningHubAPI {field_name} 必须是数字") from exc
        if parsed < 0:
            raise WorkerError(f"RunningHubAPI {field_name} 必须大于等于 0")
        return parsed
