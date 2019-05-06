<?php

namespace app\web\validate;

use think\Validate;

/**
 * V票管理---V票资金来源
 */
class VcapitalValidate extends Validate
{
    protected $rule = [
        'vRule_name'=>'require', // V票规则
        'capital_name' =>'require', // 乐捐人
        'capital_money' => 'require' // 钱数
    ];

    protected $message = [
        'vRule_name.require' => '规则名称必须选择',
        'capital_name.require'=>'乐捐人必须选择',
        'capital_money.require' => '乐捐金额必须填写'
    ];

    // protected $scene = [
    //     'add'  => ['name'],  // 添加规则
    //     'edit' => ['name']  // 编辑
    // ];
}