<?php

namespace app\error\controller;

class Error
{
    public function _empty()
    {
        return json(['code' => 404, 'msg' => '非法请求']);
    }
}