<?php
return [
    'source' => 'work/platform',
    'list' => [
        'field'      => [
            'name',
            'host',
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
        ],
    ],
];