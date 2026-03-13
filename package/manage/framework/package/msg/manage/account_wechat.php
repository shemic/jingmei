<?php
return [
    'update' => [
        'field'    => [
            'template_id' => [
                'type' => 'hidden',
            ],
            'template_name' => [
                'type' => 'show',
                'name' => '消息模板',
                'default'  => 'Dever::call("Msg/Lib/Manage.getTemplateName", [{template_id}, "{template_name}"])',
            ],
            'code' => [
                'tip' => '填写微信模板ID',
            ],
        ],
    ],
];