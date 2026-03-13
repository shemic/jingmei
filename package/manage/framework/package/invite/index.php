<?php

if (!defined('DEVER_APP_NAME')) {
	# 这样定义就可以将组件重复使用
	define('DEVER_APP_NAME', 'Invite');
}
define('DEVER_APP_LANG', '邀请组件');
define('DEVER_APP_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
include(DEVER_APP_PATH . '../../boot.php');