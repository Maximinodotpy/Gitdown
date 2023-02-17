<?php

class GDLogger {
    private $outputPath;
    private $startTime;
    private $log;

    function __construct() {
        $this->startTime = round(microtime(true) * 1000);

        $this->log = [
            'all'=> [],
            'infos'=> [],
            'warnings'=> [],
            'errors'=> [],
        ];
    }

    function __destruct() {
        $this->saveLog();
    }

    function saveLog() {
        file_put_contents($this->outputPath, json_encode($this->log, JSON_PRETTY_PRINT));
    }

    public function info($handle, $description) {
        $this->insertLog($handle, $description, 'info');
    }
    public function warning($handle, $description) {
        $this->insertLog($handle, $description, 'warning');
    }
    public function errror($handle, $description) {
        $this->insertLog($handle, $description, 'errror');
    }

    private function insertLog($handle, $description = '', $type = 'info') {

        $logData = [
            'handle' => $handle,
            'description' => $description,
            'type' => $type,
            'time' => round(microtime(true) * 1000),
            'time_passed' => round(microtime(true) * 1000) - $this->startTime,
        ];

        array_push($this->log['all'], $logData);
        array_push($this->log[$type.'s'], $logData);
    }
}