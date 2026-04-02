from __future__ import annotations

from typing import Any, Dict, List, Optional

from dever.error import WorkerError
from dever.prompt import Prompt
from dever.qiniu import Qiniu
from dever.task import TaskReporter
from tools.media.base import Base


MEDIA_FIELDS = ("image", "video", "audio")


class Workflow(Base):
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
            body = provider.workflow(data, meta=meta)
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
        provider_name = self._resolve_provider_name()
        option = input_data.get("option") if isinstance(input_data.get("option"), dict) else {}
        payload: Dict[str, Any] = {
            "model": self.model,
            "option": option,
            "nodeInfoList": self._build_node_info_list(input_data, option) if self._is_workflow_param_model() else [],
        }
        for key in ("wait",):
            if key in input_data:
                payload[key] = input_data[key]
        return provider_name, payload

    def _build_node_info_list(self, input_data: Dict[str, Any], option: Dict[str, Any]) -> List[Dict[str, Any]]:
        prompt_text = str(input_data.get("input", input_data.get("prompt", "")) or "").strip()
        media_map = self._resolve_media_map(input_data, option)
        rows: List[Dict[str, Any]] = []

        for item in self._model_params():
            field_name = str(item.get("name", "")).strip()
            node_id = str(item.get("value", "")).strip()
            if not field_name or not node_id:
                continue
            field_value = self._resolve_field_value(field_name, prompt_text, option, media_map)
            if self._is_empty_field_value(field_value):
                continue
            rows.append(
                {
                    "nodeId": node_id,
                    "fieldName": field_name,
                    "fieldValue": field_value,
                }
            )
        return rows

    def _resolve_media_map(self, input_data: Dict[str, Any], option: Dict[str, Any]) -> Dict[str, List[str]]:
        merged = {key: [] for key in MEDIA_FIELDS}
        prepared = Prompt.get_input(input_data, extract_types=list(MEDIA_FIELDS))
        prompt_map = prepared.get("file") if isinstance(prepared.get("file"), dict) else {}
        option_map = self._extract_media_map_from_option(option)
        option_file_map = self._extract_media_map_from_file_payload(option.get("file"))

        for key in MEDIA_FIELDS:
            values: List[str] = []
            values.extend(option_map.get(key, []))
            values.extend(option_file_map.get(key, []))
            values.extend(str(item).strip() for item in prompt_map.get(key, []) if str(item).strip())
            merged[key] = self._uniq_strings(values)
        return merged

    def _extract_media_map_from_option(self, option: Dict[str, Any]) -> Dict[str, List[str]]:
        media_option = {key: option.get(key) for key in MEDIA_FIELDS if key in option}
        return self._extract_media_map_from_file_payload(media_option)

    def _extract_media_map_from_file_payload(self, raw: Any) -> Dict[str, List[str]]:
        result = {key: [] for key in MEDIA_FIELDS}
        prepared = Prompt.get_input({"file": raw}, extract_types=list(MEDIA_FIELDS))
        file_map = prepared.get("file")
        if isinstance(file_map, dict):
            for key in MEDIA_FIELDS:
                value = file_map.get(key)
                if isinstance(value, list):
                    result[key].extend(str(item).strip() for item in value if str(item).strip())

        if isinstance(raw, dict):
            for key in MEDIA_FIELDS:
                if key in raw:
                    result[key].extend(self._normalize_media_value(raw.get(key)))
        return {key: self._uniq_strings(value) for key, value in result.items()}

    def _resolve_field_value(
        self,
        field_name: str,
        prompt_text: str,
        option: Dict[str, Any],
        media_map: Dict[str, List[str]],
    ) -> Any:
        lowered = field_name.strip().lower()
        if lowered == "text":
            return prompt_text
        if lowered in MEDIA_FIELDS:
            return self._collapse_values(media_map.get(lowered, []))
        return self._resolve_option_value(option, field_name)

    def _resolve_option_value(self, option: Dict[str, Any], field_name: str) -> Any:
        if field_name in option:
            return option[field_name]
        lowered = field_name.strip().lower()
        for key, value in option.items():
            if str(key).strip().lower() == lowered:
                return value
        return None

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

    @staticmethod
    def _normalize_media_value(raw: Any) -> List[str]:
        if raw is None:
            return []
        if isinstance(raw, str):
            return [raw.strip()] if raw.strip() else []
        if isinstance(raw, dict):
            for key in ("url", "src", "value"):
                value = str(raw.get(key, "")).strip()
                if value:
                    return [value]
            out: List[str] = []
            for value in raw.values():
                out.extend(Workflow._normalize_media_value(value))
            return out
        if isinstance(raw, (list, tuple, set)):
            out: List[str] = []
            for item in raw:
                out.extend(Workflow._normalize_media_value(item))
            return out
        return []

    @staticmethod
    def _collapse_values(values: Any) -> Any:
        if not isinstance(values, list):
            return values
        cleaned = [str(item).strip() for item in values if str(item).strip()]
        if not cleaned:
            return None
        if len(cleaned) == 1:
            return cleaned[0]
        return cleaned

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

    @staticmethod
    def _is_empty_field_value(value: Any) -> bool:
        if value is None:
            return True
        if isinstance(value, str):
            return not value.strip()
        if isinstance(value, (list, dict, tuple, set)):
            return len(value) == 0
        return False
