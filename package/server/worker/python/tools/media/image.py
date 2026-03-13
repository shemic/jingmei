from __future__ import annotations

from typing import Any, Dict, List, Optional

import requests

from dever.error import WorkerError
from dever.prompt import Prompt
from dever.qiniu import Qiniu
from dever.task import TaskReporter
from tools.media.base import Base


class Image(Base):
    def handle(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        if not isinstance(input, dict):
            raise WorkerError("入参必须是对象")
        reporter = TaskReporter(
            project_code=self.config.get("project_code"),
            content_code=self.config.get("content_code"),
            content_version_id=self.config.get("content_version_id"),
            model=self.model,
            meta=meta if isinstance(meta, dict) else None,
            min_interval_sec=1.0,
        )
        try:
            reporter.emit(status="start", progress=0, force=True)
            url = f"{self.host}/images/generations"
            data = self._build_payload(input)
            reporter.emit(status="run", progress=10, force=True)
            reporter.emit(status="run", progress=-1, random={"floor": 10, "cap": 78, "interval": 0.8})
            res = requests.post(url, headers=self.header, json=data)
            if not res.ok:
                preview = (res.text or "").strip()[:500]
                raise WorkerError(f"图片生成失败: status={res.status_code}, body={preview}")

            try:
                body = res.json()
            except Exception as exc:
                preview = (res.text or "").strip()[:500]
                raise WorkerError(f"返回JSON无效: {preview}") from exc

            rows = body.get("data")
            if not isinstance(rows, list) or not rows:
                raise WorkerError("返回缺少 data 列表")

            content_code = str(self.config.get("content_code", "")).strip()
            if not content_code:
                raise WorkerError("配置缺少 content_code，无法生成七牛 key")

            qiniu = Qiniu()
            uploaded: List[Dict[str, Any]] = []
            aigc_urls: List[str] = []
            total = max(len(rows), 1)
            base_progress = reporter.current_progress()
            if base_progress < 0:
                base_progress = 10
            upload_start = max(20, min(90, base_progress + 2))
            reporter.emit(status="upload", progress=upload_start, force=True)
            reporter.emit(status="upload", progress=-1, random={"floor": upload_start, "cap": 98, "interval": 0.8})
            for idx, row in enumerate(rows):
                if not isinstance(row, dict):
                    continue
                src_url = str(row.get("url", "")).strip()
                if not src_url:
                    continue

                stored = qiniu.upload(
                    source_url=src_url,
                    content_code=content_code,
                    prefix="model_generated",
                    index=idx,
                )

                row["source_url"] = src_url
                row["url"] = stored["url"]
                row["qiniu_key"] = stored["key"]
                uploaded.append({"index": idx, "key": stored["key"], "url": stored["url"]})
                aigc_urls.append(stored["url"])
                if total > 0:
                    p = upload_start + int(((idx + 1) / total) * (98 - upload_start))
                    reporter.emit(status="upload", progress=p)

            body["uploaded"] = uploaded
            body["aigc"] = ",".join(aigc_urls)
            reporter.emit(status="finish", progress=100, force=True)
            return body
        except Exception:
            reporter.emit(status="failed", progress=100, force=True)
            raise

    def _build_payload(self, input_data: Dict[str, Any]) -> Dict[str, Any]:
        model, _ = Prompt.parse_modal(self.model, default_model="gpt-image-1")
        prepared = Prompt.get_input(input_data, extract_types=["image"], mode="@")
        prompt = str(prepared.get("prompt", "")).strip()
        if not prompt:
            raise WorkerError("prompt 不能为空")
        option = prepared.get("option", {})
        if not isinstance(option, dict):
            option = {}

        payload: Dict[str, Any] = {
            "model": model,
            "prompt": prompt,
            "response_format": option.get("response_format", "url"),
            "size": option.get("size", "2048x2048"),
            "guidance_scale": option.get("guidance_scale", 3),
            "watermark": bool(option.get("watermark", False)),
            "sequential_image_generation": "auto",
            "sequential_image_generation_options": {
                "max_images": 4
            },
        }
        files = prepared.get("file")
        if isinstance(files, list) and files:
            payload["image"] = files

        passthrough = ("seed", "n", "quality", "style")
        for key in passthrough:
            if key in input_data:
                payload[key] = input_data[key]
        return payload
