<?php
return [
    'name' => '集团',
    'order' => 'sort asc',
    'struct' => [
        'name' => [
            'name'      => '集团名称',
            'type'      => 'varchar(32)',
        ],
        'number' => [
            'name'      => '集团号',
            'type'      => 'varchar(32)',
        ],
        'mobile' => [
            'name'      => '手机号',
            'type'      => 'varchar(11)',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '启用',
                2 => '关闭',
            ],
        ],
    ],
    'default' => [
        'field' => 'name,number,sort,cdate',
        'value' => [
            '"默认集团","default",-100,' . DEVER_TIME,
        ],
        'num' => 1,
    ],
];