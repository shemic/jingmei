<?php
return [
    'list' => [
        'where' => ['project_code', 'run_code'],
        'field'      => [
            'run_code',
            'node_id',
            //'output',
            'start_date' => [
                'show' => '{start_date} > 0 ? Dever::cdate("Y-m-d H:i:s", {start_date}) : "-"'
            ],
            'end_date' => [
                'show' => '{end_date} > 0 ? Dever::cdate("Y-m-d H:i:s", {end_date}) : "-"'
            ],
            'status' => '',
            'cdate',
        ],
        'button' => [
            
        ],
        'data_button' => [
            
        ],
        'search'    => [
            'status',
        ],
    ],
];