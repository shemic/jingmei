<?php
return [
    'name' => '平台签名配置',
    'order' => 'sort asc, id desc',
    'struct' => [
        'platform_id' => [
            'name'      => '平台id',
            'type'      => 'int(11)',
        ],

        'name' => [
            'name'      => '签名名称',
            'type'      => 'varchar(150)',
            'default'   => 'sign',
        ],

        'arg' => [
            'name'      => '签名参数',
            'type'      => 'varchar(2000)',
        ],

        'encrypt' => [
            'name'      => '加密方式',
            'type'      => 'int(11)',
            'default'   => '-1',
            //'value'     => $encrypt,
        ],

        'after' => [
            'name'      => '最后处理',
            'type'      => 'int(11)',
            'value'     => 'api/format',
        ],

        'kv_type' => [
            'name'      => '键名键值形式',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => 'value形式',
                2 => 'key形式',
                3 => 'key=value形式',
                4 => 'keyvalue形式',
                5 => 'key:value形式',
            ],
        ],

        'kv_sort' => [
            'name'      => '排序方式',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '按照【签名参数键名】填写顺序排序',
                2 => '按照【签名参数键名】字符顺序升序排序',
                3 => '按照【签名参数键值】字符顺序升序排序',
            ],
        ],

        'kv_value_empty' => [
            'name'      => '键值空值',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '参与签名',
                2 => '不参与签名',
            ],
        ],

        'kv_key_handle' => [
            'name'      => '健名处理',
            'type'      => 'int(11)',
            'value'     => 'api/format',
        ],

        'kv_value_handle' => [
            'name'      => '键值处理',
            'type'      => 'int(11)',
            'value'     => 'api/format',
        ],

        'kv_join' => [
            'name'      => '连接符',
            'type'      => 'varchar(30)',
        ],

        'kv_join_handle' => [
            'name'      => '连接符处理',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '删除尾部连接符',
                2 => '不删除',
            ],
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
];