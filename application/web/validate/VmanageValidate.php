<?php

namespace app\web\validate;

use think\Validate;

/**
 * V票管理
 */
class VmanageValidate extends Validate
{
    protected $rule = [
        'name'=>'require|chsAlphaNum',
        'staff' =>'require'
    ];

    protected $message = [
        'name.require' => '规则名称必须填写',
        'name.chsAlphaNum'=>'规则名称只能是汉字、字母和数字',
        'staff.require' => '员工必须勾选'
    ];

    // protected $scene = [
    //     'add'  => ['name'],  // 添加规则
    //     'edit' => ['name']  // 编辑
    // ];
}