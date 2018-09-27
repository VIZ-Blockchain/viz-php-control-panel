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

$pid_file=$site_root.'/module/waterfall.pid';
$pid=false;
if(file_exists($pid_file)){
	$pid=file_get_contents($pid_file);
}
$new_pid=posix_getpid();
if($pid){
	$working=posix_getpgid($pid);
	if($working){
		print 'VIZ Waterfall already working with PID: '.$pid.PHP_EOL;
		exit;
	}
	else{
		unlink($pid_file);
		print 'VIZ Waterfall stopped, restarting... with PID: '.$new_pid.PHP_EOL;
	}
}
file_put_contents($site_root.'/module/waterfall.pid',$new_pid);

$api=new viz_jsonrpc_web('https://testnet.viz.world/');
$plugins=new viz_plugins();
$block_id=mdb_ai('blocks');
print 'STARTUP: Find last block #'.$block_id.', working...'.PHP_EOL;
if($block_id!=1){
	$block_id++;
}
$work=true;
$current_block=$block_id;
$dgp=$api->execute_method('get_dynamic_global_properties');
$last_block=$dgp['head_block_number'];
$sleep=0;
while($work){
	for(;$current_block<=$last_block;$current_block++){
		$attempts=1;
		$success=false;
		$current_block_time=0;
		while(!$success){
			$current_block_time=microtime(true);
			$success=$plugins->block($current_block,$api->execute_method('get_ops_in_block',array($current_block,0)));
			if(!$success){
				print 'WARNING: Attempt '.$attempts.' on #'.$current_block.PHP_EOL;
				$attempts++;
				if($attempts>100){
					print 'ERROR: Failed get #'.$current_block.' block more that 1000 times, self-terminating...'.PHP_EOL;
					exit;
				}
				usleep(100000);
			}
			else{
				$end_execute_time=microtime(true);
				print 'SUCCESS block #'.$current_block.' (sleep '.($sleep/1000).'ms) ('.(int)(1000*($end_execute_time-$current_block_time)).'ms execute time)'.PHP_EOL;
			}
		}
	}
	$dgp=$api->execute_method('get_dynamic_global_properties');
	$last_block=$dgp['head_block_number'];
	if(($last_block+1)<=$current_block){
		$sleep=(int)((3-(microtime(true)-$current_block_time))*1000000);
		if($sleep>0){
			usleep($sleep);
		}
		else{
			$sleep=0;
		}
	}
	if(!file_exists($pid_file)){
		print 'INFO: PID file was deleted, self-terminating...'.PHP_EOL;
		exit;
	}
}
exit;