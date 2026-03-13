<?php
return [
    # 暂时是单一平台，后续优化成多平台
    'name' => '平台',
    'order' => 'sort asc',
    'struct' => [
        'name' => [
            'name'      => '平台名称',
            'type'      => 'varchar(32)',
        ],
        'number' => [
            'name'      => '集团号',
            'type'      => 'varchar(32)',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
    'default' => [
        'field' => 'id,name,number,sort,cdate',
        'value' => [
            '1,"默认平台","default",-100,' . DEVER_TIME,
        ],
        'num' => 1,
    ],
];