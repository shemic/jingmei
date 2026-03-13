<?php
return [
    'list' => [
        'field'      => [
            'code',
            'name',
            'cate_id',
            'model' => [
                'show' => 'Dever::call("Work/Manage/Lib/Model.getName", "{model}")',
            ],
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
            '分类' => ['route', [
                'path' => 'work_app_manage/cate',
                'param' => [
                    'set' => ['type' => 2, 'menu' => 'work_app_manage/agent', 'parent' => 'work_app_manage/agent'],
                ],
            ]],
        ],
        'data_button' => [
            '编辑' => 'fastedit',
        ],
        'search'    => [
            'cate_id' => [
                'option'    => 'Dever::call("Work/Manage/Lib/Common.getList", ["cate", ["type"=>2]])',
            ],
            'code',
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
            'cate_id' => [
                'rules' => true,
                'option'    => 'Dever::call("Work/Manage/Lib/Common.getList", ["cate", ["type"=>2]])',
            ],
            'model' => [
                'rules' => true,
                'type' => 'cascader',
                'option' => 'Dever::call("Work/Manage/Lib/Model.getList", [1])',
            ],
            'system_prompt' => [
                'rules' => true,
                'desc' => '输入系统提示词',
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
        ],
    ],
];