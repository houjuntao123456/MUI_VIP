<?php

namespace app\web\validate;

use think\Validate;

/**
 * 产品标签下的--------商品标签
 */
class LabelValidate extends Validate
{
    protected $rule = [
        'name'=>'require|chsAlphaNum',
        'type'=>'require|chsAlphaNum'
    ];

    protected $message = [
        'name.require' => '商品标签名不能为空',
        'name.chsAlphaNum'=>'商品标签名只能是汉字、字母和数字',
        'type.require'=>'标签类型不能为空',
        'type.chsAlphaNum'=>'标签类型只能是汉字、字母和数字'
    ];

    protected $scene = [
        'addSpread'  => ['name' , 'type'],  // 添加扩展类别标签
        'editAlias' => ['name'],     // 修改扩展别名
        'add' => ['name'] // 添加扩展类
    ];
}