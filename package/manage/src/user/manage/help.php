<?php
return [
    'list' => [
        'field'      => [
            'name',
            'cate_id',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd'],
            '分类' => ['route', [
                'path' => 'user_manage/help_cate',
                'param' => [
                    'set' => ['menu' => 'user_manage/help', 'parent' => 'user_manage/help'],
                ],
            ]],
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
        ],
        'search'    => [
            'name',
            'cate_id',
            'status',
        ],
    ],
    'update' => [
        'field'    => [
            'name' => [
                'rules' => true,
            ],
            'cate_id' => [
                'type' => 'select',
                'rules' => true,
            ],
            'content' => [
                'type' => 'editor',
                'rules' => true,
                'editorMenu' => [
                    'uploadImage' => 1,
                    'uploadVideo' => 3,
                ],
            ],
        ],
    ],
];