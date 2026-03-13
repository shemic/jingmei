<?php
return [
    'menu' => [
        'msg' => [
            'parent' => 'connect',
            'name' => '消息管理',
            'icon' => 'message-3-line',
            'sort' => '70',
        ],

        'template' => [
            'parent'    => 'msg',
            'name'      => '消息模板',
            'icon'      => 'message-3-line',
            'sort'      => '1',
        ],

        'account' => [
            'parent'    => 'msg',
            'name'      => '消息账户',
            'icon'      => 'codepen-fill',
            'sort'      => '2',
        ],

        /*
        'code' => [
            'parent'    => 'msg',
            'name'      => '验证码记录',
            'icon'      => 'codepen-fill',
            'sort'      => '2',
        ],
        */
    ],
];