<?php
return [
    'name' => '应用工作流设置表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'app_id' => [
            'name'      => '应用ID',
            'type'      => 'bigint',
            'value'     => 'service/app',
        ],

        'location' => [
            'name'      => '位置',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '发布栏',
                //2 => '顶部栏',
            ],
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
                2 => '删除',
            ],
        ],
    ],
    'index' => [
        'app_id' => 'app_id',
    ],
];