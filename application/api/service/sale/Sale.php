<?php

namespace app\api\service\sale;

use think\Db;
use app\api\service\LevelUp;

class Sale
{
    public static function sale($data, $db)
    {
        if (!isset($data['order']) || !isset($data['order_info'])) {
            return ['code' => 400, 'msg' => '缺少数据'];
        }

        // 1. 获取商品信息,价格  会员信息 (基础信息 和 级别信息)
        [$order, $orderInfo] = self::getGoods($data, $db);

        $levelRes = [];
        if ( isset($order['vip_code']) && $order['vip_code'] != '' ) {
            // 1. 享受权益  1. 级别折扣 2. 活动折扣 3. 感动特权 4. 生日折扣  不叠加折扣,使用最大力度折扣
            $dis = new Discount($db, $order, $orderInfo);
            [$order, $orderInfo] = $dis->dis();
    
            // 2. 级别晋升 code = 0 无变化 1 晋升
            $levelUp = new LevelUp($db, $order['dis_money'], $order['store_code'], $order['vip_code']);
            $levelRes = $levelUp->up();
        }
        
        $res = self::execDb($order, $orderInfo, $levelRes, $db);
        return $res;
    }

    /**
     * 获取商品价格
     */
    private static function getGoods($data, $db)
    {
        $goodsCodes = implode(',', array_column($data['order_info'], 'goods_code'));
        $storeGoods = Db::table($db.'.vip_goods_store')->field('goods_code,price')->where('goods_code', 'in', $goodsCodes)->where('store_code', $data['order']['store_code'])->select();
        $unifyGoods = Db::table($db.'.vip_goods')->field('code as goods_code,price')->where('code', 'in', $goodsCodes)->select();

        $storeGoods = array_combine( array_column($storeGoods, 'goods_code'), array_column($storeGoods, 'price') );
        $unifyGoods = array_combine( array_column($unifyGoods, 'goods_code'), array_column($unifyGoods, 'price') );

        foreach ($data['order_info'] as $k=>$v) {
            if ( isset($storeGoods[$v['goods_code']]) && $storeGoods[$v['goods_code']] > 0 ) {
                $data['order_info'][$k]['price'] = $storeGoods[$v['goods_code']];
            } else {
                $data['order_info'][$k]['price'] = $unifyGoods[$v['goods_code']];
            }
        }
        return [$data['order'], $data['order_info']];
    }

    /**
     * 执行数据操作
     */
    private static function execDb($order, $orderInfo, $levelRes, $db)
    {
        if (isset($order['vip_code']) && $order['vip_code'] != '') {
            $vip = Db::table($db.'.vip_viplist')->where('code', $order['vip_code'])->find();
            $vipJoinStore = Db::table($db.'.vip_vipjoin_store')->where('vip_code', $vip['code'])->where('store_code', $order['store_code'])->find();
        }

        // 启动事务
        Db::startTrans();
        try {
            
            // 订单表操作
            Db::table($db.'.vip_goods_order')->insert($order);
            Db::table($db.'.vip_goods_order_info')->insertAll($orderInfo);

            // 会员相关表操作
            if (isset($vip)) {
                Db::table($db.'.vip_viplist')->where('code', $vip['code'])
                    ->inc('consumption_times', 1)
                    ->inc('consumption_number', $order['number'])
                    ->inc('total_consumption', $order['dis_money'])
                    ->update(['final_purchases' => $order['create_time']]);

                // 判断第一次消费
                if ($vip['first_time'] <= 0) {
                    Db::table($db.'.vip_viplist')->where('code', $vip['code'])->setField('first_time', $order['create_time']);
                }

                // 判断紧急
                if (!empty($levelRes) && $levelRes['code'] == 1) {
                    Db::table($db.'.vip_vipjoin_level')->where('vip_code', $vip['code'])->where('org_code', $levelRes['org'])->setField('level_code', $levelRes['level']);
                }

                // 会员关联门店 信息
                if (empty($vipJoinStore)) {
                    $vipJoinStoreData = [
                        'vip_code' => $vip['code'],
                        'store_code' => $order['store_code'],
                        'consumption_times' => 1,
                        'consumption_number' => $order['number'],
                        'total_consumption' => $order['dis_money'],
                        'first_time' => $order['create_time'],
                        'final_purchases' => $order['create_time']
                    ];
                    Db::table($db.'.vip_vipjoin_store')->insert($vipJoinStoreData);
                } else {
                    Db::table($db.'.vip_vipjoin_store')->where('vip_code', $vip['code'])->where('store_code', $order['store_code'])
                        ->inc('consumption_times', 1)
                        ->inc('consumption_number', $order['number'])
                        ->inc('total_consumption', $order['dis_money'])
                        ->update(['final_purchases' => $order['create_time']]);
                }
            }
            
            // 提交事务
            Db::commit();
            return ['code' => 200];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['code' => 400, 'msg' => '插入失败，请检查数据格式'];
        }
    }
}