<?php
return [
    'list' => [
        'where' => ['workflow_id'],
        'field'      => [
            'code',
            'name',
            'type',
            'next',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd'],
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
            '删除' => 'delete',
        ],
        'search'    => [
            'code',
            'name',
            'status',
        ],
    ],
    'update' => [
        'field'    => [
            'workflow_id' => 'hidden',
            'name' => [
                'rules' => true,
            ],
            'code' => [
                'rules' => true,
                'desc' => '本工作流节点唯一标识，尽量用英文，第一个节点标识为main',
            ],
            'type' => [
                'rules' => true,
                'type' => 'radio',
                'remote' => 'Work/Manage/Api/Workflow.getTypeValue',
            ],
            'type_value' => [
                'rules' => true,
                'type' => 'cascader',
                //'check' => true,
                //'multiple' => true,
            ],
            'next' => [
                'desc' => '输入下一节点的标识，end为结束标识',
            ],
        ],
    ],
];