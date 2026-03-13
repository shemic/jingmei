<?php
return [
    'list' => [
        'field'      => [
            'code',
            'name',
            'uid',
            'service_id',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
        ],
        'data_button' => [
            '编辑' => 'fastedit',
            '内容' => ['route', [
                'path' => 'user_manage/content',
                'param' => [
                    'set' => ['project_id' => 'id', 'menu' => 'user_manage/project', 'parent' => 'user_manage/project'],
                ],
            ]],
            '运行' => ['route', [
                'path' => 'user_manage/run',
                'param' => [
                    'set' => ['project_code' => 'code', 'menu' => 'user_manage/project', 'parent' => 'user_manage/project'],
                ],
            ]],
        ],
        'search'    => [
            'name',
            'status',
        ],
    ],
    'update' => [
        'start' => 'Work/Manage/Lib/Common.update',
        'field'    => [
            'code' => [
                'desc' => '唯一标识，不填写将自动生成',
                'type' => Dever::input('id') ? 'hidden' : 'text',
            ],
            'name' => [
                'rules' => true,
            ],
            'uid' => [
                'rules' => true,
            ],
            'service_id' => [
                'rules' => true,
            ],
            /*
            'system_prompt' => [
                'desc' => '输入系统提示词',
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],*/
        ],
    ],
];