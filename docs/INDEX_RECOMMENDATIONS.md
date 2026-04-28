Index recommendations for common slow queries

Run these on your PostgreSQL instance (adjust schema/table names if needed):

-- Schedules: speed lookups by route/date/time
CREATE INDEX IF NOT EXISTS idx_schedules_rute_dow_jam ON schedules (rute, dow, jam);
CREATE INDEX IF NOT EXISTS idx_schedules_rute ON schedules (rute);

-- Bookings: common filters by route + date + time
CREATE INDEX IF NOT EXISTS idx_bookings_rute_tanggal_jam ON bookings (rute, tanggal, jam);
CREATE INDEX IF NOT EXISTS idx_bookings_tanggal ON bookings (tanggal);

-- Segments & Routes
CREATE INDEX IF NOT EXISTS idx_segments_rute ON segments (rute);
CREATE INDEX IF NOT EXISTS idx_routes_name ON routes (name);

-- Units: lookup by nopol
CREATE INDEX IF NOT EXISTS idx_units_nopol ON units (nopol);

-- Luggage/service lookups
CREATE INDEX IF NOT EXISTS idx_luggage_services_name ON luggage_services (name);

-- Partial/example index (payments/filter)
CREATE INDEX IF NOT EXISTS idx_bookings_unpaid ON bookings (tanggal) WHERE pembayaran <> 'Lunas';

Notes:
- Test each index with EXPLAIN ANALYZE before and after.
- Avoid adding indexes blindly; they speed reads but slow writes.
- Consider multi-column indexes tailored to your most frequent WHERE/ORDER combinations.
