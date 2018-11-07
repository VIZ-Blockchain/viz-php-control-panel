<?php
ob_start();
if('@'==mb_substr($path_array[1],0,1)){
	if($path_array[2]){
		$author=mb_substr($path_array[1],1);
		$author_id=get_user_id($author);
		$permlink=urldecode($path_array[2]);
		$data=get_content($author_id,$permlink);
		if(isset($data['_id'])){
			$author_arr=get_user_by_id($author_id);
			$content_title=stripcslashes($data['title']);
			if($author_arr['nickname']){
				$replace['title']=htmlspecialchars($author_arr['nickname']).' - '.$replace['title'];
			}
			else{
				$replace['title']='@'.$author_arr['login'].' - '.$replace['title'];
			}
			$replace['title']=htmlspecialchars($content_title).' - '.$replace['title'];

			if(('edit'==$path_array[3])&&($auth)&&(($data['author']==$user_arr['_id'])||$admin)){
				$replace['title']=htmlspecialchars('Редактирование').' - '.$replace['title'];
				print $config['wysiwyg'];
				print '<div class="page content">
				<a class="right" href="/@'.$author.'/'.stripcslashes($data['permlink']).'/">&larr; Вернуться</a>
				<h1>Редактирование</h1>
				<div class="article post-content control">';
				print '<p><input type="text" name="permlink" class="round wide" placeholder="URL" value="'.htmlspecialchars(stripcslashes($data['permlink'])).'" disabled="disabled"></p>';
				print '<p><input type="text" name="title" class="round wide" placeholder="Заголовок" value="'.htmlspecialchars(stripcslashes($data['title'])).'"></p>';
				print '<p><input type="text" name="foreword" class="round wide" placeholder="Предисловие (превью для текста)" value="'.htmlspecialchars(stripcslashes($data['foreword'])).'"></p>';
				print '<p><input type="text" name="cover" class="round wide" placeholder="Ссылка на обложку (миниатюра для превью)" value="'.htmlspecialchars(stripcslashes($data['cover'])).'"></p>';
				print '<p><textarea name="content" rows="20" class="round wide" placeholder="Содержимое контента">'.htmlspecialchars(stripcslashes($data['body'])).'</textarea></p>';
				print '<p><input id="upload-file" type="file"><a class="upload-image-action action-button"><i class="fas fa-fw fa-file-image"></i> Загрузить изображение</a> <a class="wysiwyg-action action-button"><i class="fas fa-fw fa-pen-square"></i> WYSIWYG</a></p>';
				$tags_list=array();
				$tags=$mongo->executeQuery($config['db_prefix'].'.content_tags',new MongoDB\Driver\Query(['content'=>(int)$data['_id']],['sort'=>array('_id'=>1),'limit'=>(int)100]));
				$tags->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
				foreach($tags as $tag_id){
					$tag=get_tag($tag_id['tag']);
					if($tag){
						$tags_list[]=htmlspecialchars(stripcslashes($tag));
					}
				}

				print '<p><input type="text" name="tags" class="round wide" placeholder="Тэги через запятую (ключевые термины для поиска контента)" value="'.implode(',',$tags_list).'"></p>';
				print '<p>Процент кураторам: <input type="text" name="curation_percent" value="'.($data['curation_percent']/100).'" size="4" class="round" data-fixed="curation_percent_range" disabled="disabled"> <input type="range" name="curation_percent_range" data-fixed="curation_percent" min="0" max="+100" value="'.($data['curation_percent']/100).'" disabled="disabled"></p>';
				print '<p><a class="post-content-action button">Сохранить изменения</a></p>';
				print '</div></div>';
			}
			else{
				if(''!=$path_array[3]){
					header('location:/@'.$author.'/'.stripcslashes($data['permlink']).'/');
					exit;
				}
				$descr='';
				if(isset($data['foreword'])){
					$descr=mb_substr(strip_tags(stripcslashes($data['foreword'])),0,250).'...';
				}
				else{
					$descr=mb_substr(strip_tags(stripcslashes($data['body'])),0,250).'...';
				}
				$replace['description']=htmlspecialchars(trim($descr," \r\n\t"));
				$replace['description']=str_replace("\n",' ',$replace['description']);
				$replace['description']=str_replace('  ',' ',$replace['description']);

				$replace['head_addon'].='
				<meta property="og:url" content="https://viz.world/@'.$author.'/'.$data['permlink'].'/" />
				<meta name="og:title" content="'.htmlspecialchars($content_title).'" />
				<meta name="twitter:title" content="'.htmlspecialchars($content_title).'" />';

				$cover=false;
				if(isset($data['cover'])){
					$cover=$data['cover'];
					if(!preg_match('~^https://~iUs',$cover)){
						$cover='https://i.goldvoice.club/0x0/'.$cover;
					}
					$replace['head_addon'].='
		<link rel="image_src" href="'.$cover.'" />
		<meta property="og:image" content="'.$cover.'" />
		<meta name="twitter:image" content="'.$cover.'" />
		<meta name="twitter:card" content="summary_large_image" />';
					print '<img src="'.$cover.'" itemprop="image" class="schema">';
				}


				print view_content($data);

				print '<div class="page comments" id="comments">
	<div class="actions"><div class="reply reply-action content-reply unselectable">Оставить комментарий</div></div>
	<div class="subtitle">Комментарии</div>
	<hr>';

				$find=array('content'=>(int)$data['_id']);
				$sort=array('sort'=>['sort'=>1]);
				$rows=$mongo->executeQuery($config['db_prefix'].'.subcontent',new MongoDB\Driver\Query($find,$sort));
				$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
				foreach($rows as $row){
					print view_subcontent($row);
				}
				print '</div>';
				print '<div class="new-comments"></div>';
			}
		}
	}
	else{
		$account_login=mb_substr($path_array[1],1);
		$account_id=get_user_id($account_login);
		if(0<$account_id){
			$account_arr=get_user_by_id($account_id);
			if(!isset($account_arr['shares'])){
				redis_add_ulist('update_user',$account_arr['login']);
			}
			$account_name=$account_arr['login'];
			$account_avatar='/default-avatar.png';
			$account_about='';

			if($account_arr['nickname']){
				$account_name=htmlspecialchars($account_arr['nickname']);
				$replace['title']=htmlspecialchars($account_name).' - '.$replace['title'];
			}
			else{
				$replace['title']='@'.$account_name.' - '.$replace['title'];
			}
			if($account_arr['avatar']){
				$account_avatar='https://i.goldvoice.club/64x64/'.htmlspecialchars($account_arr['avatar']);
			}
			if($account_arr['about']){
				$account_about=htmlspecialchars(strip_tags(stripcslashes($account_arr['about'])));
			}
			$account_name=str_replace('@','',$account_name);
			print '<div class="page user-badge clearfix">
			<a href="/@'.$account_login.'/" class="avatar" style="background-image:url(\''.$account_avatar.'\')"></a>';
			if($auth){
				if($user_arr['_id']!=$account_id){
					print '
					<div class="actions" data-user-login="'.$account_login.'">';
					$link=get_user_link($user_arr['_id'],$account_id);
					if(false===$link){
						print '<div class="follow follow-action">Подписаться</div><br><div class="ignore ignore-action">Игнорировать</div>';
					}
					if(1==$link){
						print '<div class="unfollow unfollow-action">Отписаться</div>';
					}
					if(2==$link){
						print '<div class="unfollow unfollow-action">Перестать игнорировать</div>';
					}
					print '</div>';
				}
			}
			print '
			<div class="info">
				<div class="login"><a href="/@'.$account_login.'/">'.$account_name.'</a></div>
				<div class="descr">
					<p>'.$account_about.'</p>';
					if(isset($account_arr['content_count'])){
						print '<p>Контента: '.$account_arr['content_count'].', Голосов: '.$account_arr['vote_count'].'</p>';
					}
					print '<p>Баланс: '.($account_arr['balance']/1000).' VIZ, '.($account_arr['shares']/1000000).' SHARES</p>
				</div>
			</div>
	</div>';

			print '<div class="page content">
			<h2>Контент пользователя</h2>
			</div>';
			$find=array('author'=>(int)get_user_id($account_login),'status'=>0);
			$perpage=50;
			$offset=0;
			$sort=array('_id'=>-1);
			$rows=$mongo->executeQuery($config['db_prefix'].'.content',new MongoDB\Driver\Query($find,['sort'=>$sort,'limit'=>(int)$perpage,'skip'=>(int)$offset]));
			$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
			foreach($rows as $row){
				print preview_content($row);
			}
			print '<div class="page content load-more" data-action="user-content" data-user-login="'.$account_login.'"><i class="fa fw-fw fa-spinner" aria-hidden="true"></i> Загрузка&hellip;</div></div>';
		}
	}
}
else
if('tools'==$path_array[1]){
	$replace['title']=htmlspecialchars('Инструменты').' - '.$replace['title'];
	if(''==$path_array[2]){
		print '<div class="page content">
		<h1><i class="fas fa-fw fa-toolbox"></i> Инструменты</h1>
		<div class="article control">';
		print '<p><a href="/tools/invites/">Система инвайтов</a></p>';
		print '<p><a href="/tools/create-account/">Создание аккаунта</a></p>';
		print '<p><a href="/tools/delegation/">Делегирование доли</a></p>';
		print '<p><a href="/tools/schedule/">Расписание делегатов</a></p>';
		print '<p><a href="/tools/blocks/">Обзор блоков</a></p>';
		print '<p><a href="/tools/reset-account/">Сброс доступов к аккаунту</a></p>';
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
			print '<p>Количество блоков: '.$dgp['head_block_number'].'</p>';
			print '<p>Количество в базе данных (индекс): '.mongo_counter('blocks').'</p>';
			print '<p>Количество в базе данных (курсор): '.mongo_count('blocks').'</p>';
			print '<p>Количество пользователей в бд: '.mongo_count('users').'</p>';
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
			print '<p>Внимание! Вы можете <a href="/tools/invites/">проверить баланс кода до регистрации</a> с помощью публичного ключа. После регистрации весь баланс кода будет переведен в SHARES нового аккаунта. Все ключи аккаунта будут идентичны указанному в форме, при желании вы сможете изменить их позже.</p>';
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
		$replace['title']=htmlspecialchars('Сброс доступов к аккаунту').' - '.$replace['title'];
		print '<div class="page content">
		<a class="right" href="/tools/">&larr; Инструменты</a>
		<h1><i class="fas fa-fw fa-exchange-alt"></i> Сброс доступов к аккаунту</h1>
		<div class="article control">';
		print '<p>Внимание! Данная форма сброса доступа использует механизм главного пароля. С помощью него формируются приватные ключи и из них публичные, которые будут транслированы в блокчейн. Убедитесь, что сохранили дополнительно главный пароль, иначе вы рискуете потерять доступ к аккаунту и его токенам навсегда.</p>';
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
else
if('login'==$path_array[1]){
	$replace['title']=htmlspecialchars('Авторизиация').' - '.$replace['title'];
	print '<div class="page content">
	<h1>Авторизация</h1>
	<div class="article control">';
	print '<p>Внимание! При авторизации ключ записывается в ваш браузер и не передается на сервер. Если вы очистите кэш браузера или localStorage, то вам нужно будет вновь ввести свои данные для входа.</p>';
	print '<p><label><input type="text" name="login" class="round"> &mdash; логин</label></p>';
	print '<p><input type="password" name="posting_key" class="round"> &mdash; posting ключ</label></p>';
	print '<p><input type="password" name="active_key" class="round"> &mdash; active ключ (по желанию)</label></p>';
	print '<p><span class="auth-error"></span></p>';
	print '<p><input type="button" class="auth-action button" value="Сохранить доступ и авторизоваться"></p>';
	print '</p>';
	print '</div></div>';
}
else
if('publication'==$path_array[1]){
	$replace['title']=htmlspecialchars('Опубликовать контент').' - '.$replace['title'];
	print $config['wysiwyg'];
	print '<div class="page content">
	<h1><i class="fas fa-fw fa-plus-circle"></i> Опубликовать контент</h1>
	<div class="article post-content control">';
	print '<p><input type="text" name="permlink" class="round wide" placeholder="URL"></p>';
	print '<p><input type="text" name="title" class="round wide" placeholder="Заголовок"></p>';
	print '<p><input type="text" name="foreword" class="round wide" placeholder="Предисловие (превью для текста)"></p>';
	print '<p><input type="text" name="cover" class="round wide" placeholder="Ссылка на обложку (миниатюра для превью)"></p>';
	print '<p><textarea name="content" rows="20" class="round wide" placeholder="Содержимое контента"></textarea></p>';
	print '<p><input id="upload-file" type="file"><a class="upload-image-action action-button"><i class="fas fa-fw fa-file-image"></i> Загрузить изображение</a> <a class="wysiwyg-action action-button"><i class="fas fa-fw fa-pen-square"></i> WYSIWYG</a></p>';
	print '<p><input type="text" name="tags" class="round wide" placeholder="Тэги через запятую (ключевые термины для поиска контента)"></p>';
	print '<p>Процент кураторам: <input type="text" name="curation_percent" value="0" size="4" class="round" data-fixed="curation_percent_range"> <input type="range" name="curation_percent_range" data-fixed="curation_percent" min="0" max="+100" value="0"></p>';
	print '<p><a class="post-content-action button">Опубликовать</a></p>';
	print '</div></div>';
}
else
if('profile'==$path_array[1]){
	$replace['title']=htmlspecialchars('Профиль').' - '.$replace['title'];
	print '<div class="page content">
	<h1><i class="fas fa-fw fa-user-circle"></i> Профиль</h1>
	<div class="article control">';
	print '<div class="profile-control"></div>';
	print '</div></div>';
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
	print '<p>Внимание! Авторизовать дополнительный аккаунты вы можете <a href="/login/">по этой ссылке</a>. Выход из аккаунта значит выход из сессии конкретным аккаунтом.<br>Для очистки сессии отключите все аккаунты.<br>';
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
					if($witness_arr['hardfork_version_vote']!=$witness_arr['running_version']){
						print ', голосует за переход с версии: '.$witness_arr['hardfork_version_vote'].' начиная с: ';
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
else
if('tags'==$path_array[1]){
	$replace['title']='Тэги - '.$replace['title'];
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
		$tag_id=get_tag_id($tag);
		if($tag_id){
			$replace['title']=htmlspecialchars($tag).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/tags/">&larr; Вернуться</a>
			<h1>Тэг: #'.$tag.'</h1>
			</div>';
			$find=array('tag'=>(int)$tag_id);
			$perpage=50;
			$offset=0;
			$sort=array('_id'=>-1);
			$rows=$mongo->executeQuery($config['db_prefix'].'.content_tags',new MongoDB\Driver\Query($find,['sort'=>$sort,'limit'=>(int)$perpage,'skip'=>(int)$offset]));
			$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
			foreach($rows as $row){
				print preview_content_by_id($row['content']);
			}
			print '<div class="page content load-more" data-action="tag-content" data-tag="'.htmlspecialchars($tag).'"><i class="fa fw-fw fa-spinner" aria-hidden="true"></i> Загрузка&hellip;</div></div>';
		}
	}
}
else
if('feed'==$path_array[1]){
	print '<div class="page content">
	<h1>Лента новостей</h1>';
	if(!$auth){
		print '<p>Личная лента новостей доступна после авторизации и подписки на других пользователей.</p>';
		print '</div>';
	}
	else{
		$perpage=30;
		$rows=redis_read_feed($user_arr['_id'],0,$perpage);
		if(0==count($rows)){
			print '<p>У вас нет публикаций в ленте.</p>';
		}
		else{
			foreach($rows as $row){
				print preview_content_by_id($row);
			}
			print '<div class="page content load-more" data-action="feed-content" data-user-login="'.$user_arr['login'].'"><i class="fa fw-fw fa-spinner" aria-hidden="true"></i> Загрузка&hellip;</div></div>';
		}
		print '</div>';
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