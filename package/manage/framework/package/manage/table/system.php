<?php
return [
    'name' => '系统',
    'order' => 'sort asc',
    'struct' => [
        'name' => [
            'name'      => '系统名称',
            'type'      => 'varchar(32)',
        ],
        'key' => [
            'name'      => '系统标识',
            'type'      => 'varchar(32)',
        ],
        'partition' => [
            'name'      => '数据隔离类型',
            'type'      => 'varchar(20)',
            'default'   => 'no',
            'value'     => [
                'no'    => '不做数据隔离',
                'database' => '分库隔离',
                'table' => '分表隔离',
                'field' => '分区隔离',
                'where' => '分条件隔离',
            ],
        ],
        'info_table' => [
            'name'      => '基本信息表名',
            'type'      => 'varchar(100)',
        ],
        'user_table' => [
            'name'      => '用户表表名',
            'type'      => 'varchar(100)',
        ],
        'role_table' => [
            'name'      => '角色表表名',
            'type'      => 'varchar(100)',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
        ],
    ],

    'default' => [
        'field' => 'id,name,`key`,`partition`,info_table,user_table,role_table,sort',
        'value' => [
            '1,"平台系统","platform","no","manage/platform","manage/admin","manage/role",-1000',
            '2,"集团系统","group","database","manage/group","manage/group_user","manage/group_role",-900',
        ],
        'num' => 1,
    ],
];