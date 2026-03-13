<?php
return [
    'list' => [
        'field'      => [
            'id',
            'name',
            'pinyin',
            'pinyin_first',
            'area' => [
                'show' => 'Dever::call("Area/Lib/Data.string", "{area}")',
            ],
            'type',
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
            'area' => [
                'type' => 'cascader',
                'remote'    => 'Area/Api/Data.get&total=3',
            ],
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
            'area' => [
                'type' => 'cascader',
                'remote'    => 'Area/Api/Data.get&total=3',
            ],
            'type' => 'radio',
            //'sort',
        ],
        'start' => 'Area/Lib/Manage.up',
    ],
];