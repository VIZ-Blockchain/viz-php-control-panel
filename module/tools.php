<?php
ob_start();
if('tools'==$path_array[1]){
	$replace['title']=htmlspecialchars('Инструменты').' - '.$replace['title'];
	if(''==$path_array[2]){
		print '<div class="page content">
		<h1><i class="fas fa-fw fa-toolbox"></i> Инструменты</h1>
		<div class="article control">';
		print '<p><a href="/tools/paid-subscriptions/">Система платных подписок</a></p>';
		print '<p><a href="/tools/invites/">Система инвайтов</a></p>';
		print '<p><a href="/tools/create-account/">Создание аккаунта</a></p>';
		print '<p><a href="/tools/delegation/">Делегирование доли</a></p>';
		print '<p><a href="/tools/schedule/">Расписание делегатов</a></p>';
		print '<p><a href="/tools/blocks/">Обзор блоков</a></p>';
		print '<p><a href="/tools/reset-account/">Смена доступов к аккаунту</a></p>';
		print '</div></div>';
	}
	elseif('blocks'==$path_array[2]){
		$dgp=$api->execute_method('get_dynamic_global_properties');
		if(''==$path_array[3]){
			$replace['title']=htmlspecialchars('Обзор блоков').' - '.$replace['title'];
			print '<div class="page content">
		<h1>Обзор блоков</h1>
		<div class="article">';
			$date=date_parse_from_format('Y-m-d\TH:i:s',$dgp['genesis_time']);
			$genesis_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
			print '<p>Время запуска сети: <span class="timestamp" data-timestamp="'.$genesis_time.'">'.date('d.m.Y H:i:s',$genesis_time).'</span></p>';
			print '<p>Количество блоков: '.$dgp['head_block_number'].' ('.$api->endpoint.')</p>';
			print '<p>Количество в базе данных (индекс): '.mongo_counter('blocks').'</p>';
			print '<p>Количество в базе данных (курсор): '.mongo_count('blocks').'</p>';
			print '<p>Количество пользователей в бд: '.mongo_count('users').'</p>';
			print '<h3>Глобальная переменная</h3>';
			print '<p>Коэффициент конвертации total_vesting_fund/total_vesting_shares на блоке '.$dgp['head_block_number'].' равен '.(floatval($dgp['total_vesting_fund'])/floatval($dgp['total_vesting_shares'])).'</p>';
			print '<pre class="view_block">';
			$view_dgp=print_r($dgp,true);
			$view_dgp=preg_replace('~\[(.[^\]]*)\] =\> (.*)\n~iUs','[<span style="color:red">$1</span>] => <span style="color:#1b72fa">$2</span>'.PHP_EOL,$view_dgp);
			$view_dgp=str_replace('<span style="color:#1b72fa">Array</span>','<span style="color:#069c40">Array</span>',$view_dgp);
			print $view_dgp;
			print '</pre>';
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
					$replace['title']=htmlspecialchars('Обзор блока VIZ '.$id.'').' - '.$replace['title'];
					print '<div class="page content">
					<a class="right" href="/tools/blocks/">&larr; Вернуться</a>
					<h1>VIZ блок #'.$id.'</h1>
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
					print '<h3>Соседние блоки</h3>';
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
			$replace['title']=htmlspecialchars('Установка условий платной подписки').' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/paid-subscriptions/">&larr; Вернуться</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> Установка условий платной подписки</h1>
			<div class="article control">';
			print '<p>Вы можете настроить опции для соглашения с периодическими платежами на ваш аккаунт (платная подписка). Заполните ниже форму и отправьте транзакцию в блокчейн VIZ.</p>';
			print '<p>Любая сторона с помощью API запросов может проверить статус соглашения, список подписок или подписчиков зафиксированных в публичной блокчейн-системе VIZ.</p>';
			print '<div class="set-paid-subscription"></div>';
			print '</div></div>';
		}
		if('sign-agreement'==$path_array[3]){
			$replace['title']=htmlspecialchars('Подпись соглашения платной подписки').' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/paid-subscriptions/">&larr; Вернуться</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> Подпись соглашения платной подписки</h1>
			<div class="article control">';
			print '<p>Загрузите условия соглашения с создателем платной подписки. Выберите уровень подписки и подпишите соглашение, отправьте транзакцию в блокчейн VIZ.</p>';
			print '<p>Любая сторона с помощью API запросов может проверить статус соглашения в публичной блокчейн-системе VIZ.</p>';
			print '<div class="set-paid-subscribe"></div>';
			print '</div></div>';
		}
		if('manage-subscription'==$path_array[3]){
			$replace['title']=htmlspecialchars('Управление автоматическими платежами').' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/paid-subscriptions/">&larr; Вернуться</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> Управление автоматическими платежами</h1>
			<div class="article control">';
			print '<p>Выберите действующую платную подписку и установите параметр по автоматической оплате.</p>';
			print '<div class="manage-subscription"></div>';
			print '</div></div>';
		}
		if(''==$path_array[3]){
			$replace['title']=htmlspecialchars('Система платных подписок').' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/">&larr; Инструменты</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> Система платных подписок</h1>
			<div class="article control">';
			print '<p>Система платных подписок &mdash; универсальный процессинговый инструмент для оформления периодических платежей VIZ за сервис или услуги. Аккаунт может <a href="/tools/paid-subscriptions/set-options/">настроить опции для соглашения с периодическими платежами</a> в его сторону. Другие пользователи могут <a href="/tools/paid-subscriptions/sign-agreement/">подписать соглашение на периодические платежи (оформить платную подписку)</a>, <a href="/tools/paid-subscriptions/manage-subscription/">управлять автоматическими платежами</a>.</p>';
			print '<p>Любая сторона с помощью API запросов может проверить статус соглашения, список подписок или подписчиков зафиксированных в публичной блокчейн-системе VIZ.</p>';
			print '<div class="paid-subscriptions-options"></div>';
			print '<div class="paid-subscriptions-lookup"></div>';
			print '<div class="paid-subscription-lookup"></div>';
			print '</div></div>';
		}
	}
	elseif('invites'==$path_array[2]){
		if('claim'==$path_array[3]){
			$replace['title']=htmlspecialchars('Забрать баланс кода').' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/invites/">&larr; Вернуться к инвайтам</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> Забрать баланс кода</h1>
			<div class="article control">';
			print '<div class="invite-claim"></div>';
			print '</div></div>';
		}
		if('register'==$path_array[3]){
			$replace['title']=htmlspecialchars('Регистрация по инвайт-коду').' - '.$replace['title'];
			print '<div class="page content">
			<h1><i class="fas fa-fw fa-toolbox"></i> Регистрация по инвайт-коду</h1>
			<div class="article control">';
			print '<p>Внимание! Вы можете <a href="/tools/invites/">проверить баланс кода до регистрации</a> с помощью публичного ключа. После регистрации весь баланс кода будет переведен в SHARES нового аккаунта. Все ключи аккаунта будут идентичны указанному в форме, при желании вы можете <a href="/tools/reset-account/">разделить ключи для разных типов доступа</a>.</p>';
			print '<div class="invite-register"></div>';
			print '</div></div>';
		}
		if(''==$path_array[3]){
			$replace['title']=htmlspecialchars('Система инвайтов').' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tools/">&larr; Инструменты</a>
			<h1><i class="fas fa-fw fa-toolbox"></i> Система инвайтов</h1>
			<div class="article control">';
			print '<p>Инвайты (они же ваучеры) &mdash; универсальный инструмент для передачи фиксированного количества токенов VIZ другим людям (или ботам) вне блокчейна. Погасить код можно двумя способами: <a href="/tools/invites/claim/">перевести его баланс себе на аккаунт</a> или <a href="/tools/invites/register/">зарегистрировать с его помощью новый аккаунт</a>.</p>';
			print '<div class="invite-lookup"></div>';
			print '<div class="invite-control"></div>';
			print '</div></div>';
		}
	}
	elseif('create-account'==$path_array[2]){
		$replace['title']=htmlspecialchars('Создание аккаунта').' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/tools/">&larr; Инструменты</a>
		<h1><i class="fas fa-fw fa-user-plus"></i> Создание аккаунта</h1>
		<div class="article control">';
		print '<p>Внимание! Данная форма создания аккаунта использует механизм главного пароля. С помощью него формируются приватные ключи и из них публичные, которые будут транслированы в блокчейн. Убедитесь, что сохранили дополнительно главный пароль или приватные ключи.</p>';
		print '<div class="create-account-control"></div>';
		print '</div></div>';
	}
	elseif('reset-account'==$path_array[2]){
		$replace['title']=htmlspecialchars('Смена доступов к аккаунту').' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/tools/">&larr; Инструменты</a>
		<h1><i class="fas fa-fw fa-exchange-alt"></i> Смена доступов к аккаунту</h1>
		<div class="article control">';
		print '<p>Внимание! Данная форма смена доступов использует механизм главного пароля. С помощью него формируются приватные ключи для каждого типа доступа. Публичные ключи будут транслированы в блокчейн. Убедитесь, что сохранили дополнительно главный пароль, иначе вы рискуете потерять доступ к аккаунту и его токенам навсегда.</p>';
		print '<div class="reset-account-control"></div>';
		print '</div></div>';
	}
	elseif('delegation'==$path_array[2]){
		$replace['title']=htmlspecialchars('Делегирование доли').' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/tools/">&larr; Инструменты</a>
		<h1><i class="fas fa-fw fa-toolbox"></i> Делегирование доли</h1>
		<div class="article control">';
		print '<div class="delegation-control"></div>';
		print '<div class="delegation-returning-shares"></div>';
		print '<div class="delegation-received-shares"></div>';
		print '<div class="delegation-delegated-shares"></div>';
		print '</div></div>';
	}
	elseif ('schedule'==$path_array[2]) {
		$replace['title'] = htmlspecialchars('Расписание делегатов') . ' - ' . $replace['title'];
		$replace['description']='Расписание делегатов';
		print '
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.14.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bignumber.js/2.4.0/bignumber.min.js"></script>
<script src="/js/schedule.js"></script>
		<div class="page content">
		<a class="right" href="/tools/">&larr; Инструменты</a>
		<h1>Расписание делегатов</h1>
		<div class="article control">
			<div class="witness_schedule">&hellip;</div>
			<h3>Резервные делегаты</h3>
			<div class="witness_support_queue">&hellip;</div>
		</div></div>';
	}
}
$content=ob_get_contents();
ob_end_clean();