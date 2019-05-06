<?php

namespace app\api\controller;

class Error
{
    public function _empty()
    {
        echo json_encode(['code' => 404, 'msg' => 'illegal request']);exit;
    }
}