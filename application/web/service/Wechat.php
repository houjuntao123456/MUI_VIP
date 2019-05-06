<?php

namespace app\web\service;

use think\Db;

class Wechat
{
    /**
     * 获取微信配置
     * @return array [微信配置信息]
     */
    public static function getConf($db, $field = '*')
    {
        if (strval($db) == '') {
            return null;
        }
        $wechatConf = Db::table($db.'.vip_wechat_set')->field($field)->find();
        return $wechatConf;
    }

    /**
     * 获取微信access_token
     */
    public static function getAccessToken($db)
    {
        if (cache('?wx_access_token'.$db) == false) {
        	// $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->secret;
			$res = http_curl($url, 'get', 'json');
			$access_token = $res['access_token'];
			cache('wx_access_token'.$db, $access_token, 7100);
        }

        return cache('wx_access_token'.$db);
    }

    /** 
     * 设置公众号菜单
     */
    public static function setItem($db)
    {
        $access_token = self::getAccessToken($db);
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        $conf = self::getConf($db, 'item');
		http_curl($url, 'post', 'json', $conf['item']);
    }

    public static function responseMsg($db, $postObj)
    {
        
    }
}