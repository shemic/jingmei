<?php
return [
    'name' => '基础配置',
    'struct' => [
        'name' => [
            'name'      => '配置名称',
            'type'      => 'varchar(32)',
        ],
    ],

    'default' => [
        'field' => 'id,name',
        'value' => [
            '1,"默认配置"',
        ],
        'num' => 1,
    ],
];