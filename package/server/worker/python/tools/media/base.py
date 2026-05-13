from __future__ import annotations
import hashlib
import importlib
import json
import re
from typing import Any, Callable, Dict, List, Optional
from urllib.parse import urlparse

from dever.error import WorkerError
from dever.prompt import Prompt
from dever.qiniu import Qiniu
from dever.redis import Redis
from dever.task import TaskReporter
from tools.provider.core import Provider


MEDIA_FIELDS = ("image", "video", "audio")
IMAGE_EXT = {".jpg", ".jpeg", ".png", ".gif", ".bmp", ".webp", ".tiff", ".tif", ".svg", ".heic", ".heif"}
VIDEO_EXT = {".mp4", ".mov", ".avi", ".mkv", ".wmv", ".flv", ".webm", ".m4v", ".3gp", ".mpeg", ".mpg"}
AUDIO_EXT = {".mp3", ".wav", ".flac", ".aac", ".ogg", ".m4a", ".wma", ".amr"}
MEDIA_SOURCE_RE = re.compile(r"^(image|video|audio)(?:\[(\d+)\]|\.(\d+))?$", re.I)

class Base(object):
    config = {}
    RESULT_CACHE_TTL = 30 * 60
    LARGE_RESULT_KEYS = {"b64_json", "base64", "image_base64", "content_base64"}

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

    def _run_generation(
        self,
        method_name: str,
        input_data: Any,
        meta: Optional[Dict[str, Any]],
        build_input: Callable[[Dict[str, Any]], tuple[str, Dict[str, Any]]],
    ) -> Dict[str, Any]:
        if not isinstance(input_data, dict):
            raise WorkerError("入参必须是对象")
        reporter = self._create_reporter(meta)
        try:
            reporter.emit(status="start", progress=0, force=True)
            provider_name, data = build_input(input_data)
            task_key = self._build_task_key(provider_name, meta)
            if task_key:
                data["task_key"] = task_key
            result_cache_key = self._build_result_cache_key(method_name, provider_name, task_key, data)
            cached = self._load_cached_result(result_cache_key)
            if cached:
                cached_task_id = str(cached.get("task_id", "")).strip()
                if cached_task_id:
                    reporter.set_task_id(cached_task_id)
                reporter.emit(status="finish", progress=100, force=True)
                return cached

            provider = self._create_provider(provider_name)
            handler = getattr(provider, method_name, None)
            if not callable(handler):
                raise WorkerError(f"provider 不支持 {method_name}")
            reporter.emit(status="run", progress=10, force=True)
            reporter.emit(status="run", progress=-1, random={"floor": 10, "cap": 78, "interval": 0.8})
            body = handler(data, meta=meta)
            task_id = str(body.get("task_id", "")).strip()
            if task_id:
                reporter.set_task_id(task_id)

            rows = body.get("data")
            if not isinstance(rows, list) or not rows:
                raise WorkerError("返回缺少 data 列表")

            uploaded, aigc_urls = self._upload_rows(rows, reporter)
            body["uploaded"] = uploaded
            body["aigc"] = ",".join(aigc_urls)
            body = self._sanitize_large_payload(body)
            self._cache_result(result_cache_key, body)
            if task_key and hasattr(provider, "clear_cached_task_id"):
                provider.clear_cached_task_id(task_key)
            reporter.emit(status="finish", progress=100, force=True)
            return body
        except Exception:
            reporter.emit(status="failed", progress=100, force=True)
            raise

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
        for source_key, target_key in mappings:
            if self._parse_media_source(source_key):
                continue
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

    def _apply_model_param_payload_mapping(self, option: Any) -> Dict[str, Any]:
        option_map = dict(option) if isinstance(option, dict) else {}
        if self._is_workflow_param_model():
            return option_map
        return self._apply_model_param_option_mapping(option_map)

    def _model_param_mappings(self) -> List[tuple[str, str]]:
        out: List[tuple[str, str]] = []
        for item in self._model_params():
            source = str(item.get("name") or "").strip()
            target = str(item.get("value") or "").strip()
            if not source or not target:
                continue
            out.append((source.lower(), target))
        return out

    def _build_model_param_media_rules(self) -> List[Dict[str, Any]]:
        rules: List[Dict[str, Any]] = []
        if self._is_workflow_param_model():
            return rules
        for source_key, target_key in self._model_param_mappings():
            media_source = self._parse_media_source(source_key)
            if not media_source:
                continue
            media_type, media_index = media_source
            rules.append(
                {
                    "source": source_key,
                    "media_type": media_type,
                    "index": media_index,
                    "target": target_key,
                }
            )
        return rules

    @staticmethod
    def _parse_media_source(source_key: str) -> Optional[tuple[str, Optional[int]]]:
        matched = MEDIA_SOURCE_RE.match(str(source_key or "").strip())
        if not matched:
            return None
        media_type = str(matched.group(1) or "").strip().lower()
        index_text = str(matched.group(2) or matched.group(3) or "").strip()
        return media_type, int(index_text) if index_text else None

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

    def _prepare_media_input(
        self,
        input_data: Dict[str, Any],
        *,
        default_model: str,
        extract_types: Optional[List[str]] = None,
    ) -> tuple[str, str, Dict[str, Any], Dict[str, Any], Dict[str, List[str]]]:
        types = list(extract_types or MEDIA_FIELDS)
        model, mode = Prompt.parse_modal(self.model, default_model=default_model)
        prompt_mode = "@" if mode == "mention" else "图"
        prepared = Prompt.get_input(input_data, extract_types=types, mode=prompt_mode)
        option = prepared.get("option", {})
        if not isinstance(option, dict):
            option = {}
        media_map = self._extract_media_map(input_data, option, prepared.get("file"), extract_types=types)
        return model, mode, prepared, option, media_map

    def _create_reporter(self, meta: Optional[Dict[str, Any]]) -> TaskReporter:
        return TaskReporter(
            project_code=self.config.get("project_code"),
            content_code=self.config.get("content_code"),
            content_version_id=self.config.get("content_version_id"),
            model=self.model,
            meta=meta if isinstance(meta, dict) else None,
            min_interval_sec=1.0,
        )

    def _upload_rows(
        self,
        rows: List[Dict[str, Any]],
        reporter: TaskReporter,
        *,
        prefix: str = "model_generated",
        file_type: str = "model_generated",
    ) -> tuple[List[Dict[str, Any]], List[str]]:
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
                prefix=prefix,
                file_type=file_type,
                index=idx,
            )
            row["source_url"] = self._safe_source_url(src_url)
            row["url"] = stored["url"]
            row["qiniu_key"] = stored["key"]
            uploaded.append({"index": idx, "key": stored["key"], "url": stored["url"]})
            aigc_urls.append(stored["url"])
            reporter.emit(status="upload", progress=upload_start + int(((idx + 1) / total) * (98 - upload_start)))
        return uploaded, aigc_urls

    def _build_result_cache_key(
        self,
        method_name: str,
        provider_name: str,
        task_key: str,
        data: Dict[str, Any],
    ) -> str:
        if not task_key:
            return ""
        fingerprint = self._stable_payload_hash(data)
        parts = ["tool_result", method_name, provider_name, task_key, fingerprint]
        return ":".join(part for part in parts if part)

    def _load_cached_result(self, cache_key: str) -> Dict[str, Any]:
        if not cache_key:
            return {}
        try:
            client = Redis.get()
            if client is None:
                return {}
            raw = client.get(Redis.key(cache_key))
            if not raw:
                return {}
            body = json.loads(raw)
        except Exception:
            return {}
        return body if isinstance(body, dict) else {}

    def _cache_result(self, cache_key: str, body: Dict[str, Any]) -> None:
        if not cache_key or not isinstance(body, dict):
            return
        try:
            client = Redis.get()
            if client is None:
                return
            client.set(Redis.key(cache_key), json.dumps(body, ensure_ascii=False), ex=self.RESULT_CACHE_TTL)
        except Exception:
            return

    @classmethod
    def _stable_payload_hash(cls, data: Dict[str, Any]) -> str:
        payload = cls._sanitize_cache_fingerprint(data)
        try:
            raw = json.dumps(payload, ensure_ascii=False, sort_keys=True, separators=(",", ":"))
        except Exception:
            raw = str(payload)
        return hashlib.sha256(raw.encode("utf-8")).hexdigest()[:24]

    @classmethod
    def _sanitize_cache_fingerprint(cls, value: Any) -> Any:
        if isinstance(value, dict):
            out: Dict[str, Any] = {}
            for key, item in value.items():
                if key in {"task_key", "task_id", "timeout", "interval", "wait"}:
                    continue
                out[str(key)] = cls._sanitize_cache_fingerprint(item)
            return out
        if isinstance(value, list):
            return [cls._sanitize_cache_fingerprint(item) for item in value]
        if isinstance(value, str) and value.startswith("data:"):
            digest = hashlib.sha256(value.encode("utf-8")).hexdigest()
            return f"data-uri:{len(value)}:{digest}"
        return value

    @classmethod
    def _sanitize_large_payload(cls, value: Any) -> Any:
        if isinstance(value, dict):
            out: Dict[str, Any] = {}
            for key, item in value.items():
                key_text = str(key)
                if key_text.lower() in cls.LARGE_RESULT_KEYS and isinstance(item, str):
                    out[key_text] = cls._redact_large_string(item, key_text)
                    continue
                out[key_text] = cls._sanitize_large_payload(item)
            return out
        if isinstance(value, list):
            return [cls._sanitize_large_payload(item) for item in value]
        if isinstance(value, str) and value.startswith("data:") and "[omitted " not in value:
            return cls._redact_data_uri(value)
        return value

    @classmethod
    def _safe_source_url(cls, value: str) -> str:
        text = str(value or "").strip()
        if text.startswith("data:"):
            return cls._redact_data_uri(text)
        return text

    @staticmethod
    def _redact_data_uri(value: str) -> str:
        text = str(value or "")
        header = text.split(",", 1)[0] if "," in text else "data:"
        return f"{header},[omitted {len(text)} chars]"

    @staticmethod
    def _redact_large_string(value: str, label: str) -> str:
        return f"[omitted {label} {len(str(value or ''))} chars]"

    def _extract_media_map(
        self,
        input_data: Optional[Dict[str, Any]],
        option: Optional[Dict[str, Any]] = None,
        prepared_file: Any = None,
        extract_types: Optional[List[str]] = None,
    ) -> Dict[str, List[str]]:
        media_fields = tuple(
            field for field in (extract_types or list(MEDIA_FIELDS)) if field in MEDIA_FIELDS
        ) or MEDIA_FIELDS
        result = {field: [] for field in media_fields}
        option_map = option if isinstance(option, dict) else {}
        input_map = input_data if isinstance(input_data, dict) else {}

        self._merge_media_map(result, self._extract_media_map_from_any(prepared_file, media_fields))
        self._merge_media_map(result, self._extract_media_map_from_any(option_map.get("file"), media_fields))
        self._merge_media_map(result, self._extract_media_map_from_any(input_map.get("file"), media_fields))

        for field in media_fields:
            result[field].extend(self._normalize_media_value(option_map.get(field)))
            result[field].extend(self._normalize_media_value(input_map.get(field)))
            result[field] = self._uniq_strings(result[field])
        return result

    def _extract_media_map_from_any(
        self,
        raw: Any,
        media_fields: Optional[tuple[str, ...]] = None,
    ) -> Dict[str, List[str]]:
        fields = media_fields or MEDIA_FIELDS
        result = {field: [] for field in fields}
        if raw is None:
            return result

        prepared = Prompt.get_input({"file": raw}, extract_types=list(fields))
        file_map = prepared.get("file")
        if isinstance(file_map, dict):
            for field in fields:
                result[field].extend(self._normalize_media_value(file_map.get(field)))
            self._merge_inferred_media_entries(result, self._normalize_media_value(file_map.get("file")), fields)

        if isinstance(raw, dict):
            for field in fields:
                if field in raw:
                    result[field].extend(self._normalize_media_value(raw.get(field)))
            self._merge_inferred_media_entries(result, self._normalize_media_value(raw.get("file")), fields)
        else:
            self._merge_inferred_media_entries(result, self._normalize_media_value(raw), fields)

        for field in fields:
            result[field] = self._uniq_strings(result[field])
        return result

    @staticmethod
    def _merge_media_map(target: Dict[str, List[str]], source: Dict[str, List[str]]) -> None:
        if not isinstance(target, dict) or not isinstance(source, dict):
            return
        for key, values in source.items():
            if key not in target:
                target[key] = []
            target[key].extend(str(item).strip() for item in values if str(item).strip())
            target[key] = Base._uniq_strings(target[key])

    @staticmethod
    def _normalize_media_value(raw: Any) -> List[str]:
        if raw is None:
            return []
        if isinstance(raw, str):
            value = raw.strip()
            return [value] if value else []
        if isinstance(raw, dict):
            for key in ("url", "src", "value"):
                value = str(raw.get(key, "")).strip()
                if value:
                    return [value]
            out: List[str] = []
            for value in raw.values():
                out.extend(Base._normalize_media_value(value))
            return out
        if isinstance(raw, (list, tuple, set)):
            out: List[str] = []
            for item in raw:
                out.extend(Base._normalize_media_value(item))
            return out
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

    @classmethod
    def _merge_inferred_media_entries(
        cls,
        target: Dict[str, List[str]],
        values: List[str],
        fields: tuple[str, ...],
    ) -> None:
        for value in values:
            media_type = cls._infer_media_type(value)
            if media_type and media_type in fields:
                target.setdefault(media_type, []).append(value)

    @staticmethod
    def _infer_media_type(value: Any) -> str:
        text = str(value or "").strip().lower()
        if not text:
            return ""
        if text.startswith("data:image/"):
            return "image"
        if text.startswith("data:video/"):
            return "video"
        if text.startswith("data:audio/"):
            return "audio"

        path = urlparse(text).path or ""
        lower = path.lower()
        dot = lower.rfind(".")
        ext = lower[dot:] if dot >= 0 else ""
        if ext in IMAGE_EXT:
            return "image"
        if ext in VIDEO_EXT:
            return "video"
        if ext in AUDIO_EXT:
            return "audio"
        return ""

    @staticmethod
    def _is_empty_field_value(value: Any) -> bool:
        if value is None:
            return True
        if isinstance(value, str):
            return not value.strip()
        if isinstance(value, (list, dict, tuple, set)):
            return len(value) == 0
        return False
