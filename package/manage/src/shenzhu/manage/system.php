<?php
return [
    'list' => [
        'field'      => [
            'name',
            'host',
            'type',
            'project',
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
            'name' => [
                'rules' => true,
            ],
            'host' => [
                'rules' => true,
            ],
            'api_key' => [
                'rules' => true,
            ],
            'project' => [
                'rules' => true,
            ],
            'type' => [
                'rules' => true,
            ],
        ],
    ],
];