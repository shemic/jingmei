<?php
return [
    'name' => '水印图',
    # 数据结构
    'struct' => [
        'name'      => [
            'name'      => '配置名称',
            'type'      => 'varchar(32)',
        ],

        'position'      => [
            'name'      => '水印位置',
            'type'      => 'tinyint(1)',
            'value' => [
                1 => '左上',
                2 => '左下',
                3 => '右上',
                4 => '右下',
                5 => '居中',
                6 => '上中',
                7 => '下中',
                8 => '左中',
                9 => '右中',
                10 => '平铺',
            ],
            'default' => '1',
        ],

        'offset'     => [
            'name'      => '偏移量',
            'type'      => 'varchar(11)',
        ],

        'pic'       => [
            'name'      => '水印图',
            'type'      => 'varchar(200)',
        ],

        'width'     => [
            'name'      => '水印图宽度',
            'type'      => 'varchar(11)',
            'default'   => '0',
        ],

        'height'     => [
            'name'      => '水印图高度',
            'type'      => 'varchar(11)',
            'default'   => '0',
        ],

        'radius'     => [
            'name'      => '圆形弧度',
            'type'      => 'varchar(11)',
            'default'   => '0',
        ],
    ],
];
