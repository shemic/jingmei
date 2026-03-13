<?php
return [
    'name' => '应用表',
    'order' => 'sort asc',
    'struct' => [
        'name' => [
            'name'      => '应用名称',
            'type'      => 'varchar(32)',
        ],

        'desc' => [
            'name'      => '应用描述',
            'type'      => 'varchar(300)',
        ],

        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '上架',
                2 => '下架',
            ],
        ],
        
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
];