<?php
return [
    'list' => [
        'field'      => [
            'id',
            'name',
            'type',
            'cdate',
        ],
        'button' => [
            '新增' => 'add',
        ],
        'data_button' => [
            '编辑' => 'edit',
        ],
    ],
    'update' => [
        'field'    => [
            'name',
            'host',
            'type' => [
                'type' => 'radio',
                'control' => true,
            ],
            'method' => [
                'type' => 'radio',
            ],
            'appkey',
            'appsecret',
            'bucket',
            'region_id' => [
                'desc' => 'oss直接填RegionId即可，如beijing，七牛可填写z1',
            ],
            'role_arn' => [
                'desc' => '建议添加权限策略',
            ],
        ],
        'control' => [
            'method' => [
                'type' => [2,3],
            ],
            'appkey' => [
                'type' => [2,3],
            ],
            'appsecret' => [
                'type' => [2,3],
            ],
            'bucket' => [
                'type' => [2,3],
            ],
            'region_id' => [
                'type' => [2,3],
            ],
            'role_arn' => [
                'type' => 3,
            ],
        ],
    ],
];