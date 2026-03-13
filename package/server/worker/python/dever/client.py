from __future__ import annotations

import atexit
import hashlib
import json
import threading
from typing import Any, Dict, Optional

import httpx

class Client:
    """
    最终版：进程级 httpx.Client 管理器（支持“多套 client 按配置缓存”）
    - 不同 url/baseUrl 没关系：连接池按 (scheme, host, port) 分桶
    - 按 proxy/verify/http2/timeout/limits/headers/transport 等差异缓存多套 client
    - key 默认用稳定哈希（避免把 proxy 等敏感字符串直接拼出来）
    """

    _lock = threading.Lock()
    _clients: Dict[str, httpx.Client] = {}
    _configs: Dict[str, Dict[str, Any]] = {}

    @staticmethod
    def _freeze(obj: Any) -> Any:
        """把配置递归转成可稳定序列化的结构（dict key 排序，limits 规整）"""
        if isinstance(obj, httpx.Limits):
            return {
                "max_connections": obj.max_connections,
                "max_keepalive_connections": obj.max_keepalive_connections,
                "keepalive_expiry": obj.keepalive_expiry,
            }
        if isinstance(obj, dict):
            return {str(k): Client._freeze(obj[k]) for k in sorted(obj.keys(), key=lambda x: str(x))}
        if isinstance(obj, (list, tuple)):
            return [Client._freeze(x) for x in obj]
        return obj

    @classmethod
    def _hash_key(cls, conf: Dict[str, Any]) -> str:
        payload = json.dumps(cls._freeze(conf), ensure_ascii=False, sort_keys=True, separators=(",", ":"))
        return hashlib.sha256(payload.encode("utf-8")).hexdigest()

    @classmethod
    def get(
        cls,
        *,
        name: str = "default",
        proxy: Any = None,
        verify: Any = True,
        http2: bool = True,
        timeout: float = 60.0,
        limits: Optional[httpx.Limits] = None,
        headers: Optional[Dict[str, str]] = None,
        transport: Optional[httpx.BaseTransport] = None,
        key: Optional[str] = None,
    ) -> httpx.Client:
        """
        获取（或创建）一个 httpx.Client。
        - 不传 key：自动用参数生成稳定哈希 key
        - 传 key：你自己控制 client 分组（比如 name="mtls-openai"）
        """
        if limits is None:
            limits = httpx.Limits(max_connections=200, max_keepalive_connections=50)

        base_headers = {"User-Agent": "Dever/1.0"}
        if headers:
            base_headers.update(headers)

        conf: Dict[str, Any] = {
            "name": name,
            "proxy": proxy,
            "verify": verify,
            "http2": http2,
            "timeout": timeout,
            "limits": limits,
            "headers": base_headers,
            # transport 不可序列化，用 id 参与 key；需要稳定请自己传 key 并复用 transport 实例
            "transport_id": id(transport) if transport is not None else None,
        }

        if key is None:
            key = cls._hash_key(conf)

        client = cls._clients.get(key)
        if client is not None:
            return client

        with cls._lock:
            client = cls._clients.get(key)
            if client is not None:
                return client

            client = httpx.Client(
                proxy=proxy,
                verify=verify,
                timeout=timeout,
                http2=http2,
                limits=limits,
                headers=base_headers,
                transport=transport,
            )
            cls._clients[key] = client
            cls._configs[key] = conf
            return client

    @classmethod
    def close(cls, key: Optional[str] = None) -> None:
        """key=None 关闭全部；key=... 关闭指定"""
        with cls._lock:
            if key is None:
                for k, c in list(cls._clients.items()):
                    try:
                        c.close()
                    finally:
                        cls._clients.pop(k, None)
                        cls._configs.pop(k, None)
                return

            c = cls._clients.pop(key, None)
            cls._configs.pop(key, None)
            if c is not None:
                c.close()

    @classmethod
    def debug_configs(cls) -> Dict[str, Dict[str, Any]]:
        """调试用：查看当前缓存了哪些 client（key -> config）"""
        with cls._lock:
            return dict(cls._configs)


# 进程退出自动 close（兜底）
atexit.register(lambda: Client.close())
