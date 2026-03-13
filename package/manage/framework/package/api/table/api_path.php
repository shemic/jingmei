<?php
return [
    'name' => '接口路径设置',
    'order' => 'sort asc',
    'struct' => [
        'api_id' => [
            'name'      => '接口id',
            'type'      => 'int(11)',
        ],

        'key' => [
            'name'      => '路径参数名',
            'type'      => 'varchar(32)',
        ],

        'value' => [
            'name'      => '路径参数值',
            'type'      => 'varchar(150)',
        ],

        'type' => [
            'name'      => '展示形式',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => 'value形式',
                2 => 'key/value形式',
                3 => 'key=value形式',
            ],
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
];