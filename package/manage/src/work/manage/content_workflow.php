<?php
return [
    'list' => [
        'field'      => [
            'type',
            'cate_id',
            'name',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
            '分类' => ['route', [
                'path' => 'work_app_manage/cate',
                'param' => [
                    'set' => ['type' => 4, 'menu' => 'work_app_manage/content_workflow', 'parent' => 'work_app_manage/content_workflow'],
                ],
            ]],
        ],
        'data_button' => [
            '编辑' => 'fastedit',
        ],
        'search'    => [
            'type',
            'cate_id' => [
                'rules' => true,
                'option'    => 'Dever::call("Work/Manage/Lib/Common.getList", ["cate", ["type"=>4]])',
            ],
            'name',
            'status',
        ],
    ],
    'update' => [
        'field'    => [
            'name' => [
                'rules' => true,
            ],
            'type' => [
                'type' => 'radio',
                'rules' => true,
            ],
            'cate_id' => [
                'rules' => true,
                'option'    => 'Dever::call("Work/Manage/Lib/Common.getList", ["cate", ["type"=>4]])',
            ],
            'workflow' => [
                'rules' => true,
                'type' => 'cascader',
                'option' => 'Dever::call("Work/Manage/Lib/Workflow.getList")',
            ],
        ],
    ],
];