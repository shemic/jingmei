<?php
return [
    'name' => '模板设置',
    'struct' => [
        'account_id'        => [
            'type'      => 'int(11)',
            'name'      => '账户',
        ],

        'template_id'        => [
            'type'      => 'int(11)',
            'name'      => '消息模板',
            'value'     => 'msg/template',
        ],

        'code'      => [
            'type'      => 'varchar(50)',
            'name'      => '微信模板ID',
        ],
    ],
    'index' => [
        'search' => 'account_id,template_id',
    ],
];
