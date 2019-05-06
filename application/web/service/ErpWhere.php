<?php

namespace app\web\service;

use think\Db;

class ErpWhere
{
    /**
     * 订单、退货单  根据组织机构或者门店查询的条件
     */
    public static function orgWhere()
    {
        $session = session('info');
        $where = false;
        if ($session['store'] != '') {
            unset($where);
            $where[] = ['store_code', '=', $session['store']];
        } else {
            if ($session['admin_org'] != '') {
                $stores = Db::table($session['db'].'.vip_store')->field('code')->where('org_code', 'in', $session['admin_org'])->select();
                if (!empty($stores)) {
                    unset($where);
                    $where[] = ['store_code', 'in', implode(',', array_column($stores, 'code'))];
                }
            }
        }
        return $where;
    }

    /**
     * 获取自己管理的组织机构及子机构
     */
    public static function orgChild($orgs = '')
    {
        if ($orgs == '') {
            $orgs = session('info.admin_org');
        }
        if ($orgs != '') {
            $codes = explode(',', $orgs);
            $orgInfo = Db::table(session('info.db').'.vip_org')->where('code', 'in', $orgs)->select();
            if (!empty($orgInfo)) {
                $where = '';
                $orgLength = count($orgInfo);
                for ($i = 0; $i < $orgLength; $i++) {
                    $where .= ' or path like "'.$orgInfo[$i]['path'].$orgInfo[$i]['code'].',%"';
                }
                $where = substr(trim($where), 2);
                $orgData = Db::table(session('info.db').'.vip_org')->field('code')->where($where)->select();
                if (!empty($orgData)) $codes = array_merge($codes, array_column($orgData, 'code')); //$codes += array_column($orgData, 'code'); 错误写法 偶尔会出错
            }
            $orgs = implode(',', array_unique($codes));
        }
        return $orgs;
    }

    /**
     * 获取门店
     */
    public static function getStore($orgs = '')
    {
        if ($orgs == '') {
            return '';
        }
        $data = Db::table(session('info.db').'.vip_store')->where('org_code', 'in', $orgs)->select();
        if (!empty($data)) {
            return implode(',', array_column($data, 'code'));
        } else {
            return '';
        }
    }

    /**
     * 获取当前类获取子分类
     */
    public static function classfiyChild($classify = '')
    {
        if ($classify == '') {
            return $classify;
        } else {
            // 查询当前类
            $req = Db::table(session('info.db').'.vip_goods_classify')->find($classify);
            // 拼接全路径
            $path = $req['path'].$req['code'].',';
            // 获得子类
            $child = Db::table(session('info.db').'.vip_goods_classify')->where('path', 'like', $path.'%')->select();
            // 拆分子类得到code 拼上当前类
            if (!empty($child)) {
               $classify .= ','.implode(',', array_column($child, 'code'));
            }
            return $classify;
        }
    }
}