from __future__ import annotations

from typing import Any, Dict

from temporalio import activity
from llm.main import LLM
from dever.temporal import run_worker_sync


@activity.defn(name="ExecuteLLM")
async def llm_activity(req: Dict[str, Any]) -> Dict[str, Any]:
    result = LLM(req).execute()
    return {"Output": result.output}

def main() -> None:
    run_worker_sync(task_queue="LLM_TASK_QUEUE", activities=[llm_activity])

if __name__ == "__main__":
    main()
