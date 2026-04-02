from __future__ import annotations

from typing import Any, Dict, List, Optional

from dever.error import WorkerError
from dever.prompt import Prompt
from dever.qiniu import Qiniu
from dever.task import TaskReporter
from tools.media.base import Base


class Image(Base):
    def handle(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        if not isinstance(input, dict):
            raise WorkerError("入参必须是对象")
        reporter = self._create_reporter(meta)
        try:
            reporter.emit(status="start", progress=0, force=True)
            provider_name, data = self._build_provider_input(input)
            task_key = self._build_task_key(provider_name, meta)
            if task_key:
                data["task_key"] = task_key
            provider = self._create_provider(provider_name)
            reporter.emit(status="run", progress=10, force=True)
            reporter.emit(status="run", progress=-1, random={"floor": 10, "cap": 78, "interval": 0.8})
            body = provider.image(data, meta=meta)
            task_id = str(body.get("task_id", "")).strip()
            if task_id:
                reporter.set_task_id(task_id)

            rows = body.get("data")
            if not isinstance(rows, list) or not rows:
                raise WorkerError("返回缺少 data 列表")

            uploaded, aigc_urls = self._upload_rows(rows, reporter)
            body["uploaded"] = uploaded
            body["aigc"] = ",".join(aigc_urls)
            if task_key and hasattr(provider, "clear_cached_task_id"):
                provider.clear_cached_task_id(task_key)
            reporter.emit(status="finish", progress=100, force=True)
            return body
        except Exception:
            reporter.emit(status="failed", progress=100, force=True)
            raise

    def _build_provider_input(self, input_data: Dict[str, Any]) -> tuple[str, Dict[str, Any]]:
        model, _ = Prompt.parse_modal(self.model, default_model="gpt-image-1")
        prepared = Prompt.get_input(input_data, extract_types=["image"], mode="@")
        prompt = str(prepared.get("prompt", "")).strip()
        if not prompt:
            raise WorkerError("prompt 不能为空")
        option = prepared.get("option", {})
        if not isinstance(option, dict):
            option = {}
        normalized_files = self._extract_input_images(prepared.get("file"), option, input_data)
        provider_option = self._apply_model_param_option_mapping(option)
        provider_name = self._resolve_provider_name()
        is_edit = bool(normalized_files)

        payload: Dict[str, Any] = {
            "model": model,
            "prompt": prompt,
            "option": provider_option,
            "files": normalized_files,
            "is_edit": is_edit,
        }
        passthrough = ("seed", "n", "quality", "style")
        for key in passthrough:
            if key in input_data:
                payload[key] = input_data[key]
        return provider_name, payload

    def _create_reporter(self, meta: Optional[Dict[str, Any]]) -> TaskReporter:
        return TaskReporter(
            project_code=self.config.get("project_code"),
            content_code=self.config.get("content_code"),
            content_version_id=self.config.get("content_version_id"),
            model=self.model,
            meta=meta if isinstance(meta, dict) else None,
            min_interval_sec=1.0,
        )

    def _upload_rows(self, rows: List[Dict[str, Any]], reporter: TaskReporter) -> tuple[List[Dict[str, Any]], List[str]]:
        content_code = str(self.config.get("content_code", "")).strip()
        if not content_code:
            raise WorkerError("配置缺少 content_code，无法生成七牛 key")

        qiniu = Qiniu()
        uploaded: List[Dict[str, Any]] = []
        aigc_urls: List[str] = []
        total = max(len(rows), 1)
        base_progress = max(reporter.current_progress(), 10)
        upload_start = max(20, min(90, base_progress + 2))
        reporter.emit(status="upload", progress=upload_start, force=True)
        reporter.emit(status="upload", progress=-1, random={"floor": upload_start, "cap": 98, "interval": 0.8})
        for idx, row in enumerate(rows):
            src_url = str(row.get("url", "")).strip() if isinstance(row, dict) else ""
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
            reporter.emit(status="upload", progress=upload_start + int(((idx + 1) / total) * (98 - upload_start)))
        return uploaded, aigc_urls

    def _extract_input_images(self, prepared_file: Any, option: Dict[str, Any], input_data: Dict[str, Any]) -> List[str]:
        values: List[str] = []
        values.extend(self._extract_image_values(prepared_file))
        values.extend(self._extract_image_values(option.get("image")))
        values.extend(self._extract_image_values_from_file_payload(option.get("file")))
        values.extend(self._extract_image_values(input_data.get("image")))
        values.extend(self._extract_image_values_from_file_payload(input_data.get("file")))
        return self._uniq_strings(values)

    def _extract_image_values_from_file_payload(self, raw: Any) -> List[str]:
        values = self._extract_image_values(raw)
        prepared = Prompt.get_input({"file": raw}, extract_types=["image"])
        file_map = prepared.get("file")
        values.extend(self._extract_image_values(file_map))
        return self._uniq_strings(values)

    @staticmethod
    def _extract_image_values(raw: Any) -> List[str]:
        if raw is None:
            return []
        if isinstance(raw, str):
            value = raw.strip()
            return [value] if value else []
        if isinstance(raw, dict):
            if "image" in raw:
                return Image._extract_image_values(raw.get("image"))
            values: List[str] = []
            for key in ("url", "src", "value"):
                value = str(raw.get(key, "")).strip()
                if value:
                    values.append(value)
            if values:
                return values
            for value in raw.values():
                values.extend(Image._extract_image_values(value))
            return values
        if isinstance(raw, (list, tuple, set)):
            values: List[str] = []
            for item in raw:
                values.extend(Image._extract_image_values(item))
            return values
        return []

    @staticmethod
    def _uniq_strings(values: List[str]) -> List[str]:
        out: List[str] = []
        seen = set()
        for item in values:
            value = str(item).strip()
            if not value or value in seen:
                continue
            seen.add(value)
            out.append(value)
        return out
