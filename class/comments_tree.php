<?php
class comments_tree{
	public $data=array();
	public $childs=array();
	public function __construct($data=array()){
		$this->data=$data;
	}
	public function find($author,$permlink){
		$look=false;
		foreach($this->childs as $child){
			if($author==$child->data['author']){
				if($permlink==$child->data['permlink']){
					return $child;
				}
			}
			$look=$child->find($author,$permlink);
			if($look!==false){
				return $look;
			}
		}
		return false;
	}
	public function add($comment,$find=false){
		if(!$find){
			$this->childs[]=&$comment;
		}
		else{
			if(1==$comment->data['depth']){
				$this->childs[]=&$comment;
			}
			else{
				$find=$this->find($comment->data['parent_author'],$comment->data['parent_permlink']);
				if($find!==false){
					$find->add($comment);
				}
			}
		}
	}
	public function view(){
		if(!$this->data['body']){
			return '';
		}
		$ret='';
		$level=$this->data['depth']-1;
		if($level>5){
			$level=5;
		}
		$date=date_parse_from_format('Y-m-d\TH:i:s',$this->data['created']);
		$reply_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
		$ret.='<div class="comment" id="'.$this->data['author'].'/'.htmlspecialchars($this->data['permlink']).'" data-level="'.$level.'">
			<div class="info">
				<div class="author"><a href="/@'.$this->data['author'].'/" class="avatar" style=""></a><a href="/@'.$this->data['author'].'/">@'.$this->data['author'].'</a></div>
				<div class="anchor"><a href="#'.$this->data['author'].'/'.htmlspecialchars($this->data['permlink']).'">#</a></div>
				<div class="timestamp" data-timestamp="'.$reply_time.'">'.date('d.m.Y H:i:s',$reply_time).'</div>
			</div>
			<div class="text">
				'.text_to_view($this->data['body']).'
			</div>
			<div class="addon">
				<a class="reply reply-action comment-reply">Ответ <i class="far fa-fw fa-comment-dots"></i></a>
				<a class="award">Наградить <i class="fas fa-fw fa-angle-up"></i></a>
			</div>
		</div>';
		return $ret;
	}
	public function tree(){
		$ret=$this->view();
		foreach($this->childs as $child){
			$ret.=$child->tree();
		}
		return $ret;
	}
}