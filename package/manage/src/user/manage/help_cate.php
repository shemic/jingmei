<?php
return [
    'list' => [
        'field'      => [
            'name',
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
            'info' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
        ],
    ],
];