<?php
return [
    'list' => [
        'field'      => [
            'id',
            'name',
            'save_id',
            'type',
            'cdate',
        ],
        'button' => [
            '新增' => 'add',
        ],
        'data_button' => [
            '编辑' => 'edit',
        ],
    ],
    'update' => [
        'field'    => [
            'name',
            'save_id' => [
                'type' => 'radio',
            ],
            'type' => [
                'type' => 'checkbox',
                'control' => true,
            ],
            'chunk' => [
                'desc' => '单位：M',
            ],
            'size' => [
                'desc' => '单位：M',
            ],
            'limit' => 'radio',
            'min_width',
            'min_height',
            'upload/rule_after'=> [
                'name'      => '后续操作',
                'desc'      => '仅支持图片',
                'where'     => ['rule_id' => 'id'],
            ],
        ],
        'control' => [
            'limit' => [
                'type' => [1,2,3],
            ],
            'min_width' => [
                'type' => [1,2,3],
            ],
            'min_height' => [
                'type' => [1,2,3],
            ],
            'upload/rule_after' => [
                'type' => [1],
            ],
        ],
    ],
];