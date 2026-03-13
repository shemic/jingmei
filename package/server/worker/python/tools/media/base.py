from __future__ import annotations
from typing import Any, Callable, Dict, List, Optional, Set
import time
import requests
from dever.error import WorkerError

class Base(object):
    config = {}

    def __init__(self, config: Optional[Dict[str, Any]] = None):
        self.config: Dict[str, Any] = config or {}
        platform_config = self.config.get("platform", {})
        model_config = self.config.get("model", {})
        self.model = model_config["model"]
        self.host = str(platform_config["host"]).rstrip("/")
        self.api_key = str(platform_config["api_key"])
        if not self.host:
            raise WorkerError("配置缺少 host")
        if not self.api_key:
            raise WorkerError("配置缺少 api_key")
        self.header = {
            "Content-Type": "application/json",
            "Authorization": f"Bearer {self.api_key}",
        }