<?php
return [
    'name' => 'api数据存储定义',
    'order' => 'id asc',
    'struct' => [
        'api_id' => [
            'name'      => '接口id',
            'type'      => 'int(11)',
        ],

        'table' => [
            'name'      => '数据表表名',
            'type'      => 'varchar(100)',
        ],

        'key' => [
            'name'      => '数据表字段名',
            'type'      => 'varchar(150)',
        ],

        'value' => [
            'name'      => '响应参数名/参数值',
            'type'      => 'varchar(100)',
        ],

        'type' => [
            'name'      => '类型',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '普通字段',
                2 => '条件字段',
                3 => '时间戳字段',
            ],
        ],
    ],
];