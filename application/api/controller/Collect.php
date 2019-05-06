<?php

namespace app\api\controller;

use think\Db;

class Collect
{
    private $ssl = 'http://';
    private $params;

    public function __construct()
    {
        if (isset($_SERVER['HTTPS'])) {
            $this->ssl = 'https://';
        }

        $params = input();
        $this->params = $params;

        /**
         * token 身份验证码
         * db    身份识别信息
         * m     MAC地址识别
         * e     时间戳识别
         * table 数据写入表
         * data  数据
         */
        if (
            empty($params['token']) ||
            empty($params['db'])    ||
            empty($params['e'])
        ) $this->api(['code' => 401, 'msg' => '缺少参数']);

        // 判断库
        $res = $this->validateUserDbExists();
        if ($res !== true) {
            $this->api($res);
        }
        
        if ($params['token'] !== md5($this->getToken().$params['e'])) {
            $this->api(['code' => 402, 'msg' => '身份验证失败']);
        }

        if (!empty($params['m'])) {
            if ($this->validateMacAddr() !== true) {
                $this->api(['code' => 403, 'msg' => 'mac地址错误']);
            } else {
                $this->api(['code' => 0]);
            }
        }

        if (empty($params['table']) || empty($params['data'])) {
            $this->api(['code' => 405, 'msg' => '缺少数据']);
        }

        $this->params['table'] = str_replace('_log', '', str_replace('sk', 'vip', $params['table']));

        $res = $this->validateTableExists();
        if ($res !== true) {
            $this->api($res);
        }
    }

    /**
     * 采集
     */
    public function index()
    {
        $params = $this->params;
        $data = json_decode($params['data'], true);
        if (empty($data)) {
            $this->api(['code' => 421, 'msg' => '空数据传入']);
        }
        $thisTableData = Db::table($params['db'].'.'.$params['table'])->field('id')->select();
        $codes = [];
        if (!empty($thisTableData)) {
            $codes = array_column($thisTableData, 'id');
        }

        Db::startTrans();
        try {
            $re = true;
            foreach ($data as $v) {
                if (in_array($v['id'], $codes)) {
                    $res = $this->existsSave($v);
                } else {
                    $res = $this->notExistsSave($v);
                }
                if ($res == false) {
                    $re = false;
                    break;
                }
            }
            if ($re) {
                Db::commit();
                // 触发级别晋升
                $this->api(['code' => 0, 'msg' => '更新成功', 'data' => implode(',', array_column($data, 'id'))]);
            } else {
                Db::rollback();
                $this->api(['code' => 499, 'msg' => '写入失败，数据异常，表：'.$params['table'].'，exception: none']);
            }
        } catch (\Exception $e) {
            Db::rollback();
            $this->api(['code' => 499, 'msg' => '更新失败，数据异常，表：'.$params['table'].'，exception: '.$e]);
        }
    }

    private function existsSave($val)
    {
        switch ($val['data_status']) {
            case 1:
                $this->update($val);
            break;
            case 2:
                $this->update($val);
            break;
            case 3:
                $this->delete($val);
            break;
            default:
                return false;
        }
        return true;
    }

    private function notExistsSave($val)
    {
        switch ($val['data_status']) {
            case 1:
                $this->insert($val);
            break;
            case 2:
                $this->insert($val);
            break;
            case 3:
                $this->insert($val);
            break;
            default:
                return false;
        }
        return true;
    }

    private function insert($v)
    {
        unset($v['data_status']);
        Db::table($this->params['db'].'.'.$this->params['table'])->insert($v);
    }

    private function update($v)
    {
        unset($v['data_status']);
        $code = $v['id'];
        unset($v['id']);
        Db::table($this->params['db'].'.'.$this->params['table'])->where('id', $code)->udpate($v);
    }

    private function delete($v)
    {
        unset($v['data_status']);
        Db::table($this->params['db'].'.'.$this->params['table'])->where('id', $v['id'])->delete();
    }

    private function validateUserDbExists()
    {
        $params = $this->params;
        $user = Db::table('company.vip_business')->where('code', $params['db'])->find();
        if (!$user) {
            return ['code' => 410, 'msg' => '商家不存在'];
        }
        $dbs = Db::query('show databases');
        if (empty($dbs)) {
            return ['code' => 411, 'msg' => '数据库不存在'];
        } else {
            $dbs = array_column($dbs, 'Database');
            if (!in_array($params['db'], $dbs)) {
                return ['code' => 411, 'msg' => '数据库不存在'];
            }
        }
        return true;
    }

    private function validateTableExists()
    {
        $params = $this->params;
        $tables = Db::query('select table_name from information_schema.tables where table_schema="'.$params['db'].'"');
        if (empty($tables)) {
            return ['code' => 412, 'msg' => '数据表缺失:'.$table];
        } else {
            $tables = array_column($tables, 'table_name');
            if (!in_array($params['table'], $tables)) {
                return ['code' => 412, 'msg' => '数据表缺失:'.$params['table']];
            }
        }
        return true;
    }

    private function validateMacAddr()
    {
        $params = $this->params;
        $set = Db::table($params['db'].'.vip_sysset')->field('mac_addr')->find();
        if ($set['mac_addr'] === '') {
            Db::table($params['db'].'.vip_sysset')->where('id', 1)->update(['mac_addr' => $params['m']]);
            return true;
        }

        if ($set['mac_addr'] === $params['m']) {
            return true;
        } else {
            return false;
        }
    }

    private function getToken()
    {
        $set = Db::table($this->params['db'].'.vip_sysset')->field('md5_token')->find();
        return $set['md5_token'];
    }

    private function api($val)
    {
        echo json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}