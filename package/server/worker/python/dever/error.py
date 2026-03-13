from __future__ import annotations

from typing import Optional

class Error(Exception):
    """
    Dever 平台基础异常
    """
    pass


class RetryableError(Error):
    """
    表示：该异常在语义上是“可重试的”
    """
    retryable: bool = True


class WorkerError(RetryableError):
    """
    Worker 调用层统一异常（provider 无关）
    """
    def __init__(
        self,
        message: str,
        *,
        retryable: bool = False,
        cause: Optional[BaseException] = None,
    ):
        super().__init__(message)
        self.retryable = retryable
        self.cause = cause


class WorkerHttpError(WorkerError):
    """
    Worker 返回了 HTTP 错误码
    """
    def __init__(
        self,
        status_code: int,
        body: str,
        *,
        retryable: bool,
        cause: Optional[BaseException] = None,
    ):
        preview = (body or "").strip()[:800]
        super().__init__(
            f"Worker HTTP {status_code}: {preview}",
            retryable=retryable,
            cause=cause,
        )
        self.status_code = status_code
        self.body = body