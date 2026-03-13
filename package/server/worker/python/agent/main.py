from __future__ import annotations

import json
from typing import Any, Dict

from dever.pgsql import PgSQL as Db
from agent.define import Request, Response
from llm.main import LLM

class Agent:
    def __init__(self, request: Dict[str, Any]):
        self.request = self._request(request)

    @staticmethod
    def _request(raw: Dict[str, Any]) -> Request:
        data: Dict[str, Any] = {
            "agent_code": raw.get("agent_code"),
            "project_code": raw.get("project_code"),
            "app_code": raw.get("app_code"),
            "workflow_code": raw.get("workflow_code"),
            "content_code": raw.get("content_code"),
            "content_version_id": raw.get("content_version_id"),
            "input": raw.get("input"),
            "meta": raw.get("meta") if isinstance(raw.get("meta"), dict) else {},
        }
        return Request(**data)

    def execute(self) -> Response:
        #self.request.policy
        agent = self._get_agent()
        msg = self._build_messages(agent["system_prompt"])
        request = self.request.dict()
        request["model_code"] = agent["model_code"]
        request["input"] = msg
        result = LLM(request).execute()
        return Response(output=result.output, aigc=result.output["content"])

    def _build_messages(self, system_prompt) -> Any:
        raw = self.request.input
        prompt = self._build_system_prompt(system_prompt, raw)
        if isinstance(raw, dict) and "input" in raw:
            raw = raw.get("input")
        return [
            {"role": "system", "content": prompt},
            {"role": "user", "content": self._to_text(raw)},
        ]

    def _build_system_prompt(self, system_prompt: Any, raw_input: Any) -> str:
        prompt = self._to_text(system_prompt) or "You are a helpful assistant."
        if not isinstance(raw_input, dict):
            return prompt

        option_text = self._build_option_text(raw_input.get("option"))
        if option_text:
            prompt = f"{prompt}\n\n[选项]如下：\n\n{option_text}"

        attachment_text = self._build_attachment_text(raw_input.get("file"))
        if attachment_text:
            prompt = f"{prompt}\n\n[附件]如下：\n\n{attachment_text}"
        return prompt

    def _build_option_text(self, option_value: Any) -> str:
        sections = []
        for code, value in self._extract_options(option_value):
            sections.append(f"{code}：{value}\n")
        return "\n".join(sections)

    def _build_attachment_text(self, file_value: Any) -> str:
        files = self._parse_files(file_value)
        if not files:
            return ""

        images = []
        videos = []
        audios = []
        office = []
        pdfs = []

        image_ext = {
            ".jpg", ".jpeg", ".png", ".gif", ".bmp", ".webp", ".tiff", ".tif", ".svg", ".heic", ".heif",
        }
        video_ext = {
            ".mp4", ".mov", ".avi", ".mkv", ".wmv", ".flv", ".webm", ".m4v", ".3gp", ".mpeg", ".mpg",
        }
        audio_ext = {".mp3", ".wav", ".flac", ".aac", ".ogg", ".m4a", ".wma", ".amr"}
        office_ext = {
            ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".csv",
        }

        for file_name in files:
            normalized = file_name.strip()
            suffix = normalized.lower().split("?", 1)[0].split("#", 1)[0]
            dot = suffix.rfind(".")
            ext = suffix[dot:] if dot != -1 else ""
            if ext in image_ext:
                images.append(normalized)
            elif ext in video_ext:
                videos.append(normalized)
            elif ext in audio_ext:
                audios.append(normalized)
            elif ext == ".pdf":
                pdfs.append(normalized)
            elif ext in office_ext:
                office.append(normalized)

        sections = []
        if images:
            sections.append(f"图片 = [{','.join(images)}]")
        if videos:
            sections.append(f"视频 = [{','.join(videos)}]")
        if audios:
            sections.append(f"音频 = [{','.join(audios)}]")
        if office:
            sections.append(f"office = [{','.join(office)}]")
        if pdfs:
            sections.append(f"pdf = [{','.join(pdfs)}]")
        return "\n".join(sections)

    def _extract_options(self, option_value: Any) -> list[tuple[str, str]]:
        parsed = option_value
        if isinstance(option_value, str):
            try:
                parsed = json.loads(option_value)
            except json.JSONDecodeError:
                return []

        items: list[tuple[str, str]] = []
        if isinstance(parsed, dict):
            # 新结构：{"code": value, ...}
            has_new_shape = False
            for key, value in parsed.items():
                if not isinstance(key, str) or not key.strip():
                    continue
                has_new_shape = True
                items.append((key.strip(), self._to_text(value)))
            if has_new_shape:
                return items

            # 兼容旧结构：{"code":"x","value":"y"}
            code = parsed.get("code")
            if isinstance(code, str) and code.strip():
                items.append((code.strip(), self._to_text(parsed.get("value"))))
            return items

        if isinstance(parsed, list):
            for item in parsed:
                if not isinstance(item, dict):
                    continue
                code = item.get("code")
                if not isinstance(code, str) or not code.strip():
                    continue
                items.append((code.strip(), self._to_text(item.get("value"))))
            return items

        return items

    @staticmethod
    def _parse_files(file_value: Any) -> list[str]:
        if file_value is None:
            return []
        if isinstance(file_value, str):
            return [item.strip() for item in file_value.split(",") if item.strip()]
        if isinstance(file_value, list):
            result = []
            for item in file_value:
                if isinstance(item, str) and item.strip():
                    result.append(item.strip())
            return result
        return []
    
    def _get_agent(self) -> dict:
        table = Db.table("work_agent")
        agent = Db.find(f"SELECT * FROM {table} WHERE code = %s", [self.request.agent_code])
        model = agent["model"].split(",")
        table = Db.table("work_model")
        model = Db.find(f"SELECT * FROM {table} WHERE id = %s", [model[1]])
        agent["model_code"] = model["code"]
        return agent
    
    @staticmethod
    def _to_text(value: Any) -> str:
        if value is None:
            return ""
        if isinstance(value, str):
            return value
        try:
            return json.dumps(value, ensure_ascii=True)
        except TypeError:
            return str(value)
