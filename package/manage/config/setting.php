<?php
return [
    # 项目通信token
    'token' => DEVER_PROJECT,
    # 语言包 暂时无用
    'lang' => 'zh-cn',
    'lang_pack' => ['zh-cn' => '简体中文', 'en' => '英文'],

    # 路由解析设置
    'route' => [
        'api/notify/(.*?)' => 'notify.common?s=$1',
    ],

    # 日志设置
    'log' => ['type' => 'file', 'host' => 'host', 'port' => 'port'],

    # 内容类型
    'content' => [
        'text' => '文字',
        'image' => '图片',
        'video' => '视频',
        'audio' => '音频',
        'doc' => '文档',
        'rich' => '富媒体',
    ],

    # 调试的shell名
    'shell' => 'debug',

    # 定义session
    //'session' => ['host' => '', 'port' => '', 'path' => '', 'cookie' => ''],

    # 定义数据库
    'database' => [
        # 是否自动建表 默认为true
        'create' => true,
        # sql优化，暂时无用
        'opt' => true,
        //'default' => [$env['db'], $env['db1'], 'type' => 'Pdo'],//读写分离
        # 平台默认数据库
        'default' => $env['db'],

        # 分区设置 
        'partition' => [
            # 当前数据库是否支持自动建库，不支持改成false，则database不会自动建库，而是类似table按照表拆分
            'create' => true,
            # 类型：database 按照库拆分（分库） table 按照表拆分（分表） field 按照字段拆分（分区） where 按照条件拆分（分条件） Dever::session('database', 1)可以设置值
            'database' => 'date("Y")',
            'table' => 'date("Ym")',
            # 字段类型分几种：range范围、list列表、hash哈希、key分区
            'field' => [
                'type' => 'range',
                'field' => 'cdate', 
                'value' => 'date("Y-m-d 23:60:60")'//date("Y-m-d 23:60:60", strtotime("-1 day"))'
            ],
            /*
            'field' => [
                'type' => 'list',
                'field' => 'type', 
                'value' => ['1', '2', '3'],//3个值3个分区，然后也可以用Dever::call("manage/admin.test")来返回数组
            ],
            'field' => [
                'type' => 'hash',
                'field' => 'id', 
                'value' => '5'//5个分区
            ],
            'field' => [
                'type' => 'key',
                'field' => 'id', 
                'value' => '5'
            ],*/
            # where类型，一般在表中设置['id' => 1],
            //'where' => 'Dever::call("manage/admin.test")'
        ],
    ],

    # 定义模板
    'template' => [
        'name' => 'pc',//模板配置,如果有手机版，直接配置：'pc,mobile'
        'replace' => [
            '../' => '{$host}',
        ],
    ],
    # 定义redis
    'redis' => $env['redis'],
];