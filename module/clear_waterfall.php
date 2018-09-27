<?php
$bulk=new MongoDB\Driver\BulkWrite;
$bulk->delete(['_id'=>'blocks']);
$mongo_connect->executeBulkWrite('viz.auto_increment',$bulk);
try{
	$mongo_connect->executeCommand('viz', new \MongoDB\Driver\Command(['drop'=>'blocks']));
}
catch(MongoDB\Driver\Exception\Exception $e){
	print '<p>MongoDB drop error</p>';
}
exit;