<?php
return [
    'name' => '应用功能表',
    'order' => 'sort asc',
    'struct' => [
        'app_id' => [
            'name'      => '应用id',
            'type'      => 'int(11)',
        ],

        'name' => [
            'name'      => '功能名称',
            'type'      => 'varchar(32)',
        ],

        'key' => [
            'name'      => '功能标识',
            'type'      => 'varchar(32)',
        ],

        'desc' => [
            'name'      => '功能描述',
            'type'      => 'varchar(300)',
        ],

        'type' => [
            'name'      => '执行方式',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '单接口-按需执行某一接口',
                2 => '工作流-按顺序执行所有接口',
                3 => '工作组-同时执行所有接口'
            ],
        ],

        'cron_date' => [
            'name'      => '定时执行时间',
            'type'      => 'int(11)',
        ],

        'cron_time' => [
            'name'      => '定时执行间隔',
            'type'      => 'int(11)',
            'default'   => '0',
        ],

        'spec_type' => [
            'name'      => '规格类型',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '免费',
                2 => '单规格',
                3 => '多规格',
            ],
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
    'index' => [
        'search' => '`key`.unique',
    ],
];