<?php
// helpers/db_profiler.php
// Provide a PDOStatement subclass that measures execute() duration and logs slow queries

if (!class_exists('ProfilingStatement')) {
    class ProfilingStatement extends PDOStatement
    {
        protected function __construct()
        {
            // PDO will call this
        }

        #[\ReturnTypeWillChange]
        public function execute($input_parameters = null)
        {
            $start = microtime(true);
            $res = parent::execute($input_parameters);
            $dur = (microtime(true) - $start) * 1000.0;
            $sql = $this->queryString ?? '';

            // Detect timezone-related queries (e.g. pg_timezone_names, SET timezone, AT TIME ZONE)
            $tzPattern = '/pg_timezone_names|SET\s+timezone|AT\s+TIME\s+ZONE|timezone_name|pg_timezone/i';
            if (preg_match($tzPattern, $sql)) {
                if (function_exists('log_timezone_query_entry')) {
                    log_timezone_query_entry($sql, $input_parameters ?? [], $dur);
                } elseif (function_exists('log_slow_query_entry')) {
                    // fallback: tag entry in slow queries log
                    log_slow_query_entry('[TIMEZONE] ' . $sql, $input_parameters ?? [], $dur);
                }
            }

            if (function_exists('log_slow_query_entry')) {
                // log queries longer than 50ms
                if ($dur > 50) {
                    log_slow_query_entry($sql, $input_parameters ?? [], $dur);
                }
            }
            return $res;
        }
    }
}
