<?php
return [
    'name' => '会话表',
    'struct' => [
        'system_id' => [
            'name' => '系统',
            'type' => 'bigint',
            'value' => 'shenzhu/system',   
        ],
        'role_id' => [
            'name' => '角色',
            'type' => 'bigint',
            'value' => 'shenzhu/role',   
        ],
        'bind_id' => [
            'name'      => '绑定ID',
            'type'      => 'varchar(256)',
        ],
        'session_id' => [
            'name'      => '会话ID',
            'type'      => 'varchar(256)',
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
        'bind' => 'bind_id,session_id.unique',
        'search' => 'role_id,bind_id,status',
    ],
];