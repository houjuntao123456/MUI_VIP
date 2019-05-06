<?php

// 表数据验证格式
return [
    'vip_goods' => [
        'name' => 'require|alphaDash',
        'code' => 'require|length:1,32'
    ],
];