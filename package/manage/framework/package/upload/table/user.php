<?php
return [
    /*
    根据token获取uid信息：
    $info = Dever::db('upload/user')->find(1);
    $token = explode('-', $info['token']);
    Dever::load('util', 'manage')->setAuth(..$token);
    Dever::db($info['table'])->find($info['table_id']);
    */
    'name' => '上传用户表',
    # 数据结构
    'struct' => [
        'token'      => [
            'name'      => '用户token',
            'type'      => 'varchar(500)',
        ],

        'table'      => [
            'name'      => '用户表名',
            'type'      => 'varchar(100)',
        ],

        'table_id'     => [
            'name'      => '用户表id',
            'type'      => 'int(11)',
        ],
    ],
];
