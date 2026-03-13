<?php
return [
    'name' => 'openid表',
    'partition' => 'Dever::call("Manage/Lib/Util.system")',
    'struct' => [
        'account_id'        => [
            'type'      => 'int(11)',
            'name'      => '账户',
        ],

        'env' => [
            'name'      => '运行环境',
            'type'      => 'tinyint(1)',
        ],

        'uid'       => [
            'type'      => 'int(11)',
            'name'      => '用户ID',
        ],

        'openid'        => [
            'type'      => 'varchar(60)',
            'name'      => 'openid',
        ],
    ],
];
