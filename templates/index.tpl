<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>{title}</title>
	<meta name="description" content="{description}">
	<meta property="og:description" content="{description}">
	<meta name="twitter:description" content="{description}">
	<meta name="viewport" content="width=device-width">
	<link rel="apple-touch-icon" sizes="57x57" href="/favicon/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="/favicon/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="/favicon/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="/favicon/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="/favicon/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="/favicon/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="/favicon/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="/favicon/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="/favicon/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="/favicon/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
	<link rel="manifest" href="/favicon/manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="/favicon/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">
	<link rel="icon" type="image/x-icon" href="/favicon.ico">
	<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">

	{head_addon}

	<link href="https://fonts.googleapis.com/css?family=Roboto|Roboto+Slab:400,700" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Arimo:400,700" rel="stylesheet">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
	<link rel="stylesheet" href="/css/app.css?{css_change_time}">

	<script type="text/javascript" src="/js/viz.min.js"></script>
	<script type="text/javascript" src="/js/jquery-3.3.1.min.js"></script>
	<script type="text/javascript" src="/js/app.js?{script_change_time}"></script>
</head>
<body>
<div class="header shadow unselectable">
	<div class="actions"><a class="menu-expand icon"><i class="fas fa-bars"></i></a></div>
	<div class="logo"><a href="/" class="logo"><img src="/logo-symbol-anim.svg" class="symbol">VIZ<span>.World</span></a></div>
	<div class="right">
		{header_menu}
		<div class="header-menu-el social-links"><a href="https://discord.gg/nEu4MqR" target="_blank" class="icon" title="Discord"><i class="fab fa-fw fa-discord"></i></a><a href="https://t.me/viz_world" target="_blank" class="icon" title="Telegram"><i class="fas fa-fw fa-paper-plane"></i></a></div>
		<div class="header-menu-el energy" title="Состояние энергии аккаунта">&hellip;</div>
		<div class="header-menu-el account"><a href="/login/" class="icon" title="Авторизация"><i class="fas fa-fw fa-sign-in-alt"></i></a></div>
	</div>

</div>
<div class="menu">
	<div class="menu-list">
{menu}
		<div class="menu-el"><i class="fas fa-fw fa-home"></i><a href="/">Обзор сети</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-newspaper"></i><a href="/feed/">Лента контента</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-users"></i><a href="/users/">Пользователи</a></div>
		<div class="menu-el inactive"><i class="fas fa-fw fa-user-friends"></i><a href="#/social/">Социальные связи</a></div>
		<div class="menu-el inactive"><i class="fas fa-fw fa-bell"></i><a href="#/notifications/">Уведомления</a></div>
		<hr>
		<div class="menu-el"><i class="fas fa-fw fa-plus-circle"></i><a href="/publication/">Опубликовать контент</a></div>
		<hr>
		<div class="menu-el"><i class="fas fa-fw fa-wallet"></i><a href="/wallet/">Кошелек</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-user-cog"></i><a href="/accounts/">Аккаунты</a></div>
		<hr>
		<div class="menu-el"><i class="fas fa-fw fa-university"></i><a href="/committee/">Комитет</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-user-shield"></i><a href="/witnesses/">Делегаты</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-toolbox"></i><a href="/tools/">Инструменты</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-user-circle"></i><a href="/profile/">Профиль</a></div>
		<div class="menu-el inactive"><i class="fas fa-fw fa-book"></i><a href="#/documentation/">Документация</a></div>
		<div class="menu-el inactive"><i class="fas fa-fw fa-cloud"></i><a href="#/services/">Сервисы</a></div>
		<div class="menu-el inactive"><i class="fas fa-fw fa-cogs"></i><a href="#/settings/">Настройки</a></div>
	</div>
</div>
<div class="main">
<div class="go-top-left-wrapper"><div class="go-top-button"><i class="fa fa-fw fa-chevron-up" aria-hidden="true"></i></div></div>
{pages}
</div>
<div class="notify-list"></div>
<div class="modal-overlay"></div>
<div class="modal drop-file"><i class="fas fa-fw fa-file-upload"></i> Drop file here&hellip;</div>
</body>
</html>