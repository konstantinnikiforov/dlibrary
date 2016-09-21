<?php

namespace D;

class Process {
    /**
     * @var Process\Application
     */
    private $application;

    /**
     * @var int
     */
    protected $pid;

    /**
     * @var \D\Semaphore
     */
    protected $semaphore;

    /**
     * @param \D\Process\Application $application
     * @throws Process\Exception
     */
    public function __construct($application) {
        if(!$application instanceof \D\Process\Application) {
            throw new \D\Process\Exception('Object does not implement \D\Process\Application');
        }
        
        $this->application = $application;
    }
    
    public function start() {
        /** @var \D\Process\Control $processControl */
        $processControl = \D\Process\Control::getInstance();
        if($this->semaphore instanceof \D\Semaphore) {
        	$this->pid = $processControl->queueProcess($this->application, $this->semaphore);
        }else{
        	$this->pid = $processControl->queueProcess($this->application);
        }
    }
    
    public function stop() {
        //
    }
    
    public function getPid() {
        return $this->pid;
    }

    /**
     * @param \D\Semaphore $semaphore
     */
    public function setSemaphore($semaphore){
    	$this->semaphore = $semaphore;
    }
}
