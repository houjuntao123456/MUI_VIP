<?php

namespace app\web\service;

use think\Db;
use think\Controller;

class System
{
    /**
     * 数据库初始化
     * @param string $db 数据库名|商家企业号
     * @return  数据库初始化状态 200|400(成功|失败)
     */
    public static function dbInit($db)
    {
        $initTables = self::initTables(); // 系统初始化表数据
        $leftMenu = self::leftMenu(); // 侧边栏数据
        $authGroup = self::authGroup(); // PC端权限组数据
        $mobileAuthGroup = self::mobileAuthGroup(); // 手机端权限组数组
        if (empty($initTables) || empty($leftMenu) || empty($authGroup) || empty($mobileAuthGroup)) {
            return ['code' => 400, 'msg' => '初始化数据获取失败'];
        }

        // 开始执行建库建表
        Db::query('CREATE DATABASE '.$db);
        $tablesRes = self::createTables($db, $initTables);
        $staff = [
            'code' => 'boss', 'name' => 'boss', 'pc_auth_code' => '1', 'm_auth_code' => '1', 'exp_time' => time() + 86400,
            'password' => '14e1b600b1fd579f47433b88e8d85291', 'pc_status' => 1, 'm_status' => 1
        ];
        $dataRes = self::createData(
            $db,
            [0 => ['menu' => $leftMenu], $authGroup, $mobileAuthGroup, $staff], 
            ['vip_left_menu', 'vip_auth_group', 'vip_auth_groupm', 'vip_staff'],
            ['one', 'one', 'one', 'one']
        );
        if (!$tablesRes || !$dataRes) {
            Db::query('DROP DATABASE IF EXISTS '.$db);
            return ['code' => 400, 'msg' => '初始化数据写入失败'];
        } else {
            return ['code' => 200];
        }
    }

    /**
     * 系统更新
     */
    public static function update($db)
    {
        $renewData = self::renewData($db);
        if (empty($renewData)) {
            return ['code' => 200, 'msg' => '没有更新'];
        }
        $res = self::execUpdate($renewData);
        return $res;
    }

    /**
     * 账号到期续费
     */
    public static function expire($db, $user)
    {
        $url = self::ssl().config('api.url').config('api.expire');
        $res = json_decode(doHttpPost($url, ['bus_account' => $db, 'user' => $user]), true);
        if ($res['code'] == 200) {
            return ['code' => 200];
        } else {
            return ['code' => 400, 'msg' => $res['msg']];
        }
    }

    private static function initTables()
    {
        $data = Db::table('company.vip_db_init')->field('sql_str')->where('status', 1)->order('sort', 'asc')->select();
        return $data;
    }

    private static function leftMenu()
    {
        $all = [
            ['title' => 'HOME  首页', 'icon' => 'layui-icon-home', 'jump' => '/']
        ];
        // 查询 & 递归重组 & 拼接数组 & 转json字符串
        $rules = Db::table('company.vip_auth_rule')
                    ->field('code,name as title,icon,file name,pid,path')
                    ->where('file', '<>', '')
                    ->where('status', 1)
                    ->order('sort')
                    ->select();
                    
        $other = self::getTree($rules, 'code', 'pid', 'list');
        $all = array_merge($all, $other);
        $menu = json_encode($all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $menu;
    }

    private static function authGroup()
    {
        $rule = Db::table('company.vip_auth_rule')->field('code')->where('status', 1)->select();
        if (!empty($rule)) {
            $rules = implode(',', array_column($rule, 'code'));
        } else {
            $rules = '';
        }
        $role = ['id' => 1, 'code' => '1', 'status' => 1, 'rules' => $rules, 'name' => '超级管理员'];
        return $role;
    }

    private static function mobileAuthGroup()
    {
        $rule = Db::table('company.vip_auth_rulem')->field('code')->where('status', 1)->select();
        if (!empty($rule)) {
            $rules = implode(',', array_column($rule, 'code'));
        } else {
            $rules = '';
        }
        $role = ['id' => 1, 'code' => '1', 'status' => 1, 'rules' => $rules, 'name' => '超级管理员'];
        return $role;
    }

    private static function getTree($items, $pk = 'id', $pid = 'pid', $child = 'children')
    {
        [$map, $tree] = [[], []];
        foreach ($items as &$it) { 
            $map[$it[$pk]] = &$it; //数据的ID名生成新的引用索引树
        }
        foreach ($items as &$at){
            $parent = &$map[$at[$pid]];
            if($parent) {
                unset($at['code'], $at['pid'], $at['path']);
                if ($at['icon'] == '') {
                    unset($at['icon']);
                }
                $parent[$child][] = &$at;
            }else{
                unset($at['code'], $at['pid'], $at['path']);
                if ($at['icon'] == '') {
                    unset($at['icon']);
                }
                $tree[] = &$at;
            }
        }
        return $tree;
    }

    private static function createTables($db, $data)
    {
        Db::startTrans();
        try {
            foreach ($data as $v) {
                Db::query(str_replace('{db}', $db, $v['sql_str']));
            }
            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

    private static function createData($db, $data, $tables, $insertType)
    {
        $num = count($tables);
        Db::startTrans();
        try {
            for ($i = 0; $i < $num; $i++) {
                if ($insertType[$i] == 'all') {
                    Db::table($db.'.'.$tables[$i])->insertAll($data[$i]);
                } else {
                    Db::table($db.'.'.$tables[$i])->insert($data[$i]);
                }
            }
            Db::table($db.'.vip_sysset')->insert(['id' => 1, 'mac_addr' => '', 'md5_token' => md5($db.time())]);
            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

    private static function ssl()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return 'https://';
        } else {
            return 'http://';
        }
    }

    /**
     * 查询是否有更新
     */
    public static function renewData($db)
    {
        $where[] = ['status', '=', 1];
        $where[] = ['renew_time', '<=', time()];
        $renewData = Db::table('company.vip_db_renew')->where($where)->select();
        if (empty($renewData)) {
            return [];
        } else {
            foreach ($renewData as $k=>$v) {
                if ( !in_array( $db, explode(',', $v['bus']) ) ) {
                    unset($renewData[$k]);
                }
            }
            sort($renewData);
            return $renewData;
        }
    }

    private static function execUpdate($db, $renewCodes)
    {
        $execSql = Db::table('company.vip_db_renew_sql')
                    ->where('code', 'in', implode(',', array_column($renewData, 'code')))
                    ->order('id', 'asc')
                    ->select();

        Db::startTrans();
        try {
            foreach ($execSql as $v) {
                Db::query(str_replace('{db}', $db, $v['sql_str']));
            }
            foreach ($renew as $k=>$v) {
                Db::table('company.vip_db_renew')->where('code', $v['code'])->setField('bus', str_replace(','.$db.',', ',', $v['bus']));
            }
            // 提交事务
            Db::commit();
            return ['code' => 200, 'msg' => '更新成功'];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['code' => 400, 'msg' => '更新失败'];
        }
    }
}