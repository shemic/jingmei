<?php
return [
    'name' => '内容产物类型对应工作流表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'cate_id' => [
            'name' => '分类',
            'type' => 'bigint',
            'value' => 'work/cate',   
        ],
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'type' => [
            'name'      => '内容类型',
            'type'      => 'varchar(20)',
            'default'   => 'text',
            'value'     => Dever::config('setting')['content'],
        ],

        'workflow' => [
            'name'      => '工作流',
            'type'      => 'varchar(500)',
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
        'search' => 'type,sort',
    ],
];