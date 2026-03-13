<?php
return [
    'list' => [
        'where' => ['tool_id'],
        'field'      => [
            'code',
            'name',
            'desc',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
        ],
        'data_button' => [
            '编辑' => 'fastedit',
            '删除' => 'delete',
        ],
        'search'    => [
            'tool_id' => 'hidden',
            'code',
            'name',
        ],
    ],
    'update' => [
        'field'    => [
            'tool_id' => 'hidden',
            'code' => [
                'rules' => true,
            ],
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