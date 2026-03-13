<?php
return [
    'name' => '输出参数配置',
    'order' => 'sort asc',
    'struct' => [
        'app_func_id' => [
            'name'      => '功能id',
            'type'      => 'int(11)',
        ],

        'name' => [
            'name'      => '模块名称',
            'type'      => 'varchar(150)',
        ],

        'key' => [
            'name'      => '模块标识',
            'type'      => 'varchar(150)',
        ],

        'data' => [
            'name'      => '包含数据',
            'type'      => 'varchar(1000)',
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
];