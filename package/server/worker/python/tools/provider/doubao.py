from __future__ import annotations

from urllib.parse import urlparse
from typing import Any, Dict, List, Optional

import requests

from dever.error import WorkerError
from tools.provider.core import Provider


class Doubao(Provider):
    CREATE_PATH = "/contents/generations/tasks"

    def image(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        _ = meta
        if not isinstance(input, dict):
            raise WorkerError("豆包图片入参必须是对象")

        payload = self.build_image_payload(input)
        return self.request_json("POST", f"{self.host}/images/generations", payload=payload, timeout=180)

    def video(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        _ = meta
        if not isinstance(input, dict):
            raise WorkerError("豆包视频入参必须是对象")

        mode = str(input.get("mode", "default") or "default").strip().lower()
        payload = self.build_video_payload(input, mode)
        created = self.create_video_task(payload, mode)
        task_id = self.extract_task_id(created)
        if not task_id:
            raise WorkerError("豆包视频返回缺少任务ID")

        wait = bool(input.get("wait", True))
        if not wait:
            return self._normalize_video_response(task_id, created)

        timeout = self._to_positive_int(input.get("timeout"), default=600)
        interval = self._to_non_negative_float(input.get("interval"), default=5.0)
        final = self.poll_until_done(
            lambda: self.query_task(task_id),
            timeout=timeout,
            interval=interval,
        )
        return self._normalize_video_response(task_id, final)

    def task_image(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        url = self.host + "/images/generations"
        _ = url
        if not isinstance(input, dict):
            raise WorkerError("豆包入参必须是对象")

        payload = self.build_create_payload(input)
        created = self.create_task(payload)
        task_id = self.extract_task_id(created)
        if not task_id:
            raise WorkerError("豆包返回缺少任务ID")

        wait = bool(input.get("wait", True))
        if not wait:
            status = self.extract_status(created) or "submitted"
            return {"task_id": task_id, "status": status, "result": created, "urls": self.collect_urls(created)}

        timeout = self._to_positive_int(input.get("timeout"), default=600)
        interval = self._to_non_negative_float(input.get("interval"), default=5.0)
        final = self.poll_until_done(
            lambda: self.query_task(task_id),
            timeout=timeout,
            interval=interval,
        )

        status = self.extract_status(final) or "succeeded"
        return {"task_id": task_id, "status": status, "result": final, "urls": self.collect_urls(final)}

    def build_image_payload(self, input_data: Dict[str, Any]) -> Dict[str, Any]:
        model = str(input_data.get("model", "")).strip()
        if not model:
            raise WorkerError("豆包图片请求缺少 model")

        prompt = str(input_data.get("prompt", "")).strip()
        if not prompt:
            raise WorkerError("prompt 不能为空")

        option = input_data.get("option", {})
        if not isinstance(option, dict):
            option = {}

        payload: Dict[str, Any] = {
            "model": model,
            "prompt": prompt,
            "response_format": "url",
            "size": "2048x2048",
            "guidance_scale": 3,
            "watermark": False,
            "sequential_image_generation": "auto",
            "sequential_image_generation_options": {"max_images": 4},
        }
        for key, value in option.items():
            if key in {"model", "timeout", "interval", "file"}:
                continue
            payload[key] = value

        files = input_data.get("files")
        if isinstance(files, list) and files:
            payload["image"] = [str(item).strip() for item in files if str(item).strip()]

        passthrough = ("seed", "n", "quality", "style")
        for key in passthrough:
            if key in input_data:
                payload[key] = input_data[key]
        return payload

    def build_video_payload(self, input_data: Dict[str, Any], mode: str) -> Dict[str, Any]:
        model = str(input_data.get("model", "")).strip()
        if not model:
            raise WorkerError("豆包视频请求缺少 model")

        prompt = str(input_data.get("prompt", "")).strip()
        if not prompt:
            raise WorkerError("豆包视频 prompt 不能为空")

        option = input_data.get("option")
        option_map = dict(option) if isinstance(option, dict) else {}
        files = self._normalize_video_files(input_data.get("files"))
        if mode == "mention":
            if not files:
                raise WorkerError("mention 模式缺少文件（file）")
            payload: Dict[str, Any] = {
                "model": model,
                "prompt": prompt,
                "files": files,
            }
            payload.update(option_map)
            return payload

        content: List[Dict[str, Any]] = [{"type": "text", "text": prompt}]
        is_seedance_15_pro = "doubao-seedance-1-5-pro" in model.lower()
        total = len(files)
        if total == 1:
            content.append(
                {
                    "type": "image_url",
                    "image_url": {"url": files[0]},
                    "role": "first_frame",
                }
            )
        elif total >= 2:
            content.append(
                {
                    "type": "image_url",
                    "image_url": {"url": files[0]},
                    "role": "first_frame",
                }
            )
            content.append(
                {
                    "type": "image_url",
                    "image_url": {"url": files[1]},
                    "role": "last_frame",
                }
            )
            if total > 2 and not is_seedance_15_pro:
                for url in files[2:]:
                    content.append(
                        {
                            "type": "image_url",
                            "image_url": {"url": url},
                            "role": "reference_image",
                        }
                    )

        payload = {"model": model, "content": content, "watermark": False}
        payload.update(option_map)
        return payload

    def create_task(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        return self.request_json("POST", self._task_url(), payload=payload, timeout=60)

    def query_task(self, task_id: str) -> Dict[str, Any]:
        return self.request_json("GET", f"{self._task_url()}/{task_id}", timeout=60)

    def create_video_task(self, payload: Dict[str, Any], mode: str) -> Dict[str, Any]:
        if mode == "mention":
            return self._post_form(self._task_url(), payload, "创建视频任务失败")
        return self.request_json("POST", self._task_url(), payload=payload, timeout=60)

    def build_create_payload(self, input_data: Dict[str, Any]) -> Dict[str, Any]:
        model = input_data.get("model")
        if not isinstance(model, str) or not model.strip():
            raise WorkerError("豆包请求缺少 model")

        content = input_data.get("content")
        if content is None:
            content = self._build_content_from_fields(input_data)

        if not isinstance(content, list) or not content:
            raise WorkerError("豆包请求 content 必须是非空列表")

        payload: Dict[str, Any] = {
            "model": model.strip(),
            "content": content,
        }
        extra = input_data.get("extra")
        if isinstance(extra, dict):
            payload.update(extra)

        passthrough_ignore = {
            "model",
            "content",
            "prompt",
            "text",
            "images",
            "image",
            "image_url",
            "wait",
            "timeout",
            "interval",
            "extra",
        }
        for key, value in input_data.items():
            if key in passthrough_ignore:
                continue
            if key not in payload:
                payload[key] = value
        return payload

    def extract_task_id(self, body: Dict[str, Any]) -> str:
        task_id = body.get("id") or body.get("task_id")
        if isinstance(task_id, (str, int)):
            return str(task_id)

        data = body.get("data")
        if isinstance(data, dict):
            task_id = data.get("id") or data.get("task_id")
            if isinstance(task_id, (str, int)):
                return str(task_id)
        return ""

    def _build_content_from_fields(self, input_data: Dict[str, Any]) -> List[Dict[str, Any]]:
        prompt = input_data.get("prompt", input_data.get("text"))
        if not isinstance(prompt, str) or not prompt.strip():
            raise WorkerError("未提供 content 时，豆包请求必须提供 prompt")

        content: List[Dict[str, Any]] = [{"type": "text", "text": prompt.strip()}]
        for image_url in self._normalize_images(input_data):
            content.append({"type": "image_url", "image_url": {"url": image_url}})
        return content

    def _normalize_images(self, input_data: Dict[str, Any]) -> List[str]:
        raw_images: List[Any] = []
        for key in ("images", "image"):
            value = input_data.get(key)
            if value is not None:
                raw_images.append(value)
        if input_data.get("image_url") is not None:
            raw_images.append(input_data.get("image_url"))

        output: List[str] = []
        for raw in raw_images:
            self._append_image(raw, output)
        return output

    def _append_image(self, raw: Any, output: List[str]) -> None:
        if isinstance(raw, str):
            value = raw.strip()
            if value:
                output.append(value)
            return

        if isinstance(raw, dict):
            url = raw.get("url")
            if isinstance(url, str) and url.strip():
                output.append(url.strip())
                return
            raise WorkerError("豆包图片对象必须包含非空 url")

        if isinstance(raw, list):
            for item in raw:
                self._append_image(item, output)
            return

        raise WorkerError("豆包 images 必须是字符串、对象或列表")

    def _task_url(self) -> str:
        return f"{self.host}{self.CREATE_PATH}"

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

    @staticmethod
    def _normalize_video_files(raw: Any) -> List[str]:
        if not isinstance(raw, list):
            return []
        return [str(item).strip() for item in raw if str(item).strip()]

    def _collect_video_urls(self, body: Dict[str, Any]) -> List[str]:
        result: List[str] = []
        self._collect_video_urls_recursive(body, result, parent_key="")
        dedup: List[str] = []
        seen = set()
        for url in result:
            if url in seen:
                continue
            seen.add(url)
            dedup.append(url)
        return dedup

    def _collect_video_urls_recursive(self, value: Any, result: List[str], parent_key: str) -> None:
        if isinstance(value, str):
            raw = value.strip()
            if raw.startswith(("http://", "https://")) and self._looks_like_video_url(raw, parent_key):
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
        suffix = urlparse(url).path.lower()
        return suffix.endswith((".mp4", ".mov", ".avi", ".mkv", ".wmv", ".flv", ".webm", ".m4v", ".3gp", ".mpeg", ".mpg"))

    def _post_form(self, url: str, payload: Dict[str, Any], err_prefix: str) -> Dict[str, Any]:
        data: Dict[str, Any] = {}
        files_field: List[str] = []
        for key, value in payload.items():
            if key == "files":
                if isinstance(value, list):
                    files_field = [str(item).strip() for item in value if str(item).strip()]
                continue
            data[key] = value

        multipart = [("files", (None, item)) for item in files_field]
        headers = dict(self.header)
        headers.pop("Content-Type", None)
        res = requests.post(url, headers=headers, data=data, files=multipart, timeout=60)
        if not res.ok:
            preview = (res.text or "").strip()[:500]
            raise WorkerError(f"{err_prefix}: status={res.status_code}, body={preview}")
        try:
            body = res.json()
        except Exception as exc:
            preview = (res.text or "").strip()[:500]
            raise WorkerError(f"{err_prefix}: 返回JSON无效: {preview}") from exc
        if not isinstance(body, dict):
            raise WorkerError(f"{err_prefix}: 返回必须是JSON对象")
        return body

    @staticmethod
    def _to_positive_int(value: Any, default: int) -> int:
        if value is None:
            return default
        try:
            parsed = int(value)
        except Exception as exc:
            raise WorkerError("豆包 timeout 必须是整数") from exc
        if parsed <= 0:
            raise WorkerError("豆包 timeout 必须大于 0")
        return parsed

    @staticmethod
    def _to_non_negative_float(value: Any, default: float) -> float:
        if value is None:
            return default
        try:
            parsed = float(value)
        except Exception as exc:
            raise WorkerError("豆包 interval 必须是数字") from exc
        if parsed < 0:
            raise WorkerError("豆包 interval 必须大于等于 0")
        return parsed
