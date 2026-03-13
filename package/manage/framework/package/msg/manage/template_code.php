<?php
return [
    'update' => [
        'field'    => [
            'type' => [
                'type' => 'select',
            ],
            'timeout' => [
                'desc' => '单位为秒数',
            ],
            'length' => [
                'desc' => '设置验证码生成的长度',
            ],
            'total' => [
                'desc' => '设置每天可以发送的最大次数',
            ],
            'interval' => [
                'desc' => '单位为秒数，填写60就是每60秒可以发送一次',
            ],
        ],
    ],
];