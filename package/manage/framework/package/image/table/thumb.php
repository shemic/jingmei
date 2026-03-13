<?php
return [
    'name' => '缩略图',
    # 数据结构
    'struct' => [
        'name'      => [
            'name'      => '配置名称',
            'type'      => 'varchar(32)',
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
