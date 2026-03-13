<?php
$config = include('platform_request_body.php');
unset($config['struct']['platform_id']);
//$config['struct']['value']['name'] = '转换参数名';
$config['struct'] += [
    'api_id' => [
        'name'      => '接口id',
        'type'      => 'int(11)',
    ],
];
$config['name'] = '接口响应体参数配置';
return $config;