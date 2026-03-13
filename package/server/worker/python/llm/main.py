from __future__ import annotations

from typing import Any, Dict

from dever.core import Dever
from dever.pgsql import PgSQL as Db
from llm.define import Request, Response

class LLM:
    def __init__(self, request: Dict[str, Any]):
        self.request = self._request(request)

    @staticmethod
    def _request(raw: Dict[str, Any]) -> Request:
        data: Dict[str, Any] = {
            "model_code": raw.get("model_code"),
            "project_code": raw.get("project_code"),
            "app_code": raw.get("app_code"),
            "workflow_code": raw.get("workflow_code"),
            "content_code": raw.get("content_code"),
            "content_version_id": raw.get("content_version_id"),
            "input": raw.get("input"),
            "meta": raw.get("meta") if isinstance(raw.get("meta"), dict) else {},
            "stream": raw.get("stream", True) if isinstance(raw.get("stream"), bool) else True,
        }
        return Request(**data)

    def execute(self) -> Response:
        table = Db.table("work_model")
        model = Db.find(f"SELECT * FROM {table} WHERE code = %s", [self.request.model_code])
        table = Db.table("work_platform")
        platform = Db.find(f"SELECT * FROM {table} WHERE id = %s", [model["platform_id"]])
        config: dict[str, Any] = {
            "host": platform["host"],
            "model": model["model"],
            "api_key": platform["api_key"],
            "project_code": self.request.project_code,
            "app_code": self.request.app_code,
            "workflow_code": self.request.workflow_code,
            "content_code": self.request.content_code,
            "content_version_id": self.request.content_version_id,
        }
        provider = Dever.load("core." + model["protocol"], config=config, cache=None, base="llm")
        if hasattr(provider, "bind_stream"):
            provider.bind_stream(self.request.project_code, self.request.content_code, self.request.content_version_id)
        result = provider.request(input=self.request.input, meta=self.request.meta, stream=self.request.stream)
        return Response(output=result)
