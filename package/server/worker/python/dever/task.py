from __future__ import annotations

import hashlib
import random as rand
import threading
import time
import uuid
from typing import Any, Dict, Optional

from dever.publisher import RedisPublisher


class TaskReporter:
    _STATUS_MSG = {
        "start": "启动中",
        "run": "运行中",
        "upload": "上传中",
        "finish": "执行完成",
        "fail": "执行失败",
    }

    def __init__(
        self,
        *,
        project_code: Any,
        content_code: Any,
        content_version_id: Any = None,
        model: Any = None,
        task_id: Any = None,
        meta: Optional[Dict[str, Any]] = None,
        min_interval_sec: float = 1.0,
    ) -> None:
        project = str(project_code or "").strip()
        content = str(content_code or "").strip()
        version = 0
        try:
            version = int(content_version_id or 0)
        except Exception:
            version = 0

        if not project or not content:
            self.publisher = None
            self.stream_name = ""
        else:
            self.stream_name = f"shemic:{project}:{content}:{version}" if version > 0 else f"shemic:{project}:{content}"
            self.publisher = RedisPublisher(channel=self.stream_name)

        self.model = str(model or "").strip()
        self.task_id = str(task_id or "").strip() or f"task_{uuid.uuid4().hex}"
        self.meta = meta if isinstance(meta, dict) else {}
        self.node_id = str(self.meta.get("node_id") or "").strip()
        self.name = self._extract_name(self.meta)
        self.code = str(self.meta.get("code") or "").strip() or self._build_code(self.stream_name)
        self._last_status = ""
        self._last_progress = 0
        self._lock = threading.Lock()

        self._min_interval_sec = float(min_interval_sec) if min_interval_sec else 1.0
        if self._min_interval_sec <= 0:
            self._min_interval_sec = 1.0

        self._auto_thread: Optional[threading.Thread] = None
        self._auto_stop: Optional[threading.Event] = None
        self._auto_cfg: Dict[str, Any] = {}
        self._auto_msg: str = self._STATUS_MSG["run"]
        self._auto_status: str = "run"

    def set_task_id(self, task_id: Any) -> None:
        raw = str(task_id or "").strip()
        if raw:
            self.task_id = raw

    def emit(
        self,
        *,
        status: str,
        progress: Any,
        msg: Optional[str] = None,
        force: bool = False,
        random: Optional[Any] = None,
    ) -> None:
        if self.publisher is None:
            return
        norm = self._normalize_status(status)
        if norm not in self._STATUS_MSG:
            return

        with self._lock:
            random_cfg = random if isinstance(random, dict) else {}
            msg_text = msg or self._STATUS_MSG[norm]

            if norm in {"run", "upload"} and self._is_auto_progress_request(progress, random_cfg):
                floor = self._clamp(self._to_int(random_cfg.get("floor"), max(1, self._last_progress)), 1, 99)
                progress_num = self._normalize_progress(max(self._last_progress, floor))
                if force or norm != self._last_status or progress_num > self._last_progress:
                    self._publish_locked(norm=norm, msg=msg_text, progress_num=progress_num)
                self._start_auto_progress_locked(random_cfg, msg_text)
                return

            self._stop_auto_progress_locked()
            progress_num = self._normalize_progress(progress)
            if not force and norm == self._last_status and progress_num <= self._last_progress:
                return
            self._publish_locked(norm=norm, msg=msg_text, progress_num=progress_num)

    def current_progress(self) -> int:
        return self._last_progress

    def close(self) -> None:
        with self._lock:
            self._stop_auto_progress_locked()

    @staticmethod
    def _normalize_status(status: Any) -> str:
        s = str(status or "").strip().lower()
        if s == "upload":
            return "upload"
        if s in {"failed", "error"}:
            return "fail"
        if s == "finish":
            return "finish"
        if s == "start":
            return "start"
        if s == "run":
            return "run"
        return s

    @staticmethod
    def _extract_name(meta: Optional[Dict[str, Any]]) -> str:
        if not isinstance(meta, dict):
            return ""
        raw = meta.get("name")
        if raw is None:
            return ""
        return str(raw).strip()

    @staticmethod
    def _build_code(stream_name: str) -> str:
        raw = str(stream_name or "").strip()
        if not raw:
            return ""
        return hashlib.md5(raw.encode("utf-8")).hexdigest()

    def _normalize_progress(self, progress: Any) -> int:
        try:
            num = int(float(progress))
        except Exception:
            num = self._last_progress
        if num < 0:
            num = self._last_progress
        if num > 100:
            num = 100
        if num < self._last_progress:
            num = self._last_progress
        return num

    def _publish_locked(self, *, norm: str, msg: str, progress_num: int) -> None:
        event: Dict[str, Any] = {
            "id": self.node_id or self.task_id,
            "name": self.name,
            "code": self.code,
            "type": "task",
            "data": {
                "status": norm,
                "msg": msg,
                "progress": progress_num,
            },
        }
        self.publisher.emit(event)
        self._last_status = norm
        self._last_progress = progress_num

    @staticmethod
    def _is_auto_progress_request(progress: Any, random_cfg: Dict[str, Any]) -> bool:
        if not isinstance(random_cfg, dict) or not random_cfg:
            return False
        try:
            return int(float(progress)) == -1
        except Exception:
            return False

    def _start_auto_progress_locked(self, random_cfg: Dict[str, Any], msg: str) -> None:
        floor = self._clamp(self._to_int(random_cfg.get("floor"), max(self._last_progress, 1)), 1, 99)
        cap = self._clamp(self._to_int(random_cfg.get("cap"), 95), floor, 99)
        interval = self._to_float(random_cfg.get("interval"), self._min_interval_sec)
        if interval < 0.2:
            interval = 0.2
        level = self._to_int(random_cfg.get("level"), 5)
        if level < 1:
            level = 1

        self._auto_cfg = {
            "floor": floor,
            "cap": cap,
            "interval": interval,
            "level": level,
        }
        self._auto_msg = msg or self._STATUS_MSG["run"]
        self._auto_status = self._last_status if self._last_status in {"run", "upload"} else "run"

        if self._auto_thread is not None and self._auto_thread.is_alive():
            return

        stop = threading.Event()
        self._auto_stop = stop
        t = threading.Thread(target=self._auto_loop, args=(stop,), daemon=True)
        self._auto_thread = t
        t.start()

    def _stop_auto_progress_locked(self) -> None:
        stop = self._auto_stop
        self._auto_stop = None
        self._auto_thread = None
        self._auto_cfg = {}
        self._auto_status = "run"
        if stop is not None:
            stop.set()

    def _auto_loop(self, stop: threading.Event) -> None:
        while not stop.is_set():
            with self._lock:
                if self.publisher is None:
                    return
                if self._last_status not in {"run", "upload"}:
                    return
                cfg = dict(self._auto_cfg)
                if not cfg:
                    return
                auto_status = self._auto_status if self._auto_status in {"run", "upload"} else "run"

                floor = self._clamp(self._to_int(cfg.get("floor"), max(self._last_progress, 1)), 1, 99)
                cap = self._clamp(self._to_int(cfg.get("cap"), 95), floor, 99)
                if self._last_progress < floor:
                    self._publish_locked(norm=auto_status, msg=self._auto_msg, progress_num=floor)

                cur = self._last_progress
                if cur < cap:
                    level = self._to_int(cfg.get("level"), 5)
                    if level < 1:
                        level = 1
                    remain = cap - cur
                    step = rand.randint(1, min(level, remain))
                    nxt = cur + step
                    if nxt > cap:
                        nxt = cap
                    self._publish_locked(norm=auto_status, msg=self._auto_msg, progress_num=nxt)

                interval = self._to_float(cfg.get("interval"), self._min_interval_sec)
                if interval < 0.2:
                    interval = 0.2

            time.sleep(interval)

    @staticmethod
    def _to_int(v: Any, default: int) -> int:
        try:
            return int(float(v))
        except Exception:
            return default

    @staticmethod
    def _to_float(v: Any, default: float) -> float:
        try:
            return float(v)
        except Exception:
            return default

    @staticmethod
    def _clamp(v: int, lo: int, hi: int) -> int:
        if v < lo:
            return lo
        if v > hi:
            return hi
        return v
