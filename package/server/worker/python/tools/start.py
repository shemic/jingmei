from __future__ import annotations

from typing import Any, Dict

from temporalio import activity
try:
    from temporalio import exceptions as temporal_exceptions
except Exception:
    temporal_exceptions = None

from dever.error import WorkerError
from tools.main import Tools
from dever.temporal import run_worker_sync


def _build_application_error(exc: WorkerError) -> BaseException:
    app_error_cls = getattr(temporal_exceptions, "ApplicationError", None) if temporal_exceptions is not None else None
    if app_error_cls is None:
        return exc
    try:
        return app_error_cls(
            str(exc),
            type=exc.__class__.__name__,
            non_retryable=not bool(getattr(exc, "retryable", False)),
        )
    except Exception:
        return exc

@activity.defn(name="ExecuteTool")
async def tool_activity(req: Dict[str, Any]) -> Dict[str, Any]:
    try:
        result = Tools(req).execute()
        return {"Output": result.output, "Aigc": result.aigc}
    except WorkerError as exc:
        converted = _build_application_error(exc)
        if converted is exc:
            raise
        raise converted from exc

def main() -> None:
    run_worker_sync(task_queue="TOOLS_TASK_QUEUE", activities=[tool_activity])

if __name__ == "__main__":
    main()
