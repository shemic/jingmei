<?php
return [
    'name' => '集团角色',
    'partition' => 'Dever::load("Manage/Util")->system()',
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
            'value'    => 'Dever::call("manage/role.getAuthData")',
        ],
    ],
    'default' => [
        'field' => 'name,cdate',
        'value' => [
            '"默认角色",' . DEVER_TIME,
        ],
        'num' => 1,
    ],
];