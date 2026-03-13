<?php
return [
    'name' => '邀请关系',
    'partition' => 'Dever::call("Manage/Lib/Util.system")',
    # 数据结构
    'struct' => [
        'uid'       => [
            'type'      => 'int(11)',
            'name'      => '邀请人',
        ],

        'to_uid'       => [
            'type'      => 'int(11)',
            'name'      => '被邀请人',
        ],

        'level'       => [
            'type'      => 'int(11)',
            'name'      => '邀请级数',
        ],
    ],

    'index' => [
        'search' => 'uid,to_uid',
        'uid' => 'uid,level',
        'to_uid' => 'to_uid,level',
    ],
];
