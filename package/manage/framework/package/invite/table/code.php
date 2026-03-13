<?php
return [
    'name' => '邀请码',
    'partition' => 'Dever::call("manage/util.system")',
    # 数据结构
    'struct' => [
        'uid'       => [
            'type'      => 'int(11)',
            'name'      => '用户',
        ],

        'value'      => [
            'name'      => '名称',
            'type'      => 'varchar(30)',
        ],
    ],

    /*
    'index' => [
        'search' => 'uid,value',
    ],
    */
];
