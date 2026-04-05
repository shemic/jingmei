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
        return self._run_task(
            input,
            resource_name="图片",
            submit_error="提交 RunningHubAPI 图片任务失败",
            submit_paths=self._resolve_submit_paths(input, "图片"),
            payload_builder=self.build_image_payload,
            ready_collector=self.collect_urls,
            response_normalizer=self._normalize_response,
        )

    def video(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        _ = meta
        return self._run_task(
            input,
            resource_name="视频",
            submit_error="提交 RunningHubAPI 视频任务失败",
            submit_paths=self._resolve_submit_paths(input, "视频"),
            payload_builder=self.build_video_payload,
            ready_collector=self.collect_video_urls,
            response_normalizer=self._normalize_video_response,
        )

    def _run_task(
        self,
        input: Any,
        *,
        resource_name: str,
        submit_error: str,
        submit_paths: List[str],
        payload_builder: Any,
        ready_collector: Any,
        response_normalizer: Any,
    ) -> Dict[str, Any]:
        if not isinstance(input, dict):
            raise WorkerError(f"RunningHubAPI {resource_name}入参必须是对象")

        prompt = str(input.get("prompt", "")).strip()
        if not prompt:
            raise WorkerError(f"RunningHubAPI {resource_name} prompt 不能为空")

        images = self._normalize_images(input.get("files"))
        option = self._option_map(input.get("option"))
        media_sources = self._media_sources(input)
        task_id = self._load_cached_task_id(input)
        if task_id:
            final_body = self._poll_result(task_id, {"taskId": task_id}, option, input, resource_name=resource_name)
            return response_normalizer(task_id, final_body)

        payload = payload_builder(
            prompt=prompt,
            images=images,
            option=option,
            media_fields=input.get("media_fields"),
            media_sources=media_sources,
            media_rules=input.get("media_rules"),
        )
        created = self._submit_with_fallback(submit_paths, payload, submit_error, input)

        task_id = self.extract_task_id(created)
        self._cache_task_id(input, task_id)
        wait = bool(input.get("wait", True))
        if not wait:
            return response_normalizer(task_id, created)

        if ready_collector(created):
            return response_normalizer(task_id, created)

        final_body = self._poll_result(task_id, created, option, input, resource_name=resource_name)
        return response_normalizer(task_id, final_body)

    def query_outputs(self, task_id: str) -> Dict[str, Any]:
        payload = {"taskId": task_id}
        return self.request_json("POST", f"{self.host}{self.OUTPUTS_PATH}", payload=payload, timeout=60)

    def build_image_payload(
        self,
        prompt: str,
        images: List[str],
        option: Dict[str, Any],
        media_fields: Any = None,
        media_sources: Optional[Dict[str, Any]] = None,
        media_rules: Any = None,
    ) -> Dict[str, Any]:
        payload = self._build_payload(
            prompt=prompt,
            option=option,
            images=images,
            media_fields=media_fields,
            media_sources=media_sources,
            media_rules=media_rules,
            ignored_keys={"model", "timeout", "interval", "size", "file"},
            default_media=lambda items: {"imageUrls": items},
        )
        payload["aspectRatio"] = self._resolve_aspect_ratio(payload, option)
        payload["resolution"] = self._resolve_resolution(payload, option)
        return payload

    def build_video_payload(
        self,
        prompt: str,
        images: List[str],
        option: Dict[str, Any],
        media_fields: Any = None,
        media_sources: Optional[Dict[str, Any]] = None,
        media_rules: Any = None,
    ) -> Dict[str, Any]:
        payload = self._build_payload(
            prompt=prompt,
            option=option,
            images=images,
            media_fields=media_fields,
            media_sources=media_sources,
            media_rules=media_rules,
            ignored_keys={"model", "timeout", "interval", "file", "image", "video", "audio", "ratio", "radio"},
            default_media=lambda items: {"imageUrls": items, "imageUrl": items[0]},
        )
        payload["aspectRatio"] = self._resolve_video_aspect_ratio(payload, option)
        if "duration" in payload and payload["duration"] not in (None, ""):
            payload["duration"] = str(payload["duration"]).strip()
        if "storyboard" not in payload:
            payload["storyboard"] = False
        return payload

    def _build_payload(
        self,
        *,
        prompt: str,
        option: Dict[str, Any],
        images: List[str],
        media_fields: Any,
        media_sources: Optional[Dict[str, Any]],
        media_rules: Any,
        ignored_keys: set[str],
        default_media: Any,
    ) -> Dict[str, Any]:
        payload: Dict[str, Any] = {"prompt": prompt}
        mapped_media = self._resolve_media_fields(media_fields, media_sources, media_rules)
        payload.update(mapped_media)
        if images and not mapped_media:
            payload.update(default_media(images))
        self._merge_option_payload(payload, option, ignored_keys)
        return payload

    def _resolve_media_fields(
        self,
        media_fields: Any,
        media_sources: Optional[Dict[str, Any]],
        media_rules: Any,
    ) -> Dict[str, Any]:
        if isinstance(media_fields, dict) and media_fields:
            return dict(media_fields)
        if not isinstance(media_sources, dict):
            return {}
        rules = media_rules if isinstance(media_rules, list) else []
        if not rules:
            return {}

        out: Dict[str, Any] = {}
        for rule in rules:
            if not isinstance(rule, dict):
                continue
            media_type = str(rule.get("media_type") or "").strip().lower()
            target = str(rule.get("target") or "").strip()
            if not media_type or not target:
                continue
            values = self._normalize_media_values(media_sources.get(media_type))
            if not values:
                continue
            index = rule.get("index")
            if index not in (None, ""):
                try:
                    idx = int(index)
                except Exception:
                    continue
                if idx < 0 or idx >= len(values):
                    continue
                values = [values[idx]]
            mapped = self._normalize_media_target_value(values, target)
            if self._is_empty_field_value(mapped):
                continue
            current = out.get(target)
            if current is None:
                out[target] = mapped
                continue
            out[target] = self._merge_mapped_field_value(current, mapped)
        return out

    def _normalize_response(self, task_id: str, body: Dict[str, Any]) -> Dict[str, Any]:
        return self._normalize_task_response(task_id, body, self.collect_urls)

    def _normalize_video_response(self, task_id: str, body: Dict[str, Any]) -> Dict[str, Any]:
        return self._normalize_task_response(task_id, body, self.collect_video_urls)

    def _normalize_task_response(self, task_id: str, body: Dict[str, Any], collector: Any) -> Dict[str, Any]:
        urls = collector(body)
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

    def _resolve_submit_paths(self, input_data: Dict[str, Any], resource_name: str) -> List[str]:
        return self._resolve_submit_path_list(
            self._parse_path_candidates(str(input_data.get("model", "")).strip(), resource_name),
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

    def _submit_with_fallback(
        self,
        submit_paths: List[str],
        payload: Dict[str, Any],
        prefix: str,
        input_data: Optional[Dict[str, Any]] = None,
    ) -> Dict[str, Any]:
        last_error: Optional[WorkerError] = None
        last_submit_path = ""
        for index, submit_path in enumerate(submit_paths):
            last_submit_path = submit_path
            try:
                created = self.request_json("POST", self._openapi_url(submit_path), payload=payload, timeout=180)
                self._raise_for_error(created, prefix)
                return created
            except WorkerError as exc:
                last_error = exc
                if index >= len(submit_paths) - 1:
                    raise WorkerError(
                        f"{exc} | submit_path={submit_path} | raw_model={self._compact_value((input_data or {}).get('model'))}",
                        retryable=exc.retryable,
                        cause=exc,
                    ) from exc
        if last_error is not None:
            raise WorkerError(
                f"{last_error} | submit_path={last_submit_path} | raw_model={self._compact_value((input_data or {}).get('model'))}",
                retryable=last_error.retryable,
                cause=last_error,
            ) from last_error
        raise WorkerError(prefix)

    @staticmethod
    def _compact_value(value: Any) -> str:
        text = str(value or "").strip()
        if len(text) <= 500:
            return text
        return f"{text[:500]}..."

    def _poll_result(
        self,
        task_id: str,
        created: Dict[str, Any],
        option: Dict[str, Any],
        input_data: Dict[str, Any],
        resource_name: str = "图片",
    ) -> Dict[str, Any]:
        timeout = self.to_positive_int(option.get("timeout", input_data.get("timeout")), default=600, field_name="RunningHubAPI timeout")
        interval = self.to_non_negative_float(option.get("interval", input_data.get("interval")), default=5.0, field_name="RunningHubAPI interval")
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

    @staticmethod
    def _option_map(option: Any) -> Dict[str, Any]:
        return dict(option) if isinstance(option, dict) else {}

    @staticmethod
    def _media_sources(input_data: Dict[str, Any]) -> Dict[str, Any]:
        return {
            "image": input_data.get("image"),
            "video": input_data.get("video"),
            "audio": input_data.get("audio"),
        }

    @staticmethod
    def _merge_option_payload(payload: Dict[str, Any], option: Dict[str, Any], ignored_keys: set[str]) -> None:
        for key, value in option.items():
            if key in ignored_keys:
                continue
            payload[key] = value

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
