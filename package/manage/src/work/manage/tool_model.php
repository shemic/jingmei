<?php
return [
    'update' => [
        'field'    => [
            'model' => [
                'rules' => true,
                'type' => 'cascader',
                'option' => 'Dever::call("Work/Manage/Lib/Model.getList")',
            ],
        ],
        'drag' => 'sort',
    ],
    
];