<?php
return [
    'name' => '用户表',
    'struct' => [
        'name' => [
            'name'      => '用户名',
            'type'      => 'varchar(32)',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'mobile' => [
            'name'      => '手机号',
            'type'      => 'bigint',
        ],
        'email' => [
            'name'      => '邮箱',
            'type'      => 'varchar(150)',
        ],
        'password' => [
            'name'      => '密码',
            'type'      => 'varchar(64)',
        ],
        'salt' => [
            'name'      => '密码salt',
            'type'      => 'varchar(32)',
        ],
        'parent_uid' => [
            'name'      => '推荐人',
            'type'      => 'bigint',
        ],
        'cur_cate_id' => [
            'name'      => '当前项目分类ID',
            'type'      => 'bigint',
            'value'     => 'user/project_cate',
            'default'   => '0',
        ],
        'cur_project_id' => [
            'name'      => '默认分类当前项目ID',
            'type'      => 'bigint',
            'value'     => 'user/project',
            'default'   => '0',
        ],
        'avatar' => [
            'name'      => '头像',
            'type'      => 'varchar(150)',
        ],
        'sign' => [
            'name'      => '签名',
            'type'      => 'varchar(300)',
        ],
        'openid' => [
            'name'      => 'OpenID',
            'type'      => 'varchar(300)',
        ],
        'sex' => [
            'name'      => '性别',
            'type'      => 'tinyint(1)',
            'default'   => 3,
            'value'     => [
                1 => '男',
                2 => '女',
                3 => '未知',
            ],
        ],
        'type' => [
            'name'      => '用户类型',
            'type'      => 'tinyint(1)',
            'default'   => 2,
            'value'     => [
                1 => '飞书登录',
                2 => '后台录入',
            ],
        ],
        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '正常',
                2 => '封禁',
            ],
        ],
        'is_delete' => [
            'name'      => '是否可删除',
            'type'      => 'tinyint(1)',
            'default'   => 2,
            'value'     => [
                1 => '可以删',
                2 => '不可删',
            ],
        ],
    ],
    'index' => [
        'code' => 'code.unique',
        'mobile' => 'mobile.unique',
        'search' => 'parent_uid',
        //'sales' => 'sales_type,sales_id,status',
    ],
];