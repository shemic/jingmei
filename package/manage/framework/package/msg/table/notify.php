<?php
return [
    'name' => '通知记录',
    'partition' => 'Dever::call("Manage/Lib/Util.system")',
    'struct' => [
        'template_id'        => [
            'type'      => 'int(11)',
            'name'      => '模板',
        ],

        'account'      => [
            'type'      => 'varchar(50)',
            'name'      => '账户',
        ],

        'record'        => [
            'type'      => 'text(255)',
            'name'      => '返回记录',
        ],

        'content'        => [
            'type'      => 'text(255)',
            'name'      => '发送内容',
        ],
    ],

    'index' => [
        'account' => 'account',
        'template_id' => 'template_id',
    ],
];
