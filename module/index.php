<?php
ob_start();
$api=new viz_jsonrpc_web('https://testnet.viz.world/');
if('@'==mb_substr($path_array[1],0,1)){
	if($path_array[2]){
		$author=mb_substr($path_array[1],1);
		$permlink=urldecode($path_array[2]);
		$cache_name=md5($author.$permlink);
		if($buf=$cache->get($cache_name)){
			print $buf;
		}
		else{
			$content=$api->execute_method('get_content',array($author,$permlink,-1));
			if($content['body']){
				$buf='';
				$buf.='test cache: '.time();
				$buf.='<div class="page content">
				<h1>API get_content</h1>
				<div class="article">';
				$buf.='<pre>';
				$buf.=print_r($content,true);
				$buf.='</pre>';
				$buf.='
				</div>
				</div>';
				$cache->set($cache_name,$buf,5);
				print $buf;
			}

		}
	}
}
if(''==$path_array[1]){
	//API examples
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