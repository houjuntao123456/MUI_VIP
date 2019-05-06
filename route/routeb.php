<?php

/**
 * viplist 会员列表
 */
Route::rule('webVipList/', 'web/Viplist/index'); //会员列表路由     
Route::rule('webVipListTransfer/', 'web/Viplist/transfer'); //会员转移  
Route::rule('webVipListExt/', 'web/Viplist/ext'); //列表追加扩展信息  
Route::rule('webVipEditExtends/', 'web/Viplist/editExtends'); // 编辑扩展查询  
Route::rule('webVipAddInfoCache/', 'web/Viplist/vipaddInfoCache'); // 点击扩展类型的内容  加入缓存 
Route::rule('webVipListextendsInfo/', 'web/Viplist/vipextendsInfo'); // 扩展标签添加内容  
Route::rule('webVipListinfoList/', 'web/Viplist/vipinfoList'); // 扩展类型 添加内容的展示  
Route::rule('webVipListdelInfo/', 'web/Viplist/vipdelInfo'); // 扩展类型 删除内容
Route::rule('webVipListeditSubmit/', 'web/Viplist/vipeditSubmit'); // 编辑的提交  
Route::rule('webVipGetlistkzval/', 'web/Viplist/getlistkzval'); // 查看中的扩展信息
Route::rule('webVipckrfm/', 'web/Viplist/viprfm'); // 查看中的RFM

Route::rule('webVipScreening/', 'web/Viplist/screening'); // 高级筛选下拉框信息   
Route::rule('webVipGJlist/', 'web/Viplist/list'); // 高级筛选列表  
Route::rule('webVipGJlistdel/', 'web/Viplist/listdel'); // 高级筛选删除 
Route::rule('webVipUpdateVlaue/', 'web/Viplist/updateVlaue'); // 修改高级筛选框的值  
Route::rule('webVipAddscreen/', 'web/Viplist/addscreen'); // 添加高级筛选条件  
Route::rule('webVipSeeCache/', 'web/Viplist/seeCache'); // 查询缓存中是否有条件  
Route::rule('webVipFilterinfo/', 'web/Viplist/filterinfo'); // 保存内容到筛选器  
Route::rule('webVipFilterlist/', 'web/Viplist/filterlist'); // 筛选器列表  
Route::rule('webVipFilterdel/', 'web/Viplist/filterdel'); // 筛选器删除  
Route::rule('webVipEitea/', 'web/Viplist/ditea'); // 点击筛选器中编辑时将信息赋值给高级筛选列表框
Route::rule('webVipdelCache/', 'web/Viplist/delCache'); // 清理缓存

/**
 * 下拉框信息 
 */
Route::rule('webDroplevel/', 'web/Promote/drop_level'); // 会员级别下拉选    
Route::rule('webStore/', 'web/Viplist/store'); //门店下拉选 
Route::rule('webStaff/', 'web/Viplist/staff'); //员工下拉选 
Route::rule('webTableTree/', 'web/Discount/tableTree'); //商品分类

/**
 *  store 门店管理 -- 组织结构
 */
Route::rule('webOrganization/', 'web/Organization/index'); // tableTree    
Route::rule('webOrganizationAdd/', 'web/Organization/add'); // 添加分类 
Route::rule('webOrganizationDel/', 'web/Organization/del'); // 删除分类 
Route::rule('webOrganizationEdit/', 'web/Organization/edit'); // 编辑分类 
// Route::rule('webOrganizationReplace/', 'web/Organization/replace'); // 修改状态  

/**
 * viprig 会员权益 -- 会员级别
 */
Route::rule('webViprightlist/', 'web/Vipright/level'); // 级别列表     
Route::rule('webViprightleveldel/', 'web/Vipright/leveldel'); // 删除  
Route::rule('webViprightdelMany/', 'web/Vipright/delMany'); // 批量删除  
Route::rule('webViprightnumber/', 'web/Vipright/levelNumber'); // 会员级别序号  
Route::rule('webViprightdtree/', 'web/Vipright/viprightdTree'); // 组织机构dTerr  
Route::rule('webViprightAdd/', 'web/Vipright/leveladd'); // 添加功能  
Route::rule('webViprightSplbvalue/', 'web/Vipright/splbvalue'); // 编辑时查询组织机构并赋值  
Route::rule('webViprightEdit/', 'web/Vipright/leveledit'); // 编辑


/**
 * viprig 会员权益 -- 晋升标准  
 */
Route::rule('webPromoteslist/', 'web/Promote/promotes'); //晋升列表   
Route::rule('webPromotesdel/', 'web/Promote/promotedel'); //删除   
Route::rule('webPromotesdelMany/', 'web/Promote/delMany'); //批量删除     
Route::rule('webPromotePromoteadd/', 'web/Promote/promoteadd'); //添加
Route::rule('webPromotePromoteedit/', 'web/Promote/promoteedit'); //编辑


/**
 * viprig 会员权益 -- 会员服务  
 */
Route::rule('webServicelist/', 'web/Service/service'); //会员服务列表
Route::rule('webServicedel/', 'web/Service/servicedel'); //删除  
Route::rule('webServicedelMany/', 'web/Service/delMany'); //批量删除
Route::rule('webServicedelAdd/', 'web/Service/serviceadd'); //添加
Route::rule('webServicedelEdit/', 'web/Service/serviceedit'); //编辑

/**
 * viprig 会员权益 -- 生日折扣  
 */
Route::rule('webBirthList/', 'web/Birthday/index'); // 列表  
Route::rule('webBirthgetcacheAdd/', 'web/Birthday/getcacheAdd'); // 添加 -->缓存所属门店信息
Route::rule('webBirthGetBirthdaycache/', 'web/Birthday/getBirthdaycache'); // 添加 -->查询缓存门店信息  
Route::rule('webBirthGetcacheDel/', 'web/Birthday/getcacheDel'); // 添加 -->删除缓存门店信息    
Route::rule('webBirthBircacheGoods/', 'web/Birthday/bircacheGoods'); // 添加 -->给选择的商品加入缓存
Route::rule('webBirthAdd/', 'web/Birthday/add'); // 添加生日折扣   
Route::rule('webBirthBirsetCacheNull/', 'web/Birthday/BirsetCacheNull'); // 清理缓存    
Route::rule('webBirthGetGoods/', 'web/Birthday/getGoods'); // 获取商品信息   
Route::rule('webBirthcacheGoods/', 'web/Birthday/cacheGoods'); // 获取缓存     
Route::rule('webBirthEdit/', 'web/Birthday/edit'); // 编辑  
Route::rule('webBirthBirthdaydel/', 'web/Birthday/birthdaydel'); // 工具条中删除  
Route::rule('webBirthDelMany/', 'web/Birthday/delMany'); // 批量删除


/**
 * viprig 会员权益 -- 感动特权 
 */
Route::rule('webDiscountlist', 'web/Discount/discounts'); //感动特权列表
Route::rule('webDiscountdel', 'web/Discount/discountdel'); //删除
Route::rule('webDiscountdelMany', 'web/Discount/delMany'); //批量删除
Route::rule('webDiscountAdd/', 'web/Discount/discountadd'); //会员折扣添加
Route::rule('webDiscountEdit/', 'web/Discount/discountedit'); //会员折扣编辑
Route::rule('webgetDcacheAdd/', 'web/Discount/getDcacheAdd'); //查询折扣缓存信息   
Route::rule('webgetBelongedAdd/', 'web/Discount/BelongedAdd'); //添加折扣缓存    
Route::rule('webgetDiscacheDel/', 'web/Discount/discacheDel'); //删除折扣缓存   
Route::rule('webStores/', 'web/Discount/store'); //所属门店下拉选 
Route::rule('webgeTzsdis/', 'web/Discount/zsdis'); //专属名称下拉   
Route::rule('webGetGoods/', 'web/Discount/getGoods'); //获取商品  
Route::rule('webCacheGoods/', 'web/Discount/cacheGoods'); //获取缓存   
Route::rule('websetCacheNull/', 'web/Discount/setCacheNull'); //清理缓存
Route::rule('websetDisstore/', 'web/Discount/disstores'); //添加所属门店    
Route::rule('webDisGoodsCache/','web/Discount/cacheGoods'); // 缓存所选商品

/**
 * viprig 会员权益 -- 活动折扣
 */
Route::rule('webActivitylist', 'web/Activity/activitys'); //列表
Route::rule('webActivitydel', 'web/Activity/activitydel'); //删除
Route::rule('webActivitydelMany', 'web/Activity/delMany'); //批量删除
Route::rule('/webActivityAdd/', 'web/Activity/activityadd'); //活动折扣添加 
Route::rule('/webActivityEdit/', 'web/Activity/activityedit'); //活动折扣修改
Route::rule('/webgetAcacheAdd/', 'web/Activity/getAcacheAdd'); //将所属店面添加到缓存中  
Route::rule('/webgetgetActivitycacheAdd/', 'web/Activity/getActivitycacheAdd'); //查询缓存信息
Route::rule('/webgetactcacheDel/', 'web/Activity/actcacheDel'); //删除缓存信息
Route::rule('/webActivityGoods/','web/Activity/getGoods');          // 获取商品
Route::rule('/webActivityGoodsCache/','web/Activity/cacheGoods');   // 缓存所选商品
Route::rule('/webActivityGoodsCacheNull/','web/Activity/setCacheNull');   // 清除缓存
Route::rule('/webActivityGoodsEdit/','web/Activity/goodsEdit');   // 删除消费项目中的一些商品
Route::rule('/webActivityProjects/','web/Activity/projects');   // 消费项目商品

/**
 * other 会员转移记录
 */
Route::rule('webTransferRecord', 'web/TransferRecord/index'); //列表

/**
 * viplable 会员标签  
 */
Route::rule('webViplabelList', 'web/Viplabel/labelList'); // 查询标签 
Route::rule('webViplabelAdd', 'web/Viplabel/addLabel'); // 添加标签信息  
Route::rule('webViplabelEdit', 'web/Viplabel/editLabel'); // 修改标签状态  
Route::rule('webViplabelDel', 'web/Viplabel/delLabel'); // 删除标签  

/**
 * 精准营销列表  Precision
 */
Route::rule('webPrecisionList', 'web/Precision/index'); // 精准营销列表 
Route::rule('webPrecisionExt', 'web/Precision/ext'); // 列表追加扩展信息  
Route::rule('webPrecisionKZ', 'web/Precision/getlistkz'); // 查看时扩展的信息  
Route::rule('webPrecisionrfm', 'web/Precision/precisionrfm'); // 查看RFM 
Route::rule('webPrecisionpext', 'web/Precision/pext'); // 查看购买商品扩展  
Route::rule('webPrecisionpgoods', 'web/Precision/purchasegoods'); // 查看购买商品列表 
Route::rule('webPrecisionOtherning', 'web/Precision/otherning'); // 高级筛选 -> 下拉选  
Route::rule('webPrecisionsxList', 'web/Precision/list'); // 高级筛选 -> 列表  
Route::rule('webPrecisionUpdateVlaue', 'web/Precision/updateVlaue'); // 高级筛选 -> 修改高级筛选的值
Route::rule('webPrecisionOtherAdd', 'web/Precision/OtherAdd'); // 高级筛选 -> 添加条件  
Route::rule('webPrecisionissCache', 'web/Precision/issCache'); // 高级筛选 -> 查询缓存是否存在 
Route::rule('webPrecisionOtherinfo', 'web/Precision/Otherinfo'); // 高级筛选 -> 保存并筛选 
Route::rule('webPrecisionOtherfilterlist', 'web/Precision/Otherfilterlist'); // 高级筛选 -> 筛选器列表  
Route::rule('webPrecisionOfilterlistdel', 'web/Precision/Ofilterlistdel'); // 高级筛选 -> 删除筛选器列表信息  
Route::rule('webPrecisionOtherassignment', 'web/Precision/Otherassignment'); // 高级筛选 -> 点击筛选器中编辑时将信息赋值给高级筛选列表框
Route::rule('webPrecisionGaojidel', 'web/Precision/gaojidel'); // 高级筛选 -> 删除

/**
 *  Index 首页
 */  
Route::rule('webIndexlist', 'web/Index/remind'); // 当日提醒
Route::rule('webIndexConsumption', 'web/Index/consumption'); // 图表一
Route::rule('webIndexRechargeamount', 'web/Index/Rechargeamount'); // 图表二





