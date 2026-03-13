from __future__ import annotations

import base64
import hashlib
import hmac
import json
import os
import time
from urllib.parse import quote, urlparse
from typing import Any, Dict, List, Optional

import requests

from dever.core import Dever
from dever.error import WorkerError
from dever.pgsql import PgSQL as Db


class Qiniu:
    CHUNK_SIZE = 4 * 1024 * 1024
    CHUNK_UPLOAD_THRESHOLD = 8 * 1024 * 1024

    def __init__(self, config: Optional[Dict[str, Any]] = None):
        cfg = config or Dever.readConfig("qiniu") or {}
        self.access_key = str(cfg.get("accessKey", "")).strip()
        self.secret_key = str(cfg.get("secretKey", "")).strip()
        self.bucket = str(cfg.get("bucket", "")).strip()
        self.region = str(cfg.get("region", "z0")).strip()
        self.domain = str(cfg.get("domain", "")).strip().rstrip("/")
        self.token_ttl = int(cfg.get("tokenTTL", 3600))

        if not self.access_key or not self.secret_key or not self.bucket:
            raise WorkerError("七牛配置不完整（accessKey/secretKey/bucket）")

    def build_key(
        self,
        content_code: str,
        prefix: str = "model_generated",
        filename: str = "",
        ext: str = ".jpg",
    ) -> str:
        uid = self.get_uid_by_content_code(content_code)
        return self.build_qiniu_key(uid=uid, prefix=prefix, filename=filename, ext=ext)

    def build_qiniu_key(
        self,
        uid: int,
        prefix: str = "model_generated",
        filename: str = "",
        ext: str = ".jpg",
    ) -> str:
        clean_prefix = str(prefix or "").strip().strip("/")
        if not clean_prefix:
            clean_prefix = "model_generated"

        clean_uid = int(uid)
        if clean_uid <= 0:
            raise WorkerError("uid 必须大于 0")

        clean_filename = str(filename or "").strip()
        base = clean_filename
        if clean_filename:
            base = os.path.splitext(clean_filename)[0].strip()
        if not base:
            base = "file"

        raw_name = f"{clean_uid}_{base}"
        name = raw_name
        if "_cr_" not in base:
            name = hashlib.md5(raw_name.encode("utf-8")).hexdigest()

        normalized_ext = self._normalize_ext(ext).lstrip(".")
        dir_parts = self._split_by_pairs(name, 3)
        dir_path = "/".join(dir_parts)
        uid_segment = self._uid_segment(clean_uid)
        return f"{clean_prefix}/{uid_segment}/{dir_path}/{name}.{normalized_ext}"

    def get_uid_by_content_code(self, content_code: str) -> int:
        code = str(content_code or "").strip()
        if not code:
            raise WorkerError("content_code 不能为空")
        table = Db.table("user_content")
        try:
            row = Db.find(f"SELECT uid FROM {table} WHERE code = %s ORDER BY id DESC LIMIT 1", [code])
        except Exception as exc:
            raise WorkerError(f"查询 user_content uid 失败: {exc}") from exc

        if not row or row.get("uid") is None:
            raise WorkerError(f"未找到 content_code 对应的 uid: {code}")
        try:
            uid = int(row["uid"])
        except Exception as exc:
            raise WorkerError(f"content_code={code} 的 uid 无效") from exc
        if uid <= 0:
            raise WorkerError(f"content_code={code} 的 uid 无效")
        return uid

    def upload(
        self,
        source_url: str,
        content_code: str,
        *,
        prefix: str = "model_generated",
        file_type: str = "model_generated",
        index: int = 0,
        timeout: int = 60,
    ) -> Dict[str, Any]:
        uid = self.get_uid_by_content_code(content_code)
        ext = self.ext_from_url(source_url)
        filename = self.filename_from_url(source_url)
        if not filename:
            filename = f"file_{index}{ext}"
        key = self.build_qiniu_key(uid=uid, prefix=prefix, filename=filename, ext=ext)
        stored = self.upload_url(source_url, key, timeout=timeout)
        self.save_user_file(
            uid=uid,
            key=stored["key"],
            file_hash=str(stored.get("hash", "")).strip(),
            mime=str(stored.get("mime", "")).strip(),
            size=int(stored.get("size", 0) or 0),
            url=stored["url"],
            file_type=file_type,
        )
        return stored

    def upload_url(self, source_url: str, key: str, timeout: int = 60) -> Dict[str, Any]:
        source = (source_url or "").strip()
        if not source:
            raise WorkerError("源文件URL不能为空")
        with requests.get(source, stream=True, timeout=timeout) as file_resp:
            if not file_resp.ok:
                raise WorkerError(f"下载源文件失败: status={file_resp.status_code}")

            mime = str(file_resp.headers.get("Content-Type", "")).split(";", 1)[0].strip()
            total_size = self._parse_content_length(file_resp.headers.get("Content-Length"))
            ext = self.ext_from_url(source)

            if self._should_use_chunked_upload(ext=ext, mime=mime, size=total_size):
                body, uploaded_size = self._upload_by_chunks(key=key, stream_resp=file_resp, timeout=timeout)
            else:
                body, uploaded_size = self._upload_by_form(key=key, stream_resp=file_resp, timeout=timeout)

        out_key = str(body.get("key") or key)
        file_hash = str(body.get("hash") or body.get("etag") or "").strip()
        return {
            "key": out_key,
            "url": self.public_url(out_key),
            "hash": file_hash,
            "mime": mime,
            "size": uploaded_size,
            "raw": body,
        }

    def public_url(self, key: str) -> str:
        if self.domain:
            return f"{self.domain}/{key}"
        return key

    @staticmethod
    def ext_from_url(url: str) -> str:
        parsed = urlparse(str(url or ""))
        path = parsed.path or ""
        if "." not in path:
            return ".jpg"
        ext = path.rsplit(".", 1)[-1].lower().strip()
        if not ext:
            return ".jpg"
        return f".{ext}"

    @staticmethod
    def filename_from_url(url: str) -> str:
        parsed = urlparse(str(url or ""))
        path = parsed.path or ""
        return os.path.basename(path).strip()

    def _make_upload_token(self, key: str) -> str:
        deadline = int(time.time()) + max(self.token_ttl, 60)
        policy = {"scope": f"{self.bucket}:{key}", "deadline": deadline}
        policy_b64 = self._urlsafe_b64(json.dumps(policy, separators=(",", ":")).encode("utf-8"))
        sign = hmac.new(self.secret_key.encode("utf-8"), policy_b64.encode("utf-8"), hashlib.sha1).digest()
        encoded_sign = self._urlsafe_b64(sign)
        return f"{self.access_key}:{encoded_sign}:{policy_b64}"

    def _upload_host(self) -> str:
        mapping = {
            "z0": "https://up-z0.qiniup.com",
            "z1": "https://up-z1.qiniup.com",
            "z2": "https://up-z2.qiniup.com",
            "na0": "https://up-na0.qiniup.com",
            "as0": "https://up-as0.qiniup.com",
        }
        return mapping.get(self.region, "https://up.qiniup.com")

    @staticmethod
    def _urlsafe_b64(raw: bytes) -> str:
        return base64.urlsafe_b64encode(raw).decode("utf-8")

    @staticmethod
    def _normalize_ext(ext: str) -> str:
        value = (ext or "").strip().lower()
        if not value:
            return ".jpg"
        if value.startswith("."):
            return value
        return f".{value}"

    @staticmethod
    def _split_by_pairs(value: str, count: int) -> list[str]:
        if count <= 0:
            return []
        out: list[str] = []
        i = 0
        while i < len(value) and len(out) < count:
            out.append(value[i : i + 2])
            i += 2
        return out

    @staticmethod
    def _uid_segment(uid: int) -> str:
        # 基于 uid 生成固定分桶目录，便于按用户归档与清理。
        return hashlib.md5(str(uid).encode("utf-8")).hexdigest()[:8]

    def _upload_by_form(self, key: str, stream_resp: requests.Response, timeout: int) -> tuple[Dict[str, Any], int]:
        token = self._make_upload_token(key)
        content = stream_resp.content
        files = {"file": (key, content)}
        data = {"token": token, "key": key}
        upload_url = self._upload_host()
        up_resp = requests.post(upload_url, data=data, files=files, timeout=timeout)
        if not up_resp.ok:
            preview = (up_resp.text or "").strip()[:500]
            raise WorkerError(f"上传七牛失败: status={up_resp.status_code}, body={preview}")
        try:
            body = up_resp.json()
        except Exception as exc:
            preview = (up_resp.text or "").strip()[:500]
            raise WorkerError(f"七牛返回JSON无效: {preview}") from exc
        return body, len(content)

    def _upload_by_chunks(self, key: str, stream_resp: requests.Response, timeout: int) -> tuple[Dict[str, Any], int]:
        token = self._make_upload_token(key)
        upload_host = self._upload_host()
        headers = {"Authorization": f"UpToken {token}", "Content-Type": "application/octet-stream"}
        ctx_list: List[str] = []
        total_size = 0

        for chunk in stream_resp.iter_content(chunk_size=self.CHUNK_SIZE):
            if not chunk:
                continue
            chunk_size = len(chunk)
            mkblk_url = f"{upload_host}/mkblk/{chunk_size}"
            mkblk_resp = requests.post(mkblk_url, data=chunk, headers=headers, timeout=timeout)
            if not mkblk_resp.ok:
                preview = (mkblk_resp.text or "").strip()[:500]
                raise WorkerError(f"七牛分片上传失败: status={mkblk_resp.status_code}, body={preview}")
            try:
                mkblk_body = mkblk_resp.json()
            except Exception as exc:
                preview = (mkblk_resp.text or "").strip()[:500]
                raise WorkerError(f"七牛分片返回JSON无效: {preview}") from exc
            ctx = str(mkblk_body.get("ctx", "")).strip()
            if not ctx:
                raise WorkerError("七牛分片响应缺少 ctx")
            ctx_list.append(ctx)
            total_size += chunk_size

        if not ctx_list:
            raise WorkerError("分片上传失败：文件内容为空")

        # mkfile 的 key 参数使用 URL-safe Base64，并进行路径安全转义，避免解析失败
        key_b64 = self._urlsafe_b64(key.encode("utf-8"))
        mkfile_url = f"{upload_host}/mkfile/{total_size}/key/{quote(key_b64, safe='')}"
        mkfile_headers = {"Authorization": f"UpToken {token}", "Content-Type": "text/plain"}
        mkfile_data = ",".join(ctx_list)
        mkfile_resp = requests.post(mkfile_url, data=mkfile_data, headers=mkfile_headers, timeout=timeout)
        if not mkfile_resp.ok:
            preview = (mkfile_resp.text or "").strip()[:500]
            raise WorkerError(f"七牛合并分片失败: status={mkfile_resp.status_code}, body={preview}")
        try:
            body = mkfile_resp.json()
        except Exception as exc:
            preview = (mkfile_resp.text or "").strip()[:500]
            raise WorkerError(f"七牛合并返回JSON无效: {preview}") from exc
        return body, total_size

    def _should_use_chunked_upload(self, *, ext: str, mime: str, size: int) -> bool:
        if size >= self.CHUNK_UPLOAD_THRESHOLD:
            return True
        ext_l = (ext or "").lower()
        mime_l = (mime or "").lower()
        video_ext = {".mp4", ".mov", ".avi", ".mkv", ".wmv", ".flv", ".webm", ".m4v", ".3gp", ".mpeg", ".mpg"}
        if ext_l in video_ext:
            return True
        if mime_l.startswith("video/"):
            return True
        return False

    @staticmethod
    def _parse_content_length(value: Any) -> int:
        try:
            return int(value)
        except Exception:
            return 0


    def save_user_file(
        self,
        *,
        uid: int,
        key: str,
        file_hash: str,
        mime: str,
        size: int,
        url: str,
        file_type: str,
    ) -> None:
        table = Db.table("user_file")
        now = int(time.time())
        row = Db.find(f"SELECT id FROM {table} WHERE key = %s LIMIT 1", [key])
        data: Dict[str, Any] = {
            "uid": int(uid),
            "key": key,
            "hash": file_hash,
            "mime": mime,
            "size": int(size),
            "bucket": self.bucket,
            "domain": self.domain,
            "url": url,
            "type": (file_type or "model_generated"),
            "status": "uploaded",
            "udate": now,
        }
        if row and row.get("id"):
            set_sql = ", ".join(
                [
                    "uid = %s",
                    "hash = %s",
                    "mime = %s",
                    "size = %s",
                    "bucket = %s",
                    "domain = %s",
                    "url = %s",
                    "type = %s",
                    "status = %s",
                    "udate = %s",
                ]
            )
            params = [
                data["uid"],
                data["hash"],
                data["mime"],
                data["size"],
                data["bucket"],
                data["domain"],
                data["url"],
                data["type"],
                data["status"],
                data["udate"],
                key,
            ]
            Db.execute(f"UPDATE {table} SET {set_sql} WHERE key = %s", params)
            return
        data["cdate"] = now
        Db.insert(table, data)
