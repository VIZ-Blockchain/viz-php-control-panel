<?php
class DataManagerTemplate {
	var $file_root='./';
	var $file_buf=array();
	var $last='';
	var $counter=0;
	function __construct($root = "./"){
		$this->dir($root);
	}
	function dir($root = "./"){
		$this->file_root = $root;
	}
	function open($filename,$name = ''){
		if (file_exists(($this->file_root).$filename))
		{
			$this->counter++;
			if (!$name){
				$name=$this->$counter;
			}
			$file=file_get_contents(($this->file_root).$filename);
			$this->file_buf[$name]=$file;
			$this->last=$name;
			return $this->counter;
		}
		else{
			return false;
		}
	}
	function get($id){
		return @$this->file_buf[$id];
	}
	function set($id,$value){
		$this->file_buf[$id]=$value;
	}
	function assign($tag,$value,$id=''){
		$value=str_replace('$','\$',$value);
		if (!$id){
			$id=$this->last;
		}
		$ret=$this->get($id);
		$ret=@preg_replace('~\{'.$tag.'\}~iUs',$value,$ret);
		$this->set($id,$ret);
	}
}