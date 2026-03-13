<?php
return [
    'db' => [
        'type' => 'Pdo',
        'pdo_type' => 'pgsql',
        'host' => '172.17.0.1',
        'port' => '5433',
        'name' => 'app',
        'user' => 'app',
        'pwd' => 'app',

        'pdo_type' => 'mysql',
        'host' => 'web-mysql',
        'port' => '3306',
        'name' => 'jingmei',
        'user' => 'root',
        'pwd' => '123456',

        'pool' => ['enable' => true, 'min' => 2, 'max' => 20, 'idle_time' => 60, 'wait_timeout' => 2],
    ],

    'redis' => [
        'host' => 'server-redis', 
        'port' => '6379', 
        'password' => '', 
        'expire' => 2147483647
    ],
];
