<?php

namespace D\Process;

abstract class Application {
	protected static $pidFilePattern = 'unknown.{PID}.pid';
    protected static $pidFolder = '/var/run/unknownProgram/';
	
	public function __construct() {
		// empty for now
	}

    abstract public function run();
    
    public static function isPidStored() {
    	return TRUE;
    }
    
    public static function storePid($pid = NULL) {
		if(!isset(static::$pidFolder)) {
			throw new \D\Process\Exception('pid_folder is not set');
		}
		
		if(!$pid) {
			$pid = posix_getpid();
		}
		if(FALSE === touch(static::$pidFolder . static::getFileName($pid))) {
			throw new \D\Process\Exception('folder [' . static::$pidFolder . '] is not writable');
		}
    }

    /**
     * @param int|NULL $pid
     */
    public static function unlinkPid($pid = NULL) {
		if(!$pid){
			$pid = posix_getpid();
		}
		unlink(static::$pidFolder . static::getFileName($pid));
    }
    
	/*
	* @return int[] process identifiers
	*/
	public static function getPids() {
		$pids = array();
		if (!isset(static::$pidFolder)){
            throw new \D\Process\Exception('pid_folder is not set');
        }
		
		$dir = opendir(static::$pidFolder);
		while($file = readdir($dir)) {
			if(preg_match('/' . static::getFilenamePattern() . '/', $file, $m)){
				$pids[] = (int)$m[1];
			}
		}
		closedir($dir);
		
		return $pids;
	}
    
    /*
     * based on pid
     * 
     * @param integer $pid
     * @return string $filename
     */
    private static function getFileName($pid) {
    	return str_replace('{PID}', $pid, static::$pidFilePattern);
    }

    /**
     * @return string
     */
    private static function getFilenamePattern() {
    	return str_replace(array('{PID}', '.'), array('(\d+)', '\.'), static::$pidFilePattern);
    }
}