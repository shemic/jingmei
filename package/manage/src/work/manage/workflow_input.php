<?php
return [
    'list' => [
        'where' => ['workflow_id'],
        'field'      => [
            'code',
            'name',
            'type',
            'must',
            'search',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd'],
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
            '选项' => ['route', [
                'path' => 'work_app_manage/workflow_input_option',
                'param' => [
                    'set' => ['workflow_id' => 'workflow_id', 'workflow_input_id' => 'id', 'menu' => 'work_app_manage/workflow', 'parent' => 'work_app_manage/workflow_input'],
                ],
            ], '', 'type=select'],
            //'删除' => 'delete',
        ],
        'search'    => [
            'name',
            'type',
            'status',
        ],
    ],
    'update' => [
        'tab' => [
            '基本信息' => 'workflow_id,workflow_nodes_id,code,name,icon,type,source,default,must,search',
            '提示设置' => 'tip_title,tip_pic,tip_desc',
        ],
        'control' => [
            'source' => 'type=list',
        ],
        'field'    => [
            'workflow_id' => 'hidden',
            'workflow_nodes_id' => [
                'type' => 'select',
                'option' => 'Dever::call("Work/Manage/Lib/Workflow.getNodesList")',
            ],
            'code' => [
                'rules' => true,
            ],
            'name' => [
                'rules' => true,
            ],
            'icon' => [
                'desc' => '图标地址：https://lucide.dev/icons/',
            ],
            'type' => [
                'type' => 'radio',
                'rules' => true,
                'remote' => 'Work/Manage/Api/Workflow.getFormDefault',
                'desc' => '注意：分类选择需要用户自行创建选项',
            ],

            'default' => [
                'type' => 'select',
                'default' => '0',
            ],

            'source' => [
                'type' => 'cascader',
                //'remote' => 'Work/Manage/Api/Workflow.getSource',
                'option' => 'Dever::call("Work/Manage/Lib/Workflow.getSource")',
            ],

            'must' => [
                'type' => 'radio',
            ],

            'search' => [
                'type' => 'radio',
            ],
            
            'tip_title' => [
                
            ],
            'tip_pic' => [
                'type' => 'upload',
                'upload' => '1',
                'project' => 'place',
                'multiple' => false,
                'style' => 'pic',
            ],
            'tip_desc' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
            
        ],
    ],
];