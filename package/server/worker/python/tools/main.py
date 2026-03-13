from __future__ import annotations

from typing import Any, Dict, List
from urllib.parse import urlparse

from dever.core import Dever
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
        model = tool["model"].split(",")
        table = Db.table("work_platform")
        platform = Db.find(f"SELECT * FROM {table} WHERE id = %s", [model[0]])
        table = Db.table("work_model")
        model = Db.find(f"SELECT * FROM {table} WHERE id = %s", [model[1]])
        
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
