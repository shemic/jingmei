<?php
return [
    'update' => [
        'field'    => [
            'key' => [
                'type' => 'hidden',
                'remote' => 'Api/Api/Manage.keyToField?project=api',
            ],
            'price' => [
                'type' => 'text',
                'tip' => '设置价格',
                'rules' => true,
            ],
            'num' => [
                'type' => 'text',
                'rules' => true,
            ],
            'day_num' => [
                'type' => 'text',
                'rules' => true,
            ],
        ],
    ],
];