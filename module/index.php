<?php
ob_start();
$api=new viz_jsonrpc_web('https://testnet.viz.world/');
if('@'==mb_substr($path_array[1],0,1)){
	if($path_array[2]){
		$author=mb_substr($path_array[1],1);
		$permlink=urldecode($path_array[2]);
		$cache_name=md5($author.$permlink);
		if($buf=$cache->get($cache_name)){
			$content=json_decode($buf,true);
		}
		else{
			$content=$api->execute_method('get_content',array($author,$permlink,-1));
			$cache->set($cache_name,json_encode($content),5);
		}
		if($content['body']){
			$buf='';
			$date=date_parse_from_format('Y-m-d\TH:i:s',$content['created']);
			$content_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			$json=json_decode($content['json_metadata'],true);
			$content_image=false;
			if(isset($json['image'][0])){
				$content_image=$json['image'][0];
			}
			$replace['title']=htmlspecialchars($content['title']).' - '.$replace['title'];

			$text=text_to_view($content['body'],($json['format']=='markdown'?true:false));
			$replace['description']=mb_substr(strip_tags($text),0,250).'...';
			$replace['description']=str_replace("\n",' ',$replace['description']);
			$replace['description']=str_replace('  ',' ',$replace['description']);

			$replace['head_addon'].='
			<meta property="og:url" content="https://viz.world/@'.$content['author'].'/'.$content['permlink'].'/" />
			<meta name="og:title" content="'.htmlspecialchars($content['title']).'" />
			<meta name="twitter:title" content="'.htmlspecialchars($content['title']).'" />
			<meta name="twitter:card" content="summary_large_image" />';
			if($content_image){
				if(!preg_match('~^https://~iUs',$content_image)){
					$content_image='https://i.goldvoice.club/0x0/'.$content_image;
				}
				$replace['head_addon'].='
<link rel="image_src" href="'.$content_image.'" />
<meta property="og:image" content="'.$content_image.'" />
<meta name="twitter:image" content="'.$content_image.'" />';
				$buf.='<img src="'.$content_image.'" itemprop="image" class="schema">';
			}

			$buf.='<div class="page content">
			<h1>'.htmlspecialchars($content['title']).'</h1>
			<div class="info">
				<div class="author"><a href="/@'.$content['author'].'/" class="avatar" style=""></a><a href="/@'.$content['author'].'/">@'.$content['author'].'</a></div>
				<div class="timestamp" data-timestamp="'.$content_time.'">'.date('d.m.Y H:i:s',$content_time).'</div>
			</div>
			<div class="article">';
			$buf.=$text;
			$buf.='</b></strong></em></i>';//fix styles

			/*$buf.='<pre>';
			$buf.=print_r($content,true);
			$buf.='</pre>';*/
			$buf.='
			</div>';
			$tags=$json['tags'];
			if($tags){
				$tags_list=array();
				foreach($tags as $tag){
					$tags_list[]='<a href="/tags/'.htmlspecialchars($tag).'/">'.htmlspecialchars($tag).'</a>';
				}
				$buf.='<div class="tags">'.implode($tags_list).'</div>';
			}
			$buf.='<hr>
			<div class="addon">
				<div class="right"><div class="comments">'.$content['children'].'<a href="#comments" class="icon"><i class="far fa-comment"></i></a></div></div>
				<a class="award"></a>
				<div class="votes_count">Получено '.$content['active_votes_count'].' голосов</div>
				<!--<div class="flag right"></div>-->
			</div>
			</div>';


			$buf.='<div class="page comments" id="comments">
<div class="actions"><div class="reply reply-action post-reply unselectable">Оставить комментарий</div></div>
<div class="subtitle">Комментарии</div>
<hr>';

			$comment_tree=new comments_tree();
			$replies=$api->execute_method('get_all_content_replies',array($author,$permlink,-1));
			$comment_arr=array();
			$i=1;
			foreach($replies as $reply){
				$comment_arr[$i]=new comments_tree($reply);
				$comment_tree->add($comment_arr[$i],true);
				$i++;
			}
			$buf.=$comment_tree->tree();
			$buf.='</div>';
			print $buf;
		}
	}
	else{
		$account_login=mb_substr($path_array[1],1);
		$account=$api->execute_method('get_accounts',array(array($account_login)));
		if($account[0]['name']==$account_login){
			$account_name=$account_login;
			$account_json=@json_decode($account[0]['json_metadata'],true);
			$account_avatar='/default-avatar.png';
			$account_about='';

			if($account_json['profile']['name']){
				$account_name=htmlspecialchars($account_json['profile']['name']);
			}
			if($account_json['profile']['profile_image']){
				$account_avatar=htmlspecialchars($account_json['profile']['profile_image']);
			}
			if($account_json['profile']['about']){
				$account_about=htmlspecialchars(strip_tags($account_json['profile']['about']));
			}
			$account_name=str_replace('@','',$account_name);
			print '<div class="page user-badge clearfix">
			<a href="/@'.$account_login.'/" class="avatar" style="background-image:url(\''.$account_avatar.'\')"></a>
			<div class="actions">
				<div class="follow">Подписаться</div><br>
				<div class="unfollow">Отписаться</div>
			</div>
			<div class="info">
				<div class="login"><a href="/@'.$account_login.'/">'.$account_name.'</a></div>
				<div class="descr">
					<p>'.$account_about.'</p>
					<p>Энергии: '.($account[0]['energy']/100).'%, Контента: '.$account[0]['content_count'].', Голосов: '.$account[0]['vote_count'].'</p>
					<p>Баланс: '.$account[0]['balance'].', '.$account[0]['vesting_shares'].'</p>
				</div>
			</div>
	</div>';

			$buf='';
			$buf.='
			<div class="page content">
				<h1>Контент пользователя:</h1>
			</div>';
		}
	}
}
else
if('login'==$path_array[1]){
	$replace['title']=htmlspecialchars('Авторизиация').' - '.$replace['title'];
	print '<div class="page content">
	<h1>Авторизация</h1>
	<div class="article control">';
	print '<p>Внимание! При авторизации ключ записывается в ваш браузер и не передается на сервер. Если вы очистите кэш браузера или localStorage, то вам нужно будет вновь ввести свои данные для входа.<br>';
	print '<label><input type="text" name="login"> &mdash; Логин</label><br>';
	print '<label><input type="password" name="posting_key"> &mdash; Posting ключ</label><br>';
	print '<label><input type="password" name="active_key"> &mdash; Active ключ (по желанию)</label><br>';
	print '<span class="auth-error"></span>';
	print '<br><input type="button" class="auth-action button" value="Сохранить доступ и авторизоваться">';
	print '</p>';
	print '</div></div>';
}
else
if('accounts'==$path_array[1]){
	$replace['title']=htmlspecialchars('Аккаунты').' - '.$replace['title'];
	print '<div class="page content">
	<h1><i class="fas fa-fw fa-user-cog"></i> Аккаунты</h1>
	<div class="article control">';
	print '<p>Внимание! Авторизовать дополнительный аккаунты вы можете <a href="/login/">по этой ссылке</a>. Выход из аккаунта значит выход из сессии конкретным аккаунтом.<br>Для очистки сессии отключите все аккаунты.<br>';
	print '<div class="session-control"></div>';
	print '</div></div>';
}
else
if('witnesses'==$path_array[1]){
	$replace['title']=htmlspecialchars('Делегаты').' - '.$replace['title'];
	if(''==$path_array[2]){
		print '<div class="page content">
		<h1>Делегаты ТОП 100</h1>
		<div class="article">';
		$list=$api->execute_method('get_witnesses_by_vote',array('',100));
		$num=1;
		foreach($list as $witness_arr){
			print '<p>#'.$num.' <a href="/@'.$witness_arr['owner'].'/">@'.$witness_arr['owner'].'</a> (<a href="'.htmlspecialchars($witness_arr['url']).'">url</a>), Голосов: '.number_format (floatval($witness_arr['votes'])/1000000/1000,1,'.',' ').'k SHARES, <a href="/witnesses/'.$witness_arr['owner'].'/">параметры</a></p>';
			$num++;
		}
		print '</div></div>';
	}
	else{
		$witness_arr=$api->execute_method('get_witness_by_account',array($path_array[2]));
		if($witness_arr['id']){
			$replace['title']=htmlspecialchars($witness_arr['owner']).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/witnesses/">&larr; Вернуться к списку делегатов</a>
			<h1>Делегат '.$witness_arr['owner'].'</h1>
			<div class="article">';
			$date=date_parse_from_format('Y-m-d\TH:i:s',$witness_arr['created']);
			$created_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			print '<p>Дата заявления о намерениях: <span class="timestamp" data-timestamp="'.$created_time.'">'.date('d.m.Y H:i:s',$created_time).'</span></p>';
			print '<p>Последний блок: <a href="/blocks/'.$witness_arr['last_confirmed_block_num'].'/">'.$witness_arr['last_confirmed_block_num'].'</a></p>';
			print '<p>Публичный ключ подписи: '.$witness_arr['signing_key'].'</p>';
			print '<h2>Голосуемые параметры цепи</h2>';
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
		}
	}
}
else
if('committee'==$path_array[1]){
	$replace['title']=htmlspecialchars('Комитет').' - '.$replace['title'];
	$committee_status_arr=array(
		0=>'Ожидает рассмотрения',
		1=>'Отменена создателем',
		2=>'Отказ (недостаток голосов)',
		3=>'Отказ (итоговая сумма вне диапазона)',
		4=>'Принята (идут выплаты)',
		5=>'Завершена'
	);
	if(''==$path_array[2]){
		print '<div class="page content">
		<h1>Заявки в комитет</h1>
		<div class="article">';
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
					print '<li><a href="/committee/'.$request_arr['request_id'].'/">#'.$request_arr['request_id'].' от '.$request_arr['creator'].'</a>, диапазон заявки: '.$request_arr['required_amount_min'].'&ndash;'.$request_arr['required_amount_max'].', окончание <span class="timestamp" data-timestamp="'.$end_time.'">'.date('d.m.Y H:i:s',$end_time).'</span></li>';
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
			$replace['title']=htmlspecialchars('Заявка #'.$request_id).' - '.$replace['title'];
			$replace['title']=htmlspecialchars('Комитет').' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/committee/">&larr; Вернуться в комитет</a>
			<h1>Заявка #'.$request_id.' в комитет</h1>
			<div class="article">';
			print '<p>Статус заявки: '.$committee_status_arr[$request_arr['status']].'</p>';
			print '<p>Создатель заявки: <a href="/@'.$request_arr['creator'].'/">@'.$request_arr['creator'].'</a></p>';
			print '<p>Ссылка на описание заявки: <a href="'.htmlspecialchars($request_arr['url']).'">'.htmlspecialchars($request_arr['url']).'</a></p>';
			print '<p>Получатель средств с комитета: <a href="/@'.$request_arr['worker'].'/">@'.$request_arr['worker'].'</a></p>';
			print '<p>Минимальная сумма токенов для удовлетворения заявки: '.$request_arr['required_amount_min'].'</p>';
			print '<p>Максимальная сумма токенов заявки: '.$request_arr['required_amount_max'].'</p>';
			$date=date_parse_from_format('Y-m-d\TH:i:s',$request_arr['start_time']);
			$start_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			$date=date_parse_from_format('Y-m-d\TH:i:s',$request_arr['end_time']);
			$end_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			print '<p>Время создания заявки: <span class="timestamp" data-timestamp="'.$start_time.'">'.date('d.m.Y H:i:s',$start_time).'</span></p>';
			print '<p>Время окончания заявки: <span class="timestamp" data-timestamp="'.$end_time.'">'.date('d.m.Y H:i:s',$end_time).'</span></p>';
			if($request_arr['status']>=2){
				$date=date_parse_from_format('Y-m-d\TH:i:s',$request_arr['conclusion_time']);
				$conclusion_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
				print '<p>Время принятия решения: <span class="timestamp" data-timestamp="'.$conclusion_time.'">'.date('d.m.Y H:i:s',$conclusion_time).'</span></p>';
			}
			if($request_arr['status']>=4){
				print '<p>Согласованная сумма: '.$request_arr['conclusion_payout_amount'].'</p>';
				print '<p>Выплачено: '.$request_arr['payout_amount'].'</p>';
				print '<p>Осталось выплатить: '.$request_arr['remain_payout_amount'].'</p>';
				$date=date_parse_from_format('Y-m-d\TH:i:s',$request_arr['last_payout_time']);
				$last_payout_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
				print '<p>Время последней выплаты: <span class="timestamp" data-timestamp="'.$last_payout_time.'">'.date('d.m.Y H:i:s',$last_payout_time).'</span></p>';
			}
			if(count($request_arr['votes'])){
				$max_rshares=0;
				$actual_rshares=0;
				print '<h2>Голоса</h2>';
				foreach($request_arr['votes'] as $vote_arr){
					$voter=$api->execute_method('get_accounts',array(array($vote_arr['voter'])));
					$effective_vesting_shares=floatval($voter[0]['vesting_shares'])-floatval($voter[0]['delegated_vesting_shares'])+floatval($voter[0]['received_vesting_shares']);
					$max_rshares+=$effective_vesting_shares;
					$actual_rshares+=$effective_vesting_shares*$vote_arr['vote_percent']/10000;
					$date=date_parse_from_format('Y-m-d\TH:i:s',$vote_arr['last_update']);
					$vote_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
					print '<p><span class="timestamp" data-timestamp="'.$vote_time.'">'.date('d.m.Y H:i:s',$vote_time).'</span>: <a href="/@'.$vote_arr['voter'].'/">@'.$vote_arr['voter'].'</a> проголосовал за обеспечение заявки в размере '.($vote_arr['vote_percent']/100).'%</p>';
				}
				$dgp=$api->execute_method('get_dynamic_global_properties');
				$chain_properties=$api->execute_method('get_chain_properties');
				$net_percent=$max_rshares/floatval($dgp['total_vesting_shares'])*100;
				$request_calced_payout=floatval($request_arr['required_amount_max'])*$actual_rshares/$max_rshares;
				print '<hr><p>Количество голосов: '.count($request_arr['votes']).', доля проголосовавших от всей сети: '.round($net_percent,2).'% (требуется >='.($chain_properties['committee_request_approve_min_percent']/100).'%), расчитанная сумма заявки на текущий момент: '.round($request_calced_payout,3).' VIZ.</p>';
			}
			print '</div></div>';
		}
	}
}
else
if('blocks'==$path_array[1]){
	$dgp=$api->execute_method('get_dynamic_global_properties');
	if(''==$path_array[2]){
		$replace['title']=htmlspecialchars('Обзор блоков').' - '.$replace['title'];
		print '<div class="page content">
	<h1>Обзор блоков</h1>
	<div class="article">';
		$date=date_parse_from_format('Y-m-d\TH:i:s',$dgp['genesis_time']);
		$genesis_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
		print '<p>Время запуска сети: <span class="timestamp" data-timestamp="'.$genesis_time.'">'.date('d.m.Y H:i:s',$genesis_time).'</span></p>';
		print '<p>Количество блоков: '.$dgp['head_block_number'].'</p>';
		print '<p>Количество в базе данных: '.mdb_ai('blocks').'</p>';
		print '<h3>Голосуемые параметры сети</h3>';
		print '<pre class="view_block">';
		$chain_properties=$api->execute_method('get_chain_properties');
		$view_props=print_r($chain_properties,true);
		$view_props=preg_replace('~\[(.[^\]]*)\] =\> (.*)\n~iUs','[<span style="color:red">$1</span>] => <span style="color:#1b72fa">$2</span>'.PHP_EOL,$view_props);
		$view_props=str_replace('<span style="color:#1b72fa">Array</span>','<span style="color:#069c40">Array</span>',$view_props);
		print $view_props;
		print '</pre>';
		print '<h3>Последние блоки</h3>';
		print '<div class="blocks">';
		$low_corner=max(0,(int)$dgp['head_block_number']-1000);
		for($i=(int)$dgp['head_block_number'];$i>$low_corner;--$i){
			print '<a href="/blocks/'.$i.'/">'.$i.'</a>';
		}
		print '<hr>';
		print '<a href="/blocks/1/">1</a>';
		print '</div>';
		print '</div></div>';
	}
	else{
		$id=(int)$path_array[2];
		if($id==$path_array[2]){
			$id_arr=$api->execute_method('get_ops_in_block',array($id,0));
			if($id_arr[0]){
				$replace['title']=htmlspecialchars('Обзор блока '.$id.'').' - '.$replace['title'];
				print '<div class="page content">
				<h1>Блокчейн VIZ, обзор блока #'.$id.'</h1>
				<div class="article">';
				print '<pre class="view_block">';
				$view_block=print_r($id_arr,true);
				$view_block=preg_replace('~\[(.[^\]]*)\] =\> (.*)\n~iUs','[<span style="color:red">$1</span>] => <span style="color:#1b72fa">$2</span>'.PHP_EOL,$view_block);
				$view_block=str_replace('<span style="color:#1b72fa">Array</span>','<span style="color:#069c40">Array</span>',$view_block);
				print $view_block;
				print '</pre>';
				print '<h3>Соседние блоки</h3>';
				print '<div class="blocks">';
				if($id+1 <= (int)$dgp['head_block_number']){
					print '<a href="/blocks/'.($id+1).'/">&uarr; '.($id+1).'</a>';
				}
				if(0 <= ($id-1)){
					print '<a href="/blocks/'.($id - 1).'/">&darr; '.($id - 1).'</a><hr>';
				}
				$high_corner=min((int)$dgp['head_block_number'],$id+50);
				$low_corner=max(0,$id-50);
				for($i=$high_corner;$i>$low_corner;--$i){
					print '<a href="/blocks/'.$i.'/"'.($i==$id?' class="current"':'').'>'.$i.'</a>';
				}
				print '</div>';
				print '</div></div>';
			}
		}
	}
}
if('tags'==$path_array[1]){
	if(''==$path_array[2]){
		print '<div class="page content">
	<h1>Популярные тэги</h1>
	<div class="article">';
		$cache_name='tags';
		if($buf=$cache->get($cache_name)){
			$tags=json_decode($buf,true);
		}
		else{
			$tags=$api->execute_method('get_trending_tags',array('',1000));
			$cache->set($cache_name,json_encode($tags),5);
		}
		$num=1;
		foreach($tags as $tag){
			print '<p id="'.$num.'">#'.$num.' <a href="/tags/'.htmlspecialchars($tag['name']).'/">'.htmlspecialchars($tag['name']).'</a>, количество отметок: '.$tag['top_posts'].', суммарная награда: '.$tag['total_payouts'].'</p>';
			$num++;
		}
		print '</div>';
	}
	else{
		$tag=urldecode($path_array[2]);
		$cache_name='tags-'.md5($tag.urldecode($_GET['start_permlink']).urldecode($_GET['start_author']));
		$content_arr=array();
		if($buf=$cache->get($cache_name)){
			$content_arr=json_decode($buf,true);
		}
		else{
			if(isset($_GET['start_author']) && isset($_GET['start_permlink'])){
				$content_arr=$api->execute_method(
					'get_discussions_by_created',
					array(
						array(
						'select_tags'=>array($tag),
						'limit'=>25,
						'start_author'=>urldecode($_GET['start_author']),
						'start_permlink'=>urldecode($_GET['start_permlink']),
						'raw'=>1
						)
					)
				);
			}
			else{
				$content_arr=$api->execute_method(
					'get_discussions_by_created',
					array(
						array(
						'select_tags'=>array($tag),
						'limit'=>25,
						'raw'=>1
						)
					)
				);
			}
			$cache->set($cache_name,json_encode($content_arr),5);
		}
		if($content_arr){
			$buf='';
			$buf.='
			<div class="page content">
				<a class="right" href="/tags/">&larr; Вернуться к тэгам</a>
				<h1>Тэг: '.htmlspecialchars($tag).'</h1>
			</div>';
			$last_author='';
			$last_permlink='';
			foreach($content_arr as $k=>$content){
				$last_author=$content['author'];
				$last_permlink=$content['permlink'];
				$date=date_parse_from_format('Y-m-d\TH:i:s',$content['created']);
				$content_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
				$json=json_decode($content['json_metadata'],true);

				$cover=false;
				if($json['image'][0]){
					$cover=$json['image'][0];
				}
				//$buf.=print_r($json['image'],true);
				$preview_text=mb_substr($content['body'],0,1024);
				$preview_text=str_replace('<br>',"\n",$preview_text);
				$preview_text=str_replace('<hr>',"\n",$preview_text);
				$preview_text=htmlspecialchars_decode($preview_text);
				$preview_text=str_replace('&nbsp;',' ',$preview_text);
				$preview_text=str_replace('<br />',"\n",$preview_text);
				$preview_text=str_replace('<br/>',"\n",$preview_text);
				$preview_text=str_replace('<p>',"\n",$preview_text);
				$preview_text=str_replace('</p>',"\n",$preview_text);
				$preview_text=mb_ereg_replace("\r",'',$preview_text);
				$preview_text=mb_ereg_replace("\t",'',$preview_text);
				$preview_text=str_replace('  ',' ',$preview_text);
				$preview_text=str_replace("\n\n","\n",$preview_text);
				$preview_text=strip_tags($preview_text);
				$preview_text=trim($preview_text,"\r\n\t ");
				$preview_text_arr=explode("\n",$preview_text);
				$preview_text_final=$preview_text_arr[0];
				if(mb_strlen($preview_text_final)<250){
					if($preview_text_arr[1]){
						$preview_text_final.='</p><p>'.(mb_strlen($preview_text_arr[1])>250?mb_substr($preview_text_arr[1],0,255,'utf-8').'&hellip;':$preview_text_arr[1]);
					}
				}
				if(mb_strlen($preview_text_final)<250){
					if($preview_text_arr[2]){
						$preview_text_final.='</p><p>'.(mb_strlen($preview_text_arr[2])>250?mb_substr($preview_text_arr[2],0,255,'utf-8').'&hellip;':$preview_text_arr[2]);
					}
				}
				else{
					$preview_text_final=mb_substr($preview_text_final,0,255,'utf-8').'&hellip;';
				}
				if($preview_text_final){
					$preview_text_final='<p>'.$preview_text_final.'</p>';
				}

				$buf.='
				<div class="page preview">
					<a href="/@'.$content['author'].'/'.htmlspecialchars($content['permlink']).'/" class="subtitle">'.htmlspecialchars($content['title']).'</a>';
				if($cover){
					$buf.='<div class="cover"><img src="https://i.goldvoice.club/0x0/'.htmlspecialchars($cover).'" alt=""></div>';
				}
				$buf.='
					<div class="article'.($cover?' cover-exist clearfix':'').'">';
				$buf.=$preview_text_final;
				$buf.='</div>';
				$tags=$json['tags'];
				if($tags){
					$tags_list=array();
					foreach($tags as $tag){
						$tags_list[]='<a href="/tags/'.htmlspecialchars($tag).'/">'.htmlspecialchars($tag).'</a>';
					}
					$buf.='<div class="tags">'.implode($tags_list).'</div>';
				}
				$buf.='<div class="info">
					<div class="author"><a href="/@'.$content['author'].'/" class="avatar" style=""></a><a href="/@'.$content['author'].'/">@'.$content['author'].'</a></div>
					<div class="timestamp" data-timestamp="'.$content_time.'">'.date('d.m.Y H:i:s',$content_time).'</div>
					<div class="right">
						<a class="award"></a>
						<a class="flag"></a>
						<div class="votes_count">'.$content['active_votes_count'].' голосов</div>
						<div class="comments">'.$content['children'].'<a href="/@'.$content['author'].'/'.htmlspecialchars($content['permlink']).'/#comments" class="icon"><i class="far fa-comment"></i></a></div>
					</div>
				</div>
			</div>';
			}
			if($last_author){
				$buf.='<div class="page">';
				$buf.='<a class="load_more" href="?start_author='.$last_author.'&start_permlink='.htmlspecialchars($last_permlink).'">Загрузить еще&hellip;</a>';
				$buf.='</div>';
			}
			print $buf;
		}
	}
}
if(''==$path_array[1]){
	$cache_name='index-'.md5(urldecode($_GET['start_permlink']).urldecode($_GET['start_author']));
	$content_arr=array();
	if($buf=$cache->get($cache_name)){
		$content_arr=json_decode($buf,true);
	}
	else{
		if(isset($_GET['start_author']) && isset($_GET['start_permlink'])){
			$content_arr=$api->execute_method(
				'get_discussions_by_created',
				array(
					array(
					'limit'=>100,
					'start_author'=>urldecode($_GET['start_author']),
					'start_permlink'=>urldecode($_GET['start_permlink']),
					'raw'=>1
					)
				)
				,true
			);
		}
		else{
			$content_arr=$api->execute_method(
				'get_discussions_by_created',
				array(
					array(
					'limit'=>100,
					'raw'=>1
					)
				)
				,true
			);
		}
		$cache->set($cache_name,json_encode($content_arr),5);
	}
	if($content_arr){
		$buf='';
		$last_author='';
		$last_permlink='';
		foreach($content_arr as $k=>$content){
			$last_author=$content['author'];
			$last_permlink=$content['permlink'];
			$date=date_parse_from_format('Y-m-d\TH:i:s',$content['created']);
			$content_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			$json=json_decode($content['json_metadata'],true);

			$cover=false;
			if($json['image'][0]){
				$cover=$json['image'][0];
			}
			//$buf.=print_r($json['image'],true);
			$preview_text=mb_substr($content['body'],0,1024);
			$preview_text=str_replace('<br>',"\n",$preview_text);
			$preview_text=str_replace('<hr>',"\n",$preview_text);
			$preview_text=htmlspecialchars_decode($preview_text);
			$preview_text=str_replace('&nbsp;',' ',$preview_text);
			$preview_text=str_replace('<br />',"\n",$preview_text);
			$preview_text=str_replace('<br/>',"\n",$preview_text);
			$preview_text=str_replace('<p>',"\n",$preview_text);
			$preview_text=str_replace('</p>',"\n",$preview_text);
			$preview_text=mb_ereg_replace("\r",'',$preview_text);
			$preview_text=mb_ereg_replace("\t",'',$preview_text);
			$preview_text=str_replace('  ',' ',$preview_text);
			$preview_text=str_replace("\n\n","\n",$preview_text);
			$preview_text=strip_tags($preview_text);
			$preview_text=trim($preview_text,"\r\n\t ");
			$preview_text_arr=explode("\n",$preview_text);
			$preview_text_final=$preview_text_arr[0];
			if(mb_strlen($preview_text_final)<250){
				if($preview_text_arr[1]){
					$preview_text_final.='</p><p>'.(mb_strlen($preview_text_arr[1])>250?mb_substr($preview_text_arr[1],0,255,'utf-8').'&hellip;':$preview_text_arr[1]);
				}
			}
			if(mb_strlen($preview_text_final)<250){
				if($preview_text_arr[2]){
					$preview_text_final.='</p><p>'.(mb_strlen($preview_text_arr[2])>250?mb_substr($preview_text_arr[2],0,255,'utf-8').'&hellip;':$preview_text_arr[2]);
				}
			}
			else{
				$preview_text_final=mb_substr($preview_text_final,0,255,'utf-8').'&hellip;';
			}
			if($preview_text_final){
				$preview_text_final='<p>'.$preview_text_final.'</p>';
			}

			$buf.='
			<div class="page preview">
				<a href="/@'.$content['author'].'/'.htmlspecialchars($content['permlink']).'/" class="subtitle">'.htmlspecialchars($content['title']).'</a>';
			if($cover){
				$buf.='<div class="cover"><img src="https://i.goldvoice.club/0x0/'.htmlspecialchars($cover).'" alt=""></div>';
			}
			$buf.='
				<div class="article'.($cover?' cover-exist clearfix':'').'">';
			$buf.=$preview_text_final;
			$buf.='</div>';
			$tags=$json['tags'];
			if($tags){
				$tags_list=array();
				foreach($tags as $tag){
					$tags_list[]='<a href="/tags/'.htmlspecialchars($tag).'/">'.htmlspecialchars($tag).'</a>';
				}
				$buf.='<div class="tags">'.implode($tags_list).'</div>';
			}
			$buf.='<div class="info">
				<div class="author"><a href="/@'.$content['author'].'/" class="avatar" style=""></a><a href="/@'.$content['author'].'/">@'.$content['author'].'</a></div>
				<div class="timestamp" data-timestamp="'.$content_time.'">'.date('d.m.Y H:i:s',$content_time).'</div>
				<div class="right">
					<a class="award"></a>
					<a class="flag"></a>
					<div class="votes_count">'.$content['active_votes_count'].' голосов</div>
					<div class="comments">'.$content['children'].'<a href="/@'.$content['author'].'/'.htmlspecialchars($content['permlink']).'/#comments" class="icon"><i class="far fa-comment"></i></a></div>
				</div>
			</div>
		</div>';
		}
		if($last_author){
			$buf.='<div class="page">';
			$buf.='<a class="load_more" href="?start_author='.$last_author.'&start_permlink='.htmlspecialchars($last_permlink).'">Загрузить еще&hellip;</a>';
			$buf.='</div>';
		}
		print $buf;
	}
}
if('test'==$path_array[1]){
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