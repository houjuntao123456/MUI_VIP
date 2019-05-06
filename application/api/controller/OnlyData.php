<?php

namespace app\api\controller;

use think\Db;
use app\api\service\OnlyData as OD;

class OnlyData extends Common
{
    public function insert()
    {
        $res = OD::allotInsert($this->data, $this->db);
        if ($res['code'] != 200) {
            $this->api(400, $res['msg']);
        } else {
            $this->api(200, 'insert success');
        }
    }

    public function update()
    {
        $res = OD::allotUpdate($this->data, $this->db);
        if ($res['code'] != 200) {
            $this->api(400, $res['msg']);
        } else {
            $this->api(200, 'update success');
        }
    }

    public function delete()
    {
        $res = OD::allotDelete($this->data, $this->db);
        if ($res['code'] != 200) {
            $this->api(400, $res['msg']);
        } else {
            $this->api(200, 'delete success');
        }
    }
}