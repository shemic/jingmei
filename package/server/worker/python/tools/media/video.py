from __future__ import annotations

from typing import Any, Dict, List, Optional
from urllib.parse import urlparse

from dever.error import WorkerError
from dever.qiniu import Qiniu
from tools.media.base import Base, MEDIA_FIELDS


class Video(Base):
    def handle(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return self._run_generation("video", input, meta, self._build_provider_input)

    def _build_provider_input(self, input_data: Dict[str, Any]) -> tuple[str, Dict[str, Any]]:
        model, mode, prepared, option, media_map = self._prepare_media_input(
            input_data,
            default_model="doubao-seedance-1-0-lite-i2v-250428",
            extract_types=list(MEDIA_FIELDS),
        )
        provider_name = self._resolve_provider_name()
        prompt_raw = str(prepared.get("prompt", "")).strip()
        if not prompt_raw:
            raise WorkerError("input 不能为空")
        normalized_option = self._normalize_option(option)
        provider_option = self._apply_model_param_payload_mapping(normalized_option)
        media_rules = self._build_model_param_media_rules()

        if mode == "mention":
            files = self._normalize_mention_files_for_video(prepared.get("file"))
        else:
            files = self._normalize_image_urls_for_video(media_map.get("image", []))

        payload: Dict[str, Any] = {
            "model": model,
            "mode": mode,
            "prompt": prompt_raw,
            "option": provider_option,
            "files": files,
            "image": media_map.get("image", []),
            "video": media_map.get("video", []),
            "audio": media_map.get("audio", []),
            "media_rules": media_rules,
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
        return self._upload_remote_urls(
            cleaned,
            content_code=content_code,
            data_uri_error="图生视频暂不支持 data URI 图片，请先上传图片",
        )

    def _normalize_mention_files_for_video(self, files: Any) -> List[str]:
        cleaned = [str(item).strip() for item in files if str(item).strip()] if isinstance(files, list) else []
        if not cleaned:
            return []

        content_code = str(self.config.get("content_code", "")).strip()
        return self._upload_remote_urls(
            cleaned,
            content_code=content_code,
            data_uri_error="mention 模式缺少 content_code，无法上传 data URI 文件",
        )

    @staticmethod
    def _qiniu_host(qiniu: Qiniu) -> str:
        domain = qiniu.domain if str(qiniu.domain).startswith("http") else f"https://{qiniu.domain}"
        return urlparse(domain).netloc.lower()

    def _upload_remote_urls(
        self,
        values: List[str],
        *,
        content_code: str,
        data_uri_error: str,
    ) -> List[str]:
        qiniu = Qiniu()
        qiniu_host = self._qiniu_host(qiniu)
        normalized: List[str] = []
        for idx, raw in enumerate(values):
            host = urlparse(raw).netloc.lower()
            if qiniu_host and host == qiniu_host:
                normalized.append(raw)
                continue
            if not content_code:
                if raw.startswith("data:"):
                    raise WorkerError(data_uri_error)
                normalized.append(raw)
                continue
            if raw.startswith("data:"):
                raise WorkerError(data_uri_error)
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
