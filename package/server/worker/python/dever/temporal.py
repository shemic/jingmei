from __future__ import annotations

import asyncio
import os
from typing import Iterable
from dever.core import Dever

from temporalio import activity, client, worker

async def run_worker(*, task_queue: str, activities: Iterable[activity.defn]) -> None:
    if not task_queue:
        raise ValueError("task_queue is required")
    config = Dever.readConfig("temporal")
    host_port = config.get("hostPort")
    namespace = config.get("namespace")
    temporal_client = await client.Client.connect(host_port, namespace=namespace)
    worker_instance = worker.Worker(
        temporal_client,
        task_queue=task_queue,
        activities=list(activities),
    )
    await worker_instance.run()


def run_worker_sync(*, task_queue: str, activities: Iterable[activity.defn]) -> None:
    asyncio.run(run_worker(task_queue=task_queue, activities=activities))
