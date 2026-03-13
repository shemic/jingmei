# Docker 启动说明

## 前置条件
- 已安装 Docker 与 Docker Compose 插件（命令为 `docker compose`）。
- 在项目根目录执行以下命令。
- PostgreSQL 数据目录会落在 `/data/origin/` 下。

## 启动
```bash
docker compose -f docker/docker-compose.yml up -d
```

## 向量扩展（pgvector）
- `app-postgres` 使用 `pgvector/pgvector:pg15` 镜像，并在首次初始化时自动执行 `CREATE EXTENSION IF NOT EXISTS vector;`。
- 如果你已有数据目录（`/data/origin/app_postgres`）且未生效，请手动执行：
```bash
docker exec -it app-postgres psql -U app -d app -c "CREATE EXTENSION IF NOT EXISTS vector;"
```

## 知识库/记忆分区与向量索引（多模态 768 维）
- 初始化脚本：`docker/initdb/app-postgres-knowledge-memory-partitions.sql`（按 `project_id` 哈希分区，32 个分区）
- 向量索引：`hnsw` + 余弦相似度
- 适用范围：仅首次初始化自动执行；已有数据目录需手动执行

```bash
docker exec -it app-postgres psql -U app -d app -f /docker-entrypoint-initdb.d/app-postgres-knowledge-memory-partitions.sql
```

注意：
- 已存在的非分区表不会被自动转换，需要你确认迁移策略（新表回填/停机迁移）。

## 查看状态
```bash
docker compose -f docker/docker-compose.yml ps
```

## 停止并清理容器
```bash
docker compose -f docker/docker-compose.yml down
```

## PostgreSQL 状态与数据查看

### 状态检查（命令行）
```bash
docker compose -f docker/docker-compose.yml ps
docker exec -it app-postgres pg_isready -U app -d app
docker exec -it temporal-postgres pg_isready -U temporal -d temporal
```

### 数据查看（命令行）
```bash
docker exec -it app-postgres psql -U app -d app
docker exec -it temporal-postgres psql -U temporal -d temporal
```

### 数据查看（Web 工具：adminer）
```text
http://localhost:8081
```

连接信息：
- app 数据库：系统=PostgreSQL，服务器=postgres_app，用户名=app，密码=app，数据库=app
- temporal 数据库：系统=PostgreSQL，服务器=postgres，用户名=temporal，密码=temporal，数据库=temporal

## Temporal 查看方式

### Temporal UI（Web）
```text
http://localhost:8233
```
