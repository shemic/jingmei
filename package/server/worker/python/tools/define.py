from __future__ import annotations

from typing import Any, Dict, List, Optional
from pydantic import BaseModel, Field

class Request(BaseModel):
    tool_code:  Optional[str] = None
    project_code: Optional[str] = None
    app_code: Optional[str] = None
    workflow_code: Optional[str] = None
    content_code: Optional[str] = None
    content_version_id: Optional[int] = None
    input: Any = None
    meta: Dict[str, Any] = Field(default_factory=dict)

class Response(BaseModel):
    output: Any
    aigc: Any = None
