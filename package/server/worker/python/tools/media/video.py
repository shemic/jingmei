from __future__ import annotations

from typing import Any, Dict, List, Optional
from urllib.parse import urlparse

from dever.error import WorkerError
from dever.prompt import Prompt
from dever.qiniu import Qiniu
from dever.task import TaskReporter
from tools.media.base import Base


class Video(Base):
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
            body = provider.video(data, meta=meta)
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
        model, mode = Prompt.parse_modal(self.model, default_model="doubao-seedance-1-0-lite-i2v-250428")
        provider_name = self._resolve_provider_name()
        extract_types = ["image", "video", "audio"] if mode == "mention" else ["image"]
        prompt_mode = "@" if mode == "mention" else "图"
        prepared = Prompt.get_input(input_data, extract_types=extract_types, mode=prompt_mode)
        prompt_raw = str(prepared.get("prompt", "")).strip()
        if not prompt_raw:
            raise WorkerError("input 不能为空")

        option = prepared.get("option", {})
        if not isinstance(option, dict):
            option = {}
        normalized_option = self._normalize_option(option)
        provider_option = self._apply_model_param_option_mapping(normalized_option)

        if mode == "mention":
            files = self._normalize_mention_files_for_video(prepared.get("file"))
        else:
            files = self._normalize_image_urls_for_video(
                self._extract_video_images(prepared.get("file"), option, input_data)
            )

        payload: Dict[str, Any] = {
            "model": model,
            "mode": mode,
            "prompt": prompt_raw,
            "option": provider_option,
            "files": files,
            "is_edit": mode != "mention" and bool(files),
        }
        for key in ("wait", "timeout", "interval"):
            if key in input_data:
                payload[key] = input_data[key]
            elif key in provider_option:
                payload[key] = provider_option[key]
        return provider_name, payload

    def _normalize_image_urls_for_video(self, image_urls: List[str]) -> List[str]:
        cleaned = [str(url).strip() for url in image_urls if str(url).strip()]
        if not cleaned:
            return []
        content_code = str(self.config.get("content_code", "")).strip()
        if not content_code:
            return cleaned

        qiniu = Qiniu()
        qiniu_host = urlparse(qiniu.domain if str(qiniu.domain).startswith("http") else f"https://{qiniu.domain}").netloc.lower()
        normalized: List[str] = []
        for idx, raw in enumerate(cleaned):
            parsed = urlparse(raw)
            host = parsed.netloc.lower()
            if qiniu_host and host == qiniu_host:
                normalized.append(raw)
                continue
            if raw.startswith("data:"):
                raise WorkerError("图生视频暂不支持 data URI 图片，请先上传图片")
            stored = qiniu.upload(
                source_url=raw,
                content_code=content_code,
                prefix="user_upload",
                file_type="user_upload",
                index=idx,
            )
            normalized.append(str(stored.get("url", "")).strip() or raw)
        return normalized

    def _normalize_mention_files_for_video(self, files: Any) -> List[str]:
        cleaned = [str(item).strip() for item in files if str(item).strip()] if isinstance(files, list) else []
        if not cleaned:
            return []

        content_code = str(self.config.get("content_code", "")).strip()
        qiniu = Qiniu()
        qiniu_host = urlparse(qiniu.domain if str(qiniu.domain).startswith("http") else f"https://{qiniu.domain}").netloc.lower()
        normalized: List[str] = []
        for idx, raw in enumerate(cleaned):
            parsed = urlparse(raw)
            host = parsed.netloc.lower()
            if qiniu_host and host == qiniu_host:
                normalized.append(raw)
                continue
            if not content_code:
                if raw.startswith("data:"):
                    raise WorkerError("mention 模式缺少 content_code，无法上传 data URI 文件")
                normalized.append(raw)
                continue
            stored = qiniu.upload(
                source_url=raw,
                content_code=content_code,
                prefix="user_upload",
                file_type="user_upload",
                index=idx,
            )
            normalized.append(str(stored.get("url", "")).strip() or raw)
        return normalized

    @staticmethod
    def _parse_bool(value: Any) -> Optional[bool]:
        if isinstance(value, bool):
            return value
        if isinstance(value, (int, float)):
            if value == 1:
                return True
            if value == 0:
                return False
            return None
        if isinstance(value, str):
            lowered = value.strip().lower()
            if lowered in {"1", "true", "yes", "y", "on"}:
                return True
            if lowered in {"0", "false", "no", "n", "off"}:
                return False
        return None

    def _normalize_option(self, option: Dict[str, Any]) -> Dict[str, Any]:
        out: Dict[str, Any] = dict(option)
        for key in ("draft", "watermark", "storyboard"):
            if key not in out:
                continue
            parsed = self._parse_bool(out.get(key))
            if parsed is not None:
                out[key] = parsed
        return out

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
                file_type="model_generated",
                index=idx,
            )
            row["source_url"] = src_url
            row["url"] = stored["url"]
            row["qiniu_key"] = stored["key"]
            uploaded.append({"index": idx, "key": stored["key"], "url": stored["url"]})
            aigc_urls.append(stored["url"])
            reporter.emit(status="upload", progress=upload_start + int(((idx + 1) / total) * (98 - upload_start)))
        return uploaded, aigc_urls

    def _extract_video_images(self, file_data: Any, option: Dict[str, Any], input_data: Dict[str, Any]) -> List[str]:
        values: List[str] = []
        values.extend(self._extract_image_values(input_data.get("image")))
        values.extend(self._extract_image_values_from_file_payload(input_data.get("file")))
        values.extend(self._extract_image_values(option.get("image")))
        values.extend(self._extract_image_values_from_file_payload(option.get("file")))
        if isinstance(file_data, dict):
            values.extend(self._extract_image_values(file_data.get("image")))
        else:
            values.extend(self._extract_image_values(file_data))
        return self._uniq_strings(values)

    def _extract_image_values_from_file_payload(self, raw: Any) -> List[str]:
        values: List[str] = []
        if isinstance(raw, (str, list, tuple, set)):
            values.extend(self._extract_image_values(raw))
        elif isinstance(raw, dict):
            if "image" in raw:
                values.extend(self._extract_image_values(raw.get("image")))
            elif "images" in raw:
                values.extend(self._extract_image_values(raw.get("images")))
            else:
                for key in ("url", "src", "value"):
                    if key in raw:
                        values.extend(self._extract_image_values(raw.get(key)))
                        break
        prepared = Prompt.get_input({"file": raw}, extract_types=["image"])
        file_map = prepared.get("file")
        if isinstance(file_map, dict):
            values.extend(self._extract_image_values(file_map.get("image")))
        if isinstance(raw, dict) and "image" in raw:
            values.extend(self._extract_image_values(raw.get("image")))
        return self._uniq_strings(values)

    @staticmethod
    def _extract_image_values(raw: Any) -> List[str]:
        if raw is None:
            return []
        if isinstance(raw, str):
            return [raw.strip()] if raw.strip() else []
        if isinstance(raw, dict):
            for key in ("url", "src", "value"):
                value = str(raw.get(key, "")).strip()
                if value:
                    return [value]
            values: List[str] = []
            for value in raw.values():
                values.extend(Video._extract_image_values(value))
            return values
        if isinstance(raw, (list, tuple, set)):
            values: List[str] = []
            for item in raw:
                values.extend(Video._extract_image_values(item))
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
