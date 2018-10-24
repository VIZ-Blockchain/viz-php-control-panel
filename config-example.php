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

// Timezone:
$config['server_timezone']='Etc/GMT';

// Enabled plugins:
$config['plugins']=array('blocks','users','transfers','content');
$config['plugins_extensions']=array('content'=>array('tags'));

$site_root=$_SERVER['DOCUMENT_ROOT'];