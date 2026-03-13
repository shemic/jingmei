<?php
return [
    'name' => '平台角色',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'module' => [
            'name'      => '系统模块',
            'type'      => 'varchar(2000)',
        ],
        'menu' => [
            'name'      => '菜单',
            'type'      => 'text',
        ],
        'auth' => [
            'name'      => '权限',
            'type'      => 'text',
            'value'    => 'Dever::call("Manage/Lib/Role.getAuthData")',
        ],
    ],
    'default' => [
        'field' => 'name,module,cdate',
        'value' => [
            '"默认角色",1,' . DEVER_TIME,
        ],
        'num' => 1,
    ],
];