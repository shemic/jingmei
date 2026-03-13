<?php
return [
    'name' => '输入参数配置',
    'order' => 'sort asc',
    'struct' => [
        'app_func_id' => [
            'name'      => '功能id',
            'type'      => 'int(11)',
        ],

        'name' => [
            'name'      => '参数描述',
            'type'      => 'varchar(150)',
        ],

        'key' => [
            'name'      => '参数名',
            'type'      => 'varchar(150)',
        ],

        'type' => [
            'name'      => '类型',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '单行输入',
                2 => '多行输入',
                3 => '单选',
                4 => '多选',
                5 => '选择框',
                11 => '图片上传',
                12 => '视频上传',
                13 => '音频上传',
                14 => '文件上传',
            ],
        ],

        'option' => [
            'name'      => '默认值/可选项',
            'type'      => 'varchar(1000)',
        ],

        'match' => [
            'name'      => '必填',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '必填',
                2 => '选填',
            ],
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
];