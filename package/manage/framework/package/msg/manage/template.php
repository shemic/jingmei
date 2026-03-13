<?php
return [
    'list' => [
        'field'      => [
            'name',
            'key',
            'type',
            'method',
            'status' => [
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
            'cdate',
        ],
        'button' => [
            '新增' => 'add',
        ],
        'data_button' => [
            '设置' => 'edit',
        ],
        'search' => [
            'name',
            'key',
            'status',
        ]
    ],
    'update' => [
        'field'    => [
            'name' => [
                'rules' => true,
            ],
            'key' => [
                'rules' => true,
            ],
            'title' => [
                'tip' => '可为空，一般为站内信或邮箱用到',
            ],
            'content' => [
                'rules' => true,
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
                'desc' => '变量用大括号包含即可，如：{name}',
            ],
            'method' => 'checkbox',
            'type' => 'radio',
            'msg/template_code' => [
                'name' => '验证码设置',
                'where'  => ['template_id' => 'id'],
                'default' => [[]],
                # 默认使用表格形式展示，可以改成每行展示
                'type' => 'line',
            ],

            /*
            'msg/template_sms' => [
                'field' => 'code',
                'name' => '短信模板',
                'where'  => ['template_id' => 'id'],
                'type' => 'text',
            ],
            'msg/template_email' => [
                'name' => '邮件标题',
                'where'  => ['template_id' => 'id'],
                'type' => 'text',
                'default' => [[]],
                # 默认使用表格形式展示，可以改成每行展示
                'type' => 'line',
                
            ],*/
        ],
        'control' => [
            'msg/template_code' => [
                'type' => 2,
            ],
            /*
            'msg/template_sms' => [
                'method' => ['sms'],
            ],
            'msg/template_email' => [
                'method' => ['email'],
            ],*/
        ],
    ],
];