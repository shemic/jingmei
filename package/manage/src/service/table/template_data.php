<?php
return [
    'name' => '业务模板数据',
    'order' => 'sort asc,id asc',
    'struct' => [

        'template_cate_id' => [
            'name'      => '模板分类',
            'type'      => 'bigint',
            'value'     => 'service/template_cate',
        ],

        'template_id' => [
            'name'      => '业务模板ID',
            'type'      => 'bigint',
            'value'     => 'service/template',
        ],

        'type' => [
            'name'      => '类型',
            'type'      => 'varchar(20)',
            'default'   => 'text',
            'value'     => Dever::config('setting')['content'],
        ],

        'prompt' => [
            'name'      => '提示词',
            'type'      => 'text',
        ],

        'file' => [
            'name'      => '文件',
            'type'      => 'varchar(150)',
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
        'template_id' => 'template_id,template_cate_id,sort',
    ],
];