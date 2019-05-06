<?php

namespace app\api\service;

use think\Db;
use think\Controller;

class OnlyData
{
    public static function allotInsert($data, $db)
    {
        $count = count($data);
        $keys = array_keys($data);
        // 启动事务
        Db::startTrans();
        try {
            for ($i = 0; $i < $count; $i++) {
                $table = config('table.'.$keys[$i]);
                if (count($data[$keys[$i]]) == count($data[$keys[$i]], 1)) {
                    Db::table($db.'.'.$table)->insert($data[$keys[$i]]);
                    if ($table == 'vip_goods_order') {
                        $touchLevelChange['dir'] = 'up';
                        $touchLevelChange['vip'] = $data[$keys[$i]]['vip_code'];
                        $touchLevelChange['store'] = $data[$keys[$i]]['store_code'];
                    }
                    if ($table == 'vip_goods_returns') {
                        $touchLevelChange['dir'] = 'down';
                        $touchLevelChange['vip'] = $data[$keys[$i]]['vip_code'];
                        $touchLevelChange['store'] = $data[$keys[$i]]['store_code'];
                    }
                } else {
                    Db::table($db.'.'.$table)->insertAll($data[$keys[$i]]);
                    if ($table == 'vip_goods_order') {
                        foreach ($data[$keys[$i]] as $v) {
                            $touchLevelChange[$k]['dir'] = 'up';
                            $touchLevelChange[$k]['vip'] = $data[$keys[$i]]['vip_code'];
                            $touchLevelChange[$k]['store'] = $data[$keys[$i]]['store_code'];
                        }
                    }
                    if ($table == 'vip_goods_returns') {
                        foreach ($data[$keys[$i]] as $v) {
                            $touchLevelChange[$k]['dir'] = 'down';
                            $touchLevelChange[$k]['vip'] = $data[$keys[$i]]['vip_code'];
                            $touchLevelChange[$k]['store'] = $data[$keys[$i]]['store_code'];
                        }
                    }
                }
            }
            // 提交事务
            Db::commit();
            // 是否触发等级改变
            if (isset($touchLevelChange) && !empty($touchLevelChange)) {
                if (isset($touchLevelChange['dir'])) {
                    self::touchLevelChange($db, $touchLevelChange['store'], $touchLevelChange['vip'], $touchLevelChange['dir']);
                } else {
                    foreach ($touchLevelChange as $v) {
                        self::touchLevelChange($db, $v['store'], $v['vip'], $v['dir']);
                    }
                }
            }
            return ['code' => 200];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['code' => 400, 'msg' => '插入失败，请检查数据格式'];
        }
    }

    public static function allotUpdate($data, $db)
    {
        $count = count($data);
        $keys = array_keys($data);
        // 启动事务
        Db::startTrans();
        try {
            for ($i = 0; $i < $count; $i++) {
                $table = config('table.'.$keys[$i]);
                $where = $data[$keys[$i].'_where'];
                if (count($data[$keys[$i]]) == count($data[$keys[$i]], 1)) {
                    Db::table($db.'.'.$table)->where($where)->update($data[$keys[$i]]);
                    if ($table == 'vip_goods_order') {
                        $touchLevelChange['dir'] = 'up';
                        $touchLevelChange['vip'] = $data[$keys[$i]]['vip_code'];
                        $touchLevelChange['store'] = $data[$keys[$i]]['store_code'];
                    }
                    if ($table == 'vip_goods_returns') {
                        $touchLevelChange['dir'] = 'down';
                        $touchLevelChange['vip'] = $data[$keys[$i]]['vip_code'];
                        $touchLevelChange['store'] = $data[$keys[$i]]['store_code'];
                    }
                } else {
                    foreach ($data[$keys[$i]] as $v) {
                        Db::table($db.'.'.$table)->where($where)->update($v);
                    }
                    if ($table == 'vip_goods_order') {
                        foreach ($data[$keys[$i]] as $v) {
                            $touchLevelChange[$k]['dir'] = 'up';
                            $touchLevelChange[$k]['vip'] = $data[$keys[$i]]['vip_code'];
                            $touchLevelChange[$k]['store'] = $data[$keys[$i]]['store_code'];
                        }
                    }
                    if ($table == 'vip_goods_returns') {
                        foreach ($data[$keys[$i]] as $v) {
                            $touchLevelChange[$k]['dir'] = 'down';
                            $touchLevelChange[$k]['vip'] = $data[$keys[$i]]['vip_code'];
                            $touchLevelChange[$k]['store'] = $data[$keys[$i]]['store_code'];
                        }
                    }
                }
            }
            // 提交事务
            Db::commit();
            // 是否触发等级改变
            if (isset($touchLevelChange) && !empty($touchLevelChange)) {
                if (isset($touchLevelChange['dir'])) {
                    self::touchLevelChange($db, $touchLevelChange['store'], $touchLevelChange['vip'], $touchLevelChange['dir']);
                } else {
                    foreach ($touchLevelChange as $v) {
                        self::touchLevelChange($db, $v['store'], $v['vip'], $v['dir']);
                    }
                }
            }
            return ['code' => 200];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['code' => 400, 'msg' => '修改失败，请检查数据格式'];
        }
    }

    public static function allotDelete($data, $db)
    {
        $count = count($data);
        $keys = array_keys($data);
        // 启动事务
        Db::startTrans();
        try {
            for ($i = 0; $i < $count; $i++) {
                $table = config('table.'.$keys[$i]);
                $where = $data[$keys[$i].'_where'];
                Db::table($db.'.'.$table)->where($where)->delete();
            }
            // 提交事务
            Db::commit();
            return ['code' => 200];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['code' => 400, 'msg' => '删除失败，请检查数据格式'];
        }
    }

    private static function touchLevelChange($db, $store, $vip, $direction)
    {
        if ($vip != '') {
            $vipData = Db::table($db.'.vip_viplist')->where('code', $vip)->find();
            if (!empty($vipData)) {
                $levelUp = new LevelUp($db, 0, $store, $vip, true);
                if ($direction == 'up') {
                    $levelRes = $levelUp->up();
                } else {
                    $levelRes = $levelUp->down();
                }
                if (!empty($levelRes) && $levelRes['code'] == 1) {
                    Db::table($db.'.vip_vipjoin_level')->where('vip_code', $vip)->where('org_code', $levelRes['org'])->setField('level_code', $levelRes['level']);
                }
            }
        }
    }
}