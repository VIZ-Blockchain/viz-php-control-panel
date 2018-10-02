<?php
$bulk=new MongoDB\Driver\BulkWrite;
$bulk->delete(['_id'=>'blocks']);
$mongo_connect->executeBulkWrite('viz.auto_increment',$bulk);
try{
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'sessions']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'blocks']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'users']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'users_links']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'witnesses']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'transfers']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'tags']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'content']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'content_votes']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'content_tags']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'content_users']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'subcontent']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'subcontent_votes']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'subcontent_tags']));
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'subcontent_users']));
}
catch(MongoDB\Driver\Exception\Exception $e){
	print '<p>MongoDB drop error</p>';
}
exit;