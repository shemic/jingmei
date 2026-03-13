<?php
# 后台核心配置 这里配置菜单即可
# 图标 https://vuejs-core.cn/admin-plus/?from=website#/vab/icon/defaultIcon
return [
    # 系统定义 默认将建立platform和group系统
    /*
    'system' => [
        'main' => [
            'name' => '测试系统',
            'sort' => '-100',
            # 这个是系统的数据隔离类型，no无需隔离，database是分库隔离，table是分表隔离，field是分区隔离，where是字段隔离
            'partition' => 'no',
            # 基本信息表名
            'info_table' => 'manage/platform',
            # 用户表名
            'user_table' => 'manage/admin',
            # 角色表名
            'role_table' => 'manage/role',
        ],
    ],*/

    # 系统模块定义 默认将建立platform和group模块
    /*
    'module' => [
        'main' => [
            # 系统key
            'system' => 'platform',
            'name' => '测试系统',
            'sort' => '-100',
            # 模块下数据表名
            'data_table' => 'manage/platform',
        ],
    ],*/

    # 菜单定义
    'menu' => [
        # 定义父级菜单
        'set' => [
            # 菜单名称
            'name' => '平台',
            # 菜单图标
            'icon' => 'flood-line',
            # 菜单排序 正序
            'sort' => '1000',
            # 所属系统模块 模块key，一般只需主菜单填写module
            'module' => 'platform',
        ],
        # 定义二级菜单
        'platform' => [
            'parent' => 'set',
            'name' => '平台管理',
            'icon' => 'book-open-line',
            'sort' => '50',
        ],
        # 定义三级菜单 一般和表名一致，如果不是表名则为自定义菜单
        'admin' => [
            # 所属项目 不填写则获取当前deverapp
            'app'       => 'manage',
            # 上级菜单
            'parent'    => 'platform',
            # 菜单名称
            'name'      => '账户管理',
            # 菜单图标
            'icon'      => 'user-settings-line',
            # 菜单排序 正序
            'sort'      => '1',
            # 菜单路径 可选项：main列表页,update更新页,stat统计页,layout自定义页，不填写默认为main
            'path'      => 'main',
            # 标签 这里需要设置获取标签的方法
            'badge'     => 'test.badge',
        ],

        /*
        'admin_diy' => [
            'parent'    => 'platform',
            'name'      => '自定义页面',
            'sort'      => '6',
            'path'      => 'diy',
        ],
        */

        'role' => [
            'parent'    => 'platform',
            'name'      => '角色管理',
            'icon'      => 'archive-line',
            'sort'      => '2',
        ],

        'recycler' => [
            'parent'    => 'platform',
            'name'      => '回收站',
            'icon'      => '',
            'sort'      => '100',
            # 不显示在菜单中 也不显示在菜单管理中
            'show'      => 3,
        ],

        'set_my' => [
            'parent'    => 'platform',
            'name'      => '个人资料',
            'icon'      => '',
            'sort'      => '100',
            # 不显示在菜单中
            'show'      => 3,
            'path'      => 'set/my',
        ],

        'menu' => [
            'parent'    => 'platform',
            'name'      => '菜单管理',
            'icon'      => 'menu-line',
            'sort'      => '3',
        ],

        'group_manage' => [
            'parent'    => 'platform',
            'name'      => '集团管理',
            'icon'      => 'group-2-line',
            'sort'      => '4',
        ],

        'config' => [
            'parent'    => 'platform',
            'name'      => '配置管理',
            'icon'      => 'album-line',
            'sort'      => '5',
            'path'      => 'update',
            # 后续完善配置功能
            'show'      => 3,
        ],

        'cron' => [
            'parent'    => 'platform',
            'name'      => '计划任务',
            'icon'      => 'stack-line',
            'sort'      => '100',
        ],

        'set_group' => [
            'name' => '配置',
            'icon' => 'settings-line',
            'sort' => '100',
            'module' => 'group',
        ],

        'group' => [
            'parent'    => 'set_group',
            'name'      => '集团管理',
            'icon'      => 'group-2-line',
            'sort'      => '100',
        ],

        'group_user' => [
            'parent'    => 'group',
            'name'      => '账户管理',
            'icon'      => 'user-settings-line',
            'sort'      => '1',
        ],

        'group_role' => [
            'parent'    => 'group',
            'name'      => '角色管理',
            'icon'      => 'archive-line',
            'sort'      => '2',
        ],

        'group_org' => [
            'parent'    => 'group',
            'name'      => '组织管理',
            'icon'      => 'voiceprint-fill',
            'sort'      => '5',
        ],
    ],
];