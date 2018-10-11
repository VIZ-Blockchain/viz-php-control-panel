<?php
error_reporting(255);
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

$work=true;
while($work){
	$sleep=0;
	$user_login=redis_get_ulist('update_user');
	if($user_login){
		$user_arr=$api->execute_method('get_accounts',array(array($user_login)));
		if($user_arr[0]['name']){
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

				'avatar'=>$json_metadata['profile']['avatar'];
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
			$mongo_connect->executeBulkWrite($config['db_prefix'].'.users',$bulk);
		}
		$sleep=1000;
	}
	if($sleep>0){
		usleep($sleep);
	}
	if(!file_exists($pid_file)){
		print 'INFO: PID file was deleted, self-terminating...'.PHP_EOL;
		exit;
	}
}
exit;