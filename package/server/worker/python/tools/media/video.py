from __future__ import annotations

import random
import time
from typing import Any, Dict, List, Optional, Tuple
from urllib.parse import urlparse

import requests

from dever.error import WorkerError
from dever.prompt import Prompt
from dever.qiniu import Qiniu
from dever.task import TaskReporter
from tools.media.base import Base


class Video(Base):
    TASK_PATH = "/contents/generations/tasks"

    def handle(self, input: Any, meta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        if not isinstance(input, dict):
            raise WorkerError("入参必须是对象")
        reporter = TaskReporter(
            project_code=self.config.get("project_code"),
            content_code=self.config.get("content_code"),
            content_version_id=self.config.get("content_version_id"),
            model=self.model,
            meta=meta if isinstance(meta, dict) else None,
            min_interval_sec=1.0,
        )

        try:
            reporter.emit(status="start", progress=0, force=True)
            request_mode, payload = self._build_create_payload(input)
            create_url = f"{self.host}{self.TASK_PATH}"
            if request_mode == "mention":
                created = self._post_form(create_url, payload, "创建视频任务失败")
            else:
                created = self._post_json(create_url, payload, "创建视频任务失败")
            task_id = self._extract_task_id(created)
            if not task_id:
                raise WorkerError("创建视频任务成功但未返回任务ID")
            reporter.set_task_id(task_id)
            reporter.emit(status="run", progress=10, force=True)
            reporter.emit(status="run", progress=-1, random={"floor": 10, "cap": 79, "interval": 0.8})
            option = input.get("option", {})
            if not isinstance(option, dict):
                option = {}
            timeout_sec = self._to_int(option.get("timeout"), 600)
            interval_sec = self._to_int(option.get("interval"), 5)
            if timeout_sec <= 0:
                timeout_sec = 600
            if interval_sec <= 0:
                interval_sec = 5

            final_body = self._poll_task(
                task_id,
                timeout_sec=timeout_sec,
                interval_sec=interval_sec,
            )
            urls = self._collect_video_urls(final_body)
            if not urls:
                raise WorkerError("视频任务已完成，但未找到可下载地址")

            content_code = str(self.config.get("content_code", "")).strip()
            if not content_code:
                raise WorkerError("配置缺少 content_code，无法生成七牛 key")

            qiniu = Qiniu()
            uploaded: List[Dict[str, Any]] = []
            aigc_urls: List[str] = []
            total = max(len(urls), 1)
            base_progress = reporter.current_progress()
            if base_progress < 0:
                base_progress = 10
            upload_start = max(30, min(90, base_progress + 2))
            reporter.emit(status="upload", progress=upload_start, force=True)
            reporter.emit(status="upload", progress=-1, random={"floor": upload_start, "cap": 98, "interval": 0.8})
            for idx, src_url in enumerate(urls):
                stored = qiniu.upload(
                    source_url=src_url,
                    content_code=content_code,
                    prefix="model_generated",
                    file_type="model_generated",
                    index=idx,
                )
                uploaded.append(
                    {
                        "index": idx,
                        "source_url": src_url,
                        "key": stored["key"],
                        "url": stored["url"],
                    }
                )
                aigc_urls.append(stored["url"])
                p = upload_start + int(((idx + 1) / total) * (98 - upload_start))
                reporter.emit(status="upload", progress=p)

            reporter.emit(status="finish", progress=100, force=True)
            return {
                "task_id": task_id,
                "status": self._extract_status(final_body) or "succeeded",
                "result": final_body,
                "uploaded": uploaded,
                "aigc": ",".join(aigc_urls),
            }
        except Exception:
            reporter.emit(status="failed", progress=100, force=True)
            raise

    def _build_create_payload(self, input_data: Dict[str, Any]) -> Tuple[str, Dict[str, Any]]:
        model, mode = Prompt.parse_modal(self.model, default_model="doubao-seedance-1-0-lite-i2v-250428")
        if mode == "mention":
            return mode, self._build_mention_payload(input_data, model)
        return mode, self._build_default_payload(input_data, model, mode)

    def _build_mention_payload(self, input_data: Dict[str, Any], model: str) -> Dict[str, Any]:
        prepared = Prompt.get_input(input_data, extract_types=["image", "video", "audio"], mode="@")
        prompt_raw = str(prepared.get("prompt", "")).strip()
        if not prompt_raw:
            raise WorkerError("input 不能为空")

        option = prepared.get("option", {})
        if not isinstance(option, dict):
            option = {}
        option = self._normalize_option(option)

        file_data = prepared.get("file")
        if not isinstance(file_data, list) or not file_data:
            raise WorkerError("mention 模式缺少文件（file）")

        payload: Dict[str, Any] = {
            "model": model,
            "prompt": prompt_raw,
            "files": file_data,
        }
        payload.update(option)
        return payload

    def _build_default_payload(self, input_data: Dict[str, Any], model: str, mode: str) -> Dict[str, Any]:
        _ = mode
        prepared = Prompt.get_input(input_data, extract_types=["image"], mode="图")
        prompt_raw = str(prepared.get("prompt", "")).strip()
        if not prompt_raw:
            raise WorkerError("input 不能为空")

        option = prepared.get("option", {})
        if not isinstance(option, dict):
            option = {}
        option = self._normalize_option(option)

        file_data = prepared.get("file")
        image_urls: List[str] = []
        if isinstance(file_data, list):
            image_urls = [str(x).strip() for x in file_data if str(x).strip()]
        elif isinstance(file_data, dict):
            raw_images = file_data.get("image")
            if isinstance(raw_images, list):
                image_urls = [str(x).strip() for x in raw_images if str(x).strip()]
            elif isinstance(raw_images, str) and raw_images.strip():
                image_urls = [raw_images.strip()]
        if not image_urls:
            raise WorkerError("图生视频缺少图片地址（option.image_url 或 file.image）")
        image_urls = self._normalize_image_urls_for_video(image_urls)

        content: List[Dict[str, Any]] = [{"type": "text", "text": prompt_raw}]
        model_name = (model or "").lower()
        is_seedance_15_pro = "doubao-seedance-1-5-pro" in model_name
        total = len(image_urls)

        # 1 张图：首帧；2 张图：首尾帧；>2 张图：普通模型附带参考图，1.5-pro 仅保留首尾帧
        if total == 1:
            content.append(
                {
                    "type": "image_url",
                    "image_url": {"url": image_urls[0]},
                    "role": "first_frame",
                }
            )
        elif total >= 2:
            content.append(
                {
                    "type": "image_url",
                    "image_url": {"url": image_urls[0]},
                    "role": "first_frame",
                }
            )
            content.append(
                {
                    "type": "image_url",
                    "image_url": {"url": image_urls[1]},
                    "role": "last_frame",
                }
            )
            if total > 2 and not is_seedance_15_pro:
                for url in image_urls[2:]:
                    content.append(
                        {
                            "type": "image_url",
                            "image_url": {"url": url},
                            "role": "reference_image",
                        }
                    )

        payload: Dict[str, Any] = {"model": model, "content": content, "watermark": False}
        payload.update(option)
        return payload

    def _normalize_image_urls_for_video(self, image_urls: List[str]) -> List[str]:
        cleaned = [str(url).strip() for url in image_urls if str(url).strip()]
        if not cleaned:
            return []
        content_code = str(self.config.get("content_code", "")).strip()
        if not content_code:
            return cleaned

        qiniu = Qiniu()
        qiniu_host = urlparse(qiniu.domain if str(qiniu.domain).startswith("http") else f"https://{qiniu.domain}").netloc.lower()
        normalized: List[str] = []
        for idx, raw in enumerate(cleaned):
            parsed = urlparse(raw)
            host = parsed.netloc.lower()
            if qiniu_host and host == qiniu_host:
                normalized.append(raw)
                continue
            if raw.startswith("data:"):
                raise WorkerError("图生视频暂不支持 data URI 图片，请先上传图片")
            stored = qiniu.upload(
                source_url=raw,
                content_code=content_code,
                prefix="user_upload",
                file_type="user_upload",
                index=idx,
            )
            normalized.append(str(stored.get("url", "")).strip() or raw)
        return normalized

    def _poll_task(
        self,
        task_id: str,
        timeout_sec: int,
        interval_sec: int,
    ) -> Dict[str, Any]:
        url = f"{self.host}{self.TASK_PATH}/{task_id}"
        start = time.time()
        while True:
            elapsed = time.time() - start
            if elapsed > timeout_sec:
                raise WorkerError(f"视频任务超时（{timeout_sec}秒）")
            body = self._get_json(url, "查询视频任务失败")
            status = self._extract_status(body).lower()
            if status in {"succeeded", "success", "done", "completed"}:
                return body
            if status in {"failed", "error", "canceled", "cancelled"}:
                raise WorkerError(f"视频任务失败，状态={status}")
            time.sleep(interval_sec)

    @staticmethod
    def _extract_progress(body: Dict[str, Any]) -> Optional[int]:
        candidates: List[Any] = []
        for key in ("progress", "percent", "percentage"):
            candidates.append(body.get(key))
        data = body.get("data")
        if isinstance(data, dict):
            for key in ("progress", "percent", "percentage"):
                candidates.append(data.get(key))
        for value in candidates:
            if value is None:
                continue
            try:
                num = int(float(value))
            except Exception:
                continue
            if num < 0:
                num = 0
            if num > 100:
                num = 100
            return num
        return None

    @staticmethod
    def _extract_status(body: Dict[str, Any]) -> str:
        for key in ("status", "state", "task_status"):
            value = body.get(key)
            if isinstance(value, str) and value.strip():
                return value.strip()
        data = body.get("data")
        if isinstance(data, dict):
            for key in ("status", "state", "task_status"):
                value = data.get(key)
                if isinstance(value, str) and value.strip():
                    return value.strip()
        return ""

    @staticmethod
    def _extract_task_id(body: Dict[str, Any]) -> str:
        value = body.get("id") or body.get("task_id")
        if isinstance(value, (str, int)):
            return str(value)
        data = body.get("data")
        if isinstance(data, dict):
            value = data.get("id") or data.get("task_id")
            if isinstance(value, (str, int)):
                return str(value)
        return ""

    def _collect_video_urls(self, body: Dict[str, Any]) -> List[str]:
        out: List[str] = []
        self._collect_urls_recursive(body, out, parent_key="")
        seen = set()
        result: List[str] = []
        for url in out:
            if url in seen:
                continue
            seen.add(url)
            result.append(url)
        return result

    def _collect_urls_recursive(self, value: Any, out: List[str], parent_key: str) -> None:
        if isinstance(value, str):
            raw = value.strip()
            if raw.startswith("http://") or raw.startswith("https://"):
                if self._looks_like_video_url(raw, parent_key):
                    out.append(raw)
            return
        if isinstance(value, list):
            for item in value:
                self._collect_urls_recursive(item, out, parent_key=parent_key)
            return
        if isinstance(value, dict):
            for key, item in value.items():
                self._collect_urls_recursive(item, out, parent_key=str(key))

    @staticmethod
    def _looks_like_video_url(url: str, key: str) -> bool:
        key_l = (key or "").lower()
        if key_l in {"video_url", "video", "url"} or key_l.endswith("video_url") or key_l.endswith("video_urls"):
            return True
        lower = url.lower().split("?", 1)[0]
        video_ext = (".mp4", ".mov", ".avi", ".mkv", ".wmv", ".flv", ".webm", ".m4v", ".3gp", ".mpeg", ".mpg")
        return lower.endswith(video_ext)

    def _post_json(self, url: str, payload: Dict[str, Any], err_prefix: str) -> Dict[str, Any]:
        res = requests.post(url, headers=self.header, json=payload, timeout=60)
        return self._decode_json_response(res, err_prefix)

    def _post_form(self, url: str, payload: Dict[str, Any], err_prefix: str) -> Dict[str, Any]:
        data: Dict[str, Any] = {}
        files_field: List[str] = []
        for key, value in payload.items():
            if key == "files":
                if isinstance(value, list):
                    files_field = [str(x).strip() for x in value if str(x).strip()]
                continue
            data[key] = value

        # form-data 里重复 files 字段，按顺序传入 @1/@2 对应的资源
        multipart = [("files", (None, x)) for x in files_field]
        headers = dict(self.header)
        headers.pop("Content-Type", None)
        res = requests.post(url, headers=headers, data=data, files=multipart, timeout=60)
        return self._decode_json_response(res, err_prefix)

    def _get_json(self, url: str, err_prefix: str) -> Dict[str, Any]:
        res = requests.get(url, headers=self.header, timeout=60)
        return self._decode_json_response(res, err_prefix)

    @staticmethod
    def _decode_json_response(res: requests.Response, err_prefix: str) -> Dict[str, Any]:
        if not res.ok:
            preview = (res.text or "").strip()[:500]
            raise WorkerError(f"{err_prefix}: status={res.status_code}, body={preview}")
        try:
            body = res.json()
        except Exception as exc:
            preview = (res.text or "").strip()[:500]
            raise WorkerError(f"{err_prefix}: 返回JSON无效: {preview}") from exc
        if not isinstance(body, dict):
            raise WorkerError(f"{err_prefix}: 返回必须是JSON对象")
        return body

    @staticmethod
    def _to_int(value: Any, default: int) -> int:
        try:
            if value is None:
                return default
            return int(value)
        except Exception:
            return default

    @staticmethod
    def _parse_bool(value: Any) -> Optional[bool]:
        if isinstance(value, bool):
            return value
        if isinstance(value, (int, float)):
            if value == 1:
                return True
            if value == 0:
                return False
            return None
        if isinstance(value, str):
            lowered = value.strip().lower()
            if lowered in {"1", "true", "yes", "y", "on"}:
                return True
            if lowered in {"0", "false", "no", "n", "off"}:
                return False
        return None

    def _normalize_option(self, option: Dict[str, Any]) -> Dict[str, Any]:
        out: Dict[str, Any] = dict(option)
        for key in ("draft", "watermark"):
            if key not in out:
                continue
            parsed = self._parse_bool(out.get(key))
            if parsed is not None:
                out[key] = parsed
        return out
