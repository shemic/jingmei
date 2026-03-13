<?php
return [
    'name' => '工作流输入选项表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],

        'value' => [
            'name'      => '选项值',
            'type'      => 'varchar(32)',
        ],

        'workflow_id' => [
            'name'      => '工作流ID',
            'type'      => 'bigint',
            'value'     => 'work/workflow',
        ],

        'workflow_input_id' => [
            'name'      => '工作流输入项ID',
            'type'      => 'bigint',
            'value'     => 'work/workflow_input',
        ],

        'tag' => [
            'name'      => '标签名',
            'type'      => 'varchar(32)',
        ],

        'icon' => [
            'name'      => '图标',
            'type'      => 'varchar(32)',
        ],

        'pic' => [
            'name'      => '图片',
            'type'      => 'varchar(150)',
        ],

        'info' => [
            'name'      => '描述',
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
        'workflow_input_id' => 'workflow_input_id',
    ],
];