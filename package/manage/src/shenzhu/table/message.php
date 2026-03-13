<?php
return [
    'name' => '会话聊天记录表',
    'struct' => [
        'message_id' => [
            'name'      => '消息ID',
            'type'      => 'varchar(256)',
        ],
        'session_id' => [
            'name'      => '会话ID',
            'type'  => 'bigint',
            'value' => 'shenzhu/session',
        ],
        'type' => [
            'name'      => '类型',
            'type'      => 'varchar(20)',
            'default'   => 'text',
            'value'     => Dever::config('setting')['content'],
        ],
        'role' => [
            'name'      => '角色',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '助理',
                2 => '用户',
            ],
        ],
        'role_id' => [
            'name' => '角色ID',
            'type' => 'bigint',
        ],
        'content' => [
            'name'      => '内容',
            'type'      => 'text',
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
        'message_id' => 'message_id',
        'search' => 'session_id,status',
    ],
];