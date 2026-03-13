<?php
return [
    'name' => '模型表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'type' => [
            'name'      => '类型',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '文本',
                2 => '向量',
                3 => '多模态',
                4 => '图片',
                5 => '视频',
                6 => '语音',
                7 => '角色',
            ],
        ],
        
        'platform_id' => [
            'name'      => '平台',
            'type'      => 'int(11)',
            'value'     => 'work/platform',
        ],

        'model' => [
            'name'      => '接入名称',
            'type'      => 'varchar(128)',
        ],

        'protocol' => [
            'name'      => '协议类型',
            'type'      => 'varchar(32)',
            'default'   => 'http',
            'value'     => [
                'http' => 'http',
                'openai' => 'openai',
            ],
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
        'code' => 'code.unique',
        'platform_id' => 'platform_id,sort',
    ],
];