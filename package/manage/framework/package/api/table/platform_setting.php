<?php
return [
    'name' => '平台配置',
    'order' => 'sort asc, id asc',
    'struct' => [
        'platform_id' => [
            'name'      => '平台id',
            'type'      => 'int(11)',
        ],

        'name' => [
            'name'      => '参数描述',
            'type'      => 'varchar(32)',
        ],

        'key' => [
            'name'      => '参数名',
            'type'      => 'varchar(32)',
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
];