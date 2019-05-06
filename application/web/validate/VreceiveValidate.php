<?php

namespace app\web\validate;

use think\Validate;

/**
 * V票管理---V票领取记录
 */
class VreceiveValidate extends Validate
{
    protected $rule = [
        'receive_claim'=>'require',
        'receive_number'=>'require|integer',
        'val' => 'require'
    ];

    protected $message = [
        'receive_claim.require' => '领取人必须选择',
        'receive_number.require'=> '数量必须填写',
        'receive_number.integer'=> '数量必须是整数',
        'val.require' => '兑换数量必须填写'
    ];

    protected $scene = [
        'receival'  => ['receive_claim' , 'receive_number'],  // 领取
        'exchange' => ['val'] // 兑换
    ];
}