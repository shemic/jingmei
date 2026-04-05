from __future__ import annotations

from typing import Any, Dict, Optional

from dever.error import WorkerError
from tools.media.base import Base, MEDIA_FIELDS


class Image(Base):
    def handle(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return self._run_generation("image", input, meta, self._build_provider_input)

    def _build_provider_input(self, input_data: Dict[str, Any]) -> tuple[str, Dict[str, Any]]:
        model, mode, prepared, option, media_map = self._prepare_media_input(
            input_data,
            default_model="gpt-image-1",
            extract_types=list(MEDIA_FIELDS),
        )
        prompt = str(prepared.get("prompt", "")).strip()
        if not prompt:
            raise WorkerError("prompt 不能为空")
        normalized_files = media_map.get("image", [])
        provider_option = self._apply_model_param_payload_mapping(option)
        media_rules = self._build_model_param_media_rules()
        provider_name = self._resolve_provider_name()
        is_edit = bool(normalized_files)

        payload: Dict[str, Any] = {
            "model": model,
            "mode": mode,
            "prompt": prompt,
            "option": provider_option,
            "files": normalized_files,
            "image": normalized_files,
            "video": media_map.get("video", []),
            "audio": media_map.get("audio", []),
            "media_rules": media_rules,
            "is_edit": is_edit,
        }
        passthrough = ("seed", "n", "quality", "style")
        for key in passthrough:
            if key in input_data:
                payload[key] = input_data[key]
        return provider_name, payload
