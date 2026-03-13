<?php
return [
    'name' => '短信设置',
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
            'name'      => '短信模板编码',
        ],
    ],
    'index' => [
        'search' => 'account_id,template_id',
    ],
];
