<?php
return [
    'list' => [
        'field'      => [
            'id',
            'name',
            'pinyin',
            'pinyin_first',
            'province_id',
            'level_id',
            'sort' => 'input',
            'status' => [
                'type' => 'switch',
                'show'  => '{status}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
            'cdate',
        ],
        'button' => [
            '新增' => 'fastadd',
        ],
        'data_button' => [
            '编辑' => 'fastedit',
        ],
        'search'    => [
            'name',
            'province_id',
            'pinyin',
            'pinyin_first',
            'status',
        ],
    ],
    'update' => [
        'field'    => [
            'name' => [
                'rules' => true,
            ],
            //'pinyin',
            //'pinyin_first',
            'province_id' => 'select',
            'level_id' => [
                'clearable' => true,
                'default' => '',
            ],
            //'sort',
        ],
        'start' => 'Area/Lib/Manage.up',
    ],
];