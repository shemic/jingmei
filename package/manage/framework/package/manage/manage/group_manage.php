<?php
return [
    'source' => 'manage/group',
    'list' => [
        'desc' => '集团即您的客户，如果系统为集团客户开发了后台管理功能，可以使用集团来为客户开启后台权限',
        'field'      => [
            'id',
            'name',
            'number',
            'sort' => 'input',
            'status' => [
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
        ],
        'data_button' => [
            '编辑' => 'fastedit',
            //'删除' => 'recycle',
            //'管理账户列表' => ['route', ['path' => 'set_group/group_user', 'param' => ['set' => ['system_id' => 2, 'relation_id' => 'id']]]],
            //'管理账户列表' => ['route', 'set_group/group_user?set[system_id]=2&set[relation_id]=id'],
        ],
    ],
    'update' => [
        'field'    => [
            'name',
            'number' => [
                'desc' => '集团号不能为空，且必须唯一，尽量使用字符串',
                'rules' => true,
            ],
            'mobile' => [
                //'show'      => '"{mobile}" ? false : true',
                'type'      => 'text',
                'desc'      => '请输入管理员手机号，默认密码123456，集团必须有管理员才能登录',
                'placeholder' => '管理员手机号',
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
            'sort',
            'status' => 'radio',
        ],
        'check' => 'key',
        'end' => 'Manage/Lib/System.update?system=group',
    ],
];