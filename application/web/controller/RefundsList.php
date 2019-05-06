<?php

namespace app\web\controller;

use think\Db;
use app\web\service\ErpWhere as EOW;

/**
 * Author hrl
 * Date 2018/12/28
 * Description 商品管理--退货记录
 */
class RefundsList extends Common
{    
    /**
     * 商品退货记录列表
     */
    public function index()
    {
        //默认查询自己组织/门店下的退货记录
        $where = EOW::orgWhere();
        if (!$where) {
            webApi(0, '', 0, []);
        }

        //  分页配置
        [$limit, $page] = [input('limit'), input('page')];

        // 查询退货订单表  关联门店表 
        $data = Db::table($this->db.'.vip_goods_returns')
                ->alias('r')
                ->leftJoin($this->db.'.vip_store s', 'r.store_code = s.code')
                ->where($where)
                ->field('r.*,s.name as store_name')
                ->order('create_time', 'desc')
                ->page($page, $limit)
                ->select();
        // 统计数量
        $count = Db::table($this->db.'.vip_goods_returns')
                ->alias('r')
                ->leftJoin($this->db.'.vip_store s', 'r.store_code = s.code')
                ->where($where)
                ->field('r.*,s.name as store_name')
                ->count();
            // 修改格式
            if ($data) {
                foreach ($data as $k=>$v) {
                    switch ($v['returns_way']) {
                        case 0:
                            $data[$k]['returns_way'] = '现金退款';
                        break;
                        case 1:
                            $data[$k]['returns_way'] = '微信退款';
                        break;
                        case 2:
                            $data[$k]['returns_way'] = '支付宝退款';
                        break;
                        case 3:
                            $data[$k]['returns_way'] = '银行卡退款';
                        break;
                    }
                    $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                    $data[$k]['money'] = number_format($v['money'], 2, '.', '');
                    $data[$k]['balance'] = number_format($v['balance'], 2, '.', '');
                    $data[$k]['recycle_balance'] = number_format($v['recycle_balance'], 2, '.', '');
                }
            }
            webApi(0, '', $count, $data);        
    }

    /**
     * 商品退货记录详情
     */
    public function detail()
    {
        // 获得订单号
        $code = input('code') ?? null;
        if ($code == null) {
            webApi(1, '参数错误');
        }
        // 依据订单号查询该订单下的详情 关联商品表 
        $data = Db::table($this->db.'.vip_goods_returns_info')
                ->alias('i')
                ->leftJoin($this->db.'.vip_goods g', 'i.goods_code = g.code')                
                ->field('i.*,g.color,g.size,g.name,g.bar_code')
                ->where('i.returns_code', $code)
                ->select();
            // 修改格式    
            if ($data) {
                foreach ($data as $k=>$v) {
                    $data[$k]['price'] = number_format($v['price'], 2, '.', '');
                    $data[$k]['return_blanace'] = number_format($v['return_blanace'], 2, '.', '');
                    $data[$k]['replace_blanace'] = number_format($v['replace_blanace'], 2, '.', '');
                    $data[$k]['money'] = number_format($v['money'], 2, '.', '');
                }
            }
            webApi(0, '', count($data), $data);   
    }
}