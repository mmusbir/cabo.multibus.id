Diagnostics tools

`tools/diagnose_queries.php` runs `EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON)` for representative queries and writes results to `logs/query_diagnostics.json`.

Usage:

```bash
php tools/diagnose_queries.php
```

Requirements:
- Database reachable via `DATABASE_URL` or `.env` in project root.
- PHP CLI and PDO_PGSQL extension installed.

If database is not available locally, run the script against a staging instance by setting `DATABASE_URL` env var:

```bash
# example
set DATABASE_URL=postgres://user:pass@db-host:5432/dbname
php tools/diagnose_queries.php
```
