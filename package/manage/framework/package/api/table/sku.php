<?php
return [
    'name' => '接口规格设置',
    'struct' => [
        'app_func_id' => [
            'name'      => '功能id',
            'type'      => 'int(11)',
        ],

        'key' => [
            'name'      => '规格ID',
            'type'      => 'varchar(500)',
        ],

        'price' => [
            'name'      => '售价',
            'type'      => 'decimal(11,2)',
        ],

        'num' => [
            'name'      => '总次数',
            'type'      => 'int(11)',
        ],

        'day_num' => [
            'name'      => '每天限制次数',
            'type'      => 'int(11)',
        ],

        'state' => [
            'name'      => '数据状态',
            'type'      => 'tinyint(1)',
            'default'   => '1',
        ],
    ],
];