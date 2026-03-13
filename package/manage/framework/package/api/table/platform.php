<?php
return [
    'name' => '平台表',
    'struct' => [
        'name' => [
            'name'      => '平台名称',
            'type'      => 'varchar(32)',
        ],

        'host' => [
            'name'      => '主机域名',
            'type'      => 'varchar(500)',
        ],

        'method' => [
            'name'      => '请求方法',
            'type'      => 'tinyint(1)',
            'default'   => '2',
            'value'     => [
                1 => 'get',
                2 => 'post',
            ],
        ],

        'post_method' => [
            'name'      => '请求方式',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '普通表单：application/x-www-form-urlencoded',
                2 => '文件表单：multipart/form-data',
                3 => 'JSON：application/json',
            ],
        ],

        'response_type' => [
            'name'      => '响应数据类型',
            'type'      => 'tinyint(1)',
            'default'   => '2',
            'value'     => [
                //1 => '无标准响应',
                2 => 'JSON',
                3 => 'XML',
                4 => 'Buffer',
            ],
        ],
    ],
];