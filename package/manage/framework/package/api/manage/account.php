<?php
$account_id = Dever::input('id');
$show = false;
$account_setting = [];
if ($account_id) {
    $account = Dever::db('api/account')->find($account_id);
    $setting = Dever::db('api/platform_setting')->select(['platform_id' => $account['platform_id']]);
    if ($setting) {
        $show = true;
        foreach ($setting as $k => $v) {
            $account_setting[] = ['platform_setting_name' => $v['name'], 'platform_setting_id' => $v['id'], 'value' => ''];
        }
    }
}

return [
    'list' => [
        'field'      => [
            'id',
            'name',
            'key',
            'app_id' => [
                'show' => 'Dever::db("api/app")->find("{app_id}")["name"]',
            ],
            'platform_id' => [
                'show' => 'Dever::db("api/platform")->find("{platform_id}")["name"]',
            ],
            'sync' => [
                'type' => 'switch',
                'show'  => '{sync}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd', 'name,key,sync,app_platform'],
        ],
        'data_button' => [
            '设置' => ['edit', 'name,key,sync,app_platform_name,api/account_setting,desc'],
            '证书' => ['route', [
                'path' => 'api_manage/account_cert',
                'param' => [
                    'set' => ['account_id' => 'id', 'menu' => 'api_manage/account', 'parent' => 'api_manage/account'],
                ],
            ]],
        ],
        'search' => [
            'name',
            'key',
            'app_platform' => [
                'type' => 'cascader',
                'remote'    => 'Api/Api/Manage.getAppPlatform',
            ],
        ],
    ],
    'update' => [
        'field'    => [
            'name' => [
                'rules' => true,
            ],
            'key',
            'sync' => [
                'type' => 'radio',
                'tip' => '开启后，子系统会自动同步该账户的设定，填入相应的子系统账户信息',
            ],
            'app_platform' => [
                'desc' => '【提交后不能更改】',
                'rules' => true,
                'type' => 'cascader',
                'remote'    => 'Api/Api/Manage.getAppPlatform',
                'remote_default' => false,
            ],
            'app_platform_name' => [
                'name' => '平台与应用',
                'type' => 'show',
                'default' => 'Dever::call("Api/Lib/App.getAppPlatform", ["{app_id}", "{platform_id}"])',
            ],
            'api/account_setting' => [
                'show' => $show,
                'name' => '平台参数配置',
                'where'  => ['account_id' => 'id'],
                'default' => $account_setting,
            ],

            'desc' => [
                'desc' => '如果该账户要推给客户使用，需要加一下介绍说明，方便客户填写参数配置',
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
                'type' => 'editor',
                'editorMenu' => [
                    'uploadImage' => 1,
                    'uploadVideo' => 3,
                ],
            ],
        ],
        'check' => 'key',
        'start' => ['Manage/Lib/Util.updateKey', 'Api/Lib/Account.update'],
    ],
];