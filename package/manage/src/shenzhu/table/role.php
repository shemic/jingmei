<?php
return [
    'name' => '角色表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'system_id' => [
            'name' => '系统',
            'type' => 'bigint',
            'value' => 'shenzhu/system',   
        ],
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'cate_id' => [
            'name'      => '分类',
            'type'      => 'bigint',
            'value'     => 'shenzhu/cate',
            'default'   => 1,
        ],
        'avatar' => [
            'name'      => '头像',
            'type'      => 'varchar(150)',
        ],
        'info' => [
            'name'      => '描述',
            'type'      => 'varchar(128)',
        ],
        'model' => [
            'name'      => '模型',
            'type'      => 'varchar(64)',
        ],
        'prompt' => [
            'name'      => '设定',
            'type'      => 'text',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
        'update' => [
            'name'      => '是否更新',
            'type'      => 'tinyint(1)',
            'default'   => 2,
            'value'     => [
                1 => '更新',
                2 => '不更新',
            ],
        ],
        'memory' => [
            'name'      => '记忆',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '保留',
                2 => '不保留',
            ],
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
        'search' => 'system_id,sort',
    ],
];