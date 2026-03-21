<?php
Dever::db('user/project');
Dever::db('user/project_cate');
Dever::db('user/file');
Dever::db('user/verify_code');
Dever::db('user/content');
Dever::db('user/content_version');
Dever::db('user/content_cate');
Dever::db('user/run');
Dever::db('user/run_nodes');
return [
    'list' => [
        'type' => 'table',
        'field'      => [
            'id',
            'name',
            'type' => [
                //'type' => 'select',
            ],
            'mobile' => [
                //'sort' => true,
                'show' => '{type} == 1 ? "-" : "{mobile}"',
            ],
            'status' => [
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
            'cdate',
        ],
        'height' => 'auto',
        
        'button' => [
            '新增' => 'fastadd',
            //'删除' => 'recycle',
        ],
        'data_button' => [
            '编辑' => ['fastedit', '', '', 'type=2'],
            //'删除' => 'delete',
        ],
        'export' => [
            'out' => '导出',
        ],
        'search'    => [
            'name',
            'mobile',
        ],
    ],
    
    'update' => [
        'desc' => '',
        'field'    => [
            'name' => [
                'type' => 'text',
                'maxlength' => 30,
                'desc' => '',
            ],
            'mobile' => [
                'name'      => '手机号',
                'type'      => 'text',
                'disable'   => false,
                'placeholder' => '',
                'rules'     => [
                    [
                        'required' => true,
                        'trigger' => 'blur',
                        'message' => '请输入手机号',
                    ],
                    [
                        'len' => 11,
                        'pattern' => Dever::rule('mobile', ''),
                        'trigger' => 'blur',
                        'message' => '手机号错误',
                        'type' => 'string',
                    ],
                ],
            ],
            'password' => [
                'type' => 'password',
                'update' => '',
                'handle' => 'Manage/Lib/Util.createPwd',
                'empty'  => false,
                'rules'     => [
                    [
                        'only' => 'add',
                        'required' => true,
                        'trigger' => 'blur',
                        'message' => '请输入密码',
                    ],
                    [
                        'min' => 6,
                        'max' => 18,
                        'trigger' => 'blur',
                        'message' => '密码长度不能超过18或者少于6个字符',
                    ],
                ],
            ],
        ],
        'check' => 'mobile',
    ],
];