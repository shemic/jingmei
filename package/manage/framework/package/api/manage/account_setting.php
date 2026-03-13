<?php
return [
    'update' => [
        'field'    => [
            'platform_setting_id' => 'hidden',
            'platform_setting_name' => [
                'name' => '参数名',
                'type' => 'show',
                'default' => 'Dever::db("api/platform_setting")->find("{platform_setting_id}")["name"]',
                //'disable' => true,
                //'option'     => 'Dever::call("api/app.getSetting", '.$account['platform_id'].')',
                //'remote' => 'api/manage.getSettingName',
                //'remote_default' => false,
            ],
            'value' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
            ],
        ],
    ],
];