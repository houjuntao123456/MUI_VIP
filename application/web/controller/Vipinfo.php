<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2018/08/15
 * Description 会员足迹
 */
class Vipinfo extends Common
{
    /**
     * 读卡与会员信息
     */
    public function cardSel()
    {
        //接受获取的号码
        $number = input('number') ?? null;
        if ($number == null) {
            webApi(1,"参数错误！");
        }
        //读卡号
        $dataone = Db::table($this->db.'.view_vipinfo')
                 ->where('code', $number)
                 ->find();
        //读手机号
        $datatwo = Db::table($this->db.'.view_vipinfo')
                 ->where('phone', $number)
                 ->find();
                
        unset($number);
        //判断是卡号还是手机号
        if ($dataone) {
            webApi(0, '读卡号成功!', 0, $dataone);
        } else if ($datatwo) {
            webApi(0, '读手机号成功!', 0, $datatwo);
        } else {
            webApi(0, '读卡失败!', 0, 'error');
        }
    }

    /**
     * 清除缓存中的内容
     */
    public function cacheClean()
    {   
        $token = input('access_token');
        //返单计划
        cache('vfd_'.$token, null);
        //返单互动
        // cache('vfdhd_'.$token, null);
        //图标100天跟进
        cache('vtia_'.$token, null);
        //图标365天跟进
        cache('vthia_'.$token, null);
        //图标专场跟进
        cache('vif_'.$token, null);
        //货品/类别
        cache('vsaleTx_'.$token, null);
        //会员服务
        cache('vsfw_'.$token,null);

        webApi(0, '清除缓存！');
    }

    /**
     * 图标返单计划添加也添加小返单
     */
    public function orderAdd()
    {
        $bill = 'FDJH'.str_replace('.' , '', microtime(1));

        $operate = Db::table($this->db.'.vip_staff')->where('code', input('executor'))->find();
        
        $data = [
            'code' => $bill,
            'org_code' => $operate['org_code'],
            'store_code' => $operate['store_code'] ?? '',
            'name' => input('listplan_name'),
            'vip_code' => input('card'),
            'level_code' => input('level'),
            'executor_code' => input('executor'),
            'custodian_code' => input('custodian'),
            'service_time'=> strtotime(input('service_time'))
        ];

        $token = input('access_token');
        $datacache = cache('vfd_'.$token);

        if ($datacache == null) {
            webApi(0, 'error', 0, '添加失败,请创建返单!');
        }

        $resbill = Db::table($this->db.'.vip_reorder')->where('code',$bill)->find();

        if($resbill){
            webApi( 0, 'error', 0, '单号已存在!添加失败!');
        }
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_reorder')->insert($data);
            foreach($datacache as $k=>$v){
                $c = array_merge($v , array('reorder_code' => $bill));
                Db::table($this->db.'.vip_reorder_info')->insert($c);
            }
            // 审核事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }

        unset($bill,$operate,$data,$c,$resbill);
        
        if ($res) {
            cache('vfd_'.$token, null);
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 图标小返单查表查出缓存中的内容
     */
    public function orderCacheSel()
    {   
        $token = input('access_token');
        $datacache = cache('vfd_'.$token);
        webApi(0, '', 0, $datacache);
    }

    /**
     * 图标返单计划中选择产品条码获取值
     */
    public function orderProduct()
    {
        $production_number = input('production_number') ?? null;
        if ($production_number == null) {
            webApi(1, '产品条码不能为空!');
        }

        $d = Db::table($this->db.'.vip_goods')
            ->where('code', $production_number)
            ->find();

        $level = Db::table($this->db.'.vip_viplevel')->where('code', input('level'))->find();

        if ($level !== null) {
            $d['vip_price'] = round($d['price'] * $level['discount'], 2);
        }

        unset($production_number, $level);
        if ($d) {
            webApi(0, '输入成功！', 0, $d);
        } else {
            webApi(1, '失败！');
        }
    }

    /**
     * 图标返单计划中查询-商品列表复选框
     */ 
    public function orderGoods()
    {
        $data = Db::table($this->db.'.vip_goods')->order('id', 'desc')->select();

        webApi(0, '', 0, $data);
    }

    /**
     * 图标返单计划中小返单添加到緩存中
     */
    public function orderCacheAdd()
    {
        $token = input('access_token');

        $d = [
            'goods_code' => input('production_number'),
            'goods_bar_code'=> '',
            'color' => '',
            'size' => '',
            'brand' => '',
            'vip_price' => '',
            'photo' =>'',
            'customer_demand'=> input('customer_demand'),
            'buy_some'=> input('buy_some'),
            'top'=> input('top'),
            'question'=> input('question'),
            'answer' => input('answer'),
            'delete_id' => 'fdjh'.str_replace('.' , '', microtime(1))
        ];

        $level = Db::table($this->db.'.vip_viplevel')->where('code', input('level'))->find();

        $production = Db::table($this->db.'.vip_goods')->where('code', $d['goods_code'])->find();

        $d['goods_bar_code'] = $production['bar_code'];
        $d['color'] = $production['color'];
        $d['size'] = $production['size'];
        $d['brand'] = $production['price'];
        $d['photo'] = $production['img'];

        if ($level !== null) {
            $d['vip_price'] = round($d['brand'] * $level['discount'], 2);
        }

        if (cache('?vfd_'.$token)) {
            $data = cache('vfd_'.$token);
        } else {
            $data = [];
        }

        array_push($data, $d);
        cache('vfd_'.$token, $data, 3600);

        unset($d, $production);

        if(cache('vfd_'.$token) !== ""){
            webApi(0, 'yes', 0, '添加成功!');
        }else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 图标返单计划中小返单删除
     */
    public function orderCacheDel()
    {
        $id = input('id') ?? null;

        if ($id == null) {
            webApi(1, '参数错误');
        }

        $token = input('access_token');
        $datacache = cache('vfd_'.$token);

        if (count($datacache) > 1) {
            foreach ($datacache as $k=>$v) {
                if ($id == $v['delete_id']) {
                    unset($datacache[$k]);
                }
            }
            sort($datacache);
            cache('vfd_'.$token, $datacache, 3600);
        } else {
            cache('vfd_'.$token, null);
        }
        unset($id);
        webApi(0,'删除成功!');
    }
    
    /**
     * 图标保存足迹中100天跟进
     */
    public function hundredFootAdd()
    {
        $foot = 'HTHD'.str_replace('.' , '', microtime(1));

        $staff = Db::table($this->db.'.vip_staff')->where('code', input('executor'))->find();

        $data = [
            'code' => $foot,
            'name' => input('name'),
            'executor_code' => input('executor'),
            'org_code' => $staff['org_code'],
            'store_code' => $staff['store_code'],
            'custodian_code' => input('custodian'),
            'vip_code' => input('card_number'),
            'level_code'=> input('level'),
            'time' => time()
        ];

        $record = [
            'code'=> $foot,
            'name'=> $data['name'],
            'vip_code'=> $data['vip_code'],
            'level_code'=> input('level'),
            'executor_code'=> $data['executor_code'],
            'custodian_code'=> $data['custodian_code'],
            'remark' => '100天跟进',
            'create_time' => time()
        ];

        $token = input('access_token');
        $datacache = cache('vtia_'.$token);

        if ($datacache == null) {
            webApi(0, 'error', 0, '添加失败,请选择100天跟进!');
        }
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_hundred_interaction_foot')->insert($data);
            foreach($datacache as $k=>$v){
                unset($v['id']);
                $c = array_merge($v , array('hundred_foot_code' => $foot));
                Db::table($this->db.'.vip_hundred_interaction_foot_info')->insert($c);
            }
            Db::table($this->db.'.vip_interaction_record')->insert($record);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }

        unset($foot, $data, $record, $c);

        if ($res) {
            cache('vtia_'.$token,null);
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 图标100天跟进添加的id搜索
     */
    public function hundredFootSearch()
    {
        $ida= input('id')?? null;
        if ($ida == null) {
            webApi(1,"请选择互动名称！");
        } 

        $token = input('access_token');

        $d = Db::table($this->db.'.vip_hundred_interaction_tpl_info')->where('hundred_tpl_code', $ida)->select();

        if ($d == null) {
            webApi(0,'error', 0, "添加失败,请查询互动!");
        }

        $data = cache('vtia_'.$token);

        foreach ($d as $k => $v){
            if (cache('?vtia_'.$token)) {
                if ($data){
                    foreach ($data as $key => $val) {
                        if ($v['hundred_tpl_code'] !== $val['hundred_tpl_code'] || $v['delid'] == $val['delid']) {
                            unset($data[$key]);
                        } 
                    }
                }
                array_push($data, $v);
            } else {
                $data = [];
                array_push($data, $v);
            }
            sort($data);
            cache('vtia_'.$token, $data, 3600);
        }

        unset($ida, $d);

        if (cache('vtia_'.$token) !== "") {
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 图标100天跟进查表查出缓存中的内容
     */
    public function hundredFootCache()
    {   
        $token = input('access_token');
        $datacache = cache('vtia_'.$token);
        webApi(0, '', 0, $datacache);
    }

    /**
     * 图标365天跟进保存
     */
    public function threeFootAdd()
    {
        $foot = '365HD'.str_replace('.' , '', microtime(1));

        $staff = Db::table($this->db.'.vip_staff')->where('code', input('executor'))->find();

        $data = [
            'code' => $foot,
            'name' => input('name'),
            'executor_code' => input('executor'),
            'org_code' => $staff['org_code'],
            'store_code' => $staff['store_code'],
            'custodian_code' => input('custodian'),
            'level_code'=>input('level'),
            'vip_code' => input('card_number'),
            'time' => time()
        ];

        $record = [
            'code'=> $foot,
            'name'=> $data['name'],
            'vip_code'=>$data['vip_code'],
            'level_code'=>input('level'),
            'executor_code'=>$data['executor_code'],
            'custodian_code'=> $data['custodian_code'],
            'remark' => '365天跟进',
            'create_time' => time(),
        ];
        
        $token = input('access_token');
        $datacache = cache('vthia_'.$token);

        if ($datacache == null) {
            webApi(0, 'error', 0, '添加失败,请选择365天跟进!');
        }
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_365_interaction_foot')->insert($data);
            foreach($datacache as $k=>$v){
                unset($v['id']);
                $c = array_merge($v , array('365_foot_code' => $foot));
                Db::table($this->db.'.vip_365_interaction_foot_info')->insert($c);
            }
            Db::table($this->db.'.vip_interaction_record')->insert($record);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }
        unset($foot,$data,$record,$c);
        if ($res) {
            cache('vthia_'.$token, null);
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 图标365天跟进添加的id搜索
     */
    public function threeFootSearch()
    {
        $ida= input('id')?? null;
        if ($ida == null) {
              webApi(1,"请选择互动名称！");
        }
        
        $token = input('access_token');

        $d = Db::table($this->db.'.vip_365_interaction_tpl_info')
            ->where('365_tpl_code', $ida)
            ->select();

        if ($d == null) {
            webApi(0,'error', 0, "添加失败,请查询互动!");
        }

        $data = cache('vthia_'.$token);

        foreach ($d as $k => $v){
            if (cache('?vthia_'.$token)) {
                if ($data){
                    foreach ($data as $key => $val) {
                        if ($v['365_tpl_code'] !== $val['365_tpl_code'] || $v['delid'] == $val['delid']) {
                            unset($data[$key]);
                        } 
                    }
                }
                array_push($data, $v);
            } else {
                $data = [];
                array_push($data, $v);
            }
            sort($data);
            cache('vthia_'.$token, $data, 3600);
        }
        
        unset($ida, $d);

        if(cache('vthia_'.$token) !== ""){
            webApi(0, "yes", 0, '添加成功!');
        } else {
            webApi(0, "no", 0,'添加失败!');
        }
    }

    /**
     * 图标365天跟进查表查出缓存中的内容
     */
    public function threeFootCache()
    {   
        $token = input('access_token');
        $datacache = cache('vthia_'.$token);
        if($datacache){
            foreach($datacache as $k=>$v ){
                $datacache[$k]['time_g'] = date('Y-m-d', $v['time']);
            }
        }
        webApi(0, '', 0, $datacache);
    }

    /**
     * 图标保存足迹中专场跟进
     */
    public function fieldFootAdd()
    {
        $foot = 'ZCGJ'.str_replace('.' , '', microtime(1));

        $staff = Db::table($this->db.'.vip_staff')->where('code', input('executor'))->find();
        
        $data = [
            'code' => $foot,
            'name' => input('name'),
            'executor_code' => input('executor'),
            'org_code' => $staff['org_code'],
            'store_code' => $staff['store_code'],
            'custodian_code' => input('custodian'),
            'vip_code' => input('card_number'),
            'level_code'=>input('level'),
            'time' => time()
        ];

        $record = [
            'code'=> $foot,
            'name'=> $data['name'],
            'vip_code'=>$data['vip_code'],
            'level_code'=>input('level'),
            'executor_code'=>$data['executor_code'],
            'custodian_code'=> $data['custodian_code'],
            'remark' => '专场跟进',
            'create_time' => time()
        ];

        $token = input('access_token');
        $datacache = cache('vif_'.$token);

        if ($datacache == null) {
            webApi(0, 'error', 0, '添加失败,请选择专场跟进!');
        }
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_field_interaction_foot')->insert($data);
            foreach($datacache as $k=>$v){
                unset($v['id']);
                $c = array_merge($v , array('field_foot_code' => $foot));
                Db::table($this->db.'.vip_field_interaction_foot_info')->insert($c);
            }
            Db::table($this->db.'.vip_interaction_record')->insert($record);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }

        unset($foot, $data, $record, $c);

        if ($res) {
            cache('vif_'.$token,null);
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 图标专场跟进添加的id搜索
     */
    public function fieldFootSearch()
    {
        $ida= input('id')?? null;

        if ($ida == null) {
            webApi(1,"请选择互动名称！");
        } 
        
        $token = input('access_token');

        $d = Db::table($this->db.'.vip_field_interaction_tpl_info')
            ->where('field_tpl_code', $ida)
            ->select();

        if ($d == null) {
            webApi(0,'error', 0, "添加失败,请查询互动!");
        }

        $data = cache('vif_'.$token);

        foreach ($d as $k => $v){
            if (cache('?vif_'.$token)) {
                if ($data){
                    foreach ($data as $key => $val) {
                        if ($v['field_tpl_code'] !== $val['field_tpl_code'] || $v['delid'] == $val['delid']) {
                            unset($data[$key]);
                        }
                    }
                }
                array_push($data, $v);
            } else {
                $data = [];
                array_push($data, $v);
            }
            sort($data);
            cache('vif_'.$token, $data, 3600);
        }

        unset($ida, $d);

        if(cache('vif_'.$token) !== ""){
           webApi(0, "yes", 0, '添加成功!');
        } else {
           webApi(0, "no", 0, '添加失败!');
        }
    }

    /**
     * 图标专场跟进查表查出缓存中的内容
     */
    public function fieldFootCache()
    {   
        $token = input('access_token');
        $datacache = cache('vif_'.$token);
        if ($datacache) {
            foreach ($datacache as $k => $v) {
                $datacache[$k]['time_g'] = date('Y-m-d', $v['time']);
            }
        }
        webApi(0, '', 0, $datacache);
    }

    /**
     * 图标会员服务查询下拉框
     */
    public function serviceSel()
    {
        //接受会员等级
        $rank = input('rank') ?? null;
        if ($rank == null) {
            webApi(1, '会员信息错误，请到会员列表修改！');
        }
        //统计数量
        $count = Db::table($this->db.'.vip_vipservice')->where('level_code',$rank)->count();
        //查询
        $data = Db::table($this->db.'.vip_vipservice')->where('level_code',$rank)->select();

        unset($rank);

        webApi(0, '', $count, $data);
    }

    /**
     * 图标会员服务添加缓存中
     */
    public function serviceCacheAdd()
    {
        $code = input('id') ?? null;

        if ($code == null) {
            webApi(1, '会员信息错误，不可以添加服务！');
        }
        $token = input('access_token');
    
        $d = Db::table($this->db.'.vip_vipservice')
            ->alias('v')
            ->leftJoin($this->db.'.vip_viplevel vg', 'vg.code = v.level_code')
            ->field('v.*,vg.username vgname')
            ->where('v.code',$code)
            ->find();

        if (time() > $d['end_limited_time']) {
            webApi(0, 'error', 0, '该服务项目的有效期已到，请选择其他服务项目！');
        }

        $record = Db::table($this->db.'.vip_service_record')
            ->where('service_code', $code)
            ->where('vip_code', input('card'))
            ->select();

        if ($record) {
            foreach ($record as $k => $v) {
                $d['service_second'] = $d['service_second'] - $record[$k]['service_number'];
                if ($d['service_second'] == 0) {
                    webApi(0, 'error', 0, '该服务项目的服务次数已经用完，请选择其他服务项目！');
                }
            }
        }

        $delete_id = ['delete_id' =>'hyfw'.str_replace('.' , '', microtime(1)), 'service_number' => 1];
        $d = array_merge($d ,$delete_id);
        $d['single_quantity_number'] = intval($d['service_number']) *intval($d['single_quantity']);

        $data = cache('vsfw_'.$token);

        if (cache('?vsfw_'.$token)) {
            $re = false;
            foreach ($data as $k=>$v) {
                if ($d['code'] == $v['code']) {
                    $data[$k]['service_number'] += 1;
                    if ($data[$k]['service_number'] > $data[$k]['service_second'] ) {
                        webApi(0, 'error', 0, '服务次数不能超过总服务次数！');
                    }
                    $data[$k]['single_quantity_number'] = intval($data[$k]['service_number']) *intval($v['single_quantity']);
                    $re = true;
                }
            }
            if ($re == false) {
                array_push($data, $d);
            }
        } else {
            $data = [];
            array_push($data, $d);
        }

        cache('vsfw_'.$token, $data, 3600);

        unset($code, $d, $record, $delete_id);

        if(cache('vsfw_'.$token) !== ""){
            webApi(0, 'yes', 0, '添加成功!');
        }else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 图标会员服务中修改服务次数
     */
    public function serviceCacheNum()
    {
        $token = input('access_token');
        $data = cache('vsfw_'.$token);
        foreach ($data as $k=>$v) {
            if (input('id') == $v['id']) {
                if(intval(input('val')) <= intval($v['service_second']) ){
                    $data[$k]['service_number'] = intval(input('val'));
                    $data[$k]['single_quantity_number'] = intval($data[$k]['service_number']) *intval($v['single_quantity']);
                } else {
                    webApi(1, '服务次数不能超过总服务次数！');
                }
            }
        }
        cache('vsfw_'.$token, $data, 3600);
        webApi(0,'', 0, $data);
    }

    /**
     * 图标会员服务查表查出缓存中的内容
     */
    public function serviceCacheSel()
    {   
        $token = input('access_token');
        $datacache = cache('vsfw_'.$token);
        if($datacache){
            foreach ($datacache as $k=>$v) {
                $datacache[$k]['days'] = round(($datacache[$k]['end_limited_time']-$datacache[$k]['start_limited_time'])/86400);
                $datacache[$k]['start_limited_time'] = date('Y-m-d H:i:s', $v['start_limited_time']);
                $datacache[$k]['end_limited_time'] = date('Y-m-d H:i:s', $v['end_limited_time']);
                $datacache[$k]['limited_time'] = $datacache[$k]['start_limited_time']. '——' . $datacache[$k]['end_limited_time'];
            }
        }
        webApi(0, '', 0, $datacache);
    }

     /**
     * 图标会员服务删除
     */
    public function serviceCacheDel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1,'参数错误');
        }
        $token = input('access_token');
        $datacache = cache('vsfw_'.$token);

        if (count($datacache) > 1) {
            foreach ($datacache as $k=>$v) {
                if ($id == $v['delete_id']) {
                    unset($datacache[$k]);
                }
            }
            sort($datacache);
            cache('vsfw_'.$token, $datacache, 3600);
        } else {
            cache('vsfw_'.$token, null);
        }
        unset($id);
        webApi(0,'删除成功!');
    }

    /**
     * 图标会员服务执行
     */
    public function serviceAdd()
    {
        $data = [
            'code' => 'HYFW'.str_replace('.' , '', microtime(1)),
            'vip_code' => input('card'),
            'level_code' => input('mg'),
            'service_code' => "",
            'service_name' => "",
            'service_number' => 0,
            'company' => "",
            'execution_time' => time()
        ];

        $token = input('access_token');
        $datacache = cache('vsfw_'.$token);

        if ($datacache) {
            foreach($datacache as $k=>$v){
                $data['service_code'] = $datacache[$k]['code'];
                $data['service_name'] = $datacache[$k]['servicename'];
                $data['service_number'] = $datacache[$k]['service_number'];
                $data['company'] = $datacache[$k]['company'];
                $res = Db::table($this->db.'.vip_service_record')->insert($data);
            }
        } else {
            webApi(0, 'error', 0, '添加失败,请选择服务项目!');
        }

        unset($data);

        if ($res) {
            cache('vsfw_'.$token, null);
            webApi(0, 'yes', 0, '服务成功！');
        } else {
            webApi(0, 'no', 0, '服务失败!');
        }
    }

    /**
     * 销售流水
     */
    public function salesFlow()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('card')];
        // 判断获取数据
        if ( $card == null) {
            webApi(1, '请重新读卡！');
        }
        // 门店限制
        $store = Db::table($this->db.'.vip_store')->where('code', session('info.store'))->find();

        if ($store == "") {
            $ws = true;
        } else {
            $ws[] = ['store_code', '=', session('info.store')];
        }

        $data = Db::table($this->db.'.vip_goods_order')
                ->alias('o')
                ->leftJoin($this->db.'.vip_store s', 'o.store_code = s.code')
                ->field('o.*,s.name sname')
                ->where('o.vip_code', $card)
                ->where($ws)
                ->order('o.create_time', 'desc')
                ->page($page, $limit)
                ->select();
        $count = Db::table($this->db.'.vip_goods_order')
                ->alias('o')
                ->leftJoin($this->db.'.vip_store s', 'o.store_code = s.code')
                ->field('o.*,s.name sname')
                ->where('o.vip_code', $card)
                ->where($ws)
                ->count();

        $money = ['money', 'dis_money', 'real_pay', 'real_income', 'integral_balance', 'storage_balance', 'cash_pay', 'wechat_pay', 'ali_pay', 'union_pay', 'not_small_change', 'give_change', 'pay_return_money'];
        $mc = count($money);
        // 修改格式
        foreach ($data as $k=>$v) {
            $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['create_time']);
            for ($i = 0; $i < $mc; $i++) {
                $data[$k][$money[$i]] = number_format($v[$money[$i]], 2, '.', '');
            }
            if ($v['status'] == 0) {
                $data[$k]['status'] = '正常';
            } else {
                $data[$k]['status'] = '已退货';
            }
        }
        unset($page, $limit, $card, $ws, $money, $mc, $store);
        webApi(0, '', $count, $data);
    }

    /**
     * 订单明细
     */
    public function salesFlowInfo()
    {
        $bill = input('bill') ?? null;
        if ($bill == null) {
            webApi(1, "参数错误！");
        }

        $count = Db::table($this->db.'.view_vipinfo_goods')->where('order_code', $bill)->count();

        $data = Db::table($this->db.'.view_vipinfo_goods')->where('order_code', $bill)->select();
        
        webApi(0, '', $count, $data);
    }

    /**
     * 积分流水
     */
    public function integralSel()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('card_number')];
        // 判断获取数据
        if ( $card == null) {
            webApi(1, '请重新读卡！');
        }
        // 门店限制
        $store = Db::table($this->db.'.vip_store')->where('code', session('info.store'))->find();

        if ($store == "") {
            $ws = true;
        } else {
            $ws[] = ['store_code', '=', session('info.store')];
        }
        
        $count = Db::table($this->db.'.vip_flow_integral')
                ->order('create_time','desc') //按照登记时间降序排列
                ->where('vip_code',$card)
                ->where($ws)
                ->count();
        $data = Db::table($this->db.'.vip_flow_integral')
                ->alias('v')
                // ->leftJoin($this->db.'.vip_staff vq', 'vq.staff_number = v.operate')
                ->leftJoin($this->db.'.vip_store vp', 'vp.code = v.store_code')
                // ->field('v.*,vq.staff_name vqname,vp.name vpname')
                ->field('v.*,vp.name vpname')
                ->order('v.create_time','desc') //按照登记时间降序排列
                ->where('v.vip_code',$card)
                ->where($ws)
                ->page($page, $limit)
                ->select();

        unset($page, $limit, $card, $ws, $store);
        // 修改格式
        foreach ($data as $k => $v) {
            //时间格式的转换
            $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        webApi(0, '', $count, $data);
    }

    /**
     * 储值流水
     */
    public function storedSel()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('card_number')];
        // 判断获取数据
        if ( $card == null) {
            webApi(1, '请重新读卡！');
        }
        // 门店限制
        $store = Db::table($this->db.'.vip_store')->where('code', session('info.store'))->find();

        if ($store == "") {
            $ws = true;
        } else {
            $ws[] = ['store_code', '=', session('info.store')];
        }
        //数量
        $count = Db::table($this->db.'.vip_flow_stored')
                ->where('vip_code', $card)
                ->where('road','<>','割肉充值')
                ->where($ws)
                ->count();

        $data = Db::table($this->db.'.vip_flow_stored')
                ->alias('v')
                // ->leftJoin($this->db.'.vip_staff vq', 'vq.staff_number = v.operate')
                ->leftJoin($this->db.'.vip_store vp', 'vp.code = v.store_code')
                // ->field('v.*,vq.staff_name vqname,vp.name vpname')
                ->field('v.*, vp.name vpname')
                ->where('v.vip_code',$card)
                ->where('v.road','<>','割肉充值')
                ->where($ws)
                ->order('v.create_time','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();

        unset($page, $limit, $card, $ws, $store);
        // 修改格式
        foreach ($data as $k => $v) {
            //时间格式的转换
            $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['create_time']);
            $data[$k]['money_g'] = number_format($v['money'], 2);
            // $data[$k]['stored_value_g'] = number_format($v['stored_value'], 2);
            // $data[$k]['actual_receipt_g'] = number_format($v['actual_receipt'], 2);
            // $data[$k]['give_amount_g'] = number_format($v['give_amount'], 2);
        }
        webApi(0, '', $count, $data);
    }

    /**
     * 割肉流水
     */
    public function storedCutMeat()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('card_number')];
        // 判断获取数据
        if ( $card == null) {
            webApi(1, '请重新读卡！');
        }
        //门店限制
        $store = Db::table($this->db.'.vip_store')->where('code', session('info.store'))->find();

        if ($store == "") {
            $ws = true;
        } else {
            $ws[] = ['store_code', '=', session('info.store')];
        }

        $count = Db::table($this->db.'.vip_flow_stored')
                ->where('vip_code',$card)
                ->where($ws)
                ->where('road','=','割肉充值')
                ->count();

        $data = Db::table($this->db.'.vip_flow_stored')
                ->alias('v')
                // ->leftJoin($this->db.'.vip_staff vq', 'vq.staff_number = v.operate')
                ->leftJoin($this->db.'.vip_store vp', 'vp.code = v.store_code')
                // ->field('v.*,vq.staff_name vqname,vp.name vpname')
                ->field('v.*, vp.name vpname')
                ->where($ws)
                ->where('v.vip_code',$card)
                ->where('v.road','=','割肉充值')
                ->order('v.create_time','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();

        unset($page, $limit, $card, $ws, $store);
        // 修改格式
        foreach ($data as $k => $v) {
            //时间格式的转换
            $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['create_time']);
            $data[$k]['money_g'] = number_format($v['money'], 2);
        }
        webApi(0, '', $count, $data);
    }

    /**
     * RFM
     */
    public function rfmSel()
    {
        $card = input('card_number') ?? null;
        if ($card == null) {
            webApi(1, '参数错误');
        }
        $data = Db::table($this->db.'.vip_viplist')->where('code' ,$card)->find();

        $data['r_days'] = round((time()-$data['final_purchases'])/86400);
        //天数
        $rfmTime = Db::table($this->db.'.vip_rfm_days')->where('store_code', $data['store_code'])->find();
        //转介绍人数需要条件---I转介绍数
        $data['i_introduction'] = Db::table($this->db.'.vip_Introducer')
                                ->where('lnttime', '>=', time() - (86400 * $rfmTime['i_consumption']))
                                ->where('lnt_code', $card)
                                ->count(); 
        //消费次数需要条件---F消费次数
        $data['f_frequency'] = Db::table($this->db.'.vip_goods_order')
                            ->where('create_time', '>=', time() - (86400 * $rfmTime['f_consumption']))
                            ->where('status', 0)
                            ->where('vip_code', $card)
                            ->count();
        //M金额
        if (!empty(Db::table($this->db.'.vip_goods_order')->field('real_pay')->where('vip_code', $card)->where('status', 0)->find())) { 
            $data['m_money'] = number_format(Db::table($this->db.'.vip_goods_order')->field('sum(real_pay) as pay')->where('create_time', '>=', time() - (86400 * $rfmTime['m_consumption']))->where('vip_code', $card)->where('status', 0)->find()['pay'], 2, '.', ''); // M金额
        } else {
            $data['m_money'] = '0.00';
        }
        //N件数
        if (!empty(Db::table($this->db.'.vip_goods_order')->field('number')->where('vip_code', $card)->where('status', 0)->find())) { 
            $data['n_number'] = Db::table($this->db.'.vip_goods_order')->field('sum(number) as num')->where('create_time', '>=', time() - (86400 * $rfmTime['n_consumption']))->where('vip_code', $card)->where('status', 0)->find()['num']; 
        } else {
            $data['n_number'] = 0;
        }
        
        //R类型和R得分
        if (!empty(Db::table($this->db.'.vip_rfm_r')->where('r_intervalone', '<=', $data['r_days'])->where('r_intervaltwo', '>', $data['r_days'])->where('store_code', $data['store_code'])->find())) {
            $data['r_type'] = Db::table($this->db.'.vip_rfm_r')->where('r_intervalone', '<=', $data['r_days'])->where('r_intervaltwo', '>', $data['r_days'])->where('store_code', $data['store_code'])->find()['r_type'];
            $data['r_score'] = Db::table($this->db.'.vip_rfm_r')->where('r_intervalone', '<=', $data['r_days'])->where('r_intervaltwo', '>', $data['r_days'])->where('store_code', $data['store_code'])->find()['r_score'];
        } else {
            $data['r_type'] = '';
            $data['r_score'] = 0;
        }
        
        //I类型 I得分
        if (!empty(Db::table($this->db.'.vip_rfm_i')->where('i_intervalone', '<=', $data['i_introduction'])->where('i_intervaltwo', '>', $data['i_introduction'])->where('store_code', $data['store_code'])->find())) { 
            $data['i_type'] = Db::table($this->db.'.vip_rfm_i')->where('i_intervalone', '<=', $data['i_introduction'])->where('i_intervaltwo', '>', $data['i_introduction'])->where('store_code', $data['store_code'])->find()['i_type'];
            $data['i_score'] = Db::table($this->db.'.vip_rfm_i')->where('i_intervalone', '<=', $data['i_introduction'])->where('i_intervaltwo', '>', $data['i_introduction'])->where('store_code', $data['store_code'])->find()['i_score'];
        } else {
            $data['i_type'] = '';
            $data['i_score'] = 0;
        }

        //F类型 F得分
        if (!empty(Db::table($this->db.'.vip_rfm_f')->where('f_intervalone', '<=', $data['f_frequency'])->where('f_intervaltwo', '>', $data['f_frequency'])->where('store_code', $data['store_code'])->find())) { 
            $data['f_type'] = Db::table($this->db.'.vip_rfm_f')->where('f_intervalone', '<=', $data['f_frequency'])->where('f_intervaltwo', '>', $data['f_frequency'])->where('store_code', $data['store_code'])->find()['f_type'];
            $data['f_score'] = Db::table($this->db.'.vip_rfm_f')->where('f_intervalone', '<=', $data['f_frequency'])->where('f_intervaltwo', '>', $data['f_frequency'])->where('store_code', $data['store_code'])->find()['f_score'];
        } else {
            $data['f_type'] = '';
            $data['f_score'] = 0;
        }

        //M类型 M得分
        if (!empty(Db::table($this->db.'.vip_rfm_m')->where('m_intervalone', '<=', $data['m_money'])->where('m_intervaltwo', '>', $data['m_money'])->where('store_code', $data['store_code'])->find())) { 
            $data['m_type'] = Db::table($this->db.'.vip_rfm_m')->where('m_intervalone', '<=', $data['m_money'])->where('m_intervaltwo', '>', $data['m_money'])->where('store_code', $data['store_code'])->find()['m_type'];
            $data['m_score'] = Db::table($this->db.'.vip_rfm_m')->where('m_intervalone', '<=', $data['m_money'])->where('m_intervaltwo', '>', $data['m_money'])->where('store_code', $data['store_code'])->find()['m_score'];
        } else {
            $data['m_type'] = '';
            $data['m_score'] = 0;
        }

        //N类型 N得分
        if (!empty(Db::table($this->db.'.vip_rfm_n')->where('n_intervalone', '<=', $data['n_number'])->where('n_intervaltwo', '>', $data['n_number'])->where('store_code', $data['store_code'])->find())) { 
            $data['n_type'] = Db::table($this->db.'.vip_rfm_n')->where('n_intervalone', '<=', $data['n_number'])->where('n_intervaltwo', '>', $data['n_number'])->where('store_code', $data['store_code'])->find()['n_type'];
            $data['n_score'] = Db::table($this->db.'.vip_rfm_n')->where('n_intervalone', '<=', $data['n_number'])->where('n_intervaltwo', '>', $data['n_number'])->where('store_code', $data['store_code'])->find()['n_score'];
        } else {
            $data['n_type'] = '';
            $data['n_score'] = 0;
        }
        

        if ($data['f_frequency'] != 0) { // P客单价
            $data['p_univalent'] = number_format($data['m_money'] / $data['f_frequency'], 2, '.', '');  
        } else {
            $data['p_univalent'] = '0.00';
        }

        if ($data['n_number'] != 0) {  // A件单价
            $data['a_univalent'] = number_format($data['m_money'] / $data['n_number'], 2, '.', ''); 
        } else {
            $data['a_univalent'] = '0.00';
        }

        if ($data['f_frequency'] != 0) {  // J连带率
            $data['j_related_rate'] = number_format($data['n_number'] / $data['f_frequency'], 2); 
        } else {
            $data['j_related_rate'] = '0';
        }

        if (!empty(Db::table($this->db.'.vip_goods_order')->field('real_pay')->where('create_time', '>=', time() - ($rfmTime['c_consumption'] * 86400))->where('vip_code', $card)->where('status', 0)->find())) { //C年消费
            $data['c_consumption'] = number_format(Db::table($this->db.'.vip_goods_order')->field('sum(real_pay) as pay')->where('create_time', '>=', time() - ($rfmTime['c_consumption'] * 86400))->where('status', 0)->where('vip_code', $card)->find()['pay'], 2, '.', ''); // M金额
        } else {
            $data['c_consumption'] = '0.00';
        }

        // C类型 C得分
        if (!empty(Db::table($this->db.'.vip_rfm_c')->where('c_intervalone', '<=', $data['c_consumption'])->where('c_intervaltwo', '>', $data['c_consumption'])->where('store_code', $data['store_code'])->find())) { 
            $data['c_type'] = Db::table($this->db.'.vip_rfm_c')->where('c_intervalone', '<=', $data['c_consumption'])->where('c_intervaltwo', '>', $data['c_consumption'])->where('store_code', $data['store_code'])->find()['c_type'];
            $data['c_score'] = Db::table($this->db.'.vip_rfm_c')->where('c_intervalone', '<=', $data['c_consumption'])->where('c_intervaltwo', '>', $data['c_consumption'])->where('store_code', $data['store_code'])->find()['c_score'];
        } else {
            $data['c_type'] = '';
            $data['c_score'] = 0;
        }
        //P类型 P得分
        if (!empty(Db::table($this->db.'.vip_rfm_p')->where('p_intervalone', '<=', $data['p_univalent'])->where('p_intervaltwo', '>', $data['p_univalent'])->where('store_code', $data['store_code'])->find())) { 
            $data['p_type'] = Db::table($this->db.'.vip_rfm_p')->where('p_intervalone', '<=', $data['p_univalent'])->where('p_intervaltwo', '>', $data['p_univalent'])->where('store_code', $data['store_code'])->find()['p_type'];
            $data['p_score'] = Db::table($this->db.'.vip_rfm_p')->where('p_intervalone', '<=', $data['p_univalent'])->where('p_intervaltwo', '>', $data['p_univalent'])->where('store_code', $data['store_code'])->find()['p_score'];
        } else {
            $data['p_type'] = '';
            $data['p_score'] = 0;
        }

        // A类型 A得分
        if (!empty(Db::table($this->db.'.vip_rfm_a')->where('a_intervalone', '<=', $data['a_univalent'])->where('a_intervaltwo', '>', $data['a_univalent'])->where('store_code', $data['store_code'])->find())) { 
            $data['a_type'] = Db::table($this->db.'.vip_rfm_a')->where('a_intervalone', '<=', $data['a_univalent'])->where('a_intervaltwo', '>', $data['a_univalent'])->where('store_code', $data['store_code'])->find()['a_type'];
            $data['a_score'] = Db::table($this->db.'.vip_rfm_a')->where('a_intervalone', '<=', $data['a_univalent'])->where('a_intervaltwo', '>', $data['a_univalent'])->where('store_code', $data['store_code'])->find()['a_score'];
        } else {
            $data['a_type'] = '';
            $data['a_score'] = 0;
        }

        // J类型 J得分
        if (!empty(Db::table($this->db.'.vip_rfm_j')->where('j_intervalone', '<=', $data['j_related_rate'])->where('j_intervaltwo', '>', $data['j_related_rate'])->where('store_code', $data['store_code'])->find())) { 
            $data['j_type'] = Db::table($this->db.'.vip_rfm_j')->where('j_intervalone', '<=', $data['j_related_rate'])->where('j_intervaltwo', '>', $data['j_related_rate'])->where('store_code', $data['store_code'])->find()['j_type'];
            $data['j_score'] = Db::table($this->db.'.vip_rfm_j')->where('j_intervalone', '<=', $data['j_related_rate'])->where('j_intervaltwo', '>', $data['j_related_rate'])->where('store_code', $data['store_code'])->find()['j_score'];
        } else {
            $data['j_type'] = '';
            $data['j_score'] = 0;
        }
        $data = [
            0 => $data
        ];

        unset($card, $rfmTime);

        webApi(0, '', 0, $data);
    }

    /**
     * 总返单计划
     */
    public function orderSel()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('card_number')];
        // 判断获取数据
        if ( $card == null) {
            webApi(1, '请重新读卡！');
        }
        //统计数量
        $count = Db::table($this->db.'.view_vip_plan')->where('vip_code', $card)->count();
        //查询的数据
        $data = Db::table($this->db.'.view_vip_plan')
                ->where('vip_code', $card)
                ->order('service_time','desc') //按照时间降序排列
                ->page($page, $limit)
                ->select();

        unset($page, $limit, $card);
        // 修改格式
        webApi(0, '', $count, $data);
    }

    /**
     * 总返单计划按单号查询
     */
    public function orderDillSel()
    {
        $bill = input('bill') ?? null;
        if ($bill == null) {
            webApi(1, '参数错误');
        }
        //统计数量
        $count = Db::table($this->db.'.vip_reorder_info')->where('reorder_code',$bill)->count();

        $data = Db::table($this->db.'.vip_reorder_info')->where('reorder_code',$bill)->select();

        unset($bill);
        webApi(0, '', $count, $data);
    }
    
    /**
     * 总100天跟进
     */
    public function hundredFootSel()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('card_number')];
        // 判断获取数据
        if ( $card == null) {
            webApi(1, '请重新读卡！');
        }
        //统计数量
        $count = Db::table($this->db.'.view_vip_hundred')
                ->where('vip_code',$card)
                ->count();
        
        //查询的数据
        $data = Db::table($this->db.'.view_vip_hundred')
                ->where('vip_code',$card)
                ->order('time','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();

        unset($page, $limit, $card);

        webApi(0, '', $count, $data);
    }

    /**
     * 总100天跟进按单号查询
     */
    public function hundredFootDill()
    {
        $bill = input('footprint_bill') ?? null;
        if ($bill == null) {
            webApi(1, '参数错误');
        }
        //统计数量
        $count = Db::table($this->db.'.vip_hundred_interaction_foot_info')
                ->where('hundred_foot_code', $bill)
                ->count();

        $data = Db::table($this->db.'.vip_hundred_interaction_foot_info')
                ->where('hundred_foot_code', $bill)
                ->select();
        //更改格式
        foreach ($data as $k => $v) {
            //状态
            $data[$k]['status'] !== 1 ? $data[$k]['status_g'] = '未审核' : $data[$k]['status_g'] = '已审核';
        }    
        unset($bill);
        webApi(0, '', $count, $data);
    }

    /**
     * 总365天跟进
     */
    public function threeFootSel()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('card_number')];
        // 判断获取数据
        if ( $card == null) {
            webApi(1, '请重新读卡！');
        }
        //统计数量
        $count = Db::table($this->db.'.view_vip_hund')
                ->where('vip_code', $card)
                ->count();
        
        //查询的数据
        $data = Db::table($this->db.'.view_vip_hund')
                ->where('vip_code', $card)
                ->order('time','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();

        unset($page, $limit, $card);

        webApi(0, '', $count, $data);
    }

    /**
     * 总365天跟进按单号查询
     */
    public function threeFootDill()
    {
        $bill = input('footprint_bill') ?? null;
        if ($bill == null) {
            webApi(1,'参数错误！');
        }
        //统计数量
        $count = Db::table($this->db.'.vip_365_interaction_foot_info')
                ->where('365_foot_code',$bill)
                ->count();
        $data = Db::table($this->db.'.vip_365_interaction_foot_info')
                ->where('365_foot_code',$bill)
                ->select();
        unset($bill);
        //更改格式
        foreach ($data as $k =>$v) {
            //状态
            $data[$k]['status'] !== 1 ? $data[$k]['status_g'] = '未审核' : $data[$k]['status_g'] = '已审核';

            $data[$k]['time_g'] = date('Y-m-d', $v['time']);
        }
        webApi(0, '', $count, $data);
    }

    /**
     * 总专场跟进
     */
    public function fieldFootSel()
    {
       // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('card_number')];
        // 判断获取数据
        if ( $card == null) {
            webApi(1, '请重新读卡！');
        }
        //统计数量
        $count = Db::table($this->db.'.view_vip_field')
                ->where('vip_code',$card)
                ->count();
        
        //查询的数据
        $data = Db::table($this->db.'.view_vip_field')
                ->where('vip_code',$card)
                ->order('time','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();

        unset($page, $limit, $card);

        webApi(0, '', $count, $data);
    }

    /**
     * 按总专场跟进单号查询
     */
    public function fieldFootDill()
    {
        $bill = input('footprint_bill') ?? null;
        if ($bill == null) {
            webApi(1, '参数错误!');
        }
        //统计数量
        $count = Db::table($this->db.'.vip_field_interaction_foot_info')
                ->where('field_foot_code', $bill)
                ->count();

        $data = Db::table($this->db.'.vip_field_interaction_foot_info')
                ->where('field_foot_code', $bill)
                ->select();
        //更改格式
        foreach ($data as $k => $v) {
            //日期
            $data[$k]['time_g'] = date('Y-m-d', $v['time']);
            //状态
            $data[$k]['status'] !== 1 ? $data[$k]['status_g'] = '未审核' : $data[$k]['status_g'] = '已审核';
        }
                
        unset($bill);
        webApi(0, '', $count, $data);
    }

    /**
     * 商品明细
     */
    public function goodInfo()
    {
        $card = input('card') ?? null;
        if ($card == null) {
             webApi(1,'参数错误');
        }

        $data = Db::table($this->db.'.view_vipinfo_goods')
                ->where('vip_code', $card)
                ->order('create_time', 'desc')
                ->page(input('page'), input('limit'))
                ->select();

        $count = Db::table($this->db.'.view_vipinfo_goods')
                ->where('vip_code', $card)
                ->count();

        unset($card);
        webApi(0, '', $count, $data);
    }

    /**
     * 会员扩展
     */
    public function memberExtend()
    {
        $card = input('card') ?? null;
        if ($card == null) {
            webApi(1, "参数错误！");
        }
        $res = Db::table($this->db.'.vip_viplist')->where('code', $card)->field('id,extension')->find();
        $extion = [];
        if ($res['extension'] != '') {
            $ext = json_decode($res['extension'], true);
            if (!empty($ext) && is_array($ext)) {
                foreach ($ext as $k=>$v) {
                    if ( is_array($v) ) {
                        $ext[$k] = implode(',', $v);
                    }
                }
                $i = 0;
                foreach ($ext as $k=>$v) {
                    $extion[$i]['key'] = $k;
                    $extion[$i]['val'] = $v;
                    $i++;
                }
            }
        }
        unset($card,$res,$ext);
        webApi(0, '', 0, $extion);
    }

    /**
     * 家属查表
     */
    public function familySel()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('card_number')];
        // 判断获取数据
        if ( $card == null) {
            webApi(1, '请重新读卡！');
        }
         //统计数量
        $count = Db::table($this->db.'.vip_family')
                ->where('vip_code', $card)
                ->order('execution_time','desc')
                ->count();

        $data = Db::table($this->db.'.vip_family')
                ->where('vip_code', $card)
                ->order('execution_time','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();

        unset($page, $limit, $card);

        foreach ($data as $k=>$v) {
            $data[$k]['birthday_g'] = date('Y-m-d', $v['date']);
        }
        webApi(0, '', $count, $data);
    }
    /**
     * 家属
     */
    public function familyAdd()
    {
        if (input('relationship') == "0") {
            $relation = "结婚纪念日";
        } else if (input('relationship') == "1") {
            $relation = "周年纪念日";
        } else {
            $relation = input('relationship');
        }

        $data = [
            'vip_code' => input('card_number'),
            'vip_name' => input('name'),
            'relation' => $relation,
            'date' => strtotime(input('birthday')),
            'phone' => input('cellphone'),
            'synopsis' => input('synopsis'),
            'execution_time' => time(),
            'code' => 'js'.str_replace('.' , '', microtime(1))
        ];

        $res = Db::table($this->db.'.vip_family')->insert($data);

        unset($data);

        if ($res) {
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }
    /**
     * 家属删除
     */
    public function familyDel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        // 执行删除
        $res = Db::table($this->db.'.vip_family')->delete($id);

        unset($id);

        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 会员服务记录
     */
    public function  serviceSelAll()
    {
        $card = input('card_number') ?? null;
        if ($card == null) {
            webApi(1, '请重新读卡！');
        }

        $data = Db::table($this->db.'.view_vip_service')
                ->where('vip_code', $card)
                ->order('execution_time','desc') //按照登记时间降序排列
                ->select();
        
        foreach ($data as $k =>$v) {
            $data[$k]['days'] = round(($data[$k]['end_limited_time']-$data[$k]['start_limited_time'])/86400);
            $data[$k]['limited_time'] = $data[$k]['start_time_g']. '—' . $data[$k]['end_time_g'];
        }

        unset($card);
        webApi(0, '', 0, $data);
    }

    /**
     * 会员互动记录查询
     */
    public function interactiveRecord()
    {
        $card = input('card_number') ?? null;
        if ($card == null) {
            webApi(1, '请重新读卡！');
        }
         //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find();
        //所属机构限制
        if ($operate['id'] == 1) {
            $w = true;
        } else {//判断是否是管理
            if ($operate['role'] == 0) {//管理查询管理机构
                $w[] = ['vzcode', 'in', $operate['admin_org_code']];
            } else { //员工查询所属机构
                $w[] = ['vzcode', '=', $operate['org_code']];
            }
        }
        //查询的数据
        $data = Db::table($this->db.'.view_vip_record')
                ->where('vip_code', $card)
                ->where($w)
                ->where('status', 1)
                ->order('create_time', 'desc')
                ->select();
        
        unset($card, $w);
        webApi(0, '', 0, $data);
    }

    /**
     * 总返单互动按单号查询
     */
    public function interactiveDill()
    {
        $bill = input('bill') ?? null;
        if ($bill == null) {
            webApi(1, '参数错误');
        }
        // 100天跟进小单
        $data = Db::table($this->db.'.vip_hundred_interaction_foot_info')->where('hundred_foot_code', $bill)->where('status', 1)->select();
        // 365跟进小单
        $dataone = Db::table($this->db.'.vip_365_interaction_foot_info')->where('365_foot_code', $bill)->where('status', 1)->select();
        // 专场跟进小单
        $datatwo = Db::table($this->db.'.vip_field_interaction_foot_info')->where('field_foot_code', $bill)->where('status', 1)->select();

        unset($bill);
        
        if ($data) {
            // 修改格式
            foreach ($data as $k => $v) { 
                $data[$k]['time_g'] = $v['time'];
            }
            webApi(0, '', 0, $data);
        } else if ($dataone) {
            foreach ($dataone as $k =>$v) {
                $dataone[$k]['time_g'] = date('Y-m-d', $v['time']);
            }
            webApi(0, '', 0, $dataone);
        } else if ($datatwo) {
            foreach ($datatwo as $k =>$v) {
                $datatwo[$k]['time_g'] = date('Y-m-d', $v['time']);
            }
            webApi(0, '', 0, $datatwo);
        }else {
            webApi(0, '', 0, []);
        }
    }

}