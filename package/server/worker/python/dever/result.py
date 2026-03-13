from __future__ import annotations

import time
import uuid
from dataclasses import dataclass, field
from typing import Any, Dict, Optional


# -----------------------------
# primitives
# -----------------------------

def now_ms() -> int:
    return int(time.time() * 1000)


def new_run_id(prefix: str = "llm") -> str:
    return f"{prefix}_{uuid.uuid4().hex}"


def normalize_usage(usage: Optional[Dict[str, Any]]) -> Optional[Dict[str, Optional[int]]]:
    """
    统一 token usage 格式：
    - OpenAI: prompt_tokens / completion_tokens / total_tokens
    - Gemini/部分厂商: input_tokens / output_tokens
    返回：
      {"prompt_tokens": int|None, "completion_tokens": int|None, "total_tokens": int|None}
    """
    if not usage or not isinstance(usage, dict):
        return None

    pt = usage.get("prompt_tokens")
    ct = usage.get("completion_tokens")

    if pt is None:
        pt = usage.get("input_tokens")
    if ct is None:
        ct = usage.get("output_tokens")

    tt = usage.get("total_tokens")
    if tt is None and pt is not None and ct is not None:
        try:
            tt = int(pt) + int(ct)
        except Exception:
            tt = None

    def _to_int(x) -> Optional[int]:
        if x is None:
            return None
        try:
            return int(x)
        except Exception:
            return None

    return {
        "prompt_tokens": _to_int(pt),
        "completion_tokens": _to_int(ct),
        "total_tokens": _to_int(tt),
    }


# -----------------------------
# context (pure data)
# -----------------------------

@dataclass(frozen=True)
class ResultContext:
    """
    纯数据：一次 LLM 调用/一次 agent 运行的上下文
    """
    run_id: str
    model: str
    start_ms: int
    meta: Dict[str, Any] = field(default_factory=dict)

    @staticmethod
    def create(model: str, *, prefix: str = "llm", meta: Optional[Dict[str, Any]] = None) -> "ResultContext":
        return ResultContext(
            run_id=new_run_id(prefix),
            model=model,
            start_ms=now_ms(),
            meta=meta or {},
        )


# -----------------------------
# factory (behavior)
# -----------------------------

class ResultFactory:
    """
    负责把 ctx + 输入数据 -> 标准事件/标准返回 dict
    事件协议（推荐）：
      delta: {"id","type":"delta","content","model","ts_ms","meta"?}
      final: {"id","type":"final","content","usage","model","latency_ms","ts_ms","meta"?,"raw"?}
      error: {"id","type":"error","message","code","retryable","model","latency_ms","ts_ms","meta"?,"raw"?}
    """

    def __init__(self, ctx: ResultContext):
        self.ctx = ctx

    def latency_ms(self) -> int:
        return now_ms() - self.ctx.start_ms

    # ---- events ----
    def delta(self, content: str, **extra: Any) -> Dict[str, Any]:
        evt: Dict[str, Any] = {
            "id": self.ctx.run_id,
            "type": "delta",
            "content": content,
            "model": self.ctx.model,
            "ts_ms": now_ms(),
        }
        if self.ctx.meta:
            evt["meta"] = self.ctx.meta
        if extra:
            evt.update(extra)
        return evt

    def final(
        self,
        content: str,
        *,
        usage: Optional[Dict[str, Any]] = None,
        raw: Any = None,
        **extra: Any,
    ) -> Dict[str, Any]:
        evt: Dict[str, Any] = {
            "id": self.ctx.run_id,
            "type": "final",
            "content": content,
            "usage": normalize_usage(usage),
            "model": self.ctx.model,
            "latency_ms": self.latency_ms(),
            "ts_ms": now_ms(),
        }
        if self.ctx.meta:
            evt["meta"] = self.ctx.meta
        if raw is not None:
            evt["raw"] = raw
        if extra:
            evt.update(extra)
        return evt

    def error(
        self,
        message: str,
        *,
        code: Optional[str] = None,
        raw: Any = None,
        retryable: Optional[bool] = None,
        **extra: Any,
    ) -> Dict[str, Any]:
        evt: Dict[str, Any] = {
            "id": self.ctx.run_id,
            "type": "error",
            "message": message,
            "code": code,
            "retryable": retryable,
            "model": self.ctx.model,
            "latency_ms": self.latency_ms(),
            "ts_ms": now_ms(),
        }
        if self.ctx.meta:
            evt["meta"] = self.ctx.meta
        if raw is not None:
            evt["raw"] = raw
        if extra:
            evt.update(extra)
        return evt
