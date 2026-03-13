<?php
return [
    'name' => '系统模块',
    'order' => 'sort asc',
    'struct' => [
        'name' => [
            'name'      => '模块名称',
            'type'      => 'varchar(32)',
        ],
        'key' => [
            'name'      => '模块标识',
            'type'      => 'varchar(32)',
        ],
        'system' => [
            'name'      => '系统标识',
            'type'      => 'varchar(100)',
        ],
        'data_table' => [
            'name'      => '模块下数据表名',
            'type'      => 'varchar(100)',
        ],
        'data_where' => [
            'name'      => '模块下数据表的获取数据方式，为空则获取所有',
            'type'      => 'varchar(2000)',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
        ],
    ],

    'default' => [
        'field' => 'id,name,`key`,system,data_table,sort',
        'value' => [
            '1,"平台","platform","platform","manage/platform",-1000',
            '2,"集团","group","group","manage/group",-900',
        ],
        'num' => 1,
    ],
];