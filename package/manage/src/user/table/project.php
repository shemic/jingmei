<?php
return [
    'name' => '用户项目表',
    'order' => 'sort desc,id desc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(256)',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'uid' => [
            'name'      => '用户ID',
            'type'      => 'bigint',
        ],
        'cate_id' => [
            'name'      => '分类',
            'type'      => 'bigint',
            'value'     => 'user/project_cate',
            'default'   => '0',
        ],
        'service_id' => [
            'name'      => '业务',
            'type'      => 'bigint',
            'value'     => 'service/info',
        ],
        'cur_app_id' => [
            'name'      => '当前应用ID',
            'type'      => 'bigint',
            'value'     => 'service/app',
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
                2 => '删除',
            ],
        ],
    ],
    'index' => [
        'search' => 'uid,cate_id,status,sort',
        'code' => 'code.unique',
    ],
];