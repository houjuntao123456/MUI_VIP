<?php

// 表对应配置 api接口表名 => 实际数据库表名
return [
    'goods'         => 'vip_goods', // ---------------- 商品表
    'order'         => 'vip_goods_order', // ---------- 销售记录表
    'order_info'    => 'vip_goods_order_info', // ----- 销售明细表
    'refunds'       => 'vip_goods_returns', // -------- 退货记录表
    'refunds_info'  => 'vip_goods_returns_info', // --- 退货明细表
    'store_goods'   => 'vip_goods_store', // ---------- 门店商品表
    'introducer'    => 'vip_introducer', // ----------- 转介绍记录表
    'push_money'    => 'vip_push_money', // ----------- 员工提成表
    'transfer'      => 'vip_transfer_record', // ------ 会员转移记录表
    'viplist'       => 'vip_viplist', // -------------- 会员表
    // 'integral_flow' => 'vip_flow_integral', 积分流水表
    // 'stored_flow'   => 'vip_flow_stored', 储值流水表
];