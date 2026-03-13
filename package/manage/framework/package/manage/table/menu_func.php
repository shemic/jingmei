<?php
return [
    'name' => '功能',
    'order' => 'sort asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'key' => [
            'name'      => '标识',
            'type'      => 'varchar(80)',
        ],
        'menu_id' => [
            'name'      => '菜单id',
            'type'      => 'int(11)',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
        ],
    ],
    'index' => [
        'search' => 'menu_id,`key`',
    ],
];