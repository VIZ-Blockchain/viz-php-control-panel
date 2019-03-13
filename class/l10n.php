<?php
$l10n=[];
$l10n_presets=['ru-RU'=>'ru','ru'=>'ru','en-US'=>'en','en'=>'en'];
function set_l10n($id){
	global $l10n,$l10n_presets,$l10n_preset;
	if(is_int($id)){
		$code2=$l10n_base[$id]['code2'];
		if(isset($l10n_preset[$code2])){
			$l10n=$l10n_preset[$code2];
		}
	}
	else{
		if($l10n_presets[$id]){
			$code2=$l10n_presets[$id];
		}
		if(isset($l10n_preset[$code2])){
			$l10n=$l10n_preset[$code2];
		}
	}
}
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
		],
		'errors'=>[
			'user_not_found'=>'Пользователь не найден',
			'user_not_provided'=>'Пользователь не указан',
		],
		'media'=>[
			'follow_success'=>'Вы успешно подписались на',
			'follow_failure'=>'Не удается отправить операцию подписки на',
			'unfollow_success'=>'Вы стали соблюдать нейтралитет с',
			'unfollow_failure'=>'Не удается отправить операцию нейтралитета с',
			'follow'=>'Подписаться',
			'ignore'=>'Игнорировать',
			'ignore_success'=>'Вы успешно начали игнорировать',
			'ignore_failure'=>'Не удается отправить операцию игнорирования',
		],
		'sessions'=>[
			'auth_error'=>'Ошибка при инициализации сессии, попробуйте авторизоваться повторно позже',
			'init_session'=>'Инициализируем сессию, подождите',
			'time_error'=>'Ошибка синхронизации, проверьте ваше системное время (включите NTP)',
			'success'=>'Вы успешно авторизованы, сессия инициализирована',
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