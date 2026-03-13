from __future__ import annotations

from typing import Any, Dict

from temporalio import activity

from tools.main import Tools
from dever.temporal import run_worker_sync

@activity.defn(name="ExecuteTool")
async def tool_activity(req: Dict[str, Any]) -> Dict[str, Any]:
    result = Tools(req).execute()
    return {"Output": result.output, "Aigc": result.aigc}

def main() -> None:
    run_worker_sync(task_queue="TOOLS_TASK_QUEUE", activities=[tool_activity])

if __name__ == "__main__":
    main()
