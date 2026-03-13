<?php
return [
    'menu' => [
        'shenzhu' => [
            'name' => '助理',
            'icon' => 'account-box-line',
            'sort' => '40',
            'module' => 'platform',
        ],

        'shenzhu_manage' => [
            'parent'    => 'shenzhu',
            'name'      => '基础设置',
            'icon'      => 'apps-2-line',
            'sort'      => '100',
        ],

        'role' => [
            'parent'    => 'shenzhu_manage',
            'name'      => '角色管理',
            'icon'      => 'earth-line',
            'sort'      => '1',
        ],

        'cate' => [
            'parent'    => 'shenzhu_manage',
            'name'      => '角色分类',
            'sort'      => '100',
            'show'      => '3',   
        ],

        'system' => [
            'parent'    => 'shenzhu_manage',
            'name'      => '系统管理',
            'icon'      => 'todo-line',
            'sort'      => '2',
        ],
    ],
];