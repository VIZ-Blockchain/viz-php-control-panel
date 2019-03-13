<?php
error_reporting(0);
include('config.php');
include('autoloader.php');
$path=$_SERVER['REQUEST_URI'];
$_GET=array();
if(false!==strpos($path,'?')){
	$query_string=substr($path,1+strpos($path,'?'));
	$path=substr($path,0,strpos($path,'?'));
	$pairs=explode('&',$query_string);
	foreach($pairs as $pair){
		list($name,$value)=explode('=',$pair);
		$_GET[$name]=urldecode($value);
	}
}
$path_ext=false;
if(false!==strrpos($path,'.')){
	$path_ext=substr($path,strrpos($path,'.'));
}
if(!$path_ext){
	if('/'!=substr($path,strlen($path)-1)){
		if($query_string){
			$query_string='?'.$query_string;
		}
		header('location:'.$path.'/'.$query_string);
		exit;
	}
}
$path_array=explode('/',trim($path));
$t->open('index.tpl','index');
$module_file=$site_root.'/module/prepare.php';
if(file_exists($module_file)){
	include($module_file);
}
$module=mongo_prepare($path_array[1]);
if(!$module){
	$module='index';
}
$module_file=$site_root.'/module/'.$module.'.php';
if(file_exists($module_file)){
	include($module_file);
}
else{
	$module_file=$site_root.'/module/index.php';
	include($module_file);
}
if($content){
	$replace['pages']=$content;
	if(isset($replace)){
		foreach ($replace as $name=>$value){
			$t->assign($name,$value,'index');
		}
	}
	foreach($l10n as $cat=>$arr){
		foreach($arr as $name=>$value){
			if(is_array($value)){
				foreach($value as $subname=>$subvalue){
					$t->assign('l10n_'.$cat.'_'.$name.'_'.$subname,$subvalue,'index');
				}
			}
			else{
				$t->assign('l10n_'.$cat.'_'.$name,$value,'index');
			}
		}
	}
	$result=$t->get('index');
	if(false!==strpos(@$_SERVER['HTTP_ACCEPT_ENCODING'],'gzip')){
		header('Content-Encoding: gzip');
		print gzencode($result);
	}
	else{
		print $result;
	}
}
else{
	header('HTTP/1.1 404 Not Found');
	print '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<TITLE>404 Not Found</TITLE>
</head>
<body>
<h1>Not Found</h1>
The requested URL '.$path.' was not found on this server.<P>
<hr>
<address>'.$_SERVER['SERVER_SOFTWARE'].' Server at '.$_SERVER['HTTP_HOST'].' Port '.$_SERVER['SERVER_PORT'].'</address>
</body>
</html>';
}
exit;