<?php
return [
    'list' => [
        'field'      => [
            'name',
            'system_id',
            'model',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd'],
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
        ],
        'search'    => [
            'system_id',
            'name',
            'status',
        ],
    ],
    'update' => [
        'field'    => [
            'name' => [
                'rules' => true,
            ],
            'system_id' => [
                'type' => 'select',
                'rules' => true,
            ],
            'model' => [
                'desc' => '为空则使用默认模型，查询模型：opencode models',
            ],
        ],
    ],
];