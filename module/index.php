<?php
set_time_limit(3);
ob_start();
if('@'==mb_substr($path_array[1],0,1)){
	if($query_string){
		$query_string='?'.$query_string;
	}
	header('location:/media'.$path.$query_string);
	exit;
}
else
if('login'==$path_array[1]){
	$replace['title']=htmlspecialchars($l10n['login']['caption']).' - '.$replace['title'];
	print '<div class="page content">
	<h1>'.$l10n['login']['caption'].'</h1>
	<div class="article control">';
	print '<p>'.$l10n['login']['descr'].'</p>';
	print '<p><label><input type="text" name="login" class="round"> &mdash; '.$l10n['login']['form-login-append'].'</label></p>';
	print '<p><input type="password" name="regular_key" class="round"> &mdash; '.$l10n['login']['form-regular-key-append'].'</label></p>';
	print '<p><input type="password" name="active_key" class="round"> &mdash; '.$l10n['login']['form-active-key-append'].'</label></p>';
	print '<p><span class="auth-error"></span></p>';
	print '<p><input type="button" class="auth-action button" value="'.$l10n['login']['form-action'].'"></p>';
	print '<hr><p><input type="button" class="auth-custom-action button opacity" value="'.$l10n['login']['custom-action'].'"></p>';
	print '<hr><h3><img src="/shield-icon.svg"> '.$l10n['login']['shield-caption'].'</h3>';
	print '<div class="shield-auth-control"></div>';
	print '</div>';
	print '</div>';
}
else
if('wallet'==$path_array[1]){
	$replace['title']=htmlspecialchars($l10n['menu']['wallet']).' - '.$replace['title'];
	print '<div class="page content">
	<h1><i class="fas fa-fw fa-wallet"></i> '.$l10n['menu']['wallet'].'</h1>
	<div class="article control">';
	print '<div class="wallet-control"></div>';
	print '</div></div>';
}
else
if('accounts'==$path_array[1]){
	$replace['title']=htmlspecialchars($l10n['menu']['accounts']).' - '.$replace['title'];
	print '<div class="page content">
	<h1><i class="fas fa-fw fa-user-cog"></i> '.$l10n['menu']['accounts'].'</h1>
	<div class="article control">';
	print '<p>'.$l10n['template']['accounts-descr'].'</p>';
	print '<div class="session-control"></div>';
	print '</div></div>';
}
else
if('witnesses'==$path_array[1]){
	$replace['title']=htmlspecialchars($l10n['witnesses']['caption']).' - '.$replace['title'];
	if(''==$path_array[2]){
		print '<div class="page content">
		<h1><i class="fas fa-fw fa-user-shield"></i> '.$l10n['witnesses']['caption'].'</h1>
		<div class="article">
		<div class="witness-votes"></div>
		<h3>TOP-100</h3>';
		$hf=$api->execute_method('get_hardfork_version',array(),true);
		print '<p>'.$l10n['witnesses']['hf-version'].': '.$hf.'</p>';
		$hf=intval(str_replace('.','',$hf));
		$hf=intval($hf/10);
		$list=$api->execute_method('get_witnesses_by_counted_vote',array('',100));
		$num=1;
		foreach($list as $witness_arr){
			$witness_hf=intval(str_replace('.','',$witness_arr['running_version']));
			$witness_hf=intval($witness_hf/10);
			print '<p'.('VIZ1111111111111111111111111111111114T1Anm'==$witness_arr['signing_key']?' style="opacity:0.5"':'').'>#'.$num.' <a href="/@'.$witness_arr['owner'].'/">@'.$witness_arr['owner'].'</a> (<a href="'.htmlspecialchars($witness_arr['url']).'">url</a>), '.$l10n['witnesses']['votes'].': '.number_format (floatval($witness_arr['counted_votes'])/1000000/1000,1,'.',' ').'k'.($witness_arr['penalty_percent']?' (<i class="fas fa-fw fa-angle-down" title="Penalty"></i>'.(min(10000,$witness_arr['penalty_percent'])/100).'%'.')':'').' SHARES, <a href="/witnesses/'.$witness_arr['owner'].'/">'.$l10n['witnesses']['params'].'</a>, '.$l10n['witnesses']['version'].': ';
			if($witness_hf>$hf){
				print '<span style="color:#090">';
				print $witness_arr['running_version'];
				print '</span>';
			}
			else
			if($witness_hf<$hf){
				print '<span style="color:#900">';
				print $witness_arr['running_version'];
				print '</span>';
			}
			else{
				print $witness_arr['running_version'];
			}
			if($witness_hf!=$hf){
				if('0.0.0'!=$witness_arr['hardfork_version_vote']){
					$witness_hf_vote=intval(str_replace('.','',$witness_arr['hardfork_version_vote']));
					$witness_hf_vote=intval($witness_hf_vote/10);
					if($witness_hf_vote>$hf){
						print ', '.$l10n['witnesses']['version-vote'].' '.$witness_arr['hardfork_version_vote'].' '.$l10n['witnesses']['version-vote-date'].' ';
						$date=date_parse_from_format('Y-m-d\TH:i:s',$witness_arr['hardfork_time_vote']);
						$vote_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
						print '<span class="timestamp" data-timestamp="'.$vote_time.'">'.date('d.m.Y H:i:s',$vote_time).'</span>';
					}
				}
			}
			print '</p>';
			$num++;
		}
		print '</div></div>';
	}
	else{
		$witness_arr=$api->execute_method('get_witness_by_account',array($path_array[2]));
		if($witness_arr['owner']){
			$replace['title']=htmlspecialchars($witness_arr['owner']).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/witnesses/">&larr; '.$l10n['witnesses']['return-link'].'</a>
			<h1>'.$l10n['witnesses']['view-caption'].' <a href="/@'.$witness_arr['owner'].'/">'.$witness_arr['owner'].'</a></h1>
			<div class="article control">';
			$date=date_parse_from_format('Y-m-d\TH:i:s',$witness_arr['created']);
			$created_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			print '<p>'.$l10n['witnesses']['view-date'].': <span class="timestamp" data-timestamp="'.$created_time.'">'.date('d.m.Y H:i:s',$created_time).'</span></p>';
			print '<p>'.$l10n['witnesses']['view-last-block'].': <a href="/tools/blocks/'.$witness_arr['last_confirmed_block_num'].'/">'.$witness_arr['last_confirmed_block_num'].'</a></p>';
			print '<p>'.$l10n['witnesses']['view-signing-key'].': '.$witness_arr['signing_key'].'</p>';
			print '<h2>'.$l10n['witnesses']['view-props-caption'].'</h2>';
			print '<pre class="view_block">';
			$view_props=print_r($witness_arr['props'],true);
			$view_props=preg_replace('~\[(.[^\]]*)\] =\> (.*)\n~iUs','[<span style="color:red">$1</span>] => <span style="color:#1b72fa">$2</span>'.PHP_EOL,$view_props);
			$view_props=str_replace('<span style="color:#1b72fa">Array</span>','<span style="color:#069c40">Array</span>',$view_props);

			$chain_properties=$api->execute_method('get_chain_properties');
			foreach($chain_properties as $prop_name=>$prop_value){
				if($witness_arr['props'][$prop_name]==$prop_value){
					$view_props=str_replace($prop_name,' <span style="color:#069c40;">&plus;</span> '.$prop_name,$view_props);
				}
				else{
					$view_props=str_replace($prop_name,' <span style="color:#000;">&minus;</span> '.$prop_name,$view_props);
				}
			}
			print $view_props;
			print '</pre>';
			print '<div class="witness-vote" data-witness="'.$witness_arr['owner'].'"></div>';
			print '<div class="witness-control" data-witness="'.$witness_arr['owner'].'"></div>';
		}
	}
}
else
if('committee'==$path_array[1]){
	$replace['title']=htmlspecialchars($l10n['committee']['caption']).' - '.$replace['title'];
	$committee_status_arr=array(
		0=>$l10n['committee']['status_0'],
		1=>$l10n['committee']['status_1'],
		2=>$l10n['committee']['status_2'],
		3=>$l10n['committee']['status_3'],
		4=>$l10n['committee']['status_4'],
		5=>$l10n['committee']['status_5']
	);
	if('create'==$path_array[2]){
		$replace['title']=htmlspecialchars($l10n['committee']['create-caption']).' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/committee/">&larr; '.$l10n['committee']['return'].'</a>
		<h1><i class="fas fa-fw fa-university"></i> '.$l10n['committee']['create-caption'].'</h1>
		<div class="article control">
		<p>'.$l10n['committee']['create-descr'].'</p>';
		print '<div class="committee-create-request"></div>';
		print '</div></div>';
	}
	else
	if(''==$path_array[2]){
		print '<div class="page content">
		<a class="right button" href="/committee/create/">'.$l10n['committee']['create-button'].'</a>
		<h1><i class="fas fa-fw fa-university"></i>'.$l10n['committee']['caption'].'</h1>
		<div class="article">';
		$dgp=$api->execute_method('get_dynamic_global_properties');
		print '<p>'.$l10n['committee']['fund'].': '.$dgp['committee_fund'].'</p>';
		print '<h3>'.$l10n['committee']['requests-caption'].'</h3>';
		print '<ul>';
		foreach($committee_status_arr as $committee_status_id=>$committee_status_name){
			print '<li>'.$committee_status_name;
			$list=$api->execute_method('get_committee_requests_list',array($committee_status_id));
			if(0<count($list)){
				print ' ('.count($list).')';
				print '<ul>';
				foreach($list as $request){
					$request_arr=$api->execute_method('get_committee_request',array($request,0));
					$date=date_parse_from_format('Y-m-d\TH:i:s',$request_arr['end_time']);
					$end_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
					print '<li><a href="/committee/'.$request_arr['request_id'].'/">#'.$request_arr['request_id'].' от '.$request_arr['creator'].'</a>, '.$l10n['committee']['request-range'].': '.$request_arr['required_amount_min'].'&ndash;'.$request_arr['required_amount_max'].', '.$l10n['committee']['request-end-time'].' <span class="timestamp" data-timestamp="'.$end_time.'">'.date('d.m.Y H:i:s',$end_time).'</span></li>';
				}
				print '</ul>';
			}
			print '</li><hr>';
		}
		print '</ul>';
		print '</div></div>';
	}
	else{
		$request_id=(int)$path_array[2];
		$request_arr=$api->execute_method('get_committee_request',array($request_id,-1));
		if($request_arr){
			$replace['title']=htmlspecialchars($l10n['committee']['request-num'].$request_id).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/committee/">&larr; '.$l10n['committee']['return'].'</a>
			<h1>'.$l10n['committee']['request-num'].$request_id.$l10n['committee']['request-num-append'].'</h1>
			<div class="article control">';
			print '<p>'.$l10n['committee']['request-status'].': '.$committee_status_arr[$request_arr['status']].'</p>';
			print '<p>'.$l10n['committee']['request-creator'].': <a href="/@'.$request_arr['creator'].'/">@'.$request_arr['creator'].'</a></p>';
			print '<p>'.$l10n['committee']['request-url'].': <a href="'.htmlspecialchars($request_arr['url']).'">'.htmlspecialchars($request_arr['url']).'</a></p>';
			print '<p>'.$l10n['committee']['request-worker'].':: <a href="/@'.$request_arr['worker'].'/">@'.$request_arr['worker'].'</a></p>';
			print '<p>'.$l10n['committee']['request-min-amount'].': '.$request_arr['required_amount_min'].'</p>';
			print '<p>'.$l10n['committee']['request-max-amount'].': '.$request_arr['required_amount_max'].'</p>';
			$date=date_parse_from_format('Y-m-d\TH:i:s',$request_arr['start_time']);
			$start_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			$date=date_parse_from_format('Y-m-d\TH:i:s',$request_arr['end_time']);
			$end_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			print '<p>'.$l10n['committee']['request-start-time'].': <span class="timestamp" data-timestamp="'.$start_time.'">'.date('d.m.Y H:i:s',$start_time).'</span></p>';
			print '<p>'.$l10n['committee']['request-end-time'].': <span class="timestamp" data-timestamp="'.$end_time.'">'.date('d.m.Y H:i:s',$end_time).'</span></p>';
			if($request_arr['status']>=2){
				$date=date_parse_from_format('Y-m-d\TH:i:s',$request_arr['conclusion_time']);
				$conclusion_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
				print '<p>'.$l10n['committee']['request-conclusion-time'].': <span class="timestamp" data-timestamp="'.$conclusion_time.'">'.date('d.m.Y H:i:s',$conclusion_time).'</span></p>';
			}
			if($request_arr['status']>=4){
				print '<p>'.$l10n['committee']['request-conclusion-amount'].': '.$request_arr['conclusion_payout_amount'].'</p>';
				print '<p>'.$l10n['committee']['request-payout-amount'].': '.$request_arr['payout_amount'].'</p>';
				print '<p>'.$l10n['committee']['request-remain-payout-amount'].': '.$request_arr['remain_payout_amount'].'</p>';
				$date=date_parse_from_format('Y-m-d\TH:i:s',$request_arr['last_payout_time']);
				$last_payout_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
				print '<p>'.$l10n['committee']['request-last-payout-time'].': <span class="timestamp" data-timestamp="'.$last_payout_time.'">'.date('d.m.Y H:i:s',$last_payout_time).'</span></p>';
			}
			print '<div class="committee-control" data-request-id="'.$request_id.'" data-creator="'.$request_arr['creator'].'" data-status="'.$request_arr['status'].'"></div>';
			if(count($request_arr['votes'])){
				$max_rshares=0;
				$actual_rshares=0;
				print '<h2>'.$l10n['committee']['request-vote-caption'].'</h2>';
				foreach($request_arr['votes'] as $vote_arr){
					$voter=$api->execute_method('get_accounts',array(array($vote_arr['voter'])));
					$effective_vesting_shares=floatval($voter[0]['vesting_shares'])-floatval($voter[0]['delegated_vesting_shares'])+floatval($voter[0]['received_vesting_shares']);
					$max_rshares+=$effective_vesting_shares;
					$actual_rshares+=$effective_vesting_shares*$vote_arr['vote_percent']/10000;
					$date=date_parse_from_format('Y-m-d\TH:i:s',$vote_arr['last_update']);
					$vote_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
					print '<p><span class="timestamp" data-timestamp="'.$vote_time.'">'.date('d.m.Y H:i:s',$vote_time).'</span>: <a href="/@'.$vote_arr['voter'].'/">@'.$vote_arr['voter'].'</a> '.$l10n['committee']['request-vote'].' '.($vote_arr['vote_percent']/100).'%</p>';
				}
				$dgp=$api->execute_method('get_dynamic_global_properties');
				$chain_properties=$api->execute_method('get_chain_properties');
				$net_percent=$max_rshares/floatval($dgp['total_vesting_shares'])*100;
				$request_calced_payout=floatval($request_arr['required_amount_max'])*$actual_rshares/$max_rshares;
				print '<hr><p>'.$l10n['committee']['request-votes-count'].': '.count($request_arr['votes']).', '.$l10n['committee']['request-votes-percent'].': '.round($net_percent,2).'% ('.$l10n['committee']['request-conclusion-percent'].' >='.($chain_properties['committee_request_approve_min_percent']/100).'%), '.$l10n['committee']['request-votes-calculated-payout'].': '.round($request_calced_payout,3).' VIZ.</p>';
			}
			print '</div></div>';
		}
	}
}
if(''==$path_array[1]){
	$t->open('landing.tpl','index');
	$replace['title']=$l10n['landing']['seo-title'];
	$replace['description']=$l10n['landing']['seo-description'];
	$replace['head_addon'].='
	<meta property="og:url" content="https://viz.world/" />
	<meta name="og:title" content="'.$l10n['landing']['seo-title'].'" />
	<meta name="twitter:title" content="'.$l10n['landing']['seo-title'].'" />
	<link rel="image_src" href="https://viz.world/landing-meta.png?v2" />
	<meta property="og:image" content="https://viz.world/landing-meta.png?v2" />
	<meta name="twitter:image" content="https://viz.world/landing-meta.png?v2" />
	<meta name="twitter:card" content="summary_large_image" />';

	print '
<div class="topbox">
	<div class="logo-symbol parallax-active"><div class="parralax-glare"></div><img src="/logo-symbol-anim.svg" style="width:100%" class="symbol" alt="'.$l10n['landing']['symbol'].'"></div>
	<div class="description-bubble">
		<h1>VIZ Blockchain</h1>
		<ul>
			<li>— '.$l10n['landing']['descriptions-dao'].'</li>
			<li>— '.$l10n['landing']['descriptions-committee'].'</li>
			<li>— '.$l10n['landing']['descriptions-award'].'</li>
			<li>— '.$l10n['landing']['descriptions-participation'].'</li>
		</ul>
	</div>
</div>';
	print '
<div class="info-bubbles">
	<a class="item color1 active" rel="award">
		<i class="icon fas fa-gem"></i><span class="title">'.$l10n['landing']['slogan-award'].'</span>
		<p>'.$l10n['landing']['slogan-award-descr'].'</p>
		<span class="color">'.$l10n['landing']['learn-more'].' <i class="fas fa-fw fa-angle-double-right"></i></span>
	</a>
	<a class="item color2" rel="create">
		<i class="icon fas fa-hat-wizard"></i><span class="title">'.$l10n['landing']['slogan-create'].'</span>
		<p>'.$l10n['landing']['slogan-create-descr'].'</p>
		<span class="color">'.$l10n['landing']['learn-more'].' <i class="fas fa-fw fa-angle-double-right"></i></span>
	</a>
	<a class="item color3" rel="manage">
		<i class="icon fas fa-globe-africa"></i><span class="title">'.$l10n['landing']['slogan-manage'].'</span>
		<p>'.$l10n['landing']['slogan-manage-descr'].'</p>
		<span class="color">'.$l10n['landing']['learn-more'].' <i class="fas fa-fw fa-angle-double-right"></i></span>
	</a>
</div>';
	print '
<div class="info-block bubble-item" id="award" style="display:block;">
	<div class="text color1">
'.$l10n['landing']['award-info'].'
	</div>
</div>';
	print '
<div class="info-block bubble-item" id="create">
	<div class="text color2">
'.$l10n['landing']['create-info'].'
	</div>
</div>';
	print '
<div class="info-block bubble-item" id="manage">
	<div class="text color3">
'.$l10n['landing']['manage-info'].'
	</div>
</div>';
	print '
<div class="info-block">
	<h2>'.$l10n['landing']['features-caption'].'</h2>
	<div class="text">
'.$l10n['landing']['features-info'].'
	</div>
</div>';
	print '
<div class="info-block">
	<h2>'.$l10n['landing']['economic-caption'].'</h2>
	<div class="text">
'.$l10n['landing']['economic-info'].'
	</div>
</div>';
	print '
<div class="info-block">
	<h2>'.$l10n['landing']['code-caption'].'</h2>
	<div class="text">
'.$l10n['landing']['code-info'].'
	</div>
</div>';
	print '
<div class="info-block">
	<h2>'.$l10n['landing']['services-caption'].'</h2>
	<div class="text">
'.$l10n['landing']['services-info'].'
	</div>
</div>';
}
$content=ob_get_contents();
ob_end_clean();