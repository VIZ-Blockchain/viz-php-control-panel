<?php
class viz_plugin_users extends viz_plugin{
	function account_witness_proxy($info,$data){
		redis_add_ulist('update_user',$data['account']);
	}
	function vote($info,$data){
		redis_add_ulist('update_user',$data['voter']);
		redis_add_ulist('update_user',$data['author']);
	}
	function transfer($info,$data){
		redis_add_ulist('update_user',$data['from']);
		redis_add_ulist('update_user',$data['to']);
	}
	function transfer_to_vesting($info,$data){
		redis_add_ulist('update_user',$data['from']);
		redis_add_ulist('update_user',$data['to']);
	}
	function change_recovery_account($info,$data){
		redis_add_ulist('update_user',$data['account_to_recover']);
	}
	function account_update($info,$data){
		redis_add_ulist('update_user',$data['account']);
	}
	function account_create($info,$data){
		global $config;
		redis_add_ulist('update_user',$data['creator']);
		$user_login=$data['new_account_name'];
		$user_arr=$this->api->execute_method('get_accounts',array(array($user_login)))[0];
		if($user_arr['name']){
			$date=date_parse_from_format('Y-m-d\TH:i:s',$user_arr['created']);
			$reg_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			$date=date_parse_from_format('Y-m-d\TH:i:s',$user_arr['last_post']);
			$last_post=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			$gender=0;
			$json_metadata=array();
			if($user_arr['json_metadata']){
				$json_metadata=json_decode($user_arr['json_metadata'],true);
				if('male'==$json_metadata['profile']['gender']){
					$gender=1;
				}
				if('female'==$json_metadata['profile']['gender']){
					$gender=2;
				}
			}
			$user_data=array(
				'login'=>$user_login,
				'_id'=>(int)$user_arr['id'],

				'creator'=>$user_arr['recovery_account'],
				'referrer'=>$user_arr['referrer'],
				'witnesses_proxy'=>$user_arr['proxy'],
				'vote_count'=>(int)$user_arr['vote_count'],
				'content_count'=>(int)$user_arr['content_count'],
				'reg_time'=>(int)$reg_time,
				'energy'=>(int)$user_arr['energy'],
				'awarded_rshares'=>(int)$user_arr['awarded_rshares'],
				'last_post'=>$last_post,

				'avatar'=>$json_metadata['profile']['avatar'],
				'balance'=>(int)substr($user_arr['balance'],0,strpos($user_arr['balance'],' '))*1000,
				'shares'=>(int)substr($user_arr['vesting_shares'],0,strpos($user_arr['vesting_shares'],' '))*1000000,

				'nickname'=>$json_metadata['profile']['nickname'],
				'about'=>$json_metadata['profile']['about'],

				'parse_time'=>(int)time(),
			);
			$bulk=new MongoDB\Driver\BulkWrite;
			if(mongo_exist('users',array('login'=>$user_login))){
				$bulk->update(['login'=>$user_login],['$set'=>$user_data]);
			}
			else{
				$bulk->insert($user_data);
			}
			$this->mongo->executeBulkWrite($config['db_prefix'].'.users',$bulk);
		}
	}
}