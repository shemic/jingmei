<?php

return [
    'name' => '账户设置',
    'order' => 'id asc',
    'struct' => [
        'account_id'        => [
            'type'      => 'int(11)',
            'name'      => '账户',
        ],

        'platform_setting_id' => [
            'name'      => '参数名',
            'type'      => 'int(11)',
        ],

        'value' => [
            'name'      => '参数值',
            'type'      => 'varchar(3000)',
        ],
    ],
];
