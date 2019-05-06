<?php

namespace app\web\controller;

use think\Db;
use app\web\service\ErpWhere as EOW;

/**
 * Author hrl
 * Date 2018/12/28
 * Description 商品管理--销售记录
 */
class SaleList extends Common
{
    /**
     * 商品销售记录列表
     */
    public function index()
    {
        //  分页配置
        [$limit, $page] = [input('limit'), input('page')];
        
        //默认查询自己组织/门店下的退货记录
        $where = EOW::orgWhere();
        // dump($where);die;
        if (!$where) {
            webApi(0, '', 0, []);
        }
        
        // 查询订单表  关联门店表 
        $data = Db::table($this->db.'.vip_goods_order')
                ->alias('o')
                ->leftJoin($this->db.'.vip_store s', 'o.store_code = s.code')
                ->field('o.*,s.name as store_name')
                ->where($where)
                ->order('create_time', 'desc')
                ->page($page, $limit)
                ->select();
        // 统计数量
        $count = Db::table($this->db.'.vip_goods_order')
                ->alias('o')
                ->leftJoin($this->db.'.vip_store s', 'o.store_code = s.code')
                ->field('o.*,s.name as store_name')
                ->where($where)
                ->count();
        
        $m = ['money', 'dis_money', 
              'integral_balance', 'storage_balance',
              'real_pay', 'real_income', 'not_small_change', 
              'give_change', 'cash_pay', 'wechat_pay',
              'ali_pay', 'union_pay', 'pay_return_money'  
            ];
        $mc = count($m);        
        foreach ($data as $k=>$v) {
            for ($i = 0; $i < $mc; $i++) {
                $data[$k][$m[$i]] = number_format($v[$m[$i]], 2, '.', '');
            }

            $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }    
        webApi(0, '', $count, $data);

    }

    /**
     * 订单明细
     */
    public function detail()
    {
        // 获得订单号
        $code = input('code') ?? null;
        if ($code == null) {
            webApi(1, '参数错误');
        }
        // 依据订单号查询该订单下的详情 关联商品表 
        $data = Db::table($this->db.'.vip_goods_order_info')
                ->alias('i')
                ->leftJoin($this->db.'.vip_goods g', 'i.goods_code = g.code')                
                ->field('i.*,g.color,g.size,g.name,g.bar_code')
                ->where('i.order_code', $code)
                ->select();

            $m = ['price', 'dis_price', 
                  'dis_money', 'money',
                  'storage_balance', 'integral_balance', 'pay_return_money', 
                ];
        $mc = count($m);          
        foreach ($data as $k=>$v) {
            for ($i = 0; $i < $mc; $i++) {
                $data[$k][$m[$i]] = number_format($v[$m[$i]], 2, '.', '');
            }
            if ($v['status'] == 0) {
                $data[$k]['status'] = '正常';
            } else {
                $data[$k]['status'] = '退货';
            }

            $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);  // 下单时间
            $data[$k]['return_time'] = date('Y-m-d H:i:s', $v['return_time']);  // 退货时间
        }
        webApi(0, '', count($data), $data);  

    }
}