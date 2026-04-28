Performance actions and prioritized fixes

Summary
- The admin UI was making several full-table SELECTs and fetchAll() calls causing high memory and DB load. We added paginated AJAX for `units`, file-cache helpers, edge header helpers, and query profiling instrumentation.

High-priority fixes (apply ASAP)
1. Convert all large-list server-renders to paginated AJAX endpoints
   - Done for `units` (`admin/ajax/units.php`) and other lists already paginated (routes, bookings). Convert `charter_create.php`, `luggage_create.php`, and other fragments that `fetchAll` entire tables into AJAX/async selects or autocomplete endpoints.
2. Add explicit column lists and LIMIT for preview queries
   - Replace `SELECT *` with `SELECT id, nopol, merek, kapasitas, status` etc. to reduce payload.
3. Add indexes (see `docs/apply_indexes.sql`) and test with EXPLAIN ANALYZE.
4. Enable slow-query logging (implemented): `helpers/perf.php` + `helpers/db_profiler.php` are integrated; review `logs/slow_queries.log` after running workload.

Medium-priority
- Use cache for rarely-changing dropdowns (`routes`, `luggage_services`, `drivers`) via `cache_get()` with TTL and Surrogate-Key headers.
- Implement CDN purge integration using `Surrogate-Key` from `helpers/cache.php` for production.

Low-priority
- Review long-running PHP loops and large in-memory arrays; stream processing where possible.

How to run diagnostics (EXPLAIN) when DB is available
1. Ensure `DATABASE_URL` or `.env` contains DB connection.
2. Run:

```bash
php tools/diagnose_queries.php
```

Outputs written to `logs/query_diagnostics.json` and slow entries to `logs/slow_queries.log`.

Next recommended actions for you
- Start DB / provide `DATABASE_URL` so I can run `tools/diagnose_queries.php` and collect EXPLAIN results. I will then produce a focused list of the slowest queries and suggested index/rewrites.
- Run `psql -f docs/apply_indexes.sql` on a staging DB and test performance with `EXPLAIN ANALYZE`.

