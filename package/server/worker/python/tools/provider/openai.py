from __future__ import annotations

import base64
import binascii
import mimetypes
import os
import re
from typing import Any, Dict, List, Optional, Tuple
from urllib.parse import unquote, urlparse

import requests

from dever.error import WorkerError
from tools.provider.core import Provider


class OpenAI(Provider):
    DEFAULT_HOST = "https://api.kuai.host"
    GENERATIONS_PATH = "images/generations"
    EDITS_PATH = "images/edits"
    DEFAULT_MODEL = "gpt-image-2"
    DOWNLOAD_TIMEOUT = 60

    CONTROL_KEYS = {
        "model",
        "mode",
        "prompt",
        "option",
        "files",
        "file",
        "image",
        "video",
        "audio",
        "media_rules",
        "media_fields",
        "is_edit",
        "wait",
        "timeout",
        "interval",
        "task_key",
        "task_id",
    }
    IGNORED_OPTION_KEYS = {"model", "file", "files", "image", "timeout", "interval"}
    DATA_URI_RE = re.compile(r"^data:(?P<mime>[^;,]+)?(?:;[^,]+)*;base64,(?P<body>.+)$", re.I | re.S)

    def image(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        _ = meta
        if not isinstance(input, dict):
            raise WorkerError("OpenAI 图片入参必须是对象")

        prompt = str(input.get("prompt", "")).strip()
        if not prompt:
            raise WorkerError("OpenAI 图片 prompt 不能为空")

        images = self._normalize_images(input.get("files") or input.get("image"))
        is_edit = bool(input.get("is_edit")) or bool(images)
        models = self._resolve_model_candidates(input.get("model"), is_edit)
        option = self._option_map(input.get("option"))

        last_error: Optional[WorkerError] = None
        for model in models:
            try:
                if is_edit:
                    body = self._create_image_edit(model, prompt, images, input, option)
                else:
                    body = self._create_image_generation(model, prompt, input, option)
                self._raise_for_error(body, "OpenAI 图片生成失败")
                return self._normalize_image_response(body)
            except WorkerError as exc:
                last_error = exc

        if last_error is not None:
            raw_model = self._compact_value(input.get("model"))
            raise WorkerError(f"{last_error} | raw_model={raw_model}", cause=last_error) from last_error
        raise WorkerError("OpenAI 图片请求缺少可用模型")

    def _create_image_generation(
        self,
        model: str,
        prompt: str,
        input_data: Dict[str, Any],
        option: Dict[str, Any],
    ) -> Dict[str, Any]:
        payload = self._build_json_payload(model, prompt, input_data, option)
        return self.request_json("POST", self._api_url(self.GENERATIONS_PATH), payload=payload, timeout=180)

    def _create_image_edit(
        self,
        model: str,
        prompt: str,
        images: List[str],
        input_data: Dict[str, Any],
        option: Dict[str, Any],
    ) -> Dict[str, Any]:
        if not images:
            raise WorkerError("OpenAI 图生图缺少 image 文件")

        fields = self._build_form_fields(model, prompt, input_data, option)
        image_files = self._build_upload_files("image", images)
        mask = self._extract_mask(input_data, option)
        mask_files = self._build_upload_files("mask", [mask]) if mask else []
        return self._post_multipart(
            self._api_url(self.EDITS_PATH),
            fields,
            image_files + mask_files,
            "OpenAI 图生图请求失败",
        )

    def _build_json_payload(
        self,
        model: str,
        prompt: str,
        input_data: Dict[str, Any],
        option: Dict[str, Any],
    ) -> Dict[str, Any]:
        payload: Dict[str, Any] = {"model": model, "prompt": prompt}
        self._merge_request_fields(payload, input_data, option)
        return payload

    def _build_form_fields(
        self,
        model: str,
        prompt: str,
        input_data: Dict[str, Any],
        option: Dict[str, Any],
    ) -> Dict[str, str]:
        payload: Dict[str, Any] = {"model": model, "prompt": prompt}
        self._merge_request_fields(payload, input_data, option)
        payload.pop("mask", None)
        return {key: self._stringify_form_value(value) for key, value in payload.items() if value not in (None, "")}

    def _merge_request_fields(
        self,
        payload: Dict[str, Any],
        input_data: Dict[str, Any],
        option: Dict[str, Any],
    ) -> None:
        for key, value in option.items():
            if key in self.IGNORED_OPTION_KEYS:
                continue
            payload[key] = value

        for key, value in input_data.items():
            if key in self.CONTROL_KEYS or key in payload:
                continue
            if value in (None, ""):
                continue
            payload[key] = value

    def _normalize_image_response(self, body: Dict[str, Any]) -> Dict[str, Any]:
        urls = self.collect_urls(body)
        urls.extend(self._collect_b64_data_uris(body))
        data = [{"url": url} for url in self.uniq_strings(urls)]
        status = self.extract_status(body) or ("succeeded" if data else "submitted")
        return {
            "task_id": self.extract_task_id(body),
            "status": status,
            "result": body,
            "data": data,
        }

    def _raise_for_error(self, body: Dict[str, Any], prefix: str) -> None:
        error = body.get("error")
        if isinstance(error, dict) and error:
            message = str(error.get("message") or error.get("msg") or error).strip()
            raise WorkerError(f"{prefix}: {message}")
        if isinstance(error, str) and error.strip():
            raise WorkerError(f"{prefix}: {error.strip()}")

        code = body.get("code")
        if code not in (None, 0, "0"):
            message = str(body.get("message") or body.get("msg") or body.get("errorMessage") or "").strip()
            raise WorkerError(f"{prefix}: code={code}, message={message}")

        status = self.extract_status(body).lower()
        if status in {"failed", "error", "cancelled", "canceled"}:
            message = str(body.get("message") or body.get("msg") or body.get("errorMessage") or "").strip()
            raise WorkerError(f"{prefix}: status={status}, message={message}")

    def _post_multipart(
        self,
        url: str,
        data: Dict[str, str],
        files: List[Tuple[str, Tuple[str, bytes, str]]],
        error_prefix: str,
    ) -> Dict[str, Any]:
        headers = dict(self.header)
        headers.pop("Content-Type", None)
        headers["Accept"] = "application/json"
        res = requests.post(url, headers=headers, data=data, files=files, timeout=180)
        if not res.ok:
            preview = (res.text or "").strip()[:500]
            raise WorkerError(f"{error_prefix}: status={res.status_code}, body={preview}")
        try:
            body = res.json()
        except Exception as exc:
            preview = (res.text or "").strip()[:500]
            raise WorkerError(f"{error_prefix}: 返回JSON无效: {preview}") from exc
        if not isinstance(body, dict):
            raise WorkerError(f"{error_prefix}: 返回必须是JSON对象")
        return body

    def _build_upload_files(self, field_name: str, sources: List[str]) -> List[Tuple[str, Tuple[str, bytes, str]]]:
        out: List[Tuple[str, Tuple[str, bytes, str]]] = []
        for index, source in enumerate(sources):
            filename, content, mime = self._load_upload_source(source, index)
            out.append((field_name, (filename, content, mime)))
        return out

    def _load_upload_source(self, source: str, index: int) -> Tuple[str, bytes, str]:
        value = str(source or "").strip()
        if not value:
            raise WorkerError("OpenAI 图生图 image 不能为空")
        if value.startswith(("http://", "https://")):
            return self._load_url_source(value, index)
        if value.startswith("data:"):
            return self._load_data_uri_source(value, index)
        if os.path.isfile(value):
            return self._load_local_file_source(value, index)
        raise WorkerError("OpenAI 图生图 image 必须是 URL、data URI 或可访问的本地文件")

    def _load_url_source(self, url: str, index: int) -> Tuple[str, bytes, str]:
        res = requests.get(url, timeout=self.DOWNLOAD_TIMEOUT)
        if not res.ok:
            raise WorkerError(f"下载 OpenAI 图生图 image 失败: status={res.status_code}")
        mime = self._normalize_mime(res.headers.get("Content-Type"), url)
        filename = self._filename_from_url(url, index, mime)
        return filename, res.content, mime

    def _load_data_uri_source(self, data_uri: str, index: int) -> Tuple[str, bytes, str]:
        matched = self.DATA_URI_RE.match(data_uri)
        if not matched:
            raise WorkerError("OpenAI 图生图 data URI 格式无效")
        mime = self._normalize_mime(matched.group("mime"), "")
        try:
            content = base64.b64decode(matched.group("body"), validate=True)
        except (binascii.Error, ValueError) as exc:
            raise WorkerError("OpenAI 图生图 data URI base64 无效") from exc
        return self._fallback_filename(index, mime), content, mime

    def _load_local_file_source(self, path: str, index: int) -> Tuple[str, bytes, str]:
        try:
            with open(path, "rb") as file_obj:
                content = file_obj.read()
        except OSError as exc:
            raise WorkerError(f"读取 OpenAI 图生图本地文件失败: {path}") from exc
        mime = self._normalize_mime(mimetypes.guess_type(path)[0], path)
        filename = os.path.basename(path) or self._fallback_filename(index, mime)
        return filename, content, mime

    def _extract_mask(self, input_data: Dict[str, Any], option: Dict[str, Any]) -> str:
        for value in (input_data.get("mask"), option.get("mask")):
            text = str(value or "").strip()
            if text:
                return text
        return ""

    def _resolve_model_candidates(self, raw_model: Any, is_edit: bool) -> List[str]:
        candidates = self._parse_model_candidates(str(raw_model or "").strip())
        selected: List[str] = []
        for text_model, edit_model in candidates:
            model = edit_model if is_edit else text_model
            normalized = self._normalize_model_name(model)
            if normalized:
                selected.append(normalized)
        if not selected:
            return [self.DEFAULT_MODEL]
        return self.uniq_strings(selected)

    def _parse_model_candidates(self, raw: str) -> List[Tuple[str, str]]:
        value = raw.strip()
        if not value:
            return [(self.DEFAULT_MODEL, self.DEFAULT_MODEL)]

        lines = [part.strip() for part in value.splitlines() if part.strip()]
        if len(lines) == 2 and all("||" not in line for line in lines):
            return [(lines[0], lines[1])]

        candidates: List[Tuple[str, str]] = []
        for line in lines:
            pair = line.split("||", 1)
            text_model = pair[0].strip()
            edit_model = pair[1].strip() if len(pair) > 1 else text_model
            candidates.append((text_model, edit_model or text_model))
        return candidates

    @staticmethod
    def _normalize_model_name(model: str) -> str:
        value = str(model or "").strip()
        if value.lower().startswith("openai."):
            return value.split(".", 1)[1].strip()
        return value

    def _api_url(self, path: str) -> str:
        normalized = "/" + str(path or "").strip().lstrip("/")
        if self.host.endswith("/v1") and normalized.startswith("/v1/"):
            normalized = normalized[3:]
        return f"{self.host}{normalized}"

    def _collect_b64_data_uris(self, value: Any) -> List[str]:
        out: List[str] = []
        self._collect_b64_data_uris_recursive(value, out)
        return out

    def _collect_b64_data_uris_recursive(self, value: Any, out: List[str]) -> None:
        if isinstance(value, dict):
            b64_json = value.get("b64_json")
            if isinstance(b64_json, str) and b64_json.strip():
                out.append(self._data_uri_from_b64(b64_json))
            for item in value.values():
                self._collect_b64_data_uris_recursive(item, out)
            return
        if isinstance(value, list):
            for item in value:
                self._collect_b64_data_uris_recursive(item, out)

    @staticmethod
    def _data_uri_from_b64(value: str) -> str:
        text = value.strip()
        if text.startswith("data:"):
            return text
        return f"data:image/png;base64,{text}"

    @staticmethod
    def _normalize_images(raw: Any) -> List[str]:
        return Provider.uniq_strings(Provider._normalize_media_values(raw))

    @staticmethod
    def _option_map(option: Any) -> Dict[str, Any]:
        return dict(option) if isinstance(option, dict) else {}

    @staticmethod
    def _stringify_form_value(value: Any) -> str:
        if isinstance(value, bool):
            return "true" if value else "false"
        return str(value)

    @staticmethod
    def _compact_value(value: Any) -> str:
        text = str(value or "").strip()
        if len(text) <= 500:
            return text
        return f"{text[:500]}..."

    @staticmethod
    def _normalize_mime(value: Any, fallback_name: str) -> str:
        mime = str(value or "").split(";", 1)[0].strip()
        if mime:
            return mime
        guessed = mimetypes.guess_type(fallback_name)[0]
        return guessed or "image/png"

    @classmethod
    def _filename_from_url(cls, url: str, index: int, mime: str) -> str:
        path = unquote(urlparse(url).path or "")
        name = os.path.basename(path).strip()
        if name:
            return name
        return cls._fallback_filename(index, mime)

    @staticmethod
    def _fallback_filename(index: int, mime: str) -> str:
        ext = mimetypes.guess_extension(mime) or ".png"
        return f"image_{index}{ext}"
