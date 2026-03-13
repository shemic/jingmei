from __future__ import annotations

import ast
import json
import re
from typing import Any, Dict, List, Optional, Set, Tuple

TYPE_ORDER: List[str] = [
    "image",
    "video",
    "audio",
    "pdf",
    "word",
    "excel",
    "ppt",
    "txt",
    "md",
    "file",
]
TYPES: Set[str] = set(TYPE_ORDER)
SKIP: Set[str] = {"text", "rich"}

TAG_RE = re.compile(r"(?is)<shemic-(quote|file)\b([^>]*)>(.*?)</shemic-\1>")
ATTR_RE = re.compile(r"([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*([\"'])(.*?)\2", re.S)


class Prompt:
    @staticmethod
    def parse_modal(model_raw: Any, default_model: str = "") -> Tuple[str, str]:
        raw = str(model_raw or "").strip()
        if not raw:
            return default_model.strip(), "default"
        if "@" not in raw:
            return raw, "default"
        model, mode = raw.split("@", 1)
        model_name = model.strip() or default_model.strip() or raw
        mode_name = mode.strip().lower() or "default"
        return model_name, mode_name

    @staticmethod
    def has_shemic_tags(prompt: Any) -> bool:
        s = str(prompt or "").lower()
        return "<shemic-quote" in s or "<shemic-file" in s

    @staticmethod
    def get_input(
        input_data: Any,
        extract_types: Optional[Any] = None,
        mode: str = "default",
    ) -> Dict[str, Any]:
        marker = Prompt._normalize_marker(mode)
        mention_mode = bool(marker)
        if not isinstance(input_data, dict):
            if mention_mode:
                return {"prompt": "", "file": [], "option": {}}
            return {"prompt": "", "file": {}, "option": {}}

        prompt_text = str(input_data.get("prompt", input_data.get("input", "")) or "")
        option = input_data.get("option")
        out_option: Dict[str, Any] = option if isinstance(option, dict) else {}
        wanted = Prompt._wanted(extract_types)
        file_raw = Prompt._resolve_file_raw(input_data)

        if mention_mode:
            return Prompt._build_mention(prompt_text, file_raw, out_option, wanted, marker)
        return Prompt._build_input(prompt_text, file_raw, out_option, wanted)

    @staticmethod
    def mention(input_data: Any, extract_types: Optional[Any] = None) -> Dict[str, Any]:
        return Prompt.get_input(input_data, extract_types=extract_types, mode="@")

    @staticmethod
    def _build_input(prompt: str, file_raw: Any, option: Dict[str, Any], wanted: Set[str]) -> Dict[str, Any]:
        file_map = Prompt._parse_file_payload(file_raw)
        from_tags = Prompt._extract_from_tags(prompt, wanted)

        # 先取 shemic 标签，再与 input.file 去重合并
        merged: Dict[str, List[str]] = {}
        for t in TYPE_ORDER:
            if t not in wanted:
                continue
            vals: List[str] = []
            vals.extend(from_tags.get(t, []))
            vals.extend(file_map.get(t, []))
            vals = Prompt._uniq(vals)
            if vals:
                merged[t] = vals

        return {
            "prompt": prompt,
            "file": merged,
            "option": option,
        }

    @staticmethod
    def _build_mention(
        prompt: str,
        file_raw: Any,
        option: Dict[str, Any],
        wanted: Set[str],
        marker: str,
    ) -> Dict[str, Any]:
        file_map = Prompt._parse_file_payload(file_raw)
        typed_files: Dict[str, List[str]] = {}
        for t in TYPE_ORDER:
            if t not in wanted:
                continue
            vals = file_map.get(t, [])
            if vals:
                typed_files[t] = vals

        files: List[str] = []
        file_index: Dict[str, int] = {}

        def add_file(v: str) -> int:
            existing = file_index.get(v)
            if existing is not None:
                return existing
            files.append(v)
            idx = len(files)
            file_index[v] = idx
            return idx

        def repl(m: re.Match[str]) -> str:
            attrs = Prompt._attrs(m.group(2))
            raw_type = attrs.get("data-type", "")
            t = Prompt._norm_type(raw_type)
            if not t or t in SKIP or t not in wanted:
                return m.group(0)

            inner = m.group(3)
            marker_type = Prompt._marker_type(inner)
            vals: List[str] = []
            if marker_type:
                marker_norm = Prompt._norm_type(marker_type)
                if marker_norm in wanted:
                    vals = typed_files.get(marker_norm, [])
            else:
                vals = Prompt._split(inner)
                if not vals:
                    vals = typed_files.get(t, [])
            if not vals:
                return m.group(0)

            refs: List[str] = []
            for v in vals:
                refs.append(f"[{marker}{add_file(v)}]")
            return ",".join(refs)

        new_prompt = TAG_RE.sub(repl, prompt)
        for t in TYPE_ORDER:
            if t not in wanted:
                continue
            for v in typed_files.get(t, []):
                add_file(v)
        return {
            "prompt": new_prompt,
            "file": files,
            "option": option,
        }

    @staticmethod
    def _extract_from_tags(prompt: str, wanted: Set[str]) -> Dict[str, List[str]]:
        out: Dict[str, List[str]] = {}
        if not prompt or not Prompt.has_shemic_tags(prompt):
            return out

        for m in TAG_RE.finditer(prompt):
            attrs = Prompt._attrs(m.group(2))
            raw_type = attrs.get("data-type", "")
            t = Prompt._norm_type(raw_type)
            if not t or t in SKIP or t not in wanted:
                continue
            vals = Prompt._split(m.group(3))
            if not vals:
                continue
            out.setdefault(t, []).extend(vals)

        return {k: Prompt._uniq(v) for k, v in out.items() if v}

    @staticmethod
    def _parse_file_payload(raw: Any) -> Dict[str, List[str]]:
        data: Any = raw
        if data is None:
            return {}

        if isinstance(data, str):
            s = data.strip()
            if not s:
                return {}
            s = (
                s.replace("，", ",")
                .replace("“", '"')
                .replace("”", '"')
                .replace("‘", "'")
                .replace("’", "'")
            )
            try:
                data = json.loads(s)
            except Exception:
                try:
                    data = ast.literal_eval(s)
                except Exception:
                    return {}

        if isinstance(data, list):
            vals = Prompt._vals(data)
            return {"file": Prompt._uniq(vals)} if vals else {}

        if not isinstance(data, dict):
            return {}

        out: Dict[str, List[str]] = {}
        for k, v in data.items():
            t = Prompt._norm_type(k)
            vals = Prompt._vals(v)
            if vals:
                out.setdefault(t, []).extend(vals)
        return {k: Prompt._uniq(v) for k, v in out.items() if v}

    @staticmethod
    def _resolve_file_raw(input_data: Dict[str, Any]) -> Any:
        base = input_data.get("file")
        extra: Dict[str, Any] = {}
        for t in TYPE_ORDER:
            if t in input_data and input_data.get(t) is not None:
                extra[t] = input_data.get(t)
        if not extra:
            return base
        if base is None:
            return extra
        if not isinstance(base, dict):
            merged: Dict[str, Any] = {"file": base}
            merged.update(extra)
            return merged
        merged = dict(base)
        for k, v in extra.items():
            if k not in merged:
                merged[k] = v
                continue
            left = Prompt._vals(merged.get(k))
            right = Prompt._vals(v)
            merged[k] = Prompt._uniq(left + right)
        return merged

    @staticmethod
    def _attrs(raw: str) -> Dict[str, str]:
        out: Dict[str, str] = {}
        for m in ATTR_RE.finditer(raw or ""):
            k = (m.group(1) or "").strip().lower()
            v = (m.group(3) or "").strip()
            if k:
                out[k] = v
        return out

    @staticmethod
    def _marker_type(raw: Any) -> str:
        text = str(raw or "").strip()
        if not text:
            return ""
        m = re.fullmatch(r"(?is)type\s*=\s*([a-zA-Z0-9_-]+)", text)
        if not m:
            return ""
        return (m.group(1) or "").strip().lower()

    @staticmethod
    def _normalize_marker(mode: Any) -> str:
        raw = str(mode or "").strip()
        if not raw:
            return ""
        lower = raw.lower()
        if lower in {"default", "none", "false", "0"}:
            return ""
        if lower == "mention":
            return "@"
        return raw

    @staticmethod
    def _wanted(extract_types: Optional[Any]) -> Set[str]:
        raw: Set[str] = set()
        if extract_types is None:
            raw = set(TYPES)
        elif isinstance(extract_types, str):
            raw = {x.strip().lower() for x in extract_types.split(",") if x.strip()}
        elif isinstance(extract_types, (list, tuple, set)):
            raw = {str(x or "").strip().lower() for x in extract_types if str(x or "").strip()}
        else:
            v = str(extract_types or "").strip().lower()
            raw = {v} if v else set()

        out: Set[str] = set()
        for t in raw:
            n = Prompt._norm_type(t)
            if n in TYPES:
                out.add(n)
        return out

    @staticmethod
    def _norm_type(v: Any) -> str:
        t = str(v or "").strip().lower()
        if not t:
            return "file"
        if t in {"doc", "docx", "word"}:
            return "word"
        if t in {"xls", "xlsx", "excel"}:
            return "excel"
        if t in {"ppt", "pptx"}:
            return "ppt"
        if t in {"markdown", "md"}:
            return "md"
        if t in {"text", "txt"}:
            return "txt"
        if t in {"image", "video", "audio", "pdf", "file"}:
            return t
        if t in {"office", "attachments", "attachment", "files"}:
            return "file"
        return "file"

    @staticmethod
    def _vals(v: Any) -> List[str]:
        if v is None:
            return []
        if isinstance(v, str):
            return Prompt._split(v)
        if isinstance(v, list):
            out: List[str] = []
            for item in v:
                if isinstance(item, str):
                    out.extend(Prompt._split(item))
            return out
        if isinstance(v, dict):
            u = str(v.get("url", "")).strip()
            return [u] if u else []
        return []

    @staticmethod
    def _split(s: Any) -> List[str]:
        text = str(s or "").strip()
        if not text:
            return []
        parts = [x.strip() for x in text.replace("，", ",").split(",")]
        return [x for x in parts if x]

    @staticmethod
    def _uniq(values: List[str]) -> List[str]:
        seen: Set[str] = set()
        out: List[str] = []
        for x in values:
            if x in seen:
                continue
            seen.add(x)
            out.append(x)
        return out
