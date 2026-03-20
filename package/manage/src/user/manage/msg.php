<?php
return [
    'list' => [
        'field'      => [
            'id',
            'name',
            'info',
            'status' => [
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
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
        'desc' => '',
        'field'    => [
            'name' => [
                'type' => 'text',
                'maxlength' => 30,
                'desc' => '',
            ],
            'info' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
        ],
    ],
];