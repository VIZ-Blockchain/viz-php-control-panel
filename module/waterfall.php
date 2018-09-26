<?php
error_reporting(255);
if(!$_SERVER['PWD']){
	exit;
}
$include_path=substr($_SERVER['PWD'],0,strrpos($_SERVER['PWD'],'/'));
set_include_path($include_path);
include('config.php');
$site_root=$include_path;
include('autoloader.php');

$api=new viz_jsonrpc_web('https://testnet.viz.world/');
$plugins=new viz_plugins();
//example
$plugins->block(91191,$api->execute_method('get_ops_in_block',array(91191,0)));
exit;