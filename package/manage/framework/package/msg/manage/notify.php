<?php
return [
    'list' => [
        'field'      => [
            'id',
            'account',
            'content',
            'record' => [
                'type' => 'popover',
                'title' => '查看',
                'location' => 'right',
                'show' => '{record}',
            ],
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
            'content',
        ]
    ],
];