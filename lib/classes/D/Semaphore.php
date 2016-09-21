<?php

namespace D;

/*
* Semaphore implementation
*/
class Semaphore {
	protected $key, $semaphore, $max_aquire;
	protected $auto_release;
	
	public function __construct($key, $max_aquire = 1) {
		$this->key = $key;
		$this->max_aquire = $max_aquire;
		$this->auto_release = 1;
	}
	
	public function setAutoRelease($set = TRUE) {
		$this->auto_release = $set ? 1 : 0;
	}
	
	public function acquire() {
		$this->semaphore = sem_get($this->key, $this->max_aquire, 0666, 1);
		if(!$this->semaphore) {
			throw new \Exception('semaphore custom error');
		}
		if(!sem_acquire($this->semaphore)) {
			throw new \Exception("cannot acquire semaphore with key[{$this->key}]");
		}
	}
	
	public function release() {
		sem_release($this->semaphore);
	}
	
	public function remove() {
		if(is_resource($this->semaphore)){
			sem_remove($this->semaphore);
		}
	}
}
