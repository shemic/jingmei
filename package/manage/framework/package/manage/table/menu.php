<?php
return [
    'name' => '菜单',
    'order' => 'sort asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'key' => [
            'name'      => '标识',
            'type'      => 'varchar(32)',
        ],
        'app' => [
            'name'      => '项目',
            'type'      => 'varchar(32)',
            'value'     => 'Dever::get(\\Dever\\Project::class)->read()',
        ],
        'parent_id' => [
            'name'      => '上级菜单',
            'type'      => 'int(11)',
            'default'   => '0',
        ],
        'module_id' => [
            'name'      => '系统模块',
            'type'      => 'varchar(80)',
        ],
        'path' => [
            'name'      => '路径',
            'type'      => 'varchar(200)',
            'default'   => 'main',
            'value'     => [
                'main' => '列表页',
                'update' => '更新页',
                'stat' => '统计页',
                'layout' => '自定义页',
                'link' => '链接',
            ],
        ],
        'link' => [
            'name'      => '链接',
            'type'      => 'varchar(2000)',
        ],
        'icon' => [
            'name'      => '图标',
            'type'      => 'varchar(150)',
        ],
        'badge' => [
            'name'      => '标签',
            'type'      => 'varchar(32)',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
        ],
        'show' => [
            'name'      => '是否展示',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '展示',
                2 => '不展示',
                3 => '隐藏',
            ],
        ],
        'func' => [
            'name'      => '是否有功能菜单',
            'type'      => 'tinyint(1)',
            'default'   => 2,
        ],
        'level' => [
            'name'      => '层级',
            'type'      => 'tinyint(11)',
            'default'   => '1',
        ],
    ],
];