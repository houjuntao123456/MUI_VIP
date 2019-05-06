<?php

namespace app\api\service\sale;

use think\Db;

class Goods
{
    private $db;
    private $classifys;

    /**
     * 获取当前类获取子分类
     */
    public function classfiyChild($classify = '', $db = '')
    {
        $this->db = $db;
        if ($classify == '') {
            $this->classifys = '';
        } else {
            // 查询当前类
            $req = Db::table($db.'.vip_goods_classify')->find($classify);
            // 拼接全路径
            $path = $req['path'].$req['code'].',';
            // 获得子类
            $child = Db::table($db.'.vip_goods_classify')->where('path', 'like', $path.'%')->select();
            // 拆分子类得到code 拼上当前类
            if (!empty($child)) {
               $classify .= ','.implode(',', array_column($child, 'code'));
            }
            $this->classifys = $classify;
        }
        return $this;
    }

    public function getGoods()
    {
        if ($this->classifys == '') {
            return '';
        }
        $goods = Db::table($this->db.'.vip_goods')->where('classify_code', 'in', $this->classifys)->select();
        if (empty($goods)) {
            return '';
        }
        return implode(',', array_column($goods, 'code'));
    }
}