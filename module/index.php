<?php
ob_start();
if(''==$path_array[1]){
	//API examples
	$api=new viz_jsonrpc_web('https://testnet.viz.world/');
	print '<div class="page content">
	<h1>API get_dynamic_global_properties</h1>
	<div class="article">';
	print '<pre>';
	print_r($api->execute_method('get_dynamic_global_properties'));
	print '</pre>';
	print '
	</div>
	</div>';

	print '<div class="page content">
	<h1>API get_account_history</h1>
	<div class="article">';
	print '<pre>';
	print_r($api->execute_method('get_account_history',array('viz','0','0')));
	print '</pre>';
	print '
	</div>
	</div>';

	print '<div class="page content">
	<h1>API get_ops_in_block</h1>
	<div class="article">';
	print '<pre>';
	print_r($api->execute_method('get_ops_in_block',array('13',true)));
	print '</pre>';
	print '
	</div>
	</div>';

	print '<div class="page content">
	<h1>API get_discussions_by_created</h1>
	<div class="article">';
	print '<pre>';
	print_r($api->execute_method('get_discussions_by_created',array(array('limit'=>10,'raw'=>1))));
	print '</pre>';
	print '
	</div>
	</div>';

	print '<div class="page content">
	<h1>API get_content</h1>
	<div class="article">';
	print '<pre>';
	print_r($api->execute_method('get_content',array('viz','permlink🍪',-1)));
	print '</pre>';
	print '
	</div>
	</div>';
}
$content=ob_get_contents();
ob_end_clean();