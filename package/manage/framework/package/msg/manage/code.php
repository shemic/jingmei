<?php
return [
    'list' => [
        'field'      => [
            'index' => [
                'name' => '序号',
            ],
            'account',
            'code',
            'record' => [
                'type' => 'popover',
                'title' => '查看',
                'location' => 'left',
                'show' => 'Dever::load("Msg/Lib/Manage")->showRecord("{id}")',
            ],
            'status',
            'cdate',
        ],
        'button' => [
            //'新增' => 'fastadd',
        ],
        'data_button' => [
            //'设置' => 'fastedit',
        ],
        'search' => [
            'account',
            'code',
            'status',
        ]
    ],
];