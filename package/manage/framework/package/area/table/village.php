<?php
return [
	'name' => '社区',
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

		'area'       => [
            'type'      => 'varchar(500)',
            'name'      => '所在城市',
        ],

		'province_id'		=> [
			'type' 		=> 'int(11)',
			'name' 		=> '省份',
		],

		'city_id'		=> [
			'type' 		=> 'int(11)',
			'name' 		=> '城市',
		],

		'county_id'		=> [
			'type' 		=> 'int(11)',
			'name' 		=> '区县',
		],

		'town_id'		=> [
			'type' 		=> 'int(11)',
			'name' 		=> '街道',
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
