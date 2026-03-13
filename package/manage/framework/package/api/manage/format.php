<?php
return [
    'list' => [
        'field'      => [
            'id',
            'name',
            'sort' => 'input',
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
        ],
        'data_button' => [
            '编辑' => 'fastedit',
        ],
        'search' => [
            'name',
        ],
    ],
    'update' => [
        'field'    => [
            'name' => [
                'rules' => true,
            ],
            'method' => [
                'rules' => true,
                'type' => 'textarea',
                'autosize' => ['minRows' => 6],
            ],
        ],
    ],
];