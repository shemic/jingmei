<?php
return [
    'list' => [
        'field'      => [
            'code',
            'name',
            'type',
            'platform_id',
            'protocol',
            'model',
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
            'type',
            'platform_id',
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
            'type' => [
                'type' => 'radio',
                'rules' => true,
            ],
            'platform_id' => [
                'type' => 'select',
                'rules' => true,
            ],
            'protocol' => [
                'type' => 'select',
                'rules' => true,
            ],
            'model' => [
                'desc' => '如模型名，或者接入点，或者工作流ID',
            ],
        ],
    ],
];