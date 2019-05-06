<?php

namespace app\api\controller;

use think\Controller;
use Firebase\JWT\JWT;
use app\api\service\ApiBase as Ab;

class Common extends Controller
{
    /**
     * @param string $key
     * @param array  $data 数据
     */
    protected $db;
    protected $key;
    protected $data;
    protected $isGetEndData;

    /**
     * @param string|JWTarray check  包含: secret 密码   timestamp 时间戳  cipher 密文(md5(key.timestamp.secret))
     */
    public function initialize()
    {
        // $this->key = input('key');
        
        // // 身份验证
        // $deteRes = Ab::detection($this->key, JWT::decode(input('check'), $this->key, array('HS256')));
        // if ($deteRes['code'] != 200) {
        //     $this->api(400, $deteRes['msg']);
        // }
        // $this->db = $deteRes['db'];

        // // 解密data数据
        // $this->data = JWT::decode(input('data'), $this->key, array('HS256'));

        // $this->isGetEndData = (bool)input('isget');

        $this->db = 'vip';
        $this->data = [
            'order' => ['code' => '123', 'store_code' => 'MDERP15459942078135', 'vip_code' => '95588', 'create_time' => time()],
            'order_info' => [
                ['order_code' => '123', 'goods_code' => '454', 'number' => 1, 'create_time' => time()],
                ['order_code' => '123', 'goods_code' => '55', 'number' => 2, 'create_time' => time()],
            ],
            'order_where' => 'code = 123',
        ];
        $this->isGetEndData = true;
        
        // 数据验证
        $url = request()->controller().'/'.request()->action();
        if (!in_array($url, config('check.nocheck'))) {
            $testRes = Ab::testData($this->data);
            if ($testRes['code'] != 200) {
                $this->api(400, $testRes['msg']);
            }
        }
    }

    /**
     * 数据返回接口
     */
    protected function api($code = 200, $msg = '', $data = [])
    {
        $return['code'] = $code;
        $return['msg'] = $msg;
        if (!empty($data)) {
            $return['data'] = JWT::encode($data, $this->key);
        }
        echo json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}