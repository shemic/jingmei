<?php
return [
    'name' => '帮助表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(256)',
        ],
        'cate_id' => [
            'name' => '分类',
            'type' => 'bigint',
            'value' => 'user/help_cate',   
        ],
        'content' => [
            'name'      => '内容',
            'type'      => 'text',
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
        
    ],
];