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

	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
	<link rel="stylesheet" href="/css/app.css?{css_change_time}">

	<script type="text/javascript" src="/js/viz.min.js"></script>
	<script type="text/javascript" src="/js/jquery-3.3.1.min.js"></script>
	<script type="text/javascript" src="/js/app.js?{script_change_time}"></script>
</head>
<body class="landing">
<div class="header shadow unselectable">
	<div class="actions"><a class="menu-expand icon"><i class="fas fa-bars"></i></a></div>
	<div class="logo"><a href="/media/" class="logo"><span class="text"><img src="/logo-text-white.svg"></span></a></div>
	<div class="right">
		{header_menu}
		<div class="header-menu-el social-links"><a href="https://github.com/VIZ-Blockchain" target="_blank" class="icon" title="Github"><i class="fab fa-fw fa-github"></i></a><a href="https://discord.gg/nEu4MqR" target="_blank" class="icon" title="Discord"><i class="fab fa-fw fa-discord"></i></a><a href="https://t.me/viz_world" target="_blank" class="icon" title="Telegram"><i class="fas fa-fw fa-paper-plane"></i></a></div>
	</div>
</div>
<div class="menu">
	<div class="menu-list">
{menu}
		<div class="menu-el"><i class="fab fa-fw fa-fort-awesome"></i><a href="/">VIZ World</a></div>
		<hr>
		<div class="menu-el"><i class="fas fa-fw fa-home"></i><a href="/media/">{l10n_menu_media}</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-newspaper"></i><a href="/media/feed/">{l10n_menu_media_feed}</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-users"></i><a href="/media/users/">{l10n_menu_media_users}</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-user-circle"></i><a href="/media/profile/">{l10n_menu_media_profile}</a></div>
		<div class="menu-el inactive"><i class="fas fa-fw fa-user-friends"></i><a href="#/media/social/">{l10n_menu_media_social}</a></div>
		<div class="menu-el inactive"><i class="fas fa-fw fa-bell"></i><a href="#/media/notifications/">{l10n_menu_media_notifications}</a></div>
		<hr>
		<div class="menu-el"><i class="fas fa-fw fa-plus-circle"></i><a href="/media/publication/">{l10n_menu_media_publication}</a></div>
		<hr>
		<div class="menu-el"><i class="fas fa-fw fa-wallet"></i><a href="/wallet/">{l10n_menu_wallet}</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-user-cog"></i><a href="/accounts/">{l10n_menu_accounts}</a></div>
		<hr>
		<div class="menu-el"><i class="fas fa-fw fa-university"></i><a href="/committee/">{l10n_menu_committee}</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-user-shield"></i><a href="/witnesses/">{l10n_menu_witnesses}</a></div>
		<div class="menu-el"><i class="fas fa-fw fa-toolbox"></i><a href="/tools/">{l10n_menu_tools}</a></div>
		<div class="menu-el inactive"><i class="fas fa-fw fa-book"></i><a href="#/documentation/">{l10n_menu_documentation}</a></div>
		<div class="menu-el inactive"><i class="fas fa-fw fa-cloud"></i><a href="#/services/">{l10n_menu_services}</a></div>
		<div class="menu-el inactive"><i class="fas fa-fw fa-cogs"></i><a href="#/settings/">{l10n_menu_settings}</a></div>
	</div>
</div>
{pages}
<div class="footer-symbol"><div class="logo-symbol parallax-active"><div class="parralax-glare"></div><img src="/logo-symbol-anim.svg" style="width:100%" class="symbol" alt="VIZ Symbol"></div></div>
</body>
</html>