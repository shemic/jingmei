<?php
return [
    'name' => '业务模板分类',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],

        'template_id' => [
            'name'      => '业务模板ID',
            'type'      => 'bigint',
            'value'     => 'service/template',
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
                2 => '删除',
            ],
        ],
    ],
    'index' => [
        'template_id' => 'template_id,sort',
    ],
];