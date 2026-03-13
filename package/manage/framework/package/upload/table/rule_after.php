<?php
return [
    'name' => '上传后续操作',
    'order' => 'sort asc',
    # 数据结构
    'struct' => [
        'rule_id'     => [
            'name'      => '上传规则',
            'type'      => 'int(11)',
        ],

        'type'      => [
            'name'      => '类型',
            'type'      => 'tinyint(1)',
            'value' => [
                1 => '缩略',
                2 => '裁剪',
                3 => '水印图',
                4 => '水印文字',
            ],
            'default' => '1',
        ],

        'type_id'     => [
            'name'      => '类型id',
            'type'      => 'int(11)',
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
        ],
    ],
];
