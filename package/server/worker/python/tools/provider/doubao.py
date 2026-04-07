from __future__ import annotations

from typing import Any, Dict, List, Optional

import requests

from dever.error import WorkerError
from tools.provider.core import Provider


class Doubao(Provider):
    CREATE_PATH = "/contents/generations/tasks"
    SEEDANCE_20_FLAG = "seedance-2-0"

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

        timeout = self.to_positive_int(input.get("timeout"), default=600, field_name="豆包 timeout")
        interval = self.to_non_negative_float(input.get("interval"), default=5.0, field_name="豆包 interval")
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

        timeout = self.to_positive_int(input.get("timeout"), default=600, field_name="豆包 timeout")
        interval = self.to_non_negative_float(input.get("interval"), default=5.0, field_name="豆包 interval")
        final = self.poll_until_done(
            lambda: self.query_task(task_id),
            timeout=timeout,
            interval=interval,
        )

        status = self.extract_status(final) or "succeeded"
        return {"task_id": task_id, "status": status, "result": final, "urls": self.collect_urls(final)}

    def build_image_payload(self, input_data: Dict[str, Any]) -> Dict[str, Any]:
        model = self._require_model(input_data, "豆包图片请求缺少 model")
        prompt = self._require_prompt(input_data, "prompt 不能为空")
        option = self._normalize_option_map(input_data.get("option"))

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
        self._merge_option_payload(payload, option, ignored={"model", "timeout", "interval", "file"})

        files = input_data.get("files")
        if isinstance(files, list) and files:
            payload["image"] = [str(item).strip() for item in files if str(item).strip()]

        passthrough = ("seed", "n", "quality", "style")
        for key in passthrough:
            if key in input_data:
                payload[key] = input_data[key]
        return payload

    def build_video_payload(self, input_data: Dict[str, Any], mode: str) -> Dict[str, Any]:
        model = self._require_model(input_data, "豆包视频请求缺少 model")
        prompt = self._require_prompt(input_data, "豆包视频 prompt 不能为空")
        option_map = self._normalize_option_map(input_data.get("option"))
        files = self._normalize_video_files(input_data.get("files"))
        if mode == "mention":
            if not files:
                raise WorkerError("mention 模式缺少文件（file）")
            payload: Dict[str, Any] = {
                "model": model,
                "prompt": prompt,
                "files": files,
            }
            self._merge_option_payload(payload, option_map)
            return payload

        media = self._collect_video_generation_media(input_data, files)
        payload = {
            "model": model,
            "content": self._build_video_content(prompt, model, media),
            "watermark": False,
        }
        self._merge_option_payload(
            payload,
            option_map,
            ignored={"model", "timeout", "interval", "file", "image", "video", "audio", "ratio", "radio"},
        )
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
        model = self._require_model(input_data, "豆包请求缺少 model")

        content = input_data.get("content")
        if content is None:
            content = self._build_content_from_fields(input_data)

        if not isinstance(content, list) or not content:
            raise WorkerError("豆包请求 content 必须是非空列表")

        payload: Dict[str, Any] = {
            "model": model,
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
            "videos",
            "video",
            "video_url",
            "audios",
            "audio",
            "audio_url",
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

    def _build_content_from_fields(self, input_data: Dict[str, Any]) -> List[Dict[str, Any]]:
        prompt = self._require_prompt(input_data, "未提供 content 时，豆包请求必须提供 prompt")
        content: List[Dict[str, Any]] = [self._text_content(prompt)]
        for image_url in self._normalize_images(input_data):
            content.append(self._url_content("image_url", image_url))
        return content

    def _build_video_content(self, prompt: str, model: str, media: Dict[str, List[str]]) -> List[Dict[str, Any]]:
        content: List[Dict[str, Any]] = [self._text_content(prompt)]
        self._append_image_reference_content(content, media.get("image", []), model)
        if self._supports_video_audio_reference(model):
            self._append_reference_content(content, media.get("video", []), "video_url", "reference_video")
            self._append_reference_content(content, media.get("audio", []), "audio_url", "reference_audio")
        return content

    def _collect_video_generation_media(self, input_data: Dict[str, Any], files: List[str]) -> Dict[str, List[str]]:
        images = self.uniq_strings(files + self._collect_media_urls(input_data, "image"))
        return {
            "image": images,
            "video": self._collect_media_urls(input_data, "video"),
            "audio": self._collect_media_urls(input_data, "audio"),
        }

    def _collect_media_urls(self, input_data: Dict[str, Any], media_type: str) -> List[str]:
        keys = [media_type, f"{media_type}s", f"{media_type}_url"]
        out: List[str] = []
        for key in keys:
            if key not in input_data:
                continue
            self._append_url_like(input_data.get(key), out, field_name=key)
        return self.uniq_strings(out)

    def _append_image_reference_content(self, content: List[Dict[str, Any]], images: List[str], model: str) -> None:
        _ = model
        if not images:
            return
        total = len(images)
        if total == 1:
            content.append(self._url_content("image_url", images[0], role="first_frame"))
            return
        if total == 2:
            content.append(self._url_content("image_url", images[0], role="first_frame"))
            content.append(self._url_content("image_url", images[1], role="last_frame"))
            return
        for url in images:
            content.append(self._url_content("image_url", url, role="reference_image"))

    def _append_reference_content(
        self,
        content: List[Dict[str, Any]],
        urls: List[str],
        content_type: str,
        role: str,
    ) -> None:
        for url in urls:
            content.append(self._url_content(content_type, url, role=role))

    @staticmethod
    def _text_content(text: str) -> Dict[str, Any]:
        return {"type": "text", "text": str(text or "").strip()}

    @staticmethod
    def _url_content(content_type: str, url: str, role: Optional[str] = None) -> Dict[str, Any]:
        item: Dict[str, Any] = {
            "type": content_type,
            content_type: {"url": str(url or "").strip()},
        }
        if role:
            item["role"] = role
        return item

    def _normalize_images(self, input_data: Dict[str, Any]) -> List[str]:
        return self._collect_media_urls(input_data, "image")

    def _append_url_like(self, raw: Any, output: List[str], field_name: str = "url") -> None:
        if isinstance(raw, str):
            value = raw.strip()
            if value:
                output.append(value)
            return

        if isinstance(raw, dict):
            url = raw.get("url") or raw.get("src") or raw.get("value")
            if isinstance(url, str) and url.strip():
                output.append(url.strip())
                return
            raise WorkerError(f"豆包 {field_name} 对象必须包含非空 url")

        if isinstance(raw, list):
            for item in raw:
                self._append_url_like(item, output, field_name=field_name)
            return

        raise WorkerError(f"豆包 {field_name} 必须是字符串、对象或列表")

    @staticmethod
    def _normalize_option_map(option: Any) -> Dict[str, Any]:
        return dict(option) if isinstance(option, dict) else {}

    @staticmethod
    def _require_model(input_data: Dict[str, Any], message: str) -> str:
        model = str(input_data.get("model", "")).strip()
        if not model:
            raise WorkerError(message)
        return model

    @staticmethod
    def _require_prompt(input_data: Dict[str, Any], message: str) -> str:
        prompt = input_data.get("prompt", input_data.get("text"))
        text = str(prompt or "").strip()
        if not text:
            raise WorkerError(message)
        return text

    def _supports_video_audio_reference(self, model: str) -> bool:
        return self._is_seedance_20_model(model)

    def _is_seedance_20_model(self, model: str) -> bool:
        return self.SEEDANCE_20_FLAG in str(model or "").strip().lower()

    @staticmethod
    def _merge_option_payload(payload: Dict[str, Any], option: Dict[str, Any], ignored: Optional[set[str]] = None) -> None:
        skip = set(ignored or set())
        for key, value in option.items():
            if key in skip:
                continue
            payload[key] = value

    def _task_url(self) -> str:
        return f"{self.host}{self.CREATE_PATH}"

    def _normalize_video_response(self, task_id: str, body: Dict[str, Any]) -> Dict[str, Any]:
        urls = self.collect_video_urls(body)
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
