<?php
return [
    'list' => [
        'field'      => [
            'id',
            'name',
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
            '功能' => ['route', [
                'path' => 'api_manage/app_func',
                'param' => [
                    'set' => ['app_id' => 'id', 'menu' => 'api_manage/app', 'parent' => 'api_manage/app'],
                ],
            ]],
        ],
        'search'    => [
            'name',
            'status',
        ],
    ],
    'update' => [
        'field'    => [
            'name' => [
                'rules' => true,
            ],
            'desc' => 'textarea',
            'status' => [
                'type' => 'radio',
            ],
        ],
    ],
];