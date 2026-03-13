<?php
return [
    'name' => '接口配置',
    'order' => 'sort asc',
    'struct' => [
        'platform_id' => [
            'name'      => '平台',
            'type'      => 'int(11)',
            'value'     => 'api/platform',
        ],

        'name' => [
            'name'      => '接口名称',
            'type'      => 'varchar(32)',
        ],

        'uri' => [
            'name'      => '接口地址',
            'type'      => 'varchar(100)',
        ],

        'method' => [
            'name'      => '请求方式',
            'type'      => 'tinyint(1)',
            'default'   => '-1',
            'value'     => [
                -1 => '使用平台标准请求方式',
                1 => 'get',
                2 => 'post',
            ],
        ],

        'post_method' => [
            'name'      => '请求头设置',
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
            'default'   => '-1',
            'value'     => [
                -1 => '使用平台标准请求方式',
                //1 => '无标准响应',
                2 => 'JSON',
                3 => 'XML',
                4 => 'Buffer',
            ],
        ],

        'notify' => [
            'name'      => '是否有回调',
            'type'      => 'tinyint(1)',
            'default'   => '2',
            'value'     => [
                1 => '有回调',
                2 => '无回调',
            ],
        ],

        'env' => [
            'name'      => '运行环境',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '通用',
                2 => 'h5',
                3 => 'jsapi',
                4 => 'app',
                5 => '小程序',
                6 => 'pc',
            ],
        ],

        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '展示',
                2 => '不展示',
            ],
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
];