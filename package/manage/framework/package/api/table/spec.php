<?php
return [
    'name' => '接口规格设置',
    'order' => 'sort asc',
    'struct' => [
        'app_func_id' => [
            'name'      => '功能id',
            'type'      => 'int(11)',
        ],
        'name' => [
            'name'      => '规格名称',
            'type'      => 'varchar(200)',
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
        'state' => [
            'name'      => '数据状态',
            'type'      => 'tinyint(1)',
            'default'   => '1',
        ],
    ],
];