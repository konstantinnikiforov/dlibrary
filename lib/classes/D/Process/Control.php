<?php

// Needed for signals to be triggered
declare( ticks = 1 );

namespace D\Process;

class Control extends \D\Singleton {
    /**
     * @var \D\Semaphore
     */
    protected $semaphore;

    /**
     * @var int
     */
    private $max_children;
    private $children;

    /**
     * @var \D\Process\Control
     */
    protected static $instance;

    /**
     * @var bool
     */
	private static $is_terminating;
    
    // Getters and setters
    public function setMaxChildren( $s )        { $this->max_children = $s; }
    public function setChildren( $s )           { $this->children = $s; }
    
    public function getMaxChildren( )           { return $this->max_children; }
    public function getChildren( )              { return $this->children; }
    public function getChildrenCount( )         { return count( $this->getChildren( ) ); }

    /**
     * @return bool
     */
	public static function isTerminating() {
		return static::$is_terminating;
	}

    /**
     * addChild
     * Add child to children array
     * @param   $pid    int PID of child to add
     * @return  void
     */
    public function addChild( $pid ) {
        $this->children[] = $pid;
    }

    /**
     * removeChild
     * Remove child from children array
     * @param   $pid    int PID of child to remove
     * @return  void
     */
    public function removeChild( $pid ) {
        foreach( $this->children as $k => $id ) {
            if( $id == $pid ) {
                unset( $this->children[$k] );
                break;
            }
        }
    }

    // Set up all the signal handlers
    protected function initSignals() {
        pcntl_signal( SIGCHLD,  array( $this, "sig_handler") );
        pcntl_signal( SIGTERM,  array( $this, "sig_handler") );
        pcntl_signal( SIGINT,   array( $this, "sig_handler") );
        pcntl_signal( SIGQUIT,  array( $this, "sig_handler") );
    }
    
    /**
     * __construct
     * Constructor
     */
    public function __construct( ) {
        $this->initSignals();
		
		static::$is_terminating = FALSE;
		
		// Set the max number of children
        $this->setMaxChildren(1);
        $this->setChildren(array());
    }
    
    /**
     * sig_handler
     * Signal handler
     * @param   $signo  int Signal number
     * @return  void
     */
    public function sig_handler( $signo ) {
		switch( $signo ) {
			case SIGTERM:
				// changing static variable to be terminating
				static::$is_terminating = TRUE;
				break;
			case SIGCHLD:
			case SIGQUIT:
			case SIGINT:
                exit(0);
				break;
		}
    }
    
    
    /**
     * killChildren
     * Kill all children associated with this process.
     * @return void
     */
    public function killChildren( ) {
        foreach($this->getChildren() as $pid) {
            posix_kill( $pid, 9 );
        }
        $this->reapAllChildren( );
    }

    /**
     * reapAllChildren
     * Reap children so they don't become zombies
     * @return void
     */
    public function reapAllChildren( ) {
        while($this->getChildrenCount()) {
            $this->reapChild( );
        }
    }

    /**
     * reapChild
     * Reap a child. Will block until child is reaped.
     * @return void
     */
    public function reapChild( ) {
        // $status is flag field containing exit information.
        $pid = pcntl_wait( $status );
        $this->removeChild( $pid );
    }

    /**
     * fork current process
     * stores oject to be acted upon and then calls parents fork method
     * @param \D\Process\Application $application
     * @param \D\Semaphore $semaphore
     * @return int|void
     */
    public function queueProcess($application, $semaphore = NULL) {
        $this->application = $application;
        if($semaphore instanceof \D\Semaphore){
        	$this->semaphore = $semaphore;
        }

        return $this->fork();
    }

    protected function detachChildProcess() {
        if (-1 === posix_setsid()) {
            throw new \Exception('could not setsid');
        }

        $isDebug = FALSE;
        if (TRUE != $isDebug) {
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);

            $stdIn = fopen('/dev/null', 'r'); // set fd/0
            $stdOut = fopen('/dev/null', 'w'); // set fd/1
            $stdErr = fopen('php://stdout', 'w'); // a hack to duplicate fd/1 to 2
        }

        pcntl_signal(SIGTSTP, SIG_IGN);
        pcntl_signal(SIGTTOU, SIG_IGN);
        pcntl_signal(SIGTTIN, SIG_IGN);
        pcntl_signal(SIGHUP, SIG_IGN);
    }

    /**
     * fork
     * method that splits into two processes
     * @throws \D\Process\Exception
     */
    public function fork() {
		$pid = pcntl_fork();
		if( $pid == 1 ) {
			throw new \D\Process\Exception( "Could not fork new process." );
		} else if( $pid ) {
			// We are the parent, call parent method.
			return $this->parentAction($pid);
		} else {
            $this->detachChildProcess();

			// We are the child. Call child method.
			$pid = posix_getpid();
			
			if( isset($this->semaphore) && $this->semaphore instanceof \D\Semaphore ) {
				$this->semaphore->acquire();
			}
			$this->childAction($pid);
			if( isset($this->semaphore) && $this->semaphore instanceof \D\Semaphore ) {
				$this->semaphore->release();
			}
            
			// Now exit successfully:
			exit( 0 );
		}
    }
    
    /**
     * parentAction
     * Action to be taken by the parent process after fork()
     * @return int
     */
    protected function parentAction($childPid) {
        $this->addChild($childPid);
        $this->application = NULL;
        return $childPid;
    }
    
    /**
     * childAction
     * Action to be taken by the child process after fork()
     * @return  void
     */
    protected function childAction($childPid) {
    	$class = get_class($this->application);
    	
    	// store pid in filesystem
    	if($class::isPidStored()) {
    		$class::storePid($childPid);
    	}
        $this->initSignals();
    	
    	// main method for current process
        $this->application->run();
        
        // unlink pid from filesystem
        if($class::isPidStored()){
        	$class::unlinkPid($childPid);
        }
        
        
        $this->application = NULL;
    }
    
    public function __destructor() {
        //wait for children to die before exiting (or should we kill them)
        $this->killChildren( );
    }
}