<?php
return [
    'name' => '项目分类表',
    'order' => 'sort desc,id desc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'uid' => [
            'name'      => '用户ID',
            'type'      => 'bigint',
        ],
        'cur_project_id' => [
            'name'      => '当前项目ID',
            'type'      => 'bigint',
            'value'     => 'user/project',
            'default'   => '0',
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
        'search' => 'uid,sort',
    ],
];