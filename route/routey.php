<?php

/**
 * 登录退出
 */
Route::rule('webLogin/','web/Login/login');
Route::rule('webLogout/','web/Login/logout');

/**
 * 系统设置 -- 角色管理
 */
Route::rule('webRole/','web/Role/index');
Route::rule('webRoleAdd/','web/Role/add');
Route::rule('webRoleGetRules/','web/Role/getRules');

/**
 * 系统设置 -- 手机端角色管理
 */
Route::rule('webRolem/','web/Rolem/index');
Route::rule('webRolemAdd/','web/Rolem/add');
Route::rule('webRolemGetRules/','web/Rolem/getRules');

/**
 * Wpp -- 公众号设置 -- 参数设置
 */
Route::rule('webWechatSet/','web/WechatSet/index');
Route::rule('webWechatSetDeveloper/','web/WechatSet/developer');
Route::rule('webWechatSetTokenReset/','web/WechatSet/tokenReset');

/**
 * Wpp -- 公众号设置 -- 回复设置 -- 图文素材管理
 */
Route::rule('WechatReplyCdr/','web/WechatReply/cdr');

/**
 * Wpp -- 公众号对接方法
 */
Route::rule('Wechat/','web/Wechat/index');

/**
 * 图片上传方法
 */
Route::rule('webBaseImgUpload/','web/BaseModule/imgUpload');

/**
 * Wpp -- 公众号设置 -- 导航栏设置
 */
Route::rule('WechatMenuTop/','web/WechatMenu/top');