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
	function delegate_vesting_shares($info,$data){
		redis_add_ulist('update_user',$data['delegator']);
		redis_add_ulist('update_user',$data['delegatee']);
	}
	function change_recovery_account($info,$data){
		redis_add_ulist('update_user',$data['account_to_recover']);
	}
	function account_update($info,$data){
		redis_add_ulist('update_user',$data['account']);
	}
	function account_metadata($info,$data){
		redis_add_ulist('update_user',$data['account']);
	}
	function committee_pay_request_operation($info,$data){
		redis_add_ulist('update_user',$data['worker']);
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
			$json_metadata=array();
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

				'balance'=>(int)substr($user_arr['balance'],0,strpos($user_arr['balance'],' '))*1000,
				'shares'=>(int)substr($user_arr['vesting_shares'],0,strpos($user_arr['vesting_shares'],' '))*1000000,

				'avatar'=>'',
				'nickname'=>'',
				'about'=>'',
				'gender'=>0,

				'status'=>0,
				'parse_time'=>(int)time(),
			);
			if($user_arr['json_metadata']){
				$json_metadata=json_decode($user_arr['json_metadata'],true);
				if(isset($json_metadata['profile'])){
					if(isset($json_metadata['profile']['gender'])){
						if('male'==$json_metadata['profile']['gender'])
							$user_data['gender']=1;
						if('female'==$json_metadata['profile']['gender'])
							$user_data['gender']=2;
					}
					if(isset($json_metadata['profile']['avatar']))
						$user_data['avatar']=mongo_prepare($json_metadata['profile']['avatar']);
					if(isset($json_metadata['profile']['nickname']))
						$user_data['nickname']=mongo_prepare($json_metadata['profile']['nickname']);
					if(isset($json_metadata['profile']['about']))
						$user_data['about']=mongo_prepare($json_metadata['profile']['about']);
				}
			}
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