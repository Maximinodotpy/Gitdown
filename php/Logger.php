<?php
namespace WP\Plugin\Gitdown;

class MGD_Logger {
    private $outputPath;
    private $startTime;
    private $log;

    function __construct($output) {
        $this->outputPath = $output;

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
        if (!MGD_DEBUG) return;
        if (!file_exists(dirname($this->outputPath))) mkdir(dirname($this->outputPath), 0777, true);
        file_put_contents($this->outputPath, json_encode($this->log, JSON_PRETTY_PRINT));
    }

    public function info($handle, $description = '') {
        $this->insertLog($handle, $description, 'info');
    }
    public function warning($handle, $description = '') {
        $this->insertLog($handle, $description, 'warning');
    }
    public function errror($handle, $description = '') {
        $this->insertLog($handle, $description, 'errror');
    }

    private function insertLog($handle, $description = '', $type = 'info') {

        if (!MGD_DEBUG) return;

        $currentTime = round(microtime(true) * 1000);

        $logData = [
            'handle' => $handle,
            'description' => $description,
            'type' => $type,
            'time' => $currentTime,
            'time_since_start' => $currentTime - $this->startTime,
        ];

        $allData = $logData;
        $typeData = $logData;

        if (count($this->log['all']) >= 1) {
            $lastTime = $this->log['all'][count($this->log['all'])-1]['time'];
            $allData['time_since_last'] = $currentTime - $lastTime;
        }
        if (count($this->log[$type.'s']) >= 1) {
            $lastTime = $this->log[$type.'s'][count($this->log[$type.'s'])-1]['time'];
            $typeData['time_since_last'] = $currentTime - $lastTime;
        }

        array_push($this->log['all'], $allData);
        array_push($this->log[$type.'s'], $typeData);
    }
}