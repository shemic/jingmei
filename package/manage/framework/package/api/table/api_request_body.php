<?php
$config = include('platform_request_body.php');
unset($config['struct']['platform_id']);
$config['struct'] += [
    'api_id' => [
        'name'      => '接口id',
        'type'      => 'int(11)',
    ],
];
$config['name'] = '接口请求体参数配置';
return $config;