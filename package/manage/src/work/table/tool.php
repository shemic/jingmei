<?php
return [
    'name' => '工具表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'cate_id' => [
            'name' => '分类',
            'type' => 'bigint',
            'value' => 'work/cate',   
        ],
        'info' => [
            'name'      => '描述',
            'type'      => 'text',
        ],
        'model' => [
            'name'      => '平台模型',
            'type'      => 'varchar(64)',
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
                2 => '封禁',
            ],
        ],
    ],
    'index' => [
        'code' => 'code.unique',
        'search' => 'cate_id,sort',
    ],
];