<?php
$replace['title']='VIZ.World';
$replace['description']='';
$replace['head_addon']='';
$replace['header_menu']='';
$replace['menu']='';
$replace['script_change_time']=filemtime('./js/app.js');
$replace['css_change_time']=filemtime('./css/app.css');

function mongo_prepare($text){
	return str_replace(array('\\',"\0","\n","\r","'",'"',"\x1a"),array('\\\\','\\0','\\n','\\r',"\\'",'\\"','\\Z'),$text);
}
function mdb_ai($collection_name,$increase=false){
	global $mongo_connect;
	$rows=$mongo_connect->executeQuery($config['db_prefix'].'.auto_increment',new MongoDB\Driver\Query(['_id'=>$collection_name]));
	$count=0;
	foreach($rows as $row){
		if($increase){
			$bulk=new MongoDB\Driver\BulkWrite;
			$bulk->update(['_id'=>$collection_name],['$inc'=>['count'=>1]]);
			try{
				$mongo_connect->executeBulkWrite('cryptostorm.auto_increment',$bulk);
			}
			catch (MongoDB\Driver\Exception\Exception $e) {
				print 'Error: '.$e->getMessage();
			}
			return ++$row->count;
		}
		else{
			return $row->count;
		}
		$count++;
	}
	if(0==$count){
		$bulk=new MongoDB\Driver\BulkWrite;
		$bulk->insert(['_id'=>$collection_name,'count'=>1]);
		try{
			$mongo_connect->executeBulkWrite($config['db_prefix'].'.auto_increment',$bulk);
		}
		catch (MongoDB\Driver\Exception\Exception $e) {
			print 'Error: '.$e->getMessage();
			return false;
		}
		return 1;
	}
}
function mdb_ai_del($collection_name){
	global $mongo_connect;
	$bulk=new MongoDB\Driver\BulkWrite;
	$bulk->delete(['_id'=>$collection_name]);
	try{
		$mongo_connect->executeBulkWrite($config['db_prefix'].'.auto_increment',$bulk);
	}
	catch (MongoDB\Driver\Exception\Exception $e) {
		print 'Error: '.$e->getMessage();
	}
	return true;
}
function mdb_ai_set($collection_name,$count){
	global $mongo_connect;
	$bulk=new MongoDB\Driver\BulkWrite;
	$bulk->update(['_id'=>$collection_name],['$set'=>['count'=>$count]]);
	try{
		$mongo_connect->executeBulkWrite($config['db_prefix'].'.auto_increment',$bulk);
	}
	catch (MongoDB\Driver\Exception\Exception $e) {
		print 'Error: '.$e->getMessage();
	}
	return true;
}