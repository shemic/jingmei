<?php
return [
    'list' => [
        'where' => ['workflow_id', 'workflow_input_id'],
        'field'      => [
            'name',
            'value',
            'type',
            /*
            'model' => [
                'name' => '平台模型',
                'show' => 'Dever::call("Work/Manage/Lib/Model.getSelectName", ["{id}", "workflow_input_option_model", "model_id"])',
            ],*/
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd'],
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
            //'删除' => 'delete',
        ],
        'search'    => [
            'name',
        ],
    ],
    'update' => [
        'field'    => [
            'workflow_id' => 'hidden',
            'workflow_input_id' => 'hidden',
            'name' => [
                'rules' => true,
            ],
            'value' => [
                'rules' => true,
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
            'type' => 'radio',
            'icon' => [
                'desc' => '图标地址：https://lucide.dev/icons/',
            ],
            'tag' => [
                
            ],
            'pic' => [
                'type' => 'upload',
                'upload' => '1',
                'project' => 'place',
                'multiple' => false,
                'style' => 'pic',
            ],
            'info' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
        ],
    ],
];