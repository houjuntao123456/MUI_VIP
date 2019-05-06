<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;


/**
 * Author lhp
 * Date 2018/12/29
 * Description 会员权益 --- 活动折扣
 */
class Activity extends Common
{
    /**
     * 活动折扣列表
     */
    public function activitys()
    {
         //分页信息
         [$page, $limit, $lookup] = [input('page'), input('limit'), input('activitylookup')];
        
         //统计数量
         $count = Db::table($this->db.'.vip_vipactivity')->count();

        // if (empty(session('info.store'))) {
        //     $storeWhere = true;
        // } else {
        //     $storeWhere[] = ['vp.ERP', '=', session('info.store')['ERP']];
        // }

        //模糊查询
        if ($lookup) {
            $where[] = ['vp.name|v.name|vg.username', 'like', '%'.$lookup.'%'];
        } else {
            $where = true;
        }
        
        //查询
        $data = Db::table($this->db.'.vip_vipactivity')
                ->alias('v')
                ->leftJoin($this->db.'.vip_viplevel vg', 'vg.code = v.level_code')
                ->leftJoin($this->db.'.vip_store vp', 'vp.code = v.store_code')
                ->field('vg.username vgname, vp.name vpname, vp.code, v.id, v.store_code, v.level_code, v.name, v.project, v.type, v.exclusive_discounts, v.time_start, v.time_end')
                // ->where($storeWhere)
                ->where($where)
                ->order('v.id','desc')
                ->page($page,$limit)
                ->select();

        unset($page, $limit, $lookup, $where);

        //修改格式
        foreach($data as $k=>$v){
            $data[$k]['exclusive_days'] = date('Y-m-d H:i:s', $v['time_start']). ' 至 ' .date('Y-m-d H:i:s', $v['time_end']);
            $data[$k]['time_start'] = date('Y-m-d H:i:s', $v['time_start']);
            $data[$k]['time_end'] = date('Y-m-d H:i:s', $v['time_end']);
        }
        webApi(0,'',$count,$data);
    }

    /**
     * 活动折扣 添加功能
     */
    public function activityadd()
    {
        $token = input('access_token');
        // if (cache('?actTx_'.$token) == false) {
        //     webApi(0, 'error', 0, '请选择消费项目');
        // }

        $up = [
            'name' => input('rulename'),//规则名称
            'level_code' => input('levelname'),//会员级别
            'project' => cache('actTx_'.$token)['goods'],//消费项目
            'type' => cache('actTx_'.$token)['type'],// 消费项目属性 分类还是产品
            'exclusive_discounts' => input('exclusive_discounts'), //专属折扣
            // 'integral_multiple' => input('integral_multiple'),//积分倍数
            'time_start' => strtotime(input('time_start')),////时间 开始
            'time_end' => strtotime(input('time_end')),//时间 结束
        ];

        $data = [];
        $cache = cache('activity_'.$token);
        if ($cache == false) {
            webApi(0, 'error', 0, '请选择所属门店');
        }
        
        if ($cache) {
            foreach ($cache as $k=>$v) {
                $data[$k] = $up;
                $data[$k]['store_code'] = $v['id'];
            }
        }
        unset($cache, $token);

        //验证场景
        // $result = $this->validate($up,'app\web\validate\v1\ActivityValidate.activity');
        // if(true !== $result  ){
        //     webApi(0,'error',0,$result);
        // }

        unset($up);
        //添加信息
        $res = Db::table($this->db.'.vip_vipactivity')->insertAll($data);
        unset($data);
        //提示信息
        if($res){
            webApi(0,'yes',0,'添加成功!');
        }else{
            webApi(0,'no',0,'添加失败!');
        }
    }

    /**
     * 工具条删除功能
     */
    public function activitydel()
    {
        $id = input('id') ?? null;
        if($id == null){
            webApi(1, '参数错误');
        }

        $res = Db::table($this->db.'.vip_vipactivity')->delete($id);
        unset($id);
        if($res){
            webApi(0,'yes',0,'删除成功!');
        }else{
            webApi(0,'no',0,'删除失败!');
        }
    }

    /**
     * 工具条编辑功能
     */
    public function activityedit()
    {

        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        // $this->actionLock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        $token = input('access_token');
        // if (cache('?actTx_'.$token) == false) {
        //     $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //     webApi(0, 'error', 0, '请选择消费项目');
        // }

        $data = [
            'id' => $id,
            'name' => input('rulename'),//规则名称
            'store_code' => input('storefront'),//所属店面
            'level_code' => input('levelname'),//会员级别
            'project' => cache('actTx_'.$token)['goods'],//消费项目
            'type' => cache('actTx_'.$token)['type'],//消费项目
            'exclusive_discounts' => input('exclusive_discounts'), //专属折扣
            // 'integral_multiple' => input('integral_multiple'),//积分倍数
            'time_start' => strtotime(input('time_start')),////时间 开始
            'time_end' => strtotime(input('time_end')),//时间 结束
        ];
        unset($token);
        //验证场景
        // $result = $this->validate($data,'app\web\validate\v1\ActivityValidate.activity');
        // if(true !== $result  ){
        //     $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //     webApi(0,'error',0,$result);
        // }

        //修改数据库信息
        $res = Db::table($this->db.'.vip_vipactivity')->update($data);
        unset($data);
        // $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //提示信息
        if($res){
            webApi(0, 'yes', 0, '修改信息成功!');
        }else if ($res === false){
            webApi(0, 'no', 0, '修改信息失败!');
        } else {
            webApi(0, 'no', 0, '未修改!');
        }
    }

    /**
     * 消费项目相关商品列表
     */
    public function getGoods()
    {
        $token = input('access_token');
        $id = input('code') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        // $where[] = ['status', '=', 1];
        $path = input('path') ?? null;
        if ($path == null) {
            webApi(1, '参数错误');
        }

        if (input('wh') != '') {
            $where[] = ['g.bar_code|g.frenum|g.name|g.color|g.size|g.price', 'like', '%'.input('wh').'%'];
        }

        // 查询所有要被查询商品的分类
        $types = Db::table($this->db.'.vip_goods_classify')->where('path', 'like', $path.$id.',%')->select();
        unset($path);
        if ($types) {
            foreach ($types as $v) {
                $id .= ','.$v['code'];
            }
        }
        unset($types);
        $where[] = ['classify_code', 'in', $id];
        
        $goods = Db::table($this->db.'.vip_goods')
                // ->leftJoin($this->db.'.vip_goods_unit u', 'u.code = g.unit')
                ->field('code,name,frenum,bar_code,classify_code,color,size,unit,price,code') //,u.name unit_name
                ->where($where)
                ->select();
        unset($where);
        if ($goods) {
            foreach ($goods as $k=>$v) {
                $goods[$k]['price'] = number_format($v['price'], 2, '.', '');
            }
            $check = cache('actTx_'.$token);
            unset($token);
            if ($check != false) {
                switch ($check['type']) {
                    case 0:
                        $cpath = Db::table($this->db.'.vip_goods_classify')->field('code,path')->where('code', input('code'))->find();
                        $ctype = Db::table($this->db.'.vip_goods_classify')->field('code')->where('path', 'like', $cpath['path'].$cpath['code'].',%')->select();
                        // dump($ctype);exit;
                        unset($cpath);
                        if ($ctype) {
                            $tcode = array_column($ctype, 'code');
                            array_push($tcode, $check['goods']);
                            foreach ($goods as $k=>$v) {
                                if (in_array($v['classify_code'], $tcode)) {
                                    $goods[$k]['LAY_CHECKED'] = true;
                                }
                            }
                        }
                        unset($ctype);
                    break;
                    case 1:
                        if ($check && !empty($check['goods'])) {
                            $checks = explode(',', $check['goods']);
                        } else {
                            $checks = [];
                        }
                        if ($checks) {
                            foreach ($goods as $k=>$v) {
                                if (in_array($v['code'], $checks)) {
                                    $goods[$k]['LAY_CHECKED'] = true;
                                }
                            }
                        }
                        unset($checks);
                    break;
                }
            }
        }
        
        webApi(0, '', 0, $goods);
    }

    /**
     * 消费项目查看
     */
    public function projects()
    {
        $id = input('id');
        if (intVal(input('type')) == 1) {
            $where[] = ['g.code', 'in', $id];
        } else {
            $type = Db::table($this->db.'.user_goods_type')->field('path')->find($id);

            // 查询所有要被查询商品的分类
            $types = Db::table($this->db.'.user_goods_type')->where('path', 'like', $type['path'].$id.',%')->select();
            unset($type);
            if ($types) {
                foreach ($types as $v) {
                    $id .= ','.$v['id'];
                }
            }
            unset($types);
            $where[] = ['g.type_id', 'in', $id];
        }
        $goods = Db::table($this->db.'.user_goods g')
                ->leftJoin($this->db.'.user_goods_unit u', 'u.code = g.unit')
                ->field('g.*,u.name unit_name')
                ->where($where)
                ->select();
                unset($where);
        if ($goods) {
            foreach ($goods as $k=>$v) {
                $goods[$k]['price'] = number_format($v['price'], 2, '.', '');
                $goods[$k]['dis_price'] = number_format($v['price'] * floatVal(input('dis')), 2, '.', '');
                $goods[$k]['LAY_CHECKED'] = true;
            }
        }

        webApi(0, '', count($goods), $goods);
    }

    /**
     * 给选择的商品加入缓存
     */
    public function cacheGoods()
    {
        $token = input('access_token');
        $goods = input('goods') ?? null; 
        $type = input('type') ?? null;
        if ($goods == null || $type == null) {
            webApi(0, 'error', 0, '参数无效');
        }
        $cache = ['type' => intVal($type), 'goods' => trim($goods, ',')];
        unset($type, $goods);
        $res = cache('actTx_'.$token, $cache, 3600);
        unset($token, $cache);
        if ($res) {
            webApi(0, 'yes', 0, '成功');
        } else {
            webApi(0, 'no', 0, '失败');
        }
    }

    /**
     * 清空缓存
     */
    public function setCacheNull()
    {  
        $token = input('access_token');
        cache('actTx_'.$token, null);
        webApi(0);
    }

    /**
     * 消费项目选择商品
     */
    public function goodsEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $res = Db::table($this->db.'.vip_vipactivity')->where('id', $id)->update(['project' => trim(input('goods'), ','), 'type' => 1]);
        
        if ($res) {
            webApi(0, 'yes', 0, trim(input('goods'), ','));
        } else {
            webApi(0, 'no', 0, '失败');
        }
    }
    /**
     * 批量删除活动
     */
    public function delMany()
    {
        if (input('ids') == '') {
            webApi(1, '参数错误');
        }
        
        $ids = trim(input('ids'), ',');
        $res = Db::table($this->db.'.vip_vipactivity')->where('id', 'in', $ids)->delete();
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

     /**
     * 将所属门店加入缓存
     */
    public function getAcacheAdd() 
    {
        $token = input('access_token');
        if (cache('?activity_'.$token) == false) {
            $data = [];
        } else {
            $data = cache('activity_'.$token);
            foreach ($data as $k=>$v) {
                if (input('ssmd_activity_name') == $v['id']) {
                    webApi(0, 'no', 0, '该门店已存在！');
                } 
            }
        }
        
        array_push($data, ['name' => input('name'), 'id' => input('ssmd_activity_name')]);

        $res = cache('activity_'.$token, $data, 3600);
        unset($token, $data);
        if ($res) {
            webApi(0, 'yes', 0, '添加成功');
        } else {
            webApi(0, 'no', 0, '添加失败');
        }
    }

    /**
     * 查询所属店面缓存信息
     */
    public function getActivitycacheAdd() 
    {
        $token = input('access_token');

        $res = cache('activity_'.$token);
        if ($res) {
            foreach($res as $k=>$v) {
                $res[$k]['type'] = $v['name'];
            }
        }
        webApi(0, '', 0, $res);
    }

    /**
     * 将所属店面在缓存中删除
     */
    public function actcacheDel() 
    {
        $token = input('access_token');
        $data = cache('activity_'.$token);
        if ($data != false) {
            foreach ($data as $k=>$v) {
                if (input('id') == $v['id']) {
                    unset($data[$k]);
                } 
            }
        }
        $res = cache('activity_'.$token, $data, 3600);
        unset($token, $data);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }


}