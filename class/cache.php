<?php
class DataManagerCache {
	private $redis;
	public function __construct(){
		global $db,$redis;
		$this->redis=&$redis;
	}
	public function get($name){
		$ret=$this->redis->get('cache:'.$name);
		if($ret) return $ret;
		return false;
	}
	public function set($name,$value,$expire=1800){
		$this->redis->set('cache:'.$name,$value);
		$this->redis->expire('cache:'.$name,$expire);
	}
}