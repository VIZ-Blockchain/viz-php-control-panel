<?php
$keys=$redis->keys('*');
print '<p>Redis keys:<p>';
print '<pre>';
print_r($keys);
print '</pre>';
print '<p>Redis before clear:<p>';
print '<pre>';
print_r($redis->info('memory'));
print '</pre>';
print PHP_EOL.'======================================'.PHP_EOL;
$redis->flushall('async');
print '<p>Redis after clear:</p>';
print '<pre>';
print_r($redis->info('memory'));
print '</pre>';
print PHP_EOL.'======================================'.PHP_EOL;
$collections_arr=array(
	'blocks',
	'sessions',
	'users',
	'users_links',
	'witnesses',
	'transfers',
	'tags',
	'content',
	'content_votes',
	'content_tags',
	'content_users',
	'subcontent',
	'subcontent_votes',
	'subcontent_tags',
	'subcontent_users'
);
foreach($collections_arr as $collection){
	$bulk=new MongoDB\Driver\BulkWrite;
	$bulk->delete(['_id'=>$collection]);
	$mongo->executeBulkWrite($config['db_prefix'].'.auto_increment',$bulk);
	try{
		$mongo->executeCommand($config['db_prefix'], new \MongoDB\Driver\Command(['drop'=>$collection]));
	}
	catch(MongoDB\Driver\Exception\Exception $e){
		print '<p>MongoDB collection '.$collection.' drop error</p>';
	}
}
exit;