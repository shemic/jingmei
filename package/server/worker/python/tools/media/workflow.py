from __future__ import annotations

from typing import Any, Dict, List, Optional

from dever.pgsql import PgSQL as Db
from dever.prompt import Prompt
from tools.media.base import Base, MEDIA_FIELDS

# WorkflowInputOption.Type=2 表示非字符串值，true/false 字面量需要传给下游为 bool。
BOOL_OPTION_VALUES = {"true": True, "false": False}


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

    def _normalize_typed_option_values(self, option: Dict[str, Any]) -> Dict[str, Any]:
        option_map = dict(option) if isinstance(option, dict) else {}
        if not option_map:
            return option_map

        bool_rules = self._load_bool_input_option_rules()
        if not bool_rules:
            return option_map

        out = dict(option_map)
        for key, value in option_map.items():
            option_key = str(key or "").strip().lower()
            value_key = self._bool_option_value_key(value)
            if not option_key or not value_key:
                continue
            if value_key in bool_rules.get(option_key, {}):
                out[key] = bool_rules[option_key][value_key]
        return out

    def _load_bool_input_option_rules(self) -> Dict[str, Dict[str, bool]]:
        workflow_id = self._resolve_input_workflow_id()
        if workflow_id <= 0:
            return {}

        input_table = Db.table("work_workflow_input")
        option_table = Db.table("work_workflow_input_option")
        rows = Db.fetch(
            f"""
            SELECT i.code, i.name, o.value
            FROM {option_table} o
            JOIN {input_table} i ON i.id = o.workflow_input_id
            WHERE i.workflow_id = %s
              AND i.status = 1
              AND o.status = 1
              AND o.type = 2
            """,
            [workflow_id],
        )

        rules: Dict[str, Dict[str, bool]] = {}
        for row in rows:
            value_key = self._bool_option_value_key(row.get("value"))
            if not value_key:
                continue
            for field in (row.get("code"), row.get("name")):
                field_key = str(field or "").strip().lower()
                if not field_key:
                    continue
                rules.setdefault(field_key, {})[value_key] = BOOL_OPTION_VALUES[value_key]
        return rules

    def _resolve_input_workflow_id(self) -> int:
        workflow_code = str(self.config.get("workflow_code") or "").strip()
        if not workflow_code:
            return 0

        workflow_table = Db.table("work_workflow")
        row = Db.find(
            f"SELECT id, workflow_id FROM {workflow_table} WHERE code = %s AND status = 1 LIMIT 1",
            [workflow_code],
        )
        if not isinstance(row, dict):
            return 0

        linked_id = self._to_int(row.get("workflow_id"))
        if linked_id <= 0:
            return self._to_int(row.get("id"))

        linked = Db.find(
            f"SELECT id FROM {workflow_table} WHERE id = %s AND status = 1 LIMIT 1",
            [linked_id],
        )
        if isinstance(linked, dict):
            return self._to_int(linked.get("id"))
        return self._to_int(row.get("id"))

    @staticmethod
    def _bool_option_value_key(value: Any) -> str:
        if not isinstance(value, str):
            return ""
        normalized = value.strip().lower()
        return normalized if normalized in BOOL_OPTION_VALUES else ""

    @staticmethod
    def _to_int(value: Any) -> int:
        try:
            return int(value)
        except Exception:
            return 0
