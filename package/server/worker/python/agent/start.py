from __future__ import annotations

from typing import Any, Dict

from temporalio import activity
from temporalio.exceptions import CancelledError

from agent.main import Agent
from dever.temporal import run_worker_sync

@activity.defn(name="ExecuteAgent")
async def agent_activity(req: Dict[str, Any]) -> Dict[str, Any]:
    try:
        result = Agent(req).execute()
        return {"Output": result.output, "Aigc": result.aigc}
    except CancelledError:
        # Activity 被取消时直接抛出，交由 Temporal 处理
        raise

def main() -> None:
    run_worker_sync(task_queue="AGENT_TASK_QUEUE", activities=[agent_activity])

if __name__ == "__main__":
    main()
