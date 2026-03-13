<?php
return [
    'list' => [
        'field'      => [
            'name',
            'sort' => 'input',
            'manage/group_org_job'=> [
                'name'      => '职位列表',
                'where'      => ['org_id' => 'id'],
            ],
            'status' => [
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
        ],

        'button' => [
            '新增' => ['fastadd'],
        ],

        'data_button' => [
            '编辑' => ['fastedit', 'name,sort,status,manage/group_org_job'],
        ],

    ],
    'update' => [
        'field'    => [
            'name',
            'sort',
            'status' => 'radio',
            'manage/group_org_job'=> [
                'name'      => '职位设置',
                'where'      => ['org_id' => 'id'],
            ],
        ],
    ],
];