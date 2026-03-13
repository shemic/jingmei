<?php
return [
    'list' => [
        'where' => ['service_id'],
        'field'      => [
            'code',
            'name',
            'mode',
            'icon',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd'],
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
        ],
        'search'    => [
            'name',
            'status',
        ],
    ],
    'update' => [
        'start' => 'Work/Manage/Lib/Common.update',
        'field'    => [
            'service_id' => 'hidden',
            'code' => [
                'desc' => '唯一标识，不填写将自动生成',
                'type' => Dever::input('id') ? 'hidden' : 'text',
            ],
            'name' => [
                'rules' => true,
            ],
            'mode' => [
                'rules' => true,
                'type' => 'radio',
            ],
            'icon' => [
                'desc' => '图标地址：https://lucide.dev/icons/',
            ],
            'service/app_workflow' => [
                'name' => '工作流',
                'where'  => ['app_id' => 'id'],
            ],
        ],
    ],
];