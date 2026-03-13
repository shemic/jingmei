<?php
$module = Dever::input('search')['module_id'] ?? 1;
return [
    'list' => [
        'where' => ['module_id' => $module, 'show' => ['<', '3']],
        # 展示左侧分栏
        'column' => [
            # 分栏数据来源
            'load' => 'manage/system_module',
            # 分栏新增按钮
            //'add' => '新增',
            # 分栏编辑按钮，这里直接用图标
            //'edit' => true,
            # 分栏删除按钮，这里直接用图标
            //'delete' => true,
            'edit' => true,
            # 关键字段，一般为id或者key
            'key' => 'id',
            # 获取数据
            'data' => 'Manage/Lib/Module.getTree',
            # 默认展开
            'active' => 1,
            # 对应的where条件的key
            'where' => 'module_id',
        ],
        # 展示的字段
        'field'      => [
            'name' => ['align' => 'left'],
            'key' => [
                'width' => '120px',
            ],
            //'path',
            'icon' => [
                'type' => 'icon',
                'width' => '75px',
            ],
            'sort',
            'show' => [
                'width' => '100px',
            ],
            /*
            'show' => [
                # 行中修改 仅支持switch、select、input，太复杂的咱就别在行中修改了吧
                'type' => 'switch',
                'show'  => '{show}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],*/
        ],
        # 树形表格 仅type=table时支持，这里设置获取根数据的条件即可
        'tree' => ['parent_id', '0', 'id'],

        'search'    => [
            'module_id' => 'hidden',
            'name',
        ],

        'button' => [
            '新增主菜单' => ['fastadd', ['parent_id' => '0', 'module_id' => $module, 'path' => '']],
        ],

        'data_button' => [
            '编辑' => ['fastedit', 'name,key,icon,path,link,sort,show'],
            '新增子菜单' => ['fastadd', ['parent_id' => 'id', 'module_id' => $module]],
            //'删除' => 'recycle',
        ],

    ],
    'update' => [
        'field'    => [
            'name',
            'key',
            'parent_id',
            'module_id',
            'path' => [
                'type' => 'select',
                'control' => true,
            ],
            'link',
            'icon' => [
                'type' => 'icon',
                'search' => true,
            ],
            'sort',
            'show' => [
                'type' => 'radio',
                'option'     => [
                    1 => '展示',
                    2 => '不展示',
                ],
            ],
        ],
        'check' => 'key',
        'control' => [
            'link' => [
                'path' => 'link',
            ],
        ],
    ],
];