<?php
return [
    'name' => '组织职位',
    'partition' => 'Dever::load("Manage/Util")->system()',
    'order' => 'sort asc',
    'struct' => [
        'org_id'      => [
            'name'      => '组织',
            'type'      => 'int(11)',
        ],
        'name' => [
            'name'      => '职位名称',
            'type'      => 'varchar(32)',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
    ],
    'default' => [
        'field' => 'name,org_id,cdate',
        'value' => [
            '"默认职位",1,' . DEVER_TIME,
        ],
    ],
];