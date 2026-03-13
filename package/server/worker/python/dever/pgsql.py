from __future__ import annotations

import threading
from typing import Any, Dict, Optional, Sequence, Tuple

from dever.core import Dever
from dever.error import WorkerError


class PgSQL:
    _pool = None
    _prefix = ""
    _lock = threading.Lock()
    _last_error: Optional[Exception] = None

    @classmethod
    def get(cls, *, fail_fast: bool = False):
        if cls._pool is not None:
            return cls._pool

        try:
            import psycopg2
            from psycopg2 import pool
        except Exception as e:
            raise WorkerError("缺少依赖 psycopg2-binary，请安装：pip install psycopg2-binary") from e

        db_cfg = cls._load_db_config()
        host, port = cls._parse_host(db_cfg.get("host"))
        user = (db_cfg.get("user") or "").strip()
        password = db_cfg.get("pwd") or db_cfg.get("password") or ""
        dbname = (db_cfg.get("dbname") or db_cfg.get("db") or "").strip()
        if not host or not user or not dbname:
            msg = "database.default 配置不完整，需包含 host/user/dbname"
            if fail_fast:
                raise WorkerError(msg)
            return None

        cls._prefix = str(db_cfg.get("prefix") or "").strip()
        max_conns = int(db_cfg.get("maxOpenConns") or 5)
        if max_conns <= 0:
            max_conns = 5

        try:
            with cls._lock:
                if cls._pool is not None:
                    return cls._pool
                cls._pool = pool.SimpleConnectionPool(
                    1,
                    max_conns,
                    host=host,
                    port=port,
                    user=user,
                    password=password,
                    dbname=dbname,
                )
                conn = cls._pool.getconn()
                conn.autocommit = True
                cls._pool.putconn(conn)
                cls._last_error = None
                return cls._pool
        except Exception as e:
            cls._pool = None
            cls._last_error = e
            if fail_fast:
                raise WorkerError(f"PostgreSQL 连接失败: {e}") from e
            return None

    @classmethod
    def find(cls, sql: str, params: Optional[Sequence[Any]] = None) -> Optional[Dict[str, Any]]:
        return cls._fetch(sql, params, one=True)

    @classmethod
    def fetch(cls, sql: str, params: Optional[Sequence[Any]] = None) -> list[Dict[str, Any]]:
        rows = cls._fetch(sql, params, one=False)
        return rows if isinstance(rows, list) else []

    @classmethod
    def execute(cls, sql: str, params: Optional[Sequence[Any]] = None) -> int:
        conn = cls._get_conn(fail_fast=True)
        try:
            with conn.cursor() as cur:
                cur.execute(sql, params or [])
                return cur.rowcount
        finally:
            cls._put_conn(conn)

    @classmethod
    def insert(cls, table: str, data: Dict[str, Any]) -> int:
        columns = []
        values = []
        for key, value in data.items():
            if key == "id":
                continue
            columns.append(key)
            values.append(value)
        if not columns:
            return 0
        placeholders = ", ".join(["%s"] * len(values))
        sql = f"INSERT INTO {table} ({', '.join(columns)}) VALUES ({placeholders})"
        return cls.execute(sql, values)

    @classmethod
    def update(cls, table: str, data: Dict[str, Any], row_id: Any) -> int:
        columns = []
        values = []
        for key, value in data.items():
            if key == "id":
                continue
            columns.append(f"{key} = %s")
            values.append(value)
        values.append(row_id)
        if not columns:
            return 0
        sql = f"UPDATE {table} SET {', '.join(columns)} WHERE id = %s"
        return cls.execute(sql, values)

    @classmethod
    def table(cls, name: str) -> str:
        name = (name or "").strip()
        if not name:
            return ""
        cls._ensure_prefix()
        if not cls._prefix or name.startswith(cls._prefix + "_"):
            return name
        return f"{cls._prefix}_{name}"

    @classmethod
    def last_error(cls) -> Optional[Exception]:
        return cls._last_error

    @classmethod
    def _fetch(cls, sql: str, params: Optional[Sequence[Any]], *, one: bool):
        conn = cls._get_conn(fail_fast=True)
        try:
            from psycopg2.extras import RealDictCursor

            with conn.cursor(cursor_factory=RealDictCursor) as cur:
                cur.execute(sql, params or [])
                if one:
                    row = cur.fetchone()
                    return dict(row) if row else None
                rows = cur.fetchall()
                return [dict(r) for r in rows]
        finally:
            cls._put_conn(conn)

    @classmethod
    def _get_conn(cls, *, fail_fast: bool) -> Any:
        pool = cls.get(fail_fast=fail_fast)
        if pool is None:
            if fail_fast:
                raise WorkerError("PostgreSQL 连接池不可用")
            return None
        return pool.getconn()

    @classmethod
    def _put_conn(cls, conn: Any) -> None:
        if cls._pool is None or conn is None:
            return
        try:
            cls._pool.putconn(conn)
        except Exception:
            pass

    @classmethod
    def _load_db_config(cls) -> Dict[str, Any]:
        cfg = Dever.readConfig("database") or {}
        default = cfg.get("default")
        if isinstance(default, dict):
            return default
        return cfg if isinstance(cfg, dict) else {}

    @classmethod
    def _parse_host(cls, host: Any) -> Tuple[str, int]:
        raw = (host or "").strip()
        if not raw:
            return "", 5432
        if ":" in raw:
            h, p = raw.rsplit(":", 1)
            try:
                return h.strip(), int(p)
            except ValueError:
                return raw, 5432
        return raw, 5432

    @classmethod
    def _ensure_prefix(cls) -> None:
        if cls._prefix:
            return
        db_cfg = cls._load_db_config()
        cls._prefix = str(db_cfg.get("prefix") or "").strip()
