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
	$replace['title']=htmlspecialchars('Аутентификация').' - '.$replace['title'];
	print '<div class="page content">
	<h1>Аутентификация</h1>
	<div class="article control">';
	print '<p>Внимание! При аутентификации ключ записывается в ваш браузер и не передается на сервер. Если вы очистите кэш браузера или localStorage, то вам нужно будет вновь ввести свои данные для входа.</p>';
	print '<p><label><input type="text" name="login" class="round"> &mdash; логин</label></p>';
	print '<p><input type="password" name="posting_key" class="round"> &mdash; posting ключ</label></p>';
	print '<p><input type="password" name="active_key" class="round"> &mdash; active ключ (по желанию)</label></p>';
	print '<p><span class="auth-error"></span></p>';
	print '<p><input type="button" class="auth-action button" value="Сохранить доступ и пройти аутентификацию"></p>';
	print '<hr><p><input type="button" class="auth-custom-action button opacity" value="Пройти аутентификацию через отправку custom операции в блокчейн"></p>';
	print '<hr><h3><img src="/shield-icon.svg"> Аутентификация через VIZ Shield</h3>';
	print '<div class="shield-auth-control"></div>';
	print '</div>';
	print '</div>';
}
else
if('wallet'==$path_array[1]){
	$replace['title']=htmlspecialchars('Кошелек').' - '.$replace['title'];
	print '<div class="page content">
	<h1><i class="fas fa-fw fa-wallet"></i> Кошелек</h1>
	<div class="article control">';
	print '<div class="wallet-control"></div>';
	print '</div></div>';
}
else
if('accounts'==$path_array[1]){
	$replace['title']=htmlspecialchars('Аккаунты').' - '.$replace['title'];
	print '<div class="page content">
	<h1><i class="fas fa-fw fa-user-cog"></i> Аккаунты</h1>
	<div class="article control">';
	print '<p>Внимание! Пройти аутентификацию дополнительныи аккаунтом вы можете <a href="/login/">по этой ссылке</a>. Выход из аккаунта значит выход из сессии конкретным аккаунтом.<br>Для очистки сессии отключите все аккаунты.<br>';
	print '<div class="session-control"></div>';
	print '</div></div>';
}
else
if('witnesses'==$path_array[1]){
	$replace['title']=htmlspecialchars('Делегаты').' - '.$replace['title'];
	if(''==$path_array[2]){
		print '<div class="page content">
		<h1><i class="fas fa-fw fa-user-shield"></i> Делегаты</h1>
		<div class="article">
		<div class="witness-votes"></div>
		<h3>ТОП-100</h3>';
		$hf=$api->execute_method('get_hardfork_version',array(),true);
		print '<p>Текущая версия hardfork: '.$hf.'</p>';
		$hf=intval(str_replace('.','',$hf));
		$hf=intval($hf/10);
		$list=$api->execute_method('get_witnesses_by_vote',array('',100));
		$num=1;
		foreach($list as $witness_arr){
			$witness_hf=intval(str_replace('.','',$witness_arr['running_version']));
			$witness_hf=intval($witness_hf/10);
			print '<p'.('VIZ1111111111111111111111111111111114T1Anm'==$witness_arr['signing_key']?' style="opacity:0.5"':'').'>#'.$num.' <a href="/@'.$witness_arr['owner'].'/">@'.$witness_arr['owner'].'</a> (<a href="'.htmlspecialchars($witness_arr['url']).'">url</a>), Голосов: '.number_format (floatval($witness_arr['votes'])/1000000/1000,1,'.',' ').'k SHARES, <a href="/witnesses/'.$witness_arr['owner'].'/">параметры</a>, версия: ';
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
						print ', голосует за переход на версию '.$witness_arr['hardfork_version_vote'].' начиная с ';
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
			<a class="right" href="/witnesses/">&larr; Вернуться к списку делегатов</a>
			<h1>Делегат <a href="/@'.$witness_arr['owner'].'/">'.$witness_arr['owner'].'</a></h1>
			<div class="article control">';
			$date=date_parse_from_format('Y-m-d\TH:i:s',$witness_arr['created']);
			$created_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			print '<p>Дата заявления о намерениях: <span class="timestamp" data-timestamp="'.$created_time.'">'.date('d.m.Y H:i:s',$created_time).'</span></p>';
			print '<p>Последний блок: <a href="/tools/blocks/'.$witness_arr['last_confirmed_block_num'].'/">'.$witness_arr['last_confirmed_block_num'].'</a></p>';
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
			print '<div class="witness-vote" data-witness="'.$witness_arr['owner'].'"></div>';
			print '<div class="witness-control" data-witness="'.$witness_arr['owner'].'"></div>';
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
	if('create'==$path_array[2]){
		$replace['title']=htmlspecialchars('Создать заявку').' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/committee/">&larr; Вернуться</a>
		<h1><i class="fas fa-fw fa-university"></i>Создать заявку в комитет</h1>
		<div class="article control">
		<p>Любой аккаунт может создать заявку для рассмотрения в комитете. Участники сети принимая участие в голосовании сами определяют, на что будет направлен фонд комитета. Это могут быть не только технические новые разработки, но и внутренняя активность в сети (поддержка, конкурсы, работа с новичками), внешний пиар, компенсация за полезные регистрации, поддержка инфраструктуры проектов. Цель комитета &mdash; принести максимальную пользу сети и токену.</p>';
		print '<div class="committee-create-request"></div>';
		print '</div></div>';
	}
	else
	if(''==$path_array[2]){
		print '<div class="page content">
		<a class="right button" href="/committee/create/">Создать заявку</a>
		<h1><i class="fas fa-fw fa-university"></i>Комитет</h1>
		<div class="article">';
		$dgp=$api->execute_method('get_dynamic_global_properties');
		print '<p>Фонд комитета: '.$dgp['committee_fund'].'</p>';
		print '<h3>Заявки в комитет</h3>';
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
			print '<div class="page content">
			<a class="right" href="/committee/">&larr; Вернуться</a>
			<h1>Заявка #'.$request_id.' в комитет</h1>
			<div class="article control">';
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
			print '<div class="committee-control" data-request-id="'.$request_id.'" data-creator="'.$request_arr['creator'].'" data-status="'.$request_arr['status'].'"></div>';
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
if('mongo'==$path_array[1] && $admin){
	$replace['title']=htmlspecialchars('Mongo admin').' - '.$replace['title'];
	if(isset($_GET['action'])){
		if('add_index'==$_GET['action']){
			if('text'!=$_POST['index']){
				$_POST['index']=(int)$_POST['index'];
			}
			$result=$mongo->executeCommand($_POST['db'],new MongoDB\Driver\Command(
				[
					'createIndexes'=>$_POST['collection'],
					'indexes'=>[
						[
							'name'=>$_POST['attr'].'_index'.$_POST['index'],
							'key'=>[$_POST['attr']=>$_POST['index']],
							'ns'=>$_POST['db'].'.'.$_POST['collection']
						]
					]
				])
			);
		}
		if('drop_index'==$_GET['action']){
			if(isset($_GET['index'])){
				$result=$mongo->executeCommand($_GET['db'],new MongoDB\Driver\Command(
					[
						'dropIndexes'=>$_GET['collection'],
						'index'=>$_GET['index']
					])
				);
			}
		}
		header('location:'.$_SERVER['HTTP_REFERER']);
		exit;
	}
	if(''!=$path_array[2]){
		$collection=$path_array[2];
		$collection_count=mongo_count($collection);
		if($collection_count){
			$replace['title']=htmlspecialchars($collection).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/mongo/">&larr; Вернуться</a>
			<h1>'.$collection.'</h1>
			<div class="article">';
			print '<p>Записей: '.$collection_count.'</p>';
			$perpage=25;
			$offset=0;
			if(isset($_GET['offset'])){
				$offset=(int)$_GET['offset'];
			}
			$pages=ceil($collection_count/$perpage);
			$page=$offset/$perpage;
			$prev_page=$page-1;
			$next_page=$page+1;
			$prev_page=max($prev_page,0);
			$next_page=min($next_page,$pages);

			$find=array();
			$sort=array('_id'=>1);
			$sort_str='';
			if(isset($_GET['sort_attr'])){
				$sort=array($_GET['sort_attr']=>(int)$_GET['sort_asc']);
				$sort_str='&sort_attr='.$_GET['sort_attr'].'&sort_asc='.$_GET['sort_asc'];
			}
			$rows=$mongo->executeQuery($config['db_prefix'].'.'.$collection,new MongoDB\Driver\Query($find,['sort'=>$sort,'limit'=>(int)$perpage,'skip'=>(int)$offset]));
			$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
			foreach($rows as $row){
				print '<p>';
				print_r($row);
				print '</p>';
			}
			print '<div class="pages">';
			print '<a>Текущая страница: '.($page+1).'</a>';
			if($offset>0){
				print '<a href="?offset='.($perpage*$prev_page).$sort_str.'">&larr; Предыдущая страница</a>';
			}
			if($next_page<$pages){
				print '<a href="?offset='.($perpage*$next_page).$sort_str.'">Следующая страница &rarr;</a>';
			}
			print '</div>';
			$indexes=$mongo->executeCommand($config['db_prefix'],new MongoDB\Driver\Command(['listIndexes'=>$collection]));
			$indexes->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
			print '<h3>Индексы</h3><ul>';
			foreach($indexes as $index){
				$sort_attr='';
				$sort_asc=1;
				foreach($index['key'] as $key=>$asc){
					$sort_attr=$key;
					$sort_asc=$asc;
					break;
				}
				print '<li class="clearfix"><a class="right" href="/mongo/?action=drop_index&db='.$config['db_prefix'].'&collection='.$collection.'&index='.$index['name'].'">Удалить индекс '.$index['name'].'</a>'.$index['name'].', ключи: '.json_encode($index['key']).($index['weights']?', weights: '.json_encode($index['weights']):'').', <a href="?sort_attr='.$sort_attr.'&sort_asc='.$sort_asc.'">сортировать</a></li>';
			}
			print '</ul>';
			print '</div></div>';
		}
	}
	if(''==$path_array[2]){
		print '<div class="page content">
		<h1>Mongo admin, db: '.$config['db_prefix'].'</h1>
		<div class="article">';
		$collections=$mongo->executeCommand($config['db_prefix'],new MongoDB\Driver\Command(['listCollections'=>1,'sort'=>['name'=>1]]));
		$collections->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
		foreach($collections as $collection){
			print '<p>';
			print '<a href="/mongo/'.$collection['name'].'/">'.$collection['name'].'</a>';
			print ', записей: '.mongo_count($collection['name']);
			print '</p>';
		}
		print '<h3>Добавить индекс</h3>';
		print '<form action="/mongo/?action=add_index" method="POST"><p>
		БД: <input type="text" name="db" value="'.$config['db_prefix'].'" class="round"><br>
		Коллекция: <input type="text" name="collection" value="" class="round"><br>
		Поле для индекса: <input type="text" name="attr" value="" class="round"><br>
		Индекс: <input type="text" name="index" value="" class="round"><br>
		<input type="submit" class="button" value="Создать индекс">
		</p></form>';
		print '</div></div>';
	}
}

if(''==$path_array[1]){
	$find=array('status'=>0,'parent'=>['$exists'=>false]);
	$perpage=50;
	$offset=0;
	$sort=array('_id'=>-1);
	$rows=$mongo->executeQuery($config['db_prefix'].'.content',new MongoDB\Driver\Query($find,['sort'=>$sort,'limit'=>(int)$perpage,'skip'=>(int)$offset]));
	$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
	foreach($rows as $row){
		print preview_content($row);
	}
	print '<div class="page content load-more" data-action="new-content"><i class="fa fw-fw fa-spinner" aria-hidden="true"></i> Загрузка&hellip;</div></div>';
}
$content=ob_get_contents();
ob_end_clean();