from __future__ import annotations

from typing import Any, Dict, List
from urllib.parse import urlparse

from dever.core import Dever
from dever.error import WorkerError
from dever.pgsql import PgSQL as Db
from tools.define import Request, Response


TYPE_ORDER: List[str] = [
    "image",
    "video",
    "audio",
    "pdf",
    "word",
    "excel",
    "ppt",
    "txt",
    "md",
    "file",
]

TYPE_LABELS: Dict[str, str] = {
    "image": "图片",
    "video": "视频",
    "audio": "音频",
    "pdf": "PDF",
    "word": "Word",
    "excel": "Excel",
    "ppt": "PPT",
    "txt": "文本",
    "md": "Markdown",
    "file": "文件",
}

IMAGE_EXT = {".jpg", ".jpeg", ".png", ".gif", ".bmp", ".webp", ".tiff", ".tif", ".svg", ".heic", ".heif"}
VIDEO_EXT = {".mp4", ".mov", ".avi", ".mkv", ".wmv", ".flv", ".webm", ".m4v", ".3gp", ".mpeg", ".mpg"}
AUDIO_EXT = {".mp3", ".wav", ".flac", ".aac", ".ogg", ".m4a", ".wma", ".amr"}
WORD_EXT = {".doc", ".docx"}
EXCEL_EXT = {".xls", ".xlsx", ".csv"}
PPT_EXT = {".ppt", ".pptx"}


class Tools:
    def __init__(self, request: Dict[str, Any]):
        self.request = self._request(request)

    @staticmethod
    def _request(raw: Dict[str, Any]) -> Request:
        data: Dict[str, Any] = {
            "tool_code": raw.get("tool_code"),
            "project_code": raw.get("project_code"),
            "app_code": raw.get("app_code"),
            "workflow_code": raw.get("workflow_code"),
            "content_code": raw.get("content_code"),
            "content_version_id": raw.get("content_version_id"),
            "input": raw.get("input"),
            "meta": raw.get("meta") if isinstance(raw.get("meta"), dict) else {},
        }
        return Request(**data)

    def execute(self) -> Response:
        table = Db.table("work_tool")
        tool = Db.find(f"SELECT * FROM {table} WHERE code = %s", [self.request.tool_code])
        if not isinstance(tool, dict):
            raise WorkerError("工具不存在")
        platform, model = self._resolve_model_and_platform(tool)

        config = {
            "project_code": self.request.project_code,
            "app_code": self.request.app_code,
            "workflow_code": self.request.workflow_code,
            "content_code": self.request.content_code,
            "content_version_id": self.request.content_version_id,
            "platform": platform,
            "model": model,
        }
        result = Dever.load(self.request.tool_code, config=config, cache=None).handle(
            input=self.request.input,
            meta=self.request.meta,
        )
        raw_aigc = result.get("aigc")
        urls = self._collect_urls(raw_aigc)
        file_map = self._group_by_type(urls)
        shemic_input = self._build_shemic_input(file_map)
        return Response(
            output=result,
            aigc={
                "result": raw_aigc,
                "input": shemic_input,
            },
        )

    @staticmethod
    def _to_int(value: Any) -> int:
        try:
            return int(value)
        except Exception:
            return 0

    @classmethod
    def _parse_model_ref_id(cls, value: Any) -> int:
        parsed = cls._to_int(value)
        if parsed > 0:
            return parsed
        text = str(value or "").strip()
        if not text:
            return 0
        for part in reversed(text.split(",")):
            parsed = cls._to_int(part.strip())
            if parsed > 0:
                return parsed
        return 0

    def _resolve_model_and_platform(self, tool: Dict[str, Any]) -> tuple[Dict[str, Any], Dict[str, Any]]:
        model_id = self._extract_selected_model_id(self.request.input, self.request.meta)
        if model_id <= 0:
            model_id = self._extract_default_model_id(self.request.meta)
        if model_id <= 0:
            model_id = self._load_default_tool_model_id(tool)
        if model_id <= 0:
            model_id = self._parse_model_ref_id(tool.get("model"))
        if model_id <= 0:
            raise WorkerError("工具未配置模型")

        model_table = Db.table("work_model")
        model = Db.find(f"SELECT * FROM {model_table} WHERE id = %s", [model_id])
        if not isinstance(model, dict):
            raise WorkerError("模型不存在")
        model["params"] = self._load_model_params(model_id)

        platform_id = self._to_int(model.get("platform_id"))
        if platform_id <= 0:
            raise WorkerError("模型缺少平台配置")

        platform_table = Db.table("work_platform")
        platform = Db.find(f"SELECT * FROM {platform_table} WHERE id = %s", [platform_id])
        if not isinstance(platform, dict):
            raise WorkerError("平台不存在")
        return platform, model

    def _load_default_tool_model_id(self, tool: Dict[str, Any]) -> int:
        tool_id = self._to_int(tool.get("id"))
        if tool_id <= 0:
            return 0
        table = Db.table("work_tool_model")
        row = Db.find(
            f"SELECT * FROM {table} WHERE tool_id = %s AND status = 1 ORDER BY sort ASC, id ASC LIMIT 1",
            [tool_id],
        )
        if not isinstance(row, dict):
            return 0
        return self._parse_model_ref_id(row.get("model"))

    def _load_model_params(self, model_id: int) -> List[Dict[str, Any]]:
        if model_id <= 0:
            return []
        table = Db.table("work_model_param")
        return Db.fetch(
            f"SELECT * FROM {table} WHERE model_id = %s AND status = 1 ORDER BY id DESC",
            [model_id],
        )

    def _extract_selected_model_id(self, input_payload: Any, meta: Any) -> int:
        if not isinstance(input_payload, dict):
            return 0
        selections = self._normalize_model_selections(input_payload.get("model"))
        if not selections:
            return 0

        meta_map = meta if isinstance(meta, dict) else {}
        lookup_keys = [
            str(meta_map.get("workflow_node_id") or "").strip(),
            str(meta_map.get("node_id") or "").strip(),
        ]
        for key in lookup_keys:
            if not key:
                continue
            selected = selections.get(key)
            if not isinstance(selected, dict):
                continue
            model_id = self._to_int(selected.get("value"))
            if model_id > 0:
                return model_id
        return 0

    @staticmethod
    def _normalize_model_selections(raw: Any) -> Dict[str, Dict[str, Any]]:
        if raw is None:
            return {}
        if isinstance(raw, list):
            items = raw
        elif isinstance(raw, dict):
            items = [raw]
        else:
            return {}

        out: Dict[str, Dict[str, Any]] = {}
        for item in items:
            if not isinstance(item, dict):
                continue
            item_id = str(item.get("id") or "").strip()
            if item_id:
                out[item_id] = item
            for key, value in item.items():
                node_id = str(key or "").strip()
                if not node_id or not isinstance(value, dict):
                    continue
                out[node_id] = value
        return out

    def _extract_default_model_id(self, meta: Any) -> int:
        if not isinstance(meta, dict):
            return 0
        return self._to_int(meta.get("default_model_id"))

    @staticmethod
    def _collect_urls(raw: Any) -> List[str]:
        out: List[str] = []

        def add(value: str) -> None:
            v = value.strip()
            if not v:
                return
            if v in out:
                return
            out.append(v)

        def walk(value: Any) -> None:
            if value is None:
                return
            if isinstance(value, str):
                for part in value.replace("，", ",").split(","):
                    add(part)
                return
            if isinstance(value, list):
                for item in value:
                    walk(item)
                return
            if isinstance(value, dict):
                for key in ("result", "aigc", "url", "urls"):
                    if key in value:
                        walk(value.get(key))
                uploaded = value.get("uploaded")
                if isinstance(uploaded, list):
                    for item in uploaded:
                        if isinstance(item, dict):
                            walk(item.get("url"))
                return

        walk(raw)
        return out

    @staticmethod
    def _group_by_type(urls: List[str]) -> Dict[str, List[str]]:
        grouped: Dict[str, List[str]] = {}
        for url in urls:
            t = Tools._infer_type(url)
            grouped.setdefault(t, []).append(url)
        return grouped

    @staticmethod
    def _infer_type(url: str) -> str:
        path = urlparse(url).path or ""
        lower = path.lower()
        dot = lower.rfind(".")
        ext = lower[dot:] if dot >= 0 else ""
        if ext in IMAGE_EXT:
            return "image"
        if ext in VIDEO_EXT:
            return "video"
        if ext in AUDIO_EXT:
            return "audio"
        if ext == ".pdf":
            return "pdf"
        if ext in WORD_EXT:
            return "word"
        if ext in EXCEL_EXT:
            return "excel"
        if ext in PPT_EXT:
            return "ppt"
        if ext == ".txt":
            return "txt"
        if ext in {".md", ".markdown"}:
            return "md"
        return "file"

    @staticmethod
    def _build_shemic_input(file_map: Dict[str, List[str]]) -> str:
        parts: List[str] = []
        for t in TYPE_ORDER:
            urls = file_map.get(t, [])
            if not urls:
                continue
            tag = TYPE_LABELS.get(t, "文件")
            content = ",".join(urls)
            parts.append(
                f'<shemic-file data-marker="#" data-type="{t}" data-tag="{tag}">{content}</shemic-file>'
            )
        return "\n".join(parts)
