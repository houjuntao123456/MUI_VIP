<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use think\facade\Env;
use image\Image;

class BaseModule
{
    protected $ssl = 'http://';
    public function __construct()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $this->ssl = 'https://';
        }
    }

    public function imgUpload()
    {
        if (input('model') == '') {
            webApi(1, '缺少参数');
        }
        // dump([
        //     'bus_account' => session('info.db'), 'type' => 'upload', 'file' => $path,
        //     'model' => input('model'), 'old_img' => strVal(input('old_img')), 'md5_file' => $md5File
        // ]);exit;
        $file = request()->file('file');
        $info = $file->validate(['ext'=>'jpg,png,gif,jpeg'])->move( '../web/upload');
        if ($info) {

            $path = Env::get('root_path').'web/upload/'.$info->getSaveName();
            $md5File = md5_file($path);
            $image = new Image();
            $image->open($path);
            $image->thumb(512, 512);
            $image->save($path);

            $url = $this->ssl.config('api.url').config('api.imgUploadUrl');
            $res = json_decode(doHttpPost($url, [
                'bus_account' => session('info.db'), 'type' => 'upload', 'file' => $path,
                'model' => input('model'), 'old_img' => strVal(input('old_img')), 'md5_file' => $md5File
            ]), true);
            if ($res['code'] == 200) {
                // is_file($path) && unlink($path);
                webApi(0, 'yes', 0, $res['data']);
            } else {
                is_file($path) && unlink($path);
                webApi(1, $res['msg']);
            }
        } else {
            $msg = '上传失败！'.$file->getError();
            webApi(1, $msg);
        }
    }
}