<?php
return [
    'source' => 'area/level',
    'list' => [
        'field'      => [
            'name',
            'level',
            'city',
        ],
        'button' => [
            '新增' => 'fastadd',
        ],
        'data_button' => [
            '编辑' => 'fastedit',
        ],
    ],
    'update' => [
        'field'    => [
            'name',
            'level',
            'city' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 6],
                'desc' => '多个城市用叹号、隔开',
            ],
        ],
        'end' => 'Area/Lib/Manage.upLevel',
    ],
];