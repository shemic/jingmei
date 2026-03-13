<?php
return [
    'name' => '计划任务',
    'struct' => [
        'name'      => [
            'type'      => 'varchar(32)',
            'name'      => '任务名',
        ],

        'project'       => [
            'type'      => 'varchar(30)',
            'name'      => '项目',
            'value'     => 'Dever::load("Manage/Lib/Util")->project()',
        ],
        
        'interface'     => [
            'type'      => 'varchar(300)',
            'name'      => '接口地址',
        ],

        'ldate'     => [
            'type'      => 'int(11)',
            'name'      => '执行时间',
        ],

        'time'      => [
            'type'      => 'int(11)',
            'name'      => '时间间隔',
            'default'   => '0',
        ],

        'state'     => [
            'type'      => 'tinyint(1)',
            'name'      => '状态',
            'default'   => 1,
            'value'    => [
                1 => '可执行',
                2 => '已完成',
            ]
        ],
    ],
];