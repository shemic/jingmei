<?php
return [
    'name' => '参数格式转换',
    'order' => 'sort asc, id asc',
    'struct' => [
        'name' => [
            'name'      => '转换名称',
            'type'      => 'varchar(30)',
        ],

        'method' => [
            'name'      => '转换方式',
            'type'      => 'varchar(1000)',
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],

    'default' => [
        'field' => 'sort,name,method,cdate',
        'value' => [
            '1,"字符串","strval(\'{value}\')",' . DEVER_TIME,
            '2,"整数","intval(\'{value}\')",' . DEVER_TIME,
            '3,"浮点数","floatval(\'{value}\')",' . DEVER_TIME,
            '4,"uri编码","urlencode(\'{value}\')",' . DEVER_TIME,
            '5,"uri编码-RFC3986规则","rawurlencode(\'{value}\')",' . DEVER_TIME,
            '6,"转小写","strtolower(\'{value}\')",' . DEVER_TIME,
            '7,"转大写","strtoupper(\'{value}\')",' . DEVER_TIME,
            '8,"去空值","trim(\'{value}\')",' . DEVER_TIME,

            '10,"时间戳","strtotime(\'{value}\')",' . DEVER_TIME,
            '11,"yyyy-MM-dd HH:mm:ss","date(\'Y-m-d H:i:s\', \'{value}\')",' . DEVER_TIME,
            '12,"yyyy-MM-DDTHH:mm:ss+TIMEZONE","date(\'Y-m-d\\\TH:i:sP\', \'{value}\')",' . DEVER_TIME,
            '13,"yyyy-MM-ddTHH:mm:ssZ","date(\'Y-m-d\\\TH:i:sZ\', \'{value}\')",' . DEVER_TIME,
        ],
        'num' => 1,
    ],
];