<?php
return [
    'list' => [
        'where' => ['template_id'],
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
            'template_id' => 'hidden',
            'name' => [
                'rules' => true,
            ],
        ],
    ],
];