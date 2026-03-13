from __future__ import annotations

import contextvars
import hashlib
import time
from typing import Any, Dict, List, Optional, Tuple

try:
    from temporalio import activity
    from temporalio.exceptions import CancelledError
except Exception:  # pragma: no cover
    activity = None
    CancelledError = None

from dever.error import WorkerError
from dever.publisher import Publisher, RedisPublisher
from dever.result import ResultContext, ResultFactory
from dever.task import TaskReporter

_task_ctx_var: contextvars.ContextVar[Optional[Dict[str, Any]]] = contextvars.ContextVar(
    "provider_task_ctx",
    default=None,
)


class Provider(object):
    """
    Provider 基类：平台级统一能力
    - PROVIDER_NAME：用于 ResultContext.prefix
    - REQUIRED_KEYS：配置必填项
    - config 读取/校验
    - publisher 初始化 + emit
    - messages 规范化
    - usage 规范化（OpenAI 风格三字段）
    - request() 统一入口：自动组装 ResultFactory/ctx，支持 stream
    """

    PROVIDER_NAME = "llm"
    REQUIRED_KEYS: Tuple[str, ...] = ("api_key", "model", "host")  # 默认适配 OpenAI/兼容网关

    def __init__(self, config: Optional[Dict[str, Any]] = None):
        self.config: Dict[str, Any] = config or {}

        self.publisher: Optional[Publisher] = None
        #self.publish_meta: Optional[Dict[str, Any]] = None
        self._validate_required()
        self._init_publisher()

        # 子类需要在 init() 里设置 self.llm
        self.llm = None
        self.model_name = self.get_model()

        self.init()

    # ---------------- hook: 子类实现 ----------------

    def init(self) -> None:
        """
        子类必须实现：
        - 初始化 self.llm
        """
        raise NotImplementedError

    # ---------------- config helpers ----------------

    def _get_str(self, key: str, default: str = "") -> str:
        v = self.config.get(key, default)
        if v is None:
            return ""
        return str(v).strip()

    def _validate_required(self) -> None:
        for k in self.REQUIRED_KEYS:
            if not self._get_str(k):
                raise WorkerError(f"{self.PROVIDER_NAME}.{k} 未配置", retryable=False)

    def get_api_key(self) -> str:
        return self._get_str("api_key")

    def get_model(self) -> str:
        return self._get_str("model")

    def get_base_url(self) -> str:
        # 统一去掉末尾 /
        return self._get_str("host").rstrip("/")

    def get_timeout(self, default: float = 60.0) -> float:
        try:
            return float(self.config.get("timeout", default))
        except Exception:
            return default

    def get_max_retries(self, default: int = 1) -> int:
        try:
            return int(self.config.get("retry_max", default))
        except Exception:
            return default

    def get_policy(self) -> Dict[str, Any]:
        default: Dict[str, Any] = {}
        try:
            return self.config.get("policy", default)
        except Exception:
            return default

    # ---------------- publisher ----------------

    def _init_publisher(self) -> None:
        self.bind_stream(
            project_code=self.config.get("project_code"),
            content_code=self.config.get("content_code"),
            content_version_id=self.config.get("content_version_id"),
        )

    def bind_stream(self, project_code: Any, content_code: Any, content_version_id: Any = None) -> None:
        project = str(project_code or "").strip()
        content = str(content_code or "").strip()
        if not project or not content:
            self.publisher = None
            return
        version_id = 0
        try:
            version_id = int(content_version_id or 0)
        except Exception:
            version_id = 0
        self.config["project_code"] = project
        self.config["content_code"] = content
        self.config["content_version_id"] = version_id
        stream_name = f"shemic:{project}:{content}:{version_id}" if version_id > 0 else f"shemic:{project}:{content}"
        self.publisher = RedisPublisher(channel=stream_name)

    def emit(self, evt: Dict[str, Any]) -> None:
        task_ctx = _task_ctx_var.get()
        should_finish = self._task_should_finish_after_emit(evt, task_ctx)
        wrapped = self._wrap_node_stream_event(evt)
        if not self.publisher:
            if should_finish:
                self._task_finish(task_ctx, status="finish")
            return
        try:
            self.publisher.emit(wrapped)
        except Exception:
            # 旁路推送失败不影响主流程
            pass
        if should_finish:
            self._task_finish(task_ctx, status="finish")

    def _wrap_node_stream_event(self, evt: Dict[str, Any]) -> Dict[str, Any]:
        meta = evt.get("meta")
        if not isinstance(meta, dict):
            return evt
        node_type = str(meta.get("node_type") or "").strip().lower()
        if node_type != "agent":
            return evt
        node_id = str(meta.get("node_id") or "").strip()
        node_name = str(meta.get("name") or "").strip()
        code = str(meta.get("code") or "").strip()
        if not code:
            code = self._stream_code()
        return {
            "id": node_id,
            "name": node_name,
            "code": code,
            "type": "agent",
            "data": evt,
        }

    def _stream_code(self) -> str:
        project = str(self.config.get("project_code") or "").strip()
        content = str(self.config.get("content_code") or "").strip()
        version = 0
        try:
            version = int(self.config.get("content_version_id") or 0)
        except Exception:
            version = 0
        if not project or not content or version <= 0:
            return ""
        stream_name = f"shemic:{project}:{content}:{version}"
        return hashlib.md5(stream_name.encode("utf-8")).hexdigest()

    # ---------------- message normalize ----------------

    def messages(self, msg: Any) -> List[Dict[str, Any]]:
        """
        平台统一输入：
        - list[{"role","content"}]
        - dict{"role","content"}
        - other -> {"role":"user","content":str(x)}
        """
        if isinstance(msg, list):
            out: List[Dict[str, Any]] = []
            for m in msg:
                if not isinstance(m, dict):
                    out.append({"role": "user", "content": str(m)})
                    continue

                role = str(m.get("role") or "user").strip().lower()
                content = m.get("content")
                content = "" if content is None else content

                out.append({"role": role, "content": content})
            return out

        if isinstance(msg, dict):
            role = str(msg.get("role") or "user").strip().lower()
            content = msg.get("content")
            content = "" if content is None else content
            return [{"role": role, "content": content}]
        return [{"role": "user", "content": str(msg)}]

    # ---------------- usage normalize ----------------

    @staticmethod
    def usage(msg: Any) -> Optional[Dict[str, Any]]:
        """
        统一输出 OpenAI 风格 usage：
        {prompt_tokens, completion_tokens, total_tokens}

        兼容：
        - msg.usage_metadata: {input_tokens, output_tokens, total_tokens}
        - msg.response_metadata["token_usage"|"usage"]
        """
        um = getattr(msg, "usage_metadata", None)
        if isinstance(um, dict):
            pt = um.get("input_tokens")
            ct = um.get("output_tokens")
            tt = um.get("total_tokens")
            if tt is None and pt is not None and ct is not None:
                tt = pt + ct
            return {"prompt_tokens": pt, "completion_tokens": ct, "total_tokens": tt}

        meta = getattr(msg, "response_metadata", None)
        if isinstance(meta, dict):
            u = meta.get("token_usage") or meta.get("usage")
            if isinstance(u, dict):
                pt = u.get("prompt_tokens") or u.get("input_tokens")
                ct = u.get("completion_tokens") or u.get("output_tokens")
                tt = u.get("total_tokens")
                if tt is None and pt is not None and ct is not None:
                    tt = pt + ct
                return {"prompt_tokens": pt, "completion_tokens": ct, "total_tokens": tt}

        return None

    # ---------------- request entrypoint ----------------

    def request(self, input: Any, meta: Optional[Dict[str, Any]] = None, stream: bool = True) -> Dict[str, Any]:
        messages = self.messages(input)
        ctx = ResultContext.create(model=self.model_name, prefix=self.PROVIDER_NAME, meta=meta)
        rf = ResultFactory(ctx)
        task_ctx: Optional[Dict[str, Any]] = None
        token = None
        if stream:
            task_ctx = self._task_begin(run_id=rf.ctx.run_id, meta=meta)
            token = _task_ctx_var.set(task_ctx)
        try:
            return self._stream_impl(rf, messages) if stream else self._request_impl(rf, messages)
        except Exception:
            if stream and task_ctx is not None:
                self._task_finish(task_ctx, status="failed")
            raise
        finally:
            if stream and task_ctx is not None:
                self._task_stop(task_ctx)
            if token is not None:
                _task_ctx_var.reset(token)

    def _task_begin(self, *, run_id: str, meta: Optional[Dict[str, Any]]) -> Dict[str, Any]:
        task_meta: Dict[str, Any] = {}
        if isinstance(meta, dict):
            session_id = str(meta.get("session_id") or "").strip()
            if session_id:
                task_meta["session_id"] = session_id
            name = str(meta.get("name") or "").strip()
            if name:
                task_meta["name"] = name
            node_id = str(meta.get("node_id") or "").strip()
            if node_id:
                task_meta["node_id"] = node_id
            node_type = str(meta.get("node_type") or "").strip()
            if node_type:
                task_meta["node_type"] = node_type
            code = str(meta.get("code") or "").strip()
            if code:
                task_meta["code"] = code
        reporter = TaskReporter(
            project_code=self.config.get("project_code"),
            content_code=self.config.get("content_code"),
            content_version_id=self.config.get("content_version_id"),
            model=self.model_name,
            task_id=run_id,
            meta=task_meta if task_meta else None,
            min_interval_sec=0.3,
        )
        reporter.emit(status="start", progress=0, force=True)
        reporter.emit(status="run", progress=15, force=True)
        # run 阶段采用偏快增长，兼顾启动体感与稳定性。
        reporter.emit(status="run", progress=-1, random={"floor": 15, "cap": 96, "interval": 0.55, "level": 6})
        return {"reporter": reporter, "finished": False}

    def _task_should_finish_after_emit(self, evt: Dict[str, Any], ctx: Optional[Dict[str, Any]]) -> bool:
        if not isinstance(ctx, dict) or not evt:
            return False
        if bool(ctx.get("finished")):
            return False
        evt_type = str(evt.get("type") or "").strip().lower()
        has_delta = evt_type in {"delta", "shenzhu_delta"} and bool(str(evt.get("content") or "").strip())
        has_reasoning = evt_type == "reasoning" and bool(str(evt.get("reasoning_content") or "").strip())
        return bool(has_delta or has_reasoning)

    def _task_finish(self, ctx: Optional[Dict[str, Any]], *, status: str) -> None:
        if not isinstance(ctx, dict) or bool(ctx.get("finished")):
            return
        reporter = ctx.get("reporter")
        if reporter is not None:
            reporter.emit(status=status, progress=100, force=True)
        ctx["finished"] = True

    def _task_stop(self, ctx: Optional[Dict[str, Any]]) -> None:
        if not isinstance(ctx, dict):
            return
        reporter = ctx.get("reporter")
        if reporter is not None:
            reporter.close()

    def _request_impl(self, rf: ResultFactory, messages: List[Dict[str, Any]]) -> Dict[str, Any]:
        try:
            if self._is_cancelled() and CancelledError is not None:
                raise CancelledError()
            resp = self.llm.invoke(messages)
            u = self.usage(resp)
            return rf.final(
                content=getattr(resp, "content", "") or "",
                usage=u,
                raw=getattr(resp, "response_metadata", None),
            )
        except Exception as e:
            raise WorkerError(f"LLM 调用异常: {e}", retryable=True, cause=e)

    def _stream_impl(self, rf: ResultFactory, messages: List[Dict[str, Any]]) -> Dict[str, Any]:
        parts: List[str] = []
        usage: Optional[Dict[str, Any]] = None
        last_raw = None
        stream_usage = bool(self.config.get("stream_usage", True))

        try:
            if stream_usage:
                stream_iter = self.llm.stream(messages, stream_usage=True)
            else:
                stream_iter = self.llm.stream(messages)

            for chunk in stream_iter:
                if self._is_cancelled() and CancelledError is not None:
                    raise CancelledError()
                last_raw = getattr(chunk, "response_metadata", None) or last_raw

                delta = getattr(chunk, "content", "")
                reasoning = getattr(chunk, "reasoning_content", "")
                emitted = False
                if isinstance(delta, str) and delta:
                    parts.append(delta)
                    self.emit(rf.delta(delta))
                    emitted = True
                if reasoning:
                    evt: Dict[str, Any] = {
                        "id": rf.ctx.run_id,
                        "type": "reasoning",
                        "reasoning_content": reasoning,
                        "model": self.model_name,
                        "ts_ms": int(time.time() * 1000),
                    }
                    if rf.ctx.meta:
                        evt["meta"] = rf.ctx.meta
                    self.emit(evt)
                    emitted = True

                u = self.usage(chunk)
                if u:
                    usage = u

            final = rf.final(content="".join(parts), usage=usage, raw=last_raw)
            self.emit(final)
            return final

        except Exception as e:
            err_evt = rf.error(
                message=str(e),
                code="error",
                raw=getattr(e, "__dict__", None),
                retryable=True,
            )
            self.emit(err_evt)
            raise WorkerError(f"LLM 流式调用异常: {e}", retryable=True, cause=e)

    def _is_cancelled(self) -> bool:
        if activity is None:
            return False
        try:
            return bool(activity.is_cancelled())
        except RuntimeError as e:
            # 非 Temporal activity 上下文（例如本地脚本直接调用）
            if "Not in activity context" in str(e):
                return False
            raise
