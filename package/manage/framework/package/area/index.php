<?php

if (!defined('DEVER_APP_NAME')) {
	# 这样定义就可以将组件重复使用
	define('DEVER_APP_NAME', 'Area');
}
define('DEVER_APP_LANG', '地区设置');
define('DEVER_APP_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
include(DEVER_APP_PATH . '../../boot.php');