from __future__ import annotations

from typing import Any, Dict, List, Optional

from dever.prompt import Prompt
from tools.media.base import Base, MEDIA_FIELDS


class Workflow(Base):
    def handle(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return self._run_generation("workflow", input, meta, self._build_provider_input)

    def _build_provider_input(self, input_data: Dict[str, Any]) -> tuple[str, Dict[str, Any]]:
        provider_name = self._resolve_provider_name()
        prepared = Prompt.get_input(input_data, extract_types=list(MEDIA_FIELDS), mode="图")
        option = prepared.get("option") if isinstance(prepared.get("option"), dict) else {}
        option = self._normalize_typed_option_values(option)
        payload: Dict[str, Any] = {
            "model": self.model,
            "option": option,
            "nodeInfoList": self._build_node_info_list(input_data, prepared, option) if self._is_workflow_param_model() else [],
        }
        for key in ("wait",):
            if key in input_data:
                payload[key] = input_data[key]
        return provider_name, payload

    def _build_node_info_list(self, input_data: Dict[str, Any], prepared: Dict[str, Any], option: Dict[str, Any]) -> List[Dict[str, Any]]:
        prompt_text = str(prepared.get("prompt", input_data.get("input", input_data.get("prompt", ""))) or "").strip()
        media_map = self._extract_media_map(input_data, option, prepared.get("file"), extract_types=list(MEDIA_FIELDS))
        rows: List[Dict[str, Any]] = []

        for item in self._model_params():
            source_field, field_name, node_id = self._resolve_workflow_param_binding(item)
            if not field_name or not node_id:
                continue
            field_value = self._resolve_field_value(source_field, prompt_text, option, media_map)
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

    @staticmethod
    def _resolve_workflow_param_binding(item: Dict[str, Any]) -> tuple[str, str, str]:
        source_field = str(item.get("name") or "").strip()
        target_field = str(item.get("value") or "").strip()
        node_id = str(item.get("nid") or "").strip()
        if source_field and target_field and node_id:
            return source_field, target_field, node_id
        if source_field and target_field:
            return source_field, source_field, target_field
        return "", "", ""

    def _resolve_field_value(
        self,
        field_name: str,
        prompt_text: str,
        option: Dict[str, Any],
        media_map: Dict[str, List[str]],
    ) -> Any:
        lowered = field_name.strip().lower()
        if lowered in {"text", "prompt", "input"}:
            return prompt_text
        media_source = self._parse_media_source(field_name)
        if media_source:
            media_type, media_index = media_source
            values = media_map.get(media_type, [])
            if media_index is None:
                return self._collapse_values(values)
            if media_index < 0 or media_index >= len(values):
                return None
            return values[media_index]
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
