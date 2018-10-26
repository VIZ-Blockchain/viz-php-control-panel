<?php
class viz_plugin_session extends viz_plugin{
	function custom($info,$data){
		global $config;
		$custom_name=$data['id'];
		$required_posting_auths=$data['required_posting_auths'];
		$required_auths=$data['required_auths'];
		$json=$data['json'];
		$json=json_decode($json,true);
		if(in_array('users',$config['plugins'])){
			if('session'==$custom_name){
				$custom_action=$json[0];
				$custom_data=$json[1];
				$custom_json_action=$json[0];
				if('auth'==$custom_action){
					$user_login=$required_posting_auths[0];
					$user_id=get_user_id($user_login);
					$session_key=$custom_data['key'];
					if($user_id){
						$check_session_id=$this->redis->zscore('session_key',$session_key);
						if($check_session_id){
							$this->redis->hset('session:'.$check_session_id,'user',$user_id);
						}
					}
				}
			}
		}
	}
}