<?php
putenv('TZ='.$config['server_timezone']);
date_default_timezone_set($config['server_timezone']);
include($site_root.'/class/template.php');
include($site_root.'/class/cache.php');
include($site_root.'/class/viz_jsonrpc.php');
include($site_root.'/class/parsedown/parsedown.php');
include($site_root.'/class/parsedown/parsedownextra.php');
include($site_root.'/class/comments_tree.php');
include($site_root.'/class/viz_plugins.php');
include($site_root.'/class/viz_keys.php');

$parsedown = new Parsedown();
$parsedownextra = new ParsedownExtra();

$t=new DataManagerTemplate($site_root.'/templates/');
$cache=new DataManagerCache;
$time=time();

try{
	$mongo=new MongoDB\Driver\Manager('mongodb://'.$config['db_login'].':'.$config['db_password'].'@'.$config['db_host'].'/'.$config['db_base']);
}
catch(MongoDB\Driver\Exception\Exception $e){
	print 'MongoDB connection error';
	exit;
}

$redis=new Redis();
if(!$redis->connect($config['redis_host'],6379,1)){
	print 'Redis connection error';
	exit;
}
if(!$redis->auth($config['redis_password'])){
	print 'Redis authentication error';
	exit;
}

$ip='';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
	$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
}
else{
	if(isset($_SERVER['REMOTE_ADDR'])){
		$ip=$_SERVER['REMOTE_ADDR'];
	}
}