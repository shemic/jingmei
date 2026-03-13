<?php
return [
    'name' => '裁剪图',
    # 数据结构
    'struct' => [
        'name'      => [
            'name'      => '配置名称',
            'type'      => 'varchar(32)',
        ],

        'position'      => [
            'name'      => '裁剪位置',
            'type'      => 'tinyint(1)',
            'value' => [
                1 => '左上',
                2 => '左下',
                3 => '右上',
                4 => '右下',
                5 => '居中',
                6 => '上中',
                7 => '下中',
            ],
            'default' => '1',
        ],

        'width'     => [
            'name'      => '宽度',
            'type'      => 'varchar(11)',
            'default'   => '0',
        ],

        'height'     => [
            'name'      => '高度',
            'type'      => 'varchar(11)',
            'default'   => '0',
        ],
    ],
];
