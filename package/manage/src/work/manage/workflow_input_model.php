<?php
return [
    'update' => [
        'field'    => [
            'model' => [
                'rules' => true,
                'type' => 'cascader',
                'option' => 'Dever::call("Work/Manage/Lib/Model.getList")',
            ],
            'workflow_input_option_id' => [
                'type' => 'select',
                'multiple' => true,
                'option' => 'Dever::call("Work/Manage/Lib/Tool.getInputOption")',
            ],
        ],
    ],
    
];