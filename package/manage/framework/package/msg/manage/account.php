<?php
$config = [
    'list' => [
        'desc' => '当同一个功能有两个账户时，将按照排序和状态使用最新的一个账户',
        'field'      => [
            'name',
            'method',
            'sort',
            'test' => [
                'tip' => '开启后，将不会实际发送请求',
                'type' => 'switch',
                'show'  => '{test}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
            'status',
        ],
        'button' => [
            '新增' => ['fastadd', 'name,method,api_account_id,msg/account_email'],
        ],
        'data_button' => [
            '设置' => ['fastedit', 'name,method,api_account_id,msg/account_email'],
            '短信模板' => ['fastedit', 'msg/account_sms,tip', '', 'method=Sms'],
            '微信模板' => ['fastedit', 'msg/account_wechat,tip', '', 'method=WechatService,WechatApplet'],
        ],
        'search' => [
            'name',
            'method',
            'status',
        ]
    ],
    'update' => [
        'field'    => [
            'name',
            'method' => 'radio',
            'api_account_id' => 'select',
            'msg/account_email' => [
                'name' => '邮件设置',
                'where'  => ['account_id' => 'id'],
                'default' => [[]],
                # 默认使用表格形式展示，可以改成每行展示
                'type' => 'line',
            ],
        ],
        'control' => [
            'msg/account_email' => [
                'method' => ['Email'],
            ],
            'api_account_id' => [
                'method' => ['Sms', 'WechatService', 'WechatApplet', 'App'],
            ],
        ],
    ],
];

$id = Dever::input('id');
if ($id > 0) {
    $default = [];
    $info = Dever::db('msg/account')->find($id);
    $template = Dever::db('msg/template')->select(['method' => ['like', $info['method']]]);
    if ($template) {
        foreach ($template as $v) {
            $default[] = [
                'template_id' => $v['id'],
                'template_name' => $v['name'],
            ];
        }
        if ($info['method'] == 'Sms') {
            $config['update']['field']['msg/account_sms'] = [
                'name' => '短信模板',
                'where'  => ['account_id' => 'id'],
                'default' => $default,
            ];
        } elseif (strstr($info['method'], 'Wechat')) {
            $config['update']['field']['msg/account_wechat'] = [
                'name' => '微信模板',
                'where'  => ['account_id' => 'id'],
                'default' => $default,
            ];
        }
    } else {
        if ($info['method'] == 'Sms') {
            $config['update']['field']['tip'] = [
                'name' => '短信模板',
                'type' => 'show',
                'default' => '暂无短信模板，请在消息模板中选择',
            ];
        } elseif (strstr($info['method'], 'Wechat')) {
            $config['update']['field']['tip'] = [
                'name' => '微信模板',
                'type' => 'show',
                'default' => '暂无微信模板，请在消息模板中选择',
            ];
        }
    }
}
return $config;