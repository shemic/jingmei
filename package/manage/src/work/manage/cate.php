<?php
return [
    'list' => [
        'where' => ['type'],
        'field'      => [
            'name',
            'icon',
            'info',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
        ],
        'data_button' => [
            '编辑' => 'fastedit',
        ],
        'search'    => [
            'name',
            'status',
        ],
    ],
    'update' => [
        'field'    => [
            'type' => 'hidden',
            'name' => [
                'rules' => true,
            ],
            'icon' => [
                'desc' => '图标地址：https://lucide.dev/icons/',
            ],
            'info' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
        ],
    ],
];