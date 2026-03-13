<?php
return [
    'menu' => [
        'user' => [
            'name' => '用户',
            'icon' => 'file-user-line',
            'sort' => '30',
            'module' => 'platform',
        ],

        # 用户管理
        'user_manage' => [
            'parent'    => 'user',
            'name'      => '用户管理',
            'icon'      => 'folder-user-line',
            'sort'      => '100',
        ],

        'info' => [
            'parent'    => 'user_manage',
            'name'      => '用户列表',
            'icon'      => 'user-3-line',
            'sort'      => '1',
        ],

        'project' => [
            'parent'    => 'user_manage',
            'name'      => '项目列表',
            'icon'      => 'shopping-cart-line',
            'sort'      => '2',
        ],

        'content' => [
            'parent'    => 'user_manage',
            'name'      => '项目内容',
            'sort'      => '100',
            'show'      => '3',
        ],

        'run' => [
            'parent'    => 'user_manage',
            'name'      => '运行记录',
            'sort'      => '100',
            'show'      => '3',
        ],

        'run_nodes' => [
            'parent'    => 'user_manage',
            'name'      => '运行记录节点',
            'sort'      => '100',
            'show'      => '3',
        ],
    ],
];