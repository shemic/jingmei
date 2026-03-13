from __future__ import annotations

from typing import Any, Dict, List, Optional

from dever.error import WorkerError
from worker.python.tools.media.core import Provider


class Doubao(Provider):
    def image(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        url = self.host + "/images/generations"
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

    def create_task(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        return self.request_json("POST", self._task_url(), payload=payload, timeout=60)

    def query_task(self, task_id: str) -> Dict[str, Any]:
        return self.request_json("GET", f"{self._task_url()}/{task_id}", timeout=60)

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
