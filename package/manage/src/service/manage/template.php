<?php
return [
    'list' => [
        'field'      => [
            'id',
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
            '数据' => ['route', [
                'path' => 'service_manage/template_data',
                'param' => [
                    'set' => ['template_id' => 'id', 'menu' => 'service_manage/template', 'parent' => 'service_manage/template'],
                ],
            ]],
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
        ],
    ],
];