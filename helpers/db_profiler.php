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
            if (function_exists('log_slow_query_entry')) {
                // log queries longer than 50ms
                if ($dur > 50) {
                    $sql = $this->queryString ?? '';
                    log_slow_query_entry($sql, $input_parameters ?? [], $dur);
                }
            }
            return $res;
        }
    }
}
