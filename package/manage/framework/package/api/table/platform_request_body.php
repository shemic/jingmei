<?php
return [
    'name' => '请求体通用参数配置',
    'order' => 'sort asc,id asc',
    'struct' => [
        'platform_id' => [
            'name'      => '平台id',
            'type'      => 'int(11)',
        ],

        'key' => [
            'name'      => '参数名',
            'type'      => 'varchar(32)',
        ],

        'value' => [
            'name'      => '参数值',
            'type'      => 'varchar(1000)',
        ],

        'type' => [
            'name'      => '参数处理',
            'type'      => 'varchar(100)',
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
];