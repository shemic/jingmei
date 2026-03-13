<?php
return [
    'list' => [
        'field'      => [
            'code',
            'cate_id',
            'name',
            'out_type',
            'icon',
            'shenzhu_role' => [
                'show' => 'Dever::call("Shenzhu/Manage/Lib/Role.getName", "{shenzhu_role}")',
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
                    'set' => ['type' => 1, 'menu' => 'work_app_manage/workflow', 'parent' => 'work_app_manage/workflow'],
                ],
            ]],
        ],
        'data_button' => [
            '编辑' => 'fastedit',
            '节点' => ['route', [
                'path' => 'work_app_manage/workflow_nodes',
                'param' => [
                    'set' => ['workflow_id' => 'id', 'menu' => 'work_app_manage/workflow', 'parent' => 'work_app_manage/workflow'],
                ],
            ]],
            '输入项' => ['route', [
                'path' => 'work_app_manage/workflow_input',
                'param' => [
                    'set' => ['workflow_id' => 'id', 'menu' => 'work_app_manage/workflow', 'parent' => 'work_app_manage/workflow'],
                ],
            ]],
        ],
        'search'    => [
            'cate_id' => [
                'rules' => true,
                'option'    => 'Dever::call("Work/Manage/Lib/Common.getList", ["cate", ["type"=>1]])',
            ],
            'code',
            'name',
            'status',
        ],
    ],
    'update' => [
        'tab' => [
            '基本信息' => 'code,name,cate_id,shenzhu_role,out_type,icon,info',
            '提示设置' => 'is_text,tip,is_upload,upload_type',
        ],
        'start' => 'Work/Manage/Lib/Common.update',
        'field'    => [
            'code' => [
                'desc' => '唯一标识，不填写将自动生成',
                'type' => Dever::input('id') ? 'hidden' : 'text',
            ],
            'name' => [
                'rules' => true,
            ],
            'info' => [
                'desc' => '主要用于描述怎么给这个工作流传入参数',
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
            'cate_id' => [
                'rules' => true,
                'option'    => 'Dever::call("Work/Manage/Lib/Common.getList", ["cate", ["type"=>1]])',
            ],
            'shenzhu_role' => [
                'type' => 'cascader',
                'clearable' => true,
                'option'    => 'Dever::call("Shenzhu/Manage/Lib/Role.getList")',
            ],
            'out_type' => [
                'type' => 'radio',
                'rules' => true,
            ],
            'icon' => [
                'desc' => '图标地址：https://lucide.dev/icons/',
            ],
            'is_text' => [
                'type' => 'radio',
                'rules' => true,
            ],
            'tip' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
            'is_upload' => [
                'type' => 'radio',
                'rules' => true,
            ],
            'upload_type' => [
                'type' => 'checkbox',
                'rules' => true,
            ],
        ],
        'control' => [
            'upload_type' => 'is_upload=1',
        ],
    ],
];