from __future__ import annotations

from typing import Any, Dict, List, Optional

from dever.error import WorkerError
from tools.provider.runninghubapi import RunningHubAPI


class RunningHubFlow(RunningHubAPI):
    CREATE_PATH = "/task/openapi/create"
    OUTPUTS_PATH = "/task/openapi/outputs"
    EXTRA_OPTION_KEYS = {"addMetadata", "webhookUrl", "workflow", "instanceType", "usePersonalQueue"}

    def workflow(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        _ = meta
        if not isinstance(input, dict):
            raise WorkerError("RunningHubFlow 工作流入参必须是对象")

        workflow_id = str(input.get("model", "")).strip()
        if not workflow_id:
            raise WorkerError("RunningHubFlow 缺少 workflowId")

        option = input.get("option") if isinstance(input.get("option"), dict) else {}
        task_id = self._load_cached_task_id(input)
        if task_id:
            final_body = self._poll_result(task_id, {"taskId": task_id}, option, input, resource_name="工作流")
            return self._normalize_response(task_id, final_body)

        payload = self.build_workflow_payload(workflow_id, input.get("nodeInfoList"), option)
        created = self.request_json("POST", f"{self.host}{self.CREATE_PATH}", payload=payload, timeout=180)
        self._raise_for_error(created, "提交 RunningHubFlow 工作流任务失败")

        task_id = self.extract_task_id(created)
        self._cache_task_id(input, task_id)
        wait = bool(input.get("wait", True))
        if not wait:
            return self._normalize_response(task_id, created)

        if self.collect_urls(created):
            return self._normalize_response(task_id, created)

        final_body = self._poll_result(task_id, created, option, input, resource_name="工作流")
        return self._normalize_response(task_id, final_body)

    def query_outputs(self, task_id: str) -> Dict[str, Any]:
        payload = {"apiKey": self.token, "taskId": task_id}
        return self.request_json("POST", f"{self.host}{self.OUTPUTS_PATH}", payload=payload, timeout=60)

    def build_workflow_payload(self, workflow_id: str, node_info_list: Any, option: Dict[str, Any]) -> Dict[str, Any]:
        payload: Dict[str, Any] = {
            "apiKey": self.token,
            "workflowId": workflow_id,
        }
        nodes = self._normalize_node_info_list(node_info_list)
        if nodes:
            payload["nodeInfoList"] = nodes

        for key in self.EXTRA_OPTION_KEYS:
            if key in option:
                payload[key] = option[key]
        return payload

    @staticmethod
    def _normalize_node_info_list(raw: Any) -> List[Dict[str, Any]]:
        if not isinstance(raw, list):
            return []

        out: List[Dict[str, Any]] = []
        for item in raw:
            if not isinstance(item, dict):
                continue
            node_id = str(item.get("nodeId", "")).strip()
            field_name = str(item.get("fieldName", "")).strip()
            if not node_id or not field_name:
                continue
            out.append(
                {
                    "nodeId": node_id,
                    "fieldName": field_name,
                    "fieldValue": item.get("fieldValue"),
                }
            )
        return out
