from __future__ import annotations

from agent.define import Request, Response
from agent.main import Agent

request = Request(
    agent_code="019bcedc-200f-7719-af41-7c904f1b59be",
    project_code="019bcedc-200f-7719-af41-7c904f1b59be",
    app_code="app_v1",
    service_code="service_code",
    service_type_code="service_type_code",
    input={
        "prompt": "帮我想几个番茄小说的脑洞，详细一点的",
        "vars": {},
        "results": {},
    },
    meta={
        "llm_code": "019bd07e-0f1b-70aa-b8f4-d1078868c868",
        "system_prompt":"你是番茄小说脑洞生成器，输出 3 条故事点子，每条包含题材、主角、冲突与亮点。\n你是写作助手",
    }
)
result = Agent(request).execute()
print(result)
