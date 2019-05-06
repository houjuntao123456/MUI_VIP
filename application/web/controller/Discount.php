<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author lhp
 * Date 2018/12/29
 * Description 会员权益 --- 感动特权
 */
class Discount extends Common
{
    /**
     * 感动特权列表
     */
    public function discounts()
    {
        //分页信息
        [$page, $limit, $lookup] = [input('page'), input('limit'), input('discountlookup')];
        
        //统计数量
        $count = Db::table($this->db.'.vip_vipdiscount')->count();

        //模糊查询
        if ($lookup) {
            $where[] = ['vp.name|v.username|vg.username', 'like', '%' . $lookup . '%'];
        } else {
            $where = true;
        }
        // if (empty(session('info.store'))) {
        //     $storeWhere = true;
        // } else {
        //     $storeWhere[] = ['vp.ERP', '=', session('info.store')['ERP']];
        // }
        unset($lookup);
        //查询
        $data = Db::table($this->db.'.vip_vipdiscount')
            ->alias('v')
            ->leftJoin($this->db.'.vip_viplevel vg', 'vg.code = v.level_code')
            ->leftJoin($this->db.'.vip_store vp', 'vp.code = v.store_code')
            ->field('vg.username vgname, vp.name vpname, vp.code, v.id, v.username, v.store_code, v.level_code, v.project, v.type, v.exclusive_code, v.limited_time_start, v.limited_time_end, v.exclusive_discounts')
            // ->where($storeWhere)
            ->where($where)
            ->order('v.id','desc')
            ->page($page, $limit)
            ->select();
            unset($page, $limit, $where);
        //修改格式
        foreach ($data as $k => $v) {
            $data[$k]['limited_time'] = $data[$k]['exclusive_code'] . ' 前 ' . $data[$k]['limited_time_start'] . ' 天 至 后 ' . $data[$k]['limited_time_end'] . ' 天'; //限定时间
        }
        webApi(0, '', $count, $data);
    }

    /**
     * 感动特权 添加功能
     */
    public function discountadd()
    {
        $token = input('access_token');
        // if (cache('?disTx_'.$token) == false) {
        //     webApi(0, 'error', 0, '请选择消费项目');
        // }
        $up = [
            'username' => input('username'),//规则名称
            'level_code' => input('level'),//会员级别
            'project' => cache('disTx_'.$token)['goods'],//消费项目
            'type' => cache('disTx_'.$token)['type'],// 消费项目属性 分类还是产品
            'exclusive_code' => input('exclusive_days'),//专属名称
            'limited_time_start' => Intval(input('limited_time_start')),//专属名称开始
            'limited_time_end' => Intval(input('limited_time_end')),//专属名称结束
            'exclusive_discounts' => input('exclusive_discounts') //专属折扣
            // 'integral_multiple' => input('integral_multiple')//积分倍数
        ];

        $data = [];
        $cache = cache('discount_'.$token);
        if ($cache) {
            foreach ($cache as $k=>$v) {
                $data[$k] = $up;
                $data[$k]['store_code'] = $v['id'];
            }
        }
        unset($cache);
        //验证场景
        // $result = $this->validate($up, 'app\web\validate\v1\DiscountValidate.discount');
        // if (true !== $result) {
        //     webApi(0, 'error', 0, $result);
        // }
        // unset($result, $up);
        // dump($data);exit;
        //添加信息
        $res = Db::table($this->db.'.vip_vipdiscount')->insertAll($data);
        unset($data);
        //提示信息
        if ($res) {
            cache('disTx_'.$token, null);
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 工具条删除功能
     */
    public function discountdel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $res = Db::table($this->db.'.vip_vipdiscount')->delete($id);

        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 查询所属门店下拉框信息
     */
    public function disstore()
    {
        if (empty(session('info.store'))) {
            $storeWhere = true;
        } else {
            $storeWhere[] = ['ERP', '=', session('info.store')['ERP']];
        }
        $res = Db::table($this->db.'.user_store')->where($storeWhere)->field('ERP,store_name')->select();
        webApi(0, '', 0, $res);
    }

    /**
     * 工具条编辑功能
     */
    public function discountedit()
    {
        $token = input('access_token');
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        // $this->actionLock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        // if (cache('?disTx_'.$token) == false) {
        //     $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //     webApi(0, 'error', 0, '请选择消费项目');
        // }

        $data = [
            'id' => $id,
            'username' => input('username'),//规则名称
            'store_code' => input('storefront'),//所属店面
            'level_code' => input('level'),//会员级别
            'project' => cache('disTx_'.$token)['goods'],//消费项目
            'type' => cache('disTx_'.$token)['type'],//消费项目
            'exclusive_code' => input('exclusive_days'),//专属名称
            'limited_time_start' => Intval(input('limited_time_start')),//专属名称开始
            'limited_time_end' => Intval(input('limited_time_end')),//专属名称结束
            'exclusive_discounts' => input('exclusive_discounts') //专属折扣
            // 'integral_multiple' => input('integral_multiple')//积分倍数
        ];

        //验证场景
        // $result = $this->validate($data, 'app\web\validate\v1\DiscountValidate.discount');
        // if (true !== $result) {
        //     $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //     webApi(0, 'error', 0, $result);
        // }
        // unset($result);
        //修改数据库信息
        $res = Db::table($this->db.'.vip_vipdiscount')->update($data);
        unset($data);
        // $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //提示信息
        if($res){
            cache('disTx_'.$token, null);
            webApi(0, 'yes', 0, '修改信息成功!');
        }else if ($res === false){
            webApi(0, 'no', 0, '修改信息失败!');
        } else {
            webApi(0, 'no', 0, '未修改!');
        }

    }

    /**
     * 感动特权添加编辑消费项目相关商品列表
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
            $check = cache('disTx_'.$token);
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
     * 给选择的商品加入缓存
     */
    public function cacheGoods()
    {
        $token = input('access_token');
        $goods = input('goods') ?? null;
        $type = input('type') ?? null;
        // if ($goods == null || $type == null) {
        //     webApi(1, '参数错误');
        // }
        $cache = ['type' => intVal($type), 'goods' => trim($goods, ',')];
        unset($goods);
        $res = cache('disTx_'.$token, $cache, 3600);
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
        cache('disTx_'.$token, null);
        cache('discount_'.$token, null);
        cache('activity_'.$token, null);
        webApi(0);
    }

    /**
     * 获取专属名称  扩展标签
     */
    public function getKzBq()
    {
        $find = Db::table($this->db.'.user_viplabel')->find();
        if (!empty($find['info'])) {
            $field = array_keys(json_decode($find['info'], true));
        } else {
            $field = [];
        }
        webApi(0, '', count($field), $field);
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
        $res = Db::table($this->db.'.vip_vipdiscount')->where('id', $id)->update(['project' => trim(input('goods'), ','), 'type' => 1]);
        if ($res) {
            webApi(0, 'yes', 0, trim(input('goods'), ','));
        } else {
            webApi(0, 'no', 0, '失败');
        }
    }

    /**
     * 批量删除
     */
    public function delMany()
    {
        if (input('ids') == '') {
            webApi(1, '参数错误');
        }
        
        $ids = trim(input('ids'), ',');
        $res = Db::table($this->db.'.vip_vipdiscount')->where('id', 'in', $ids)->delete();
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

     /**
     * 将所属门店加入缓存
     */
    public function BelongedAdd() 
    {
        $token = input('access_token');
        if (cache('?discount_'.$token) == false) {
            $data = [];
        } else {
            $data = cache('discount_'.$token);
            foreach ($data as $k=>$v) {
                if (input('ssmd_name') == $v['id']) {
                    webApi(0, 'no', 0, '该门店已存在！');
                } 
            }
        }
        
        array_push($data, ['name' => input('name'), 'id' => input('ssmd_name')]);

        $res = cache('discount_'.$token, $data, 3600);
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
    public function getDcacheAdd() 
    {
        $token = input('access_token');

        $res = cache('discount_'.$token);
        unset($token);
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
    public function discacheDel() 
    {
        $token = input('access_token');
        $data = cache('discount_'.$token);
        if ($data != false) {
            foreach ($data as $k=>$v) {
                if (input('id') == $v['id']) {
                    unset($data[$k]);
                } 
            }
        }
        $res = cache('discount_'.$token, $data, 3600);
        unset($token, $data);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

    /**
     * 专属名称 下拉框
     */
    public function zsdis() 
    {
       $data = Db::table($this->db.'.vip_family')->field('relation, code')->select();
       webApi(0, '', 0, $data);
    }

    /**
     * 所属门店下拉框
     */
    public function store()
    {
        $data = Db::table($this->db. '.vip_store')->field('code, name')->select();
        webApi(0, '', 0, $data);
    }

    // 产品分类列表
    public function tableTree()
    {
        $data = Db::table($this->db.'.vip_goods_classify')->select();
        $data = getTree($data, 'code', 'pid', 'children');
        webApi(0, '', 0, $data);
    }

    /**
     * 查询所属门店下拉框信息
     */
    public function disstores()
    {
        if (empty(session('info.store'))) {
            $storeWhere = true;
        } else {
            $storeWhere[] = ['code', '=', session('info.store')['code']];
        }
        $res = Db::table($this->db.'.vip_store')->where($storeWhere)->field('code,name')->select();
        webApi(0, '', 0, $res);
    }



}