<?php
ob_start();
if('tools'==$path_array[1]){
	$replace['title']=htmlspecialchars($l10n['tools']['title']).' - '.$replace['title'];
	if(''==$path_array[2]){
		print '<div class="page content">
		<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['title'].'</h1>
		<div class="article control">';
		print '<p><a href="/tools/paid-subscriptions/">'.$l10n['tools']['list']['paid-subscriptions'].'</a></p>';
		print '<p><a href="/tools/invites/">'.$l10n['tools']['list']['invites'].'</a></p>';
		print '<p><a href="/tools/create-account/">'.$l10n['tools']['list']['create-account'].'</a></p>';
		print '<p><a href="/tools/sell-account/">'.$l10n['tools']['list']['sell-account'].'</a></p>';
		print '<p><a href="/tools/sell-subaccount/">'.$l10n['tools']['list']['sell-subaccount'].'</a></p>';
		print '<p><a href="/tools/buy-account/">'.$l10n['tools']['list']['buy-account'].'</a></p>';
		print '<p><a href="/tools/delegation/">'.$l10n['tools']['list']['delegation'].'</a></p>';
		print '<p><a href="/tools/schedule/">'.$l10n['tools']['list']['schedule'].'</a></p>';
		print '<p><a href="/tools/blocks/">'.$l10n['tools']['list']['blocks'].'</a></p>';
		print '<p><a href="/tools/reset-account/">'.$l10n['tools']['list']['reset-account'].'</a></p>';
		print '<p><a href="/tools/localization/">'.$l10n['tools']['list']['localization'].'</a></p>';
		print '</div></div>';
	}
	elseif('blocks'==$path_array[2]){
		$dgp=$api->execute_method('get_dynamic_global_properties');
		if(''==$path_array[3]){
			$replace['title']=htmlspecialchars($l10n['tools']['list']['blocks']).' - '.$replace['title'];
			print '<div class="page content">
		<h1>'.$l10n['tools']['list']['blocks'].'</h1>
		<div class="article">';
			$date=date_parse_from_format('Y-m-d\TH:i:s',$dgp['genesis_time']);
			$genesis_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			print '<p>'.$l10n['tools']['blocks']['genesis'].': <span class="timestamp" data-timestamp="'.$genesis_time.'">'.date('d.m.Y H:i:s',$genesis_time).'</span></p>';
			print '<p>'.$l10n['tools']['blocks']['api_num'].': '.$dgp['head_block_number'].' ('.$api->endpoint.')</p>';
			print '<p>'.$l10n['tools']['blocks']['index_num'].': '.mongo_counter('blocks').'</p>';
			print '<p>'.$l10n['tools']['blocks']['cursor_num'].': '.mongo_count('blocks').'</p>';
			print '<p>'.$l10n['tools']['blocks']['users_num'].': '.mongo_count('users').'</p>';

			print '<h3>'.$l10n['tools']['blocks']['global_var'].'</h3>';
			print '<p>'.$l10n['tools']['blocks']['ratio'].' '.$dgp['head_block_number'].' '.$l10n['tools']['blocks']['ratio_equal'].' '.(floatval($dgp['total_vesting_fund'])/floatval($dgp['total_vesting_shares'])).'</p>';
			print '<pre class="view_block">';
			$view_dgp=print_r($dgp,true);
			$view_dgp=preg_replace('~\[(.[^\]]*)\] =\> (.*)\n~iUs','[<span style="color:red">$1</span>] => <span style="color:#1b72fa">$2</span>'.PHP_EOL,$view_dgp);
			$view_dgp=str_replace('<span style="color:#1b72fa">Array</span>','<span style="color:#069c40">Array</span>',$view_dgp);
			print $view_dgp;
			print '</pre>';

			print '<h3>'.$l10n['tools']['blocks']['chain_properties_caption'].'</h3>';
			print '<pre class="view_block">';
			$chain_properties=$api->execute_method('get_chain_properties');
			$view_props=print_r($chain_properties,true);
			$view_props=preg_replace('~\[(.[^\]]*)\] =\> (.*)\n~iUs','[<span style="color:red">$1</span>] => <span style="color:#1b72fa">$2</span>'.PHP_EOL,$view_props);
			$view_props=str_replace('<span style="color:#1b72fa">Array</span>','<span style="color:#069c40">Array</span>',$view_props);
			print $view_props;
			print '</pre>';

			print '<h3>'.$l10n['tools']['blocks']['last_blocks_caption'].'</h3>';
			print '<div class="blocks">';
			$low_corner=max(0,(int)$dgp['head_block_number']-1000);
			for($i=(int)$dgp['head_block_number'];$i>$low_corner;--$i){
				print '<a href="/tools/blocks/'.$i.'/">'.$i.'</a>';
			}
			print '<hr>';
			print '<a href="/tools/blocks/1/">1</a>';
			print '</div>';
			print '</div></div>';
		}
		else{
			$id=(int)$path_array[3];
			if($id==$path_array[3]){
				$id_arr=$api->execute_method('get_ops_in_block',array($id,0));
				if($id_arr[0]){
					$replace['title']=htmlspecialchars($l10n['tools']['blocks']['block_title'].' '.$id.'').' - '.$replace['title'];
					print '<div class="page content">
					<a class="right" href="/tools/blocks/">&larr; '.$l10n['tools']['return'].'</a>
					<h1>'.$l10n['tools']['blocks']['block_caption'].$id.'</h1>
					<div class="article">';
					print '<pre class="view_block">';
					function htmlspecialchars_filter(&$value){
						$value = htmlspecialchars($value);
					}
					array_walk_recursive($id_arr,'htmlspecialchars_filter');
					$view_block=print_r($id_arr,true);
					$view_block=preg_replace('~\[(.[^\]]*)\] =\> (.*)\n~iUs','[<span style="color:red">$1</span>] => <span style="color:#1b72fa">$2</span>'.PHP_EOL,$view_block);
					$view_block=str_replace('<span style="color:#1b72fa">Array</span>','<span style="color:#069c40">Array</span>',$view_block);
					print $view_block;
					print '</pre>';
					print '<h3>'.$l10n['tools']['blocks']['near_blocks_caption'].'</h3>';
					print '<div class="blocks">';
					if($id+1 <= (int)$dgp['head_block_number']){
						print '<a href="/tools/blocks/'.($id+1).'/">&uarr; '.($id+1).'</a>';
					}
					if(0 <= ($id-1)){
						print '<a href="/tools/blocks/'.($id - 1).'/">&darr; '.($id - 1).'</a><hr>';
					}
					$high_corner=min((int)$dgp['head_block_number'],$id+50);
					$low_corner=max(0,$id-50);
					for($i=$high_corner;$i>$low_corner;--$i){
						print '<a href="/tools/blocks/'.$i.'/"'.($i==$id?' class="current"':'').'>'.$i.'</a>';
					}
					print '</div>';
					print '</div></div>';
				}
			}
		}
	}
	elseif('paid-subscriptions'==$path_array[2]){
		if('set-options'==$path_array[3]){
			$replace['title']=htmlspecialchars($l10n['tools']['ps']['set-offer-caption']).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/paid-subscriptions/">&larr; '.$l10n['tools']['return'].'</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['ps']['set-offer-caption'].'</h1>
			<div class="article control">';
			print '<p>'.$l10n['tools']['ps']['set-offer-descr'].'</p>';
			print '<p>'.$l10n['tools']['ps']['descr-open'].'</p>';
			print '<div class="set-paid-subscription"></div>';
			print '</div></div>';
		}
		if('sign-agreement'==$path_array[3]){
			$replace['title']=htmlspecialchars($l10n['tools']['ps']['sign-offer-caption']).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/paid-subscriptions/">&larr; '.$l10n['tools']['return'].'</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['ps']['sign-offer-caption'].'</h1>
			<div class="article control">';
			print '<p>'.$l10n['tools']['ps']['sign-offer-descr'].'</p>';
			print '<p>'.$l10n['tools']['ps']['sign-offer-descr-open'].'</p>';
			print '<div class="set-paid-subscribe"></div>';
			print '</div></div>';
		}
		if('manage-subscription'==$path_array[3]){
			$replace['title']=htmlspecialchars($l10n['tools']['ps']['manage-contracts-caption']).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/paid-subscriptions/">&larr; '.$l10n['tools']['return'].'</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['ps']['manage-contracts-caption'].'</h1>
			<div class="article control">';
			print '<p>'.$l10n['tools']['ps']['manage-contracts-descr'].'</p>';
			print '<div class="manage-subscription"></div>';
			print '</div></div>';
		}
		if(''==$path_array[3]){
			$replace['title']=htmlspecialchars($l10n['tools']['list']['paid-subscriptions']).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/">&larr; '.$l10n['tools']['title'].'</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['list']['paid-subscriptions'].'</h1>
			<div class="article control">';
			print '<p>'.$l10n['tools']['ps']['descr'].'</p>';
			print '<p>'.$l10n['tools']['ps']['descr-open'].'</p>';
			print '<div class="paid-subscriptions-options"></div>';
			print '<div class="paid-subscriptions-lookup"></div>';
			print '<div class="paid-subscription-lookup"></div>';
			print '</div></div>';
		}
	}
	elseif('invites'==$path_array[2]){
		$replace['title']=htmlspecialchars($l10n['tools']['list']['invites']).' - '.$replace['title'];
		if('register'==$path_array[3]){
			header('location:/tools/invites/registration/');
			exit;
		}
		if('claim'==$path_array[3]){
			$replace['title']=htmlspecialchars($l10n['tools']['invites']['claim_caption']).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/invites/">&larr; '.$l10n['tools']['return'].'</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['invites']['claim_caption'].'</h1>
			<div class="article control">';
			print '<div class="invite-claim"></div>';
			print '</div></div>';
		}
		if('registration'==$path_array[3]){
			$replace['title']=htmlspecialchars($l10n['tools']['invites']['registration_caption']).' - '.$replace['title'];
			print '<div class="page content">
			<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['invites']['registration_caption'].'</h1>
			<div class="article control">';
			print '<p>'.$l10n['tools']['invites']['registration_descr'].'</p>';
			print '<div class="invite-registration"></div>';
			print '</div></div>';
		}
		if(''==$path_array[3]){
			print '<div class="page content">
			<a class="right" href="/tools/">&larr; '.$l10n['tools']['title'].'</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['list']['invites'].'</h1>
			<div class="article control">';
			print '<p>'.$l10n['tools']['invites']['descr'].'</p>';
			print '<div class="invite-lookup"></div>';
			print '<div class="invite-control"></div>';
			print '</div></div>';
		}
	}
	elseif('create-account'==$path_array[2]){
		$replace['title']=htmlspecialchars($l10n['tools']['list']['create-account']).' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/tools/">&larr; '.$l10n['tools']['title'].'</a>
		<h1><i class="fas fa-fw fa-user-plus"></i> '.$l10n['tools']['list']['create-account'].'</h1>
		<div class="article control">';
		print '<p>'.$l10n['tools']['create-account-descr'].'</p>';
		print '<div class="create-account-control"></div>';
		print '</div></div>';
	}
	elseif('sell-account'==$path_array[2]){
		$replace['title']=htmlspecialchars($l10n['tools']['list']['sell-account']).' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/tools/">&larr; '.$l10n['tools']['title'].'</a>
		<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['list']['sell-account'].'</h1>
		<div class="article control">';
		print '<p>'.$l10n['tools']['sell-account-descr'].'</p>';
		print '<div class="sell-account-control"></div>';
		print '</div></div>';
	}
	elseif('sell-subaccount'==$path_array[2]){
		$replace['title']=htmlspecialchars($l10n['tools']['list']['sell-subaccount']).' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/tools/">&larr; '.$l10n['tools']['title'].'</a>
		<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['list']['sell-subaccount'].'</h1>
		<div class="article control">';
		print '<p>'.$l10n['tools']['sell-subaccount-descr'].'</p>';
		print '<div class="sell-subaccount-control"></div>';
		print '</div></div>';
	}
	elseif('buy-account'==$path_array[2]){
		$replace['title']=htmlspecialchars($l10n['tools']['list']['buy-account']).' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/tools/">&larr; '.$l10n['tools']['title'].'</a>
		<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['list']['buy-account'].'</h1>
		<div class="article control">';
		print '<p>'.$l10n['tools']['buy-account-descr'].'</p>';
		print '<div class="buy-account-control"></div>';
		print '</div></div>';
	}
	elseif('reset-account'==$path_array[2]){
		$replace['title']=htmlspecialchars($l10n['tools']['list']['reset-account']).' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/tools/">&larr; '.$l10n['tools']['title'].'</a>
		<h1><i class="fas fa-fw fa-exchange-alt"></i> '.$l10n['tools']['list']['reset-account'].'</h1>
		<div class="article control">';
		print '<p>'.$l10n['tools']['reset-account-descr'].'</p>';
		print '<div class="reset-account-control"></div>';
		print '</div></div>';
	}
	elseif('delegation'==$path_array[2]){
		$replace['title']=htmlspecialchars($l10n['tools']['list']['delegation']).' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/tools/">&larr; '.$l10n['tools']['title'].'</a>
		<h1><i class="fas fa-fw fa-toolbox"></i> '.$l10n['tools']['list']['delegation'].'</h1>
		<div class="article control">';
		print '<div class="delegation-control"></div>';
		print '<div class="delegation-returning-shares"></div>';
		print '<div class="delegation-received-shares"></div>';
		print '<div class="delegation-delegated-shares"></div>';
		print '</div></div>';
	}
	elseif ('schedule'==$path_array[2]) {
		$replace['title'] = htmlspecialchars($l10n['tools']['list']['schedule']) . ' - ' . $replace['title'];
		print '
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.14.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bignumber.js/2.4.0/bignumber.min.js"></script>
<script src="/js/schedule.js"></script>
		<div class="page content">
		<a class="right" href="/tools/">&larr; '.$l10n['tools']['title'].'</a>
		<h1>'.$l10n['tools']['list']['schedule'].'</h1>
		<div class="article control">
			<div class="witness_schedule">&hellip;</div>
			<h3>'.$l10n['tools']['schedule_support_caption'].'</h3>
			<div class="witness_support_queue">&hellip;</div>
		</div></div>';
	}
	elseif('localization'==$path_array[2]){
		$replace['title']=htmlspecialchars($l10n['tools']['localization']['title']).' - '.$replace['title'];
		if($path_array[3]){
			$code2=$path_array[3];
			if($l10n_base[$code2]){
				$replace['title']=htmlspecialchars($l10n_base[$code2]['name']).' - '.$replace['title'];
				print '<div class="page content">
				<a class="right" href="/tools/localization/">&larr; '.$l10n['tools']['localization']['title'].'</a>
				<h1><i class="fas fa-fw fa-language"></i> '.htmlspecialchars($l10n_base[$code2]['name']).'</h1>
				<div class="article control">';
				print '<p>'.$l10n['tools']['localization']['view_descr1'].' <a class="save-localization-action link" rel="'.$code2.'">'.$l10n['tools']['localization']['view_descr2'].'</a>'.$l10n['tools']['localization']['view_descr3'].'</p>';
				print '<textarea class="localization" rel="'.$code2.'" style="width:100%;" rows="15">';
				print htmlspecialchars(var_export_min($l10n_preset[$code2],false,1,'$l10n_preset[\''.$code2.'\']='));
				print '</textarea>';
				print '</div></div>';
			}
			else{
				header('location:/tools/localization/');
			}
		}
		if(''==$path_array[3]){
			print '<div class="page content">
			<a class="right" href="/tools/">&larr; '.$l10n['tools']['title'].'</a>
			<h1><i class="fas fa-fw fa-language"></i> '.$l10n['tools']['localization']['title'].'</h1>
			<div class="article control">';
			print '<p>'.$l10n['tools']['localization']['descr'].'</p>';
			print '<ul>';
			foreach($l10n_base as $k=>$v){
				print '<li><a href="/tools/localization/'.$v['code2'].'/">'.$v['name'].'</a> &mdash; '.$v['local-name'].', '.($v['active']?$l10n['tools']['localization']['active'].', '.($l10n_current==$v['code2']?'<strong>'.$l10n['tools']['localization']['selected'].'</strong>':'<a href="?set_localization='.$v['code2'].'">'.$l10n['tools']['localization']['select'].'</a>'):$l10n['tools']['localization']['inactive']).'</li>';
			}
			print '</ul>';
			print '</div></div>';
		}
	}
}
$content=ob_get_contents();
ob_end_clean();