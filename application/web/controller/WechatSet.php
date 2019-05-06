<?php

namespace app\web\controller;

use think\Db;

/**
 * Author lwy
 * Date 2019/01/03
 * Description 微信设置
 */
class WechatSet extends Common
{
    public function index()
    {
        $data = Db::table($this->db.'.vip_wechat_set')->find();
        $data['url'] .= '?bus='.$this->db;
        webApi(0, '', 0, $data);
    }

    public function developer()
    {
        $validate = new \think\Validate;
        $validate->rule([
            'appid' => 'require|alphaNum|length:10,64',
            'app_secret' => 'require|alphaNum|length:10,72'
        ]);
        $validate->message([
            'appid.require' => '开发者ID不能为空',
            'appid.alphaNum' => '开发者ID只能是数字或字母',
            'appid.length' => '开发者ID长度超出范围',
            'app_secret.require' => '开发者密码不能为空',
            'app_secret.alphaNum' => '开发者密码只能是数字或字母',
            'app_secret.length' => '开发者密码长度超出范围'
        ]);

        $data = [
            'appid' => trim(input('appid')),
            'app_secret' => trim(input('app_secret'))
        ];
        if (!$validate->check($data)) {
            webApi(0, 'no', 0, $validate->getError());
        }

        $res = Db::table($this->db.'.vip_wechat_set')->where('id', 1)->update($data);
        if ($res) {
            webApi(0, 'yes');
        } else {
            webApi(0, 'no', 0, '修改失败');
        }
    }

    public function tokenReset()
    {
        $res = Db::table($this->db.'.vip_wechat_set')->where('id', 1)->setField('token', md5(microtime(true).$this->db));
        if ($res) {
            webApi(0, 'yes');
        } else {
            webApi(0, 'no');
        }
    }
}