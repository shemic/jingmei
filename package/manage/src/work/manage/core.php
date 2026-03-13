<?php
return [
    'menu' => [
        'work' => [
            'name' => '设置',
            'icon' => 'flask-line',
            'sort' => '50',
            'module' => 'platform',
        ],

        'work_app_manage' => [
            'parent' => 'work',
            'name' => '工作台设置',
            'icon' => 'dropbox-line',
            'sort' => '10',
        ],

        'cate' => [
            'parent'    => 'work_app_manage',
            'name'      => '分类',
            'icon'      => 'archive-drawer-line',
            'sort'      => '100',
            'show'      => '3',
        ],

        'workflow' => [
            'parent'    => 'work_app_manage',
            'name'      => '工作流',
            'icon'      => 'dropbox-line',
            'sort'      => '1',
        ],

        'workflow_nodes' => [
            'parent'    => 'work_app_manage',
            'name'      => '工作流节点',
            'sort'      => '100',
            'show'      => '3',
        ],

        'workflow_input' => [
            'parent'    => 'work_app_manage',
            'name'      => '工作流输入项',
            'sort'      => '100',
            'show'      => '3',
        ],

        'workflow_input_option' => [
            'parent'    => 'work_app_manage',
            'name'      => '工作流输入选项',
            'sort'      => '100',
            'show'      => '3',
        ],

        'agent' => [
            'parent'    => 'work_app_manage',
            'name'      => '智能体',
            'icon'      => 'archive-line',
            'sort'      => '2',
        ],

        'tool' => [
            'parent'    => 'work_app_manage',
            'name'      => '工具',
            'icon'      => 'shopping-cart-line',
            'sort'      => '3',
        ],

        'tool_param' => [
            'parent'    => 'work_app_manage',
            'name'      => '工具参数',
            'sort'      => '100',
            'show'      => '3',
        ],

        'tool_method' => [
            'parent'    => 'work_app_manage',
            'name'      => '工具方法',
            'sort'      => '100',
            'show'      => '3',
        ],

        'content_workflow' => [
            'parent'    => 'work_app_manage',
            'name'      => '产物设置',
            'icon'      => 'service-line',
            'sort'      => '4',
        ],

        'work_manage' => [
            'parent' => 'work',
            'name' => '基础设置',
            'icon' => 'skull-2-line',
            'sort' => '20',
        ],

        'model' => [
            'parent'    => 'work_manage',
            'name'      => '大模型',
            'icon'      => 'dribbble-fill',
            'sort'      => '1',
        ],

        'platform_manage' => [
            'parent'    => 'work_manage',
            'name'      => '平台设置',
            'icon'      => 'archive-drawer-line',
            'sort'      => '2',
        ],
    ],
];