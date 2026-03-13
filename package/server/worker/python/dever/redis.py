from __future__ import annotations
import time
from typing import Any, Dict, Optional
from dever.error import WorkerError
from dever.core import Dever

class Redis:
    _client = None
    _prefix = ""
    _last_error: Optional[Exception] = None
    _last_fail_ts: float = 0.0
    _retry_after_sec: float = 2.0   # 失败后 2s 内不重连（可配置）

    @classmethod
    def get(cls, *, fail_fast: bool = False):
        """
        返回进程级 redis client（单例）
        - fail_fast=True: 连不上就抛异常（适合 SSE 网关）
        - fail_fast=False: 连不上返回 None（适合非关键服务）
        """
        if cls._client is not None:
            return cls._client

        # 失败冷却：避免每次调用都重连
        """
        now = time.time()
        if cls._last_fail_ts and (now - cls._last_fail_ts) < cls._retry_after_sec:
            return None if not fail_fast else cls._raise_last()
        """

        try:
            import redis
        except Exception as e:
            raise WorkerError("缺少依赖 redis，请安装：pip install redis") from e

        cfg: Dict[str, Any] = Dever.readConfig("redis") or {}
        if not cfg or not cfg.get("enable"):
            return None

        addr = (cfg.get("addr") or "").strip()
        if ":" not in addr:
            raise WorkerError("redis.addr 配置错误，应为 host:port")

        host, port = addr.split(":", 1)
        cls._prefix = str(cfg.get("prefix") or "").strip()

        # 可选：从配置读重试冷却时间
        cls._retry_after_sec = cls._parse_time(cfg.get("retryAfter"), 2.0)

        try:
            client = redis.Redis(
                host=host,
                port=int(port),
                username=(cfg.get("username") or None),
                password=(cfg.get("password") or None),
                db=int(cfg.get("db") or 0),
                ssl=bool(cfg.get("useTLS", False)),
                socket_connect_timeout=cls._parse_time(cfg.get("dialTimeout"), 2.0),
                socket_timeout=cls._parse_time(cfg.get("readTimeout"), 1.0),
                max_connections=int(cfg.get("poolSize") or 10),
                decode_responses=True,
            )

            client.ping()  # 探测

            cls._client = client
            cls._last_error = None
            cls._last_fail_ts = 0.0
            return cls._client

        except Exception as e:
            # 连接失败：记录错误 + 进入冷却，不缓存坏 client
            cls._client = None
            cls._last_error = e
            cls._last_fail_ts = time.time()

            if fail_fast:
                raise WorkerError(f"Redis 连接失败: {e}") from e
            return None

    @classmethod
    def last_error(cls) -> Optional[Exception]:
        return cls._last_error

    @classmethod
    def _raise_last(cls):
        if cls._last_error:
            raise WorkerError(f"Redis 不可用: {cls._last_error}") from cls._last_error
        raise WorkerError("Redis 不可用")

    @classmethod
    def prefix(cls) -> str:
        return cls._prefix

    @classmethod
    def key(cls, name: str) -> str:
        """
        拼接带 prefix 的 key / channel
        """
        cls._ensure_prefix()
        name = (name or "").strip()
        if not name:
            return ""
        return f"{cls._prefix}:{name}" if cls._prefix else name

    @classmethod
    def _ensure_prefix(cls) -> None:
        if cls._prefix:
            return
        cfg: Dict[str, Any] = Dever.readConfig("redis") or {}
        cls._prefix = str(cfg.get("prefix") or "").strip()

    @staticmethod
    def _parse_time(v: Any, default: float) -> float:
        """
        支持：
        - "2s" -> 2.0
        - "500ms" -> 0.5
        - 数字 -> float
        """
        if v is None:
            return default
        if isinstance(v, (int, float)):
            return float(v)
        s = str(v).strip().lower()
        if s.endswith("ms"):
            return float(s[:-2]) / 1000
        if s.endswith("s"):
            return float(s[:-1])
        return default
