<?php
return [
    'name' => '工作流输入项关联表',
    'order' => 'id asc',
    'struct' => [
        'workflow_input_id' => [
            'name'      => '工作流输入项ID',
            'type'      => 'bigint',
            'value'     => 'work/workflow_input',
        ],
        'model' => [
            'name'      => '平台模型',
            'type'      => 'varchar(64)',
        ],
        'workflow_input_option_id' => [
            'name'      => '选项',
            'type'      => 'varchar(256)',
            'value'     => 'work/workflow_input_option',
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
        'search' => 'workflow_input_id',
    ],
];