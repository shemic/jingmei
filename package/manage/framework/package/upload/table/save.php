<?php
return [
    'name' => '存储位置',
    # 数据结构
    'struct' => [
        'name'      => [
            'name'      => '名称',
            'type'      => 'varchar(24)',
        ],

        'type'     => [
            'name'      => '存储类别',
            'type'      => 'tinyint(1)',
            'value' => [
                1 => '本地',
                2 => '七牛云',
                //3 => '阿里云',
            ],
            'default'   => '1',
        ],

        'method'     => [
            'name'      => '上传方式',
            'type'      => 'tinyint(1)',
            'value' => [
                1 => '后端上传至云端',
                2 => '前端上传至云端',
            ],
            'default'   => '1',
        ],

        'host'      => [
            'name'      => '域名',
            'type'      => 'varchar(800)',
        ],
        
        'appkey'      => [
            'name'      => 'appkey',
            'type'      => 'varchar(100)',
        ],

        'appsecret'      => [
            'name'      => 'appsecret',
            'type'      => 'varchar(200)',
        ],

        'bucket'      => [
            'name'      => '存储位置',
            'type'      => 'varchar(100)',
        ],

        'region_id'      => [
            'name'      => '区域id',
            'type'      => 'varchar(200)',
        ],

        'role_arn'      => [
            'name'      => 'ARN',
            'type'      => 'varchar(200)',
        ],
    ],
    'default' => [
        'field' => 'name,type,cdate',
        'value' => [
            '"本地存储",1,' . DEVER_TIME,
        ],
        'num' => 1,
    ],
];
