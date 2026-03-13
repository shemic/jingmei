from __future__ import annotations

import json
import time
import os
from typing import Any, Dict, Optional

from dever.redis import Redis


class Publisher:
    """
    通用发布器：
    - 统一事件编码
    - emit(): 给 HTTP 流用
    - publish(): 给消息总线用（默认 Noop）
    """

    @staticmethod
    def encode(evt: Dict[str, Any], *, as_sse: bool = False) -> str:
        payload = json.dumps(evt, ensure_ascii=False)
        if as_sse:
            return f"data: {payload}\n\n"
        # 平台统一流协议：JSON + 空行
        return payload + "\n\n"

    @staticmethod
    def now_ms() -> int:
        return int(time.time() * 1000)

    def publish(self, evt: Dict[str, Any], *, meta: Optional[Dict[str, Any]] = None) -> None:
        return

    def emit(self, evt: Dict[str, Any], *, meta: Optional[Dict[str, Any]] = None) -> bytes:
        """
        统一出口：
        - 发布（失败不影响主链路）
        - 返回 bytes（HTTP Streaming / SSE）
        """
        try:
            self.publish(evt, meta=meta)
        except Exception:
            # 发布失败不影响主链路
            pass

        return self.encode(evt).encode("utf-8")


class RedisPublisher(Publisher):
    """
    Redis Pub/Sub 发布器
    - 使用 dever.redis.Redis 单例
    - HTTP 输出与 Redis 消息解耦
    """

    def __init__(self, *, channel: str, maxlen: int = 20000):
        self.channel = channel
        self.maxlen = maxlen  # 防止 stream 无限增长

    def publish(self, evt: Dict[str, Any], *, meta: Optional[Dict[str, Any]] = None) -> None:
        
        r = Redis.get()
        if r is None:
            return

        stream_key = Redis.key(self.channel)

        evt_bus = dict(evt)
        sse_line = self.encode(evt_bus, as_sse=True)

        fields = {
            "data": sse_line,
            "type": evt.get("type", ""),
            "ts": str(self.now_ms()),
        }
        # XADD（MAXLEN ~ 防爆）
        r.xadd(stream_key, fields, maxlen=self.maxlen, approximate=True)
            
