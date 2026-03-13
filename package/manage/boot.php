<?php
define('DEVER_ENTRY', 'index.php');
define('DEVER_PROJECT', 'jingmei');
define('DEVER_PROJECT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
if (defined('DEVER_PACKAGE')) {
	include('framework/package/'.DEVER_PACKAGE.'/index.php');
} else {
	include('framework/boot.php');
}