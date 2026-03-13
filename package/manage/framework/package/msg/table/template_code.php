<?php
return [
    'name' => '验证码设置',
    'struct' => [
        'template_id'        => [
            'type'      => 'int(11)',
            'name'      => '模板',
        ],

        'timeout'      => [
            'type'      => 'int(11)',
            'name'      => '有效期',
            'default'   => '600',
        ],

        'length'       => [
            'type'      => 'int(11)',
            'name'      => '长度',
            'default'   => '4',
        ],

        'type'      => [
            'type'      => 'tinyint(1)',
            'name'      => '类型',
            'default'   => '1',
            'value'     => [
                1 => '全数字',
                2 => '小写字母',
                3 => '大写字母',
                4 => '大小写字母',
                5 => '数字+大小写字母',
            ],
        ],

        'total'      => [
            'type'      => 'int(11)',
            'name'      => '最大发送次数',
            'default'   => '5',
        ],

        'interval'       => [
            'type'      => 'int(11)',
            'name'      => '发送间隔',
            'default'   => '60',
        ],
    ],
];
