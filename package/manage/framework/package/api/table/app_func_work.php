<?php
return [
    'name' => '应用功能工作表',
    'order' => 'sort asc',
    'struct' => [
        'app_id' => [
            'name'      => '应用id',
            'type'      => 'int(11)',
        ],
        
        'app_func_id' => [
            'name'      => '功能id',
            'type'      => 'int(11)',
        ],

        'platform_id' => [
            'name'      => '平台',
            'type'      => 'int(11)',
            'value'     => 'api/platform',
        ],

        'api_id' => [
            'name'      => '接口',
            'type'      => 'int(11)',
        ],

        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '上架',
                2 => '下架',
            ],
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
];