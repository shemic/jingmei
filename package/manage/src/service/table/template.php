<?php
return [
    'name' => '业务模板',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
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
                1 => '正常',
                2 => '删除',
            ],
        ],
    ],
];