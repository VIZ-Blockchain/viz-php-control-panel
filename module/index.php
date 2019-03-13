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
	print '<p>Внимание! Пройти аутентификацию аккаунтом вы можете <a href="/login/">по этой ссылке</a>. Выход из аккаунта значит выход из сессии конкретным аккаунтом.<br>Для очистки сессии отключите все аккаунты.<br>';
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
	$t->open('landing.tpl','index');
	$replace['description']='VIZ Blockchain — Decentralized Autonomous Society (DAS) with powerful award mechanic from emission';
	$replace['head_addon'].='
	<meta property="og:url" content="https://viz.world/" />
	<meta name="og:title" content="VIZ Blockchain" />
	<meta name="twitter:title" content="VIZ Blockchain" />
	<link rel="image_src" href="https://viz.world/landing-meta.png?v2" />
	<meta property="og:image" content="https://viz.world/landing-meta.png?v2" />
	<meta name="twitter:image" content="https://viz.world/landing-meta.png?v2" />
	<meta name="twitter:card" content="summary_large_image" />';

	print '
<div class="topbox">
	<div class="logo-symbol parallax-active"><div class="parralax-glare"></div><img src="/logo-symbol-anim.svg" style="width:100%" class="symbol" alt="VIZ Symbol"></div>
	<div class="description-bubble">
		<h1>VIZ Blockchain</h1>
		<ul>
			<li>— ДАО (Децентрализованное Автономное Общество)</li>
			<li>— Комитет общественных работ и инициатив</li>
			<li>— Награждение достойных</li>
			<li>— Справедливое участие</li>
		</ul>
	</div>
</div>';
	print '
<div class="info-bubbles">
	<a class="item color1" rel="award"><i class="icon fas fa-gem"></i><span class="title">Награждай</span><p>Стимулируйте экспансию ДАО в любом направлении, награждайте полезное.</p><span class="color">Узнать больше <i class="fas fa-angle-double-right"></i></span></a>
	<a class="item color2" rel="create"><i class="icon fas fa-hat-wizard"></i><span class="title">Создавай</span><p>Участвуйте в совместных проектах, созидайте, общайтесь.</p><span class="color">Узнать больше <i class="fas fa-angle-double-right"></i></span></a>
	<a class="item color3" rel="manage"><i class="icon fas fa-globe-africa"></i><span class="title">Управляй</span><p>Участники ДАО управляют всем блокчейном VIZ. Присоединяйтесь!</p><span class="color">Узнать больше <i class="fas fa-angle-double-right"></i></span></a>
</div>';
	print '
<div class="info-block bubble-item" id="award">
	<div class="text color1">
		<p>Каждый участник может награждать других пользователей из фонда наград, пополняемого эмиссией. Чем выше доля участника в ДАО, тем большую долю эмиссии он может раздать в виде наград. Награждение происходит мгновенно.<br>Также награда может быть <a href="https://viz.world/media/@on1x/viz-control-panel-beneficiaries/" target="_blank">разделена между несколькими получателями</a>.</p>
		<p><strong>Осознанное участие</strong> в ДАО VIZ логически сводится к награждению полезных действий для ДАО или лично для пользователя.</p>
		<p>Таким образом, награда стимулирует полезные действия, порождая цепную реакцию и увеличивая ценность ДАО VIZ.</p>
		<p>Кто-то написал полезный сервис и достоин награды? <strong>Просто наградите его.</strong> Это вернётся сторицей.</p>
		<p>Кто-то написал интересную статью, записал видеоролик, посадил дерево, написал отчёт по волонтерской работе, нарисовал картину или сделал общественно значимое дело? Просто наградите его. <strong>Награждение стимулирует паттерн поведения.</strong> <a href="https://viz.world/media/@on1x/viz-real-life-usage/" target="_blank">Хорошее притягивает хорошее</a>.</p>
		<p>Энергия восстанавливается линейно на 20% за 24 часа, что позволяет планировать и контролировать её использование.</p>
		<p><em>Ограничений на использование энергии нет, аккаунт может использовать для награждения все 100% своего потенциала.</em></p>
	</div>
</div>';
	print '
<div class="info-block bubble-item" id="create">
	<div class="text color2">
		<p>Кем бы вы ни были, чем бы ни занимались, <strong>инициативным</strong> всегда найдётся место в ДАО VIZ:</p>
		<p>1. Пользователи могут просто использовать сайт (интегрированный с VIZ)  по назначению и получать награды от других.</p>
		<p>2. Владельцы сайтов и сообществ могут подключить VIZ, <strong>стимулировать полезные действия внутри своего сообщества</strong> и формировать рейтинг или репутацию пользователей. Это могут быть любые сайты: творческие группы, сборник научных публикаций, рассказы для детей, блоги про видеоигры или портал волонтеров для сбора пожертвований.</p>
		<p>3. Исследователям будет интересно разобраться в тонкостях механики блокчейн-системы, визионерам — принимать участие в просвещении новичков, расширении локальных сообществ.</p>
		<p>4. Для разработчиков игр, приложений и сервисов — <strong>интеграция VIZ</strong> для награждения своих пользователей. Сервис платных подписок позволяет организовать процессинг переводов токенов для любого вида приложений. Механика награждений из эмиссии также позволяет создать замкнутую систему с собственным распределением фонда наград приложения.</p>
		<p>Вы можете делать, что пожелаете — в одиночку или с командой единомышленников. Всё зависит от вас самих.</p>
	</div>
</div>';
	print '
<div class="info-block bubble-item" id="manage">
	<div class="text color3">
		<p>Управление происходит на добровольной основе. Принцип ДАО VIZ — свобода выбора и истинная долевая демократия. Если кто-то не пользуется своим правом управления согласно своей доле, то управлять будут инициативные участники ДАО.</p>
		<p>Каждый участник может конвертировать токены (VIZ) в долю ДАО VIZ (SHARES). Именно благодаря долевому участию и происходит управление ДАО VIZ:</p>
		<p>1. <strong>Управление фондом наград</strong> — каждый участник может использовать энергию (возобновляемый со временем потенциал доли в ДАО VIZ), чтобы на конкурентной основе распоряжаться частью фонда наград. Награждение происходит мгновенно.</p>
		<p>2. <strong>Управление фондом комитета поддержки инициатив</strong> — каждый участник может проголосовать за заявку в комитете, повлияв на сумму, которую получит исполнитель в случае удовлетворения заявки. Потенциал влияния линейно зависит от доли в ДАО VIZ.</p>
		<p>3. <strong>Голосование за делегатов</strong> — каждый участник может проголосовать за любое количество делегатов, которые поддерживают инфраструктуру сети и участвуют в <a href="https://viz.world/media/@on1x/viz-quorum-calc-median-chain-properties/" target="_blank">голосовании за параметры блокчейн-системы</a>. Вес от доли в ДАО VIZ будет поровну распределён между выбранными делегатами.</p>
	</div>
</div>';
	print '
<div class="info-block">
	<h2>Особенности и возможности</h2>
	<div class="text">
		<p><img src="/check.svg" alt=""> <a href="https://viz.world/media/@on1x/viz-technical-documentation/" target="_blank">ДАО VIZ</a> — только сообщество участников VIZ решает, как будет развиваться экосистема. Никаких начальников или официального сайта! Видите возможность? Просто беритесь за неё и реализуйте!</p>
		<p><img src="/check.svg" alt=""> Очень быстрый — 3 секунды между блоками, блокчейн VIZ относится к Graphene экосистеме.</p>
		<p><img src="/check.svg" alt=""> <a href="https://viz.world/media/@on1x/viz-quorum-calc-median-chain-properties/" target="_blank">Консенсус управления блокчейн-системой</a> — уникальная система чередования делегатов.</p>
		<p><img src="/check.svg" alt=""> <a href="https://viz.world/media/@on1x/fair-dpos/" target="_blank">Справедливый DPoS</a> — участник может проголосовать за любое количество делегатов, при этом вес его доли разделится между ними поровну.</p>
		<p><img src="/check.svg" alt=""> Награждение полезного происходит из фонда наград, пополняемого эмиссией. Каждый участник, таким образом, может управлять своим «потоком эмиссии», согласно своей доле ДАО VIZ.</p>
		<p><img src="/check.svg" alt=""> Комитет поддержки инициатив — помогайте развивать ДАО VIZ и получайте за это токены от комитета. Участвуйте в работе комитета, голосуя за награждение других участников сообщества.</p>
		<p><img src="/check.svg" alt=""> <a href="https://viz.world/media/@on1x/paid-subscriptions-processing/" target="_blank">Система платных подписок</a> — процессинг периодических переводов на блокчейне.</p>
		<p><img src="/check.svg" alt=""> Система ваучеров (они же инвайт-коды) и <a href="https://viz.world/media/@on1x/anonymous-account/" target="_blank">анонимная регистрация</a> — две дополнительных механики для процессинга создания аккаунтов.</p>
		<p><img src="/check.svg" alt=""> Возможность экспансии — механизм награждения уникален: он мгновенный, <a href="https://viz.world/media/@on1x/viz-control-panel-beneficiaries/" target="_blank">гибкий</a>, самовозобновляемый, стремится к справедливой долевой конкуренции за фонд наград. В совокупности с <a href="https://viz.world/media/@on1x/viz-gates/" target="_blank">социальными шлюзами</a> позволяет привлекать без регистрации инициативных и созидающих людей со всего интернета!</p>
	</div>
</div>';
	print '
<div class="info-block">
	<h2>Код и библиотеки</h2>
	<div class="text">
		<p>Все основные разработки открыты (<a href="https://github.com/VIZ-Blockchain" target="_blank">ссылка на GitHub</a>) и большинство из них доступны по свободной MIT лицензии:
			<ul class="disc">
				<li><a href="https://github.com/VIZ-Blockchain/viz-cpp-node" target="_blank">Блокчейн-нода VIZ на C++</a> — актуальная версия в ветке master, MIT лицензия.</li>
				<li><a href="https://github.com/VIZ-Blockchain/viz-php-control-panel" target="_blank">Контрольная панель на PHP</a> — поддерживает актуальную версию VIZ, MIT лицензия, система плагинов, содержит медиа-платформу в виде плагина (<a href="https://viz.world/media/" target="_blank">рабочий пример расположен на VIZ.world</a>).</li>
				<li><a href="https://github.com/VIZ-Blockchain/viz-js-lib" target="_blank">JS библиотека</a> — поддерживает актуальную версию VIZ, MIT лицензия, <a href="https://github.com/VIZ-Blockchain/viz-js-lib/tree/master/doc" target="_blank">доступна документация</a>.</li>
				<li><a href="https://github.com/VIZ-Blockchain/viz-go-lib" target="_blank">GO библиотека</a> — поддерживает актуальную версию VIZ, MIT лицензия, <a href="https://viz.world/media/@asuleymanov/viz-gov2/" target="_blank">публикация автора</a>.</li>
				<li><a href="https://github.com/t3ran13/php-graphene-node-client" target="_blank">PHP библиотека</a> — поддерживает актуальную версию VIZ, MIT лицензия, <a href="https://viz.world/media/@php-node-client/update-of-php-graphene-node-client-v5-1-2-v5-2-0/" target="_blank">публикация автора</a>.</li>
				<li><a href="https://github.com/VIZ-Blockchain/viz-python-lib" target="_blank">Python библиотека</a> — в разработке, MIT лицензия.</li>
			</ul>
		</p>
	</div>
</div>';
	print '
<div class="info-block">
	<h2>Сервисы от разработчиков</h2>
	<div class="text">
		<p>Среди участников ДАО VIZ есть разработчики (программисты), которые создали или имплементировали разные приложения или сервисы:
			<ul class="disc">
				<li><a href="https://viz-doc.rtfd.io/" target="_blank">VIZ.doc</a> — документация и описание механизмов VIZ, <a href="https://viz.world/media/@viz.report/viz-doc-2-0/" target="_blank">публикация автора</a>.</li>
				<li><a href="https://viz.world/media/" target="_blank">VIZ.World</a> — один из первых сайтов про VIZ (на нём вы сейчас и находитесь), выступает в роли медиа-платформы про VIZ и контрольной панели.</li>
				<li><a href="https://viz.world/media/@viz-social-bot/social-viz-gateway-for-telegram/" target="_blank">Социальный бот VIZ для Telegram</a> — простой сервис-бот, после добавления в любой чат позволяет награждать других участников.</li>
				<li><a href="https://viz.sale/" target="_blank">Магазин инвайтов VIZ</a> — магазин с инвайт-кодами, <a href="https://viz.world/media/@solox/invites/" target="_blank">публикация автора</a>.</li>
				<li><a href="https://viz.world/media/@xchng/%D0%BF%D1%80%D0%B0%D0%B2%D0%B8%D0%BB%D0%B0-%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D1%8B-%D0%B0%D0%B2%D1%82%D0%BE%D0%BC%D0%B0%D1%82%D0%B8%D1%87%D0%B5%D1%81%D0%BA%D0%BE%D0%B3%D0%BE-%D1%88%D0%BB%D1%8E%D0%B7%D0%B0-xchngviz/" target="_blank">BitShares шлюз от XCHNG</a> — позволяет торговать тикером <a href="http://cryptofresh.com/a/XCHNG.VIZ" target="_blank">XCHNG.VIZ</a> в DEX блокчейн-системе BitShares.</li>
				<li><a href="https://github.com/denis-skripnik/viz-exchange" target="_blank">Код сервиса для частного обменника</a> — позволяет обменивать в автоматическом режиме токены между Graphene блокчейн-системами, <a href="https://viz.world/media/@denis-skripnik/viz-exchange-1/" target="_blank">публикация автора</a>.</li>
				<li><a href="https://t.me/viz_awards_bot" target="_blank">Телеграм бот уведомлений о наградах в VIZ</a> — <a href="https://viz.world/media/@denis-skripnik/viz-awards-notify/" target="_blank">публикация автора</a>.</li>
				<li><a href="https://t.me/viz_committee_bot" target="_blank">Телеграм бот уведомлений о заявках в комитет VIZ</a> — <a href="https://viz.world/media/@denis-skripnik/viz-committee-bot/" target="_blank">публикация автора</a>.</li>
				<li><a href="https://t.me/thallid_pom_bot" target="_blank">Thallid POM бот для VIZ</a> — бот для награждения участников Телеграм чата, <a href="https://viz.world/media/@ksantoprotein/thallid-intro/" target="_blank">публикация автора</a>.</li>
				<li><a href="https://t.me/viz_props_bot" target="_blank">Телеграм бот уведомлений о смене голосуемых параметров VIZ</a> — <a href="https://viz.world/media/@denis-skripnik/" target="_blank">автор</a>.</li>
				<li><a href="https://t.me/vizwatchdogbot" target="_blank">Телеграм бот Watchdog</a> — следит и уведомляет об активности (или неактивности) делегатов, <a href="https://viz.world/media/@ropox/viz-watchdog-1539462587/" target="_blank">публикация автора</a>.</li>
				<li><a href="https://dpos.space/profiles/" target="_blank">Dpos.space</a> — сайт со множеством сервисов для VIZ (просмотр истории аккаунта), <a href="https://viz.world/media/@denis-skripnik/dpos.space/" target="_blank">публикация автора</a>.</li>
			</ul>
		</p>
	</div>
</div>';
}
$content=ob_get_contents();
ob_end_clean();