<?php
return [
	'name' => '省份',
	'order' => 'sort asc,id asc',
	'struct' => [
		'name'		=> [
			'type' 		=> 'varchar(150)',
			'name' 		=> '省份名称',
		],

		'pinyin'		=> [
			'type' 		=> 'varchar(300)',
			'name' 		=> '拼音',
		],

		'pinyin_first'		=> [
			'type' 		=> 'varchar(30)',
			'name' 		=> '拼音首字母',
		],

		'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '启用',
                2 => '关闭',
            ],
        ],
	],
];
