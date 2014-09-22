<?php
/**
* BackQ
*
* Copyright (c) 2014, Sergey Shilko (contact@sshilko.com)
*
* @author Sergey Shilko
* @see https://github.com/sshilko/backq
*
**/
namespace BackQ;

class Logger implements \ApnsPHP_Log_Interface
{
    protected $logFile;

    public function __construct($logFile) {
        $this->logFile = $logFile;
    }

    public function log($sMessage, $debug = false) {
        if (!$debug && (false !== strpos($sMessage, 'INFO:') || false !== strstr($sMessage, 'STATUS:'))) {
            return;
        }
        
        global $config;        
        $log_file = $config['base_dir'] . $this->logFile;
        if ($log_handler = fopen($log_file, 'a')) {
            fwrite($log_handler, date('Y-m-d H:i:s') . ' - ' . getmypid() . ' - ' . trim($sMessage)."\n");            
            fclose($log_handler);
        }          
    }

}
