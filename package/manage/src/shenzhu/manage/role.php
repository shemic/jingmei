<?php
Dever::db('shenzhu/session');
Dever::db('shenzhu/message');
return [
    'list' => [
        'field'      => [
            'code',
            'name',
            'cate_id',
            'system_id',
            'memory',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd'],
            '分类' => ['route', [
                'path' => 'shenzhu_manage/cate',
                'param' => [
                    'set' => ['menu' => 'shenzhu_manage/role', 'parent' => 'shenzhu_manage/role'],
                ],
            ]],
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
            //'删除' => 'delete',
        ],
        'search'    => [
            'cate_id',
            'system_id',
            'code',
            'name',
            'status',
        ],
    ],
    'update' => [
        'start' => 'Shenzhu/Manage/Lib/Role.update',
        'end' => 'Shenzhu/Manage/Lib/Role.sync',
        'field'    => [
            'code' => [
                'desc' => '唯一标识，使用助理时将通过该标识调用，不能为空，请勿重复',
            ],
            'name' => [
                'rules' => true,
            ],
            'cate_id' => [
                'type' => 'select',
                'rules' => true,
            ],
            'system_id' => [
                'type' => 'select',
                'rules' => true,
            ],
            'model' => [
                'desc' => '为空则使用默认模型，查询模型：opencode models',
            ],
            'memory' => [
                'type' => 'radio',
            ],
            'info' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
            'prompt' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
        ],
    ],
];