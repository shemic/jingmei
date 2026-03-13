<?php
return [
    'name' => '接口规格值设置',
    'order' => 'sort asc',
    'struct' => [
        'app_func_id' => [
            'name'      => '功能id',
            'type'      => 'int(11)',
        ],
        'spec_id' => [
            'name'      => '规格ID',
            'type'      => 'int(11)',
        ],
        'value' => [
            'name'      => '规格值',
            'type'      => 'varchar(500)',
        ],
        'is_checked' => [
            'name'      => '是否选中',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '选中',
                2 => '未选中',
            ]
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