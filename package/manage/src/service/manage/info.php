<?php
return [
    'list' => [
        'field'      => [
            'code',
            'name',
            'info',
            'icon',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
        ],
        'data_button' => [
            '编辑' => 'fastedit',
            '应用' => ['route', [
                'path' => 'service_manage/app',
                'param' => [
                    'set' => ['service_id' => 'id', 'menu' => 'service_manage/info', 'parent' => 'service_manage/info'],
                ],
            ]],
        ],
        'search'    => [
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
            'icon' => [
                'desc' => '图标地址：https://lucide.dev/icons/',
            ],
            'info' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
            /*
            'work/service_type' => [
                'name' => '业务类型',
                'where'  => ['service_id' => 'id'],
            ],
            */
        ],
    ],
];