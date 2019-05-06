<?php

namespace app\web\Controller;

use think\Db;
use think\Controller;
use app\web\service\Wechat as Wx;

class Wechat extends Controller
{
	protected $db;
	
	public function initialize()
	{
		$this->db = input('bus');
	}

    /**
     * 微信开发者认证方法
     */
    public function index()
    {
        $conf = Wx::getConf($this->db, 'token');
        // 1.获得参数 signature nonce token timestamp echostr
		$token = $conf['token'];
		$nonce = input('nonce');
		$timestamp = input('timestamp');
		$signature = input('signature');
		$echostr = input('echostr');
		// 2.字典序排序并加密
		$array = [];
		$array = [$nonce, $timestamp, $token];
		sort($array);
		$str = sha1(implode('', $array));
		if ($str == $signature) {
			echo $echostr;
			exit;
		} else {
			$this->responseMsg();
		}
	}
	
	/**
	 * 消息回复
	 */
	public function responseMsg()
	{
    	// 获取数据
		$postArr = file_get_contents('php://input');
		libxml_disable_entity_loader(true);
    	// 处理消息类型
    	$postObj = simplexml_load_string($postArr, 'SimpleXMLElement', LIBXML_NOCDATA);
		Wx::responseMsg($this->db, $postObj);
	}

	/**
	 * 设置微信菜单
	 */
	public function setItem()
	{
		Wx::setItem($this->db);
	}
}