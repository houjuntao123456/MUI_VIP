<?php

namespace app\api\service;

use think\Db;

class ApiBase
{
    /**
     * @param string $key
     * @param array  $array 验证信息 包含['secret', 'timestamp', 'cipher']
     */
    public static function detection($key, $array)
    {
        $isRes = self::isLog($key, $array['secret']);
        $comRes = self::comparison($key, $array);
        if (!$isRes || !$comRes) {
            return ['code' => 400, 'msg' => '身份验证失败'];
        } else {
            return ['code' => 200, 'db' => $isRes];
        }
    }

    /**
     * 检测key - secret - 商家 是否匹配
     */
    public static function isLog($key, $secret)
    {
        $where[] = ['api_key', '=', $key];
        $where[] = ['api_secret', '=', $secret];
        $isExists = Db::table('company.vip_business')->where($where)->find();
        if (!empty($isExists)) {
            return $isExists['code'];
        } else {
            return false;
        }
    }

    /**
     * 检测 cipher 是否匹配
     */
    public static function comparison($key, $array)
    {
        return md5($key.$array['timestamp'].$array['secret']) === $array['cipher'];
    }

    /**
     * 检测数据
     */
    public static function testData($data)
    {
        // 循环检测表数据
        $keys = array_keys($data);
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {
            $table = config('table.'.$keys[$i]);
            $checkRes = self::validate($data[$keys[$i]], config('validate.'.$table));
            if ($checkRes['code'] != 200) {
                return ['code' => 400, 'msg' => $keys[$i].' 表数据验证未通过: '.$checkRes['msg']];
            }
        }
        return ['code' => 200];
    }

    /**
     * 验证
     */
    private static function validate($data, $rule)
    {
        $validate = new \think\Validate;
        $validate->rule($rule);
        if (count($data) == count($data, 1)) {
            if (!$validate->check($data)) {
                return ['code' => 400, 'msg' => $validate->getError()];
            }
        } else {
            foreach ($data as $v) {
                if (!$validate->check($v)) {
                    return ['code' => 400, 'msg' => $validate->getError()];
                }
            }
        }
        unset($validate);
        return ['code' => 200];
    }
}