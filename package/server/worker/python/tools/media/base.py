from __future__ import annotations
import importlib
from typing import Any, Dict, List, Optional

from dever.error import WorkerError
from tools.provider.core import Provider

class Base(object):
    config = {}

    def __init__(self, config: Optional[Dict[str, Any]] = None):
        self.config: Dict[str, Any] = config or {}
        platform_config = self.config.get("platform", {}) if isinstance(self.config.get("platform"), dict) else {}
        model_config = self.config.get("model", {}) if isinstance(self.config.get("model"), dict) else {}
        self.platform_config = platform_config
        self.model_config = model_config
        self.model = str(model_config.get("model") or "").strip()
        self.provider_name = str(model_config.get("protocol") or "").strip().lower()
        self.host = str(platform_config.get("host") or "").rstrip("/")
        self.api_key = str(platform_config.get("api_key") or "").strip()
        if not self.host:
            raise WorkerError("配置缺少 host")
        if not self.api_key:
            raise WorkerError("配置缺少 api_key")
        self.header = {
            "Content-Type": "application/json",
            "Authorization": f"Bearer {self.api_key}",
        }

    def _model_param_mode(self) -> str:
        for key in ("protocol", "type"):
            value = str(self.model_config.get(key) or "").strip().lower()
            if value:
                return value
        return self.provider_name

    def _is_workflow_param_model(self) -> bool:
        return self._model_param_mode() == "runninghubflow"

    def _model_params(self) -> List[Dict[str, Any]]:
        raw = self.model_config.get("params")
        if not isinstance(raw, list):
            return []
        return [item for item in raw if isinstance(item, dict)]

    def _apply_model_param_option_mapping(self, option: Any) -> Dict[str, Any]:
        option_map = dict(option) if isinstance(option, dict) else {}
        if not option_map or self._is_workflow_param_model():
            return option_map

        mappings = self._model_param_mappings()
        if not mappings:
            return option_map

        out = dict(option_map)
        for source_key, target_key in mappings.items():
            actual_source = self._find_option_key(out, source_key)
            if not actual_source:
                continue
            actual_target = self._find_option_key(out, target_key)
            if actual_target and actual_target != actual_source:
                out.pop(actual_source, None)
                continue
            if actual_source == target_key:
                continue
            out[target_key] = out.pop(actual_source)
        return out

    def _model_param_mappings(self) -> Dict[str, str]:
        out: Dict[str, str] = {}
        for item in self._model_params():
            source = str(item.get("name") or "").strip()
            target = str(item.get("value") or "").strip()
            if not source or not target:
                continue
            out[source.lower()] = target
        return out

    @staticmethod
    def _find_option_key(option: Dict[str, Any], name: str) -> str:
        target = str(name or "").strip()
        if not target:
            return ""
        if target in option:
            return target
        target_lower = target.lower()
        for key in option.keys():
            key_text = str(key).strip()
            if key_text.lower() == target_lower:
                return key_text
        return ""

    def _build_provider_config(
        self,
        provider_name: str = "",
        provider_cls: Optional[Any] = None,
    ) -> Dict[str, str]:
        name = str(provider_name or "").strip().lower()
        platform_config = self.config.get("platform", {})
        if not isinstance(platform_config, dict):
            platform_config = {}
        raw_providers = platform_config.get("providers")
        provider_config = raw_providers.get(name, {}) if isinstance(raw_providers, dict) and name else {}
        if not isinstance(provider_config, dict):
            provider_config = {}

        base_host = str(platform_config.get("host") or self.host).strip()
        base_token = str(platform_config.get("api_key") or self.api_key).strip()

        host = str(provider_config.get("host") or base_host).strip()
        token = str(
            provider_config.get("token")
            or provider_config.get("api_key")
            or base_token
        ).strip()

        default_host = str(getattr(provider_cls, "DEFAULT_HOST", "") or "").strip()
        if default_host and host and default_host not in host and "runninghub" in name:
            host = default_host

        return {
            "host": host,
            "token": token,
        }

    def _resolve_provider_name(self) -> str:
        provider_name = self.provider_name
        if not provider_name:
            raise WorkerError("模型缺少 protocol")
        self._load_provider_class(provider_name)
        return provider_name

    def _load_provider_class(self, provider_name: str) -> Any:
        name = str(provider_name or "").strip().lower()
        if not name:
            raise WorkerError("provider_name 不能为空")
        try:
            module = importlib.import_module(f"tools.provider.{name}")
            module = importlib.reload(module)
        except ModuleNotFoundError as exc:
            raise WorkerError(f"未知 provider: {provider_name}") from exc

        for value in vars(module).values():
            if (
                isinstance(value, type)
                and issubclass(value, Provider)
                and value is not Provider
                and value.__module__ == module.__name__
            ):
                return value
        raise WorkerError(f"provider 未实现: {provider_name}")

    def _create_provider(
        self,
        provider_name: str,
    ) -> Any:
        name = str(provider_name or "").strip().lower()
        if not name:
            raise WorkerError("provider_name 不能为空")
        provider_cls = self._load_provider_class(name)
        return provider_cls(self._build_provider_config(name, provider_cls))

    def _build_task_key(self, provider_name: str, meta: Optional[Dict[str, Any]] = None) -> str:
        name = str(provider_name or "").strip().lower()
        if not name:
            return ""
        meta_map = meta if isinstance(meta, dict) else {}
        parts = [
            "tool_task",
            name,
            str(self.config.get("workflow_code") or "").strip(),
            str(self.config.get("content_code") or "").strip(),
            str(self.config.get("content_version_id") or "").strip(),
            str(meta_map.get("node_id") or "").strip(),
        ]
        return ":".join(part for part in parts if part)

    @staticmethod
    def _has_option_file(value: Any) -> bool:
        if isinstance(value, str):
            return bool(value.strip())
        if isinstance(value, (list, dict, tuple, set)):
            return len(value) > 0
        return bool(value)
