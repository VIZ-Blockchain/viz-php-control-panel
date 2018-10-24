<?php
// config.php example, copy and modify
// MongoDB access:
$config['db_host']='localhost';
$config['db_login']='viz';
$config['db_password']='';
$config['db_base']='viz';
$config['db_prefix']='viz';

// Redis access:
$config['redis_host']='localhost';
$config['redis_password']='';

$config['user_active_time']=300;//5 min

// Timezone:
$config['server_timezone']='Etc/GMT';

// Enabled plugins:
$config['plugins']=array('blocks','users','transfers','content','links');
$config['plugins_extensions']=array('content'=>array('tags','feed'));

$site_root=$_SERVER['DOCUMENT_ROOT'];