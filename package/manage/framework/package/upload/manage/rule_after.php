<?php
return [
    'update' => [
        'field'    => [
            'type' => [
                'type' => 'radio',
                'remote' => 'Upload/Manage/Api/Manage.getImage',
            ],
            'type_id' => [
                'name' => '操作配置',
                'type' => 'select',
                'option' => [],
            ],
        ],
        'drag' => 'sort',
    ],
];