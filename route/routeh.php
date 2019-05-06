<?php

/**
 * 商品管理---销售记录
 */
Route::rule('webSaleListIndex/','web/SaleList/index'); // 销售记录列表
Route::rule('webSaleListDetail/','web/SaleList/detail'); // 销售记录详情

/**
 * 商品管理---退货记录
 */
Route::rule('webRefundsListIndex/','web/RefundsList/index'); // 退货记录列表
Route::rule('webRefundsListDetail/','web/RefundsList/detail'); // 退货记录详情

/**
 * 商品管理---商品标签
 */
// Route::rule('webGoodsExtendAdd/','web/GoodsExtend/addExtend'); // 添加商品标签
// Route::rule('webGoodsGoodsOrg/','web/GoodsExtend/goodsOrg'); // 标签的组织结构查询
// Route::rule('webGoodsExtendsIndex/','web/GoodsExtend/index'); // 标签列表
// Route::rule('webGoodsStatus/','web/GoodsExtend/status'); // 标签列表的状态

Route::rule('webGoodsExtendAddLabel/','web/GoodsExtend/addLabel'); // 添加商品标签
Route::rule('webGoodsExtendsIndex/','web/GoodsExtend/index'); // 标签列表
Route::rule('webGoodsExtendsDelLabel/','web/GoodsExtend/delLabel'); // 删除标签
Route::rule('webGoodsEditLabel/','web/GoodsExtend/editLabel'); // 标签列表的状态

/**
 * 商品管理---商品列表
 */
Route::rule('webGoodsIndex/','web/Goods/index'); // 商品列表
Route::rule('webGoodsDetail/','web/Goods/goodsDetail'); // 请求详情里面的扩展详情
Route::rule('webGoodsEditExtends/','web/Goods/editExtends'); // 编辑--->扩展信息(请求标签列表)
Route::rule('webGoodsEditGetTxt/','web/Goods/getTxt'); // 编辑--->文本类型赋初值
Route::rule('webGoodsEditExtendsInfo/','web/Goods/extendsInfo'); // 编辑--->扩展类型的添加内容
Route::rule('webGoodsEditGetTxt/','web/Goods/getTxt'); // 编辑--->文本类型赋初值
Route::rule('webGoodsEditExtendInfoList/','web/Goods/infoList'); // 编辑--->扩展类型的添加内容的展示
Route::rule('webGoodsEditExtendDelInfo/','web/Goods/delInfo'); // 编辑--->扩展类型的添加内容的 删除
Route::rule('webGoodsEditExtendAddInfoCache/','web/Goods/addInfoCache'); // 编辑扩展 -- 加入缓存
Route::rule('webGoodsEditSubmit/','web/Goods/editSubmit'); // 编辑扩展 -- 提交
Route::rule('webGoodOrg/','web/Goods/goodsOrg'); // 门店价格---的组织结构
Route::rule('webGoodStoreList/','web/Goods/storeList'); // 门店价格---的组织结构
Route::rule('webGoodVipGoogsVipClassify/','web/Goods/vipClassify'); // 分类列表
Route::rule('webGoodVipGoogsTree/','web/Goods/vipGoogsTree'); // 分类搜索
Route::rule('webGoodCacheClear/','web/Goods/cacheClear'); // 清空缓存

/**
 * 商品管理---商品列表---高级筛选
 */
Route::rule('webGoodScreenSelectField/','web/Goods/screenSelectField'); // 选择筛选条件
Route::rule('webGoodScreenHighScreenAdd/','web/Goods/highScreenAdd'); // 将筛选条件加入缓存
Route::rule('webGoodScreenVipHighScreenList/','web/Goods/vipHighScreenList'); // 获得条件列表
Route::rule('webGoodScreenTiaojianDel/','web/Goods/tiaojianDel'); // 条件列表的删除
Route::rule('webGoodScreenClearHighScreenCache/','web/Goods/clearHighScreenCache'); // 清空缓存
Route::rule('webGoodScreenCheckScreenIsEmpty/','web/Goods/checkScreenIsEmpty'); // 检查筛选条件是否为空
Route::rule('webGoodScreenSizerAdd/','web/Goods/sizerAdd'); // 保存并筛选

// 筛选器

Route::rule('webGoodScreenSxqList/','web/Goods/sxqList'); // 筛选器列表
Route::rule('webGoodScreenSxqDel/','web/Goods/sxqDel'); // 筛选器列表的删除
Route::rule('webGoodScreenSxqEdit/','web/Goods/sxqEdit'); // 筛选器列表的编辑

/**
 * STM---员工管理---V票管理--v票规则
 */
Route::rule('webVmanageStaffList/','web/Vmanage/staffList'); // 员工列表
Route::rule('webVmanageVRuleAdd/','web/Vmanage/vRuleAdd'); // 添加V票规则
Route::rule('webVmanageIndex/','web/Vmanage/index'); // 规则列表
Route::rule('webVmanageEdit/','web/Vmanage/edit'); // 编辑规则列表
Route::rule('webVmanageDel/','web/Vmanage/del'); // 删除规则列表

/**
 * STM---员工管理---V票管理--v票资金来源
 */
Route::rule('webVcapitalStaff/','web/Vcapital/vcapitalStaff'); // 乐捐人列表
Route::rule('webVcapitalAddcapital/','web/Vcapital/addcapital'); // 添加资金来源
Route::rule('webVcapitalIndex/','web/Vcapital/index'); // 资金来源列表

/**
 * STM---员工管理---v票管理---v票领取记录
 */
Route::rule('webVreceiveReceiveList/','web/Vreceive/receiveList'); // v票领取人列表
Route::rule('webVreceiveReceival/','web/Vreceive/receival'); // 领取
Route::rule('webVreceiveIndex/','web/Vreceive/index'); // V票领取记录
Route::rule('webVreceiveExchangeOne/','web/Vreceive/exchangeOne'); // 兑换(计算汇率和兑换金额)
Route::rule('webVreceiveConfirmExchange/','web/Vreceive/confirmExchange'); // 执行兑换
Route::rule('webVreceiveDel/','web/Vreceive/del'); // 删除领取记录

/**
 * STM---员工管理----V票管理---V票兑换记录
 */
Route::rule('webVexchangeIndex/','web/Vexchange/index'); // v票兑换记录

/**
 * STM---员工管理----V票管理---V票报表
 */
Route::rule('webVtableIndex/','web/Vtable/index'); // v票报表