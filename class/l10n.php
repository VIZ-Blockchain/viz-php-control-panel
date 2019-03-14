<?php
function set_l10n($id){
	global $l10n,$l10n_default,$l10n_presets,$l10n_preset;
	$err=false;
	if(is_int($id)){
		$code2=$l10n_base[$id]['code2'];
		if(isset($l10n_preset[$code2])){
			if($l10n_preset[$code2]['active']){
				$l10n=$l10n_preset[$code2];
				return true;
			}
			else{
				$err=true;
			}
		}
		else{
			$err=true;
		}
	}
	else{
		if($l10n_presets[$id]){
			$code2=$l10n_presets[$id];
		}
		if(isset($l10n_preset[$code2])){
			$l10n=$l10n_preset[$code2];
			return true;
		}
		else{
			$err=true;
		}
	}
	if($err){
		$l10n=$l10n_preset[$l10n_default];
		return false;
	}
}

$l10n=[];
$l10n_presets=['ru-RU'=>'ru','ru'=>'ru','en-US'=>'en','en'=>'en'];
$l10n_default='ru';
$l10n_base=[
	'ru'=>[
		'code2'=>'ru',
		'code3'=>'rus',
		'alias'=>false,
		'name'=>'Russian',
		'local-name'=>'Русский',
		'ru-name'=>'Русский язык',
		'active'=>true,
	]
];
$l10n_preset['ru']=[
	'template'=>[
		'auth'=>'Аутентификация',
		'energy_status'=>'Состояние энергии аккаунта',
		'drop_file'=>'Перетащите файл сюда (Drop file here)',
		'loading'=>'Загрузка',
	],
	'js'=>[
		'global'=>[
			'loading'=>'Загрузка',
			'auth'=>'Аутентификация',
			'attempt'=>'попытка',
			'error'=>'Ошибка',
			'need_auth'=>'Вам необходимо <a href="/login/">авторизоваться</a>.',
			'need_auth_with_active_key'=>'Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.',
		],
		'errors'=>[
			'user_not_found'=>'Пользователь не найден',
			'user_not_provided'=>'Пользователь не указан',
			'awarded_content'=>'Вы уже награждали данный контент',
			'api'=>'Ошибка в API запросе',
		],
		'media'=>[
			'follow_success'=>'Вы успешно подписались на',
			'follow_failure'=>'Не удается отправить операцию подписки на',
			'unfollow_success'=>'Вы стали соблюдать нейтралитет с',
			'unfollow_failure'=>'Не удается отправить операцию нейтралитета с',
			'follow'=>'Подписаться',
			'unfollow'=>'Отписаться',
			'ignore'=>'Игнорировать',
			'stop_ignore'=>'Перестать игнорировать',
			'ignore_success'=>'Вы успешно начали игнорировать',
			'ignore_failure'=>'Не удается отправить операцию игнорирования',
		],
		'sessions'=>[
			'auth_error'=>'Ошибка при инициализации сессии, попробуйте авторизоваться повторно позже',
			'init_session'=>'Инициализируем сессию, подождите',
			'time_error'=>'Ошибка синхронизации, проверьте ваше системное время (включите NTP)',
			'success'=>'Вы успешно авторизованы, сессия инициализирована',
			'custom_failure'=>'Не удается отправить custom операцию для инициализации сессии',
			'verify'=>'Сессия подтверждена',
			'active_key'=>'Сохранен Active ключ',
			'viz_shield'=>'Используется VIZ-Shield',
			'active'=>'используется',
			'switch'=>'переключиться',
			'eject'=>'отключить',
		],
		'shield'=>[
			'locked'=>'У вас заблокирован кошелек. После разблокировки перезагрузите страницу или',
			'link'=>'нажмите на ссылку',
			'accounts'=>'Выберите аккаунт для авторизации на сайте:',
			'login'=>'Пройти аутентификацию',
			'not_found'=>'У вас не включен <a class="start-shield-action link">VIZ.Shield</a>. После <a class="start-shield-action link">запуска</a> и разблокировки перезагрузите страницу или <a class="shield-auth-control-action link">нажмите на ссылку</a>.',
			'miss_user'=>'Не удается получить информацию о пользователе',
		],
		'wallet'=>[
			'withdraw_disabled'=>'Понижение доли отменено',
			'withdraw_enabled'=>'Понижение доли запущено',
			'delegate_success'=>'Делегирование прошло успешно',
			'delegate_failure'=>'Ошибка в делегировании',
			'no_records'=>'Записи отсутствуют',
			'return_delegation_caption'=>'Возврат делегированной доли',
			'returning'=>'вернется',
			'delegated_caption'=>'Список делегированной доли',
			'none_delegated'=>'Вы никому не делегировали долю.',
			'delegated_hold'=>'держит',
			'delegated_expire'=>'отозвать можно',
			'received_delegation_caption'=>'Держание доли',
			'none_received_delegation'=>'Никто не делегировал вам долю.',
			'received_delegation_from'=>'от',
			'received_delegation_expire_time'=>'отзыв возможен с',
			'enable_withdraw'=>'Включить понижение',
			'disable_withdraw'=>'Отключить понижение',
			'balance'=>'Баланс',
			'shares'=>'Доля сети',
			'effective_shares'=>'Эффективная доля сети',
			'delegated'=>'Делегировано',
			'received_delegation'=>'Получено делегированием',
			'delegate_caption'=>'Назначить делегирование',
			'delegate_descr'=>'Для того чтобы отозвать делегирование, укажите в количестве SHARES нулевое значение. Возврат делегированной доли может занять время.',
			'delegate_receiver'=>'получатель',
			'delegate_amount'=>'количество',
			'delegate_action'=>'Делегировать',
			'transfer_caption'=>'Выполнить перевод',
			'transfer_receiver'=>'получатель',
			'transfer_amount'=>'количество',
			'transfer_memo'=>'заметка',
			'transfer_in_shares'=>'перевод в долю сети',
			'transfer_action'=>'Отправить перевод',
			'history_caption'=>'История переводов',
			'history_filter_text'=>'Фильтр',
			'history_filter_range1'=>'От',
			'history_filter_range2'=>'До',
			'history_filter_all'=>'Всё',
			'history_filter_income'=>'Входящие',
			'history_filter_outcome'=>'Исходящие',
			'history_memo'=>'Заметка',
			'history_token'=>'Токен',
			'history_amount'=>'Количество',
			'history_receiver'=>'Получатель',
			'history_sender'=>'Отправитель',
			'history_date'=>'Дата',
		],
		'invite'=>[
			'reg_success'=>'Инвайт-код успешно активирован',
			'reg_failure'=>'Ошибка при активации инвайт-кода',
			'login'=>'Логин',
			'unavailable'=>'недоступен',
			'claim_success'=>'Код успешно активирован',
			'claim_failure'=>'Ошибка при активации кода',
			'create_success'=>'Инвайт-код успешно создан (информация сохранена в файл)',
			'create_failure'=>'Ошибка при создании инвайт-кода',
		],
		'committee'=>[
			'create_success'=>'Вы успешно подали заявку',
			'cancel_success'=>'Вы успешно отменили заявку',
			'vote_success'=>'Вы успешно приняли участие в голосовании по заявке',
			'vote_caption'=>'Голосование за заявку',
			'vote_percent'=>'Процент от максимальной суммы заявки',
			'vote_action'=>'Проголосовать',
			'manage_request_caption'=>'Управление заявкой',
			'cancel_request_action'=>'Отменить заявку',
			'request_url'=>'URL заявки',
			'request_worker'=>'Аккаунт-воркер',
			'request_min_amount'=>'Минимальная сумма токенов',
			'request_max_amount'=>'Максимальная сумма токенов',
			'request_duration'=>'Длительность заявки в днях (от 5 до 30)',
			'request_action'=>'Создать заявку',
		],
		'witness'=>[
			'invalid_user'=>'Текущий пользователь не совпадает с делегатом для обновления',
			'update_success'=>'Данные успешно транслированы в сеть',
			'properties_success'=>'Параметры успешно транслированы в сеть',
			'vote_success'=>'Вы успешно проголосовали за делегата',
			'votes_list'=>'Ваши голоса',
			'vote_caption'=>'Голосование за делегата',
			'manage_caption'=>'Управление делегатом',
		],
		'ps'=>[//paid subscriptions
			'subscribe_success'=>'Условия соглашения подписаны',
			'subscribe_failure'=>'Ошибка при подписи условий соглашения',
			'set_success'=>'Условия соглашения установлены',
			'set_failure'=>'Ошибка при установке условий соглашения',
			'manage_list'=>'Управление подписками',
			'contract_with'=>'Соглашение с',
			'account_prepand'=>'У аккаунта',
			'none_contracts'=>'отсутствуют активные платные подписки.',
			'sign_offer_descr'=>'Введите логин создателя соглашения, чтобы посмотреть условия соглашения платной подписки.',
			'sign_offer_login'=>'Логин',
			'sign_offer_lookup'=>'Запросить информацию',
			'set_offer_caption'=>'Условия соглашения платной подписки',
			'set_offer_creator'=>'Создатель соглашения',
			'set_offer_url'=>'URL (ссылка на сервис или услугу)',
			'set_offer_levels'=>'Количество доступных уровней подписки',
			'set_offer_levels_descr'=>'укажите 0, если намерены остановить продление или подписание новых соглашений',
			'set_offer_amount'=>'Количество токенов VIZ (например, 12.500)',
			'set_offer_period'=>'Период действия подписки (количество дней, например 30)',
			'set_offer_action'=>'Установить условия соглашения для платных подписок',
		],
	],
	'landing'=>[
		'descriptions_dao'=>'ДАО (Децентрализованное Автономное Общество)',
		'descriptions_committee'=>'Комитет общественных инициатив',
		'descriptions_award'=>'Награждение достойных',
		'descriptions_participation'=>'Справедливое участие',
	],
	'menu'=>[
		'media'=>'Медиа платформа',
		'media_feed'=>'Лента контента',
		'media_users'=>'Пользователи',
		'media_profile'=>'Профиль',
		'media_social'=>'Социальные связи',
		'media_notifications'=>'Уведомления',
		'media_publication'=>'Опубликовать контент',
		'wallet'=>'Кошелек',
		'accounts'=>'Аккаунты',
		'committee'=>'Комитет',
		'witnesses'=>'Делегаты',
		'tools'=>'Инструменты',
		'documentation'=>'Документация',
		'services'=>'Сервисы',
		'settings'=>'Настройки',
	],
];