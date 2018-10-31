<?php
error_reporting(0);
if(!$_SERVER['PWD']){
	exit;
}

$include_path=substr($_SERVER['SCRIPT_NAME'],0,strrpos($_SERVER['SCRIPT_NAME'],'/'));
$include_path=substr($include_path,0,strrpos($include_path,'/'));
set_include_path($include_path);
include('config.php');
$site_root=$include_path;
include('autoloader.php');
include('module/prepare.php');

$pid_file=$site_root.'/module/updater.pid';
$pid=false;
if(file_exists($pid_file)){
	$pid=file_get_contents($pid_file);
}
$new_pid=posix_getpid();
if($pid){
	$working=posix_getpgid($pid);
	if($working){
		print 'VIZ Updater already working with PID: '.$pid.PHP_EOL;
		exit;
	}
	else{
		unlink($pid_file);
		print 'VIZ Updater stopped, restarting... with PID: '.$new_pid.PHP_EOL;
	}
}
file_put_contents($site_root.'/module/updater.pid',$new_pid);
print 'STARTUP: pid file: '.$pid_file.', pid: '.$new_pid.PHP_EOL;
$work=true;
while($work){

	$witness_login=redis_get_ulist('update_witness');
	if($witness_login){
		$user_id=get_user_id($witness_login);
		$witness_arr=$api->execute_method('get_witness_by_account',array($witness_login));
		if($witness_arr['owner']){
			$date=date_parse_from_format('Y-m-d\TH:i:s',$witness_arr['created']);
			$created_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			$witness_data=array(
				'_id'=>(int)$user_id,
				'login'=>$witness_login,
				'url'=>$witness_arr['url'],
				'created_time'=>(int)$created_time,
				'votes'=>(int)$witness_arr['votes'],
				'last_confirmed_block_num'=>(int)$witness_arr['last_confirmed_block_num'],
				'signing_key'=>$witness_arr['signing_key'],
				'props'=>json_encode($witness_arr['props'])
			);
			$bulk=new MongoDB\Driver\BulkWrite;
			if(mongo_exist('witnesses',array('login'=>$witness_login))){
				$bulk->update(['login'=>$witness_login],['$set'=>$witness_data]);
			}
			else{
				$bulk->insert($witness_data);
			}
			$mongo->executeBulkWrite($config['db_prefix'].'.witnesses',$bulk);
			print 'SUCCESS witness update: '.$witness_login.PHP_EOL;
		}
	}
	$user_login=redis_get_ulist('update_user');
	if($user_login){
		$user_arr=$api->execute_method('get_accounts',array(array($user_login)))[0];
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
				$user_data['status']=0;
				$bulk->insert($user_data);
			}
			$mongo->executeBulkWrite($config['db_prefix'].'.users',$bulk);
			print 'SUCCESS user update: '.$user_login.PHP_EOL;
		}
	}
	$content_id=redis_get_ulist('update_content');
	if($content_id){
		$content_arr=mongo_find('content',array('_id'=>(int)$content_id));
		if($content_arr){
			$author_login=get_user_login($content_arr['author']);
			$content_info=$api->execute_method('get_content',array($author_login,$content_arr['permlink']));
			if($content_info['permlink']==$content_arr['permlink']){
				$date=date_parse_from_format('Y-m-d\TH:i:s',$content_info['cashout_time']);
				$cashout_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
				$date=date_parse_from_format('Y-m-d\TH:i:s',$content_info['last_payout']);
				$last_payout=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
				$update_arr=array(
					'payout_value'=>$content_info['payout_value'],
					'shares_payout_value'=>$content_info['shares_payout_value'],
					'curator_payout_value'=>$content_info['curator_payout_value'],
					'beneficiary_payout_value'=>$content_info['beneficiary_payout_value'],
					'total_pending_payout_value'=>$content_info['total_pending_payout_value'],
					'cashout_time'=>(int)$cashout_time,
					'last_payout'=>(int)$last_payout,
					'parse_time'=>(int)time()
				);
				$bulk=new MongoDB\Driver\BulkWrite;
				$bulk->update(['_id'=>(int)$content_id],['$set'=>$update_arr]);
				$mongo->executeBulkWrite($config['db_prefix'].'.content',$bulk);
				print 'SUCCESS content update: '.$content_id.PHP_EOL;
			}
		}
	}
	$subcontent_id=redis_get_ulist('update_subcontent');
	if($subcontent_id){
		$content_arr=mongo_find('content',array('_id'=>(int)$subcontent_id));
		if($content_arr){
			$author_login=get_user_login($content_arr['author']);
			$content_info=$api->execute_method('get_content',array($author_login,$content_arr['permlink']));
			if($content_info['permlink']==$content_arr['permlink']){
				$date=date_parse_from_format('Y-m-d\TH:i:s',$content_info['cashout_time']);
				$cashout_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
				$date=date_parse_from_format('Y-m-d\TH:i:s',$content_info['last_payout']);
				$last_payout=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
				$update_arr=array(
					'payout_value'=>$content_info['payout_value'],
					'shares_payout_value'=>$content_info['shares_payout_value'],
					'curator_payout_value'=>$content_info['curator_payout_value'],
					'beneficiary_payout_value'=>$content_info['beneficiary_payout_value'],
					'total_pending_payout_value'=>$content_info['total_pending_payout_value'],
					'cashout_time'=>(int)$cashout_time,
					'last_payout'=>(int)$last_payout,
					'parse_time'=>(int)time()
				);
				$bulk=new MongoDB\Driver\BulkWrite;
				$bulk->update(['_id'=>(int)$subcontent_id],['$set'=>$update_arr]);
				$mongo->executeBulkWrite($config['db_prefix'].'.subcontent',$bulk);
				print 'SUCCESS subcontent update: '.$content_id.PHP_EOL;
			}
		}
	}
	usleep(100);
	if(!file_exists($pid_file)){
		print 'INFO: PID file was deleted, self-terminating...'.PHP_EOL;
		exit;
	}
}
exit;