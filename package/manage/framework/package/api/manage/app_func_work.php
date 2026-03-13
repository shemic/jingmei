<?php
return [
    'update' => [
        'field'    => [
            'platform_id' => [
                'rules' => true,
                'type' => 'select',
                'remote'    => 'Api/Api/Manage.getApi',
                # 无需默认值
                'remote_default' => false,
            ],
            'api_id' => [
                'rules' => true,
                'type' => 'select',
                'placeholder' => '选择接口',
            ],
            /*
            'status' => [
                'width' => '40',
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],*/
        ],
        'drag' => 'sort',
    ],
];