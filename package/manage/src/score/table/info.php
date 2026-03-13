<?php
return [
    'name' => '用户积分设置表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '积分名称',
            'type'      => 'varchar(32)',
        ],
        'desc' => [
            'name'      => '介绍',
            'type'      => 'varchar(800)',
        ],
        'exp' => [
            'name'      => '货币换算',
            'type'      => 'varchar(50)',
            'default'   => '0',
        ],
        'symbol' => [
            'name'      => '积分符号',
            'type'      => 'varchar(32)',
            'default'   => 'sparkles',
        ],
        'symbol_location' => [
            'name'      => '符号位置',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '前',
                2 => '后',
            ],
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => 1,
        ],
        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '正常',
                2 => '封禁',
            ],
        ],
    ],

    'default' => [
        'field' => 'name,exp,symbol,cdate',
        'value' => [
            '"积分",100,"sparkles",' . DEVER_TIME,
        ],
        'num' => 1,
    ],
];