<?php

/**
 * Author lxy
 * Date 2018/12/28
 * Description 路由
 */

/**
 * CRM---会员管理---读卡与会员信息
 */
Route::rule('webVipinfoCardSel/','web/Vipinfo/cardSel');  //查询会员信息

/**
 * CRM---会员管理---图标返单计划
 */
Route::rule('webVipinfoOrderAdd/','web/Vipinfo/orderAdd');            //返单计划添加也添加小返单
Route::rule('webVipinfoCacheClean/','web/Vipinfo/cacheClean');        //清除缓存
Route::rule('webVipinfoOrderCacheSel/','web/Vipinfo/orderCacheSel');  //查询缓存中的数据加入表
Route::rule('webVipinfoOrderCacheAdd/','web/Vipinfo/orderCacheAdd');  //新建返单小表一条一条添加到緩存中
Route::rule('webVipinfoOrderCacheDel/','web/Vipinfo/orderCacheDel');  //删除缓存中的数据
Route::rule('webVipinfoOrderProduct/','web/Vipinfo/orderProduct');    //选择产品条码获取值
Route::rule('webVipinfoOrderGoods/','web/Vipinfo/orderGoods');        //查询产品条码下拉框

/**
 * CRM---会员管理---图标100天跟进
 */
Route::rule('webVipinfoHundredFootAdd/','web/Vipinfo/hundredFootAdd');        //100天跟进添加也添加小100天跟进与记录
Route::rule('webVipinfoHundredFootSearch/','web/Vipinfo/hundredFootSearch');  //用模板100天跟进id去查询模板100天跟进名称赋值互动名称
Route::rule('webVipinfoHundredFootCache/','web/Vipinfo/hundredFootCache');    //查询缓存中的数据加入表

/**
 * CRM---会员管理---图标365天跟进 
 */
Route::rule('webVipinfoThreeFootAdd/','web/Vipinfo/threeFootAdd');        //添加到大365天跟进表也小365天跟进表与记录
Route::rule('webVipinfoThreeFootSearch/','web/Vipinfo/threeFootSearch');  //用模板365天跟进id去查询模板365天跟进名称赋值365名称
Route::rule('webVipinfoThreeFootCache/','web/Vipinfo/threeFootCache');    //查询缓存中的数据加入表

/**
 * CRM---会员管理---图标专场跟进
 */
Route::rule('webVipinfoFieldFootAdd/','web/Vipinfo/fieldFootAdd');        //专场跟进添加也添加小专场跟进与记录
Route::rule('webVipinfoFieldFootSearch/','web/Vipinfo/fieldFootSearch');  //用模板专场跟进id去查询模板专场跟进名称赋值互动名称
Route::rule('webVipinfoFieldFootCache/','web/Vipinfo/fieldFootCache');    //查询缓存中的数据加入表

/**  
 * CRM---会员管理---图标会员服务
 */
Route::rule('webVipinfoServiceSel/','web/Vipinfo/serviceSel');            //按会员级别查询会员服务名称下拉框
Route::rule('webVipinfoServiceAdd/','web/Vipinfo/serviceAdd');            //执行添加记录
Route::rule('webVipinfoServiceCacheAdd/','web/Vipinfo/serviceCacheAdd');  //从名字添加到缓存
Route::rule('webVipinfoServiceCacheSel/','web/Vipinfo/serviceCacheSel');  //从缓存中查表
Route::rule('webVipinfoServiceCacheDel/','web/Vipinfo/serviceCacheDel');  //从缓存中删除
Route::rule('webVipinfoServiceCacheNum/','web/Vipinfo/serviceCacheNum');  //修改会员服务次数

/**
 * CRM---会员管理---销售流水
 */
Route::rule('webVipinfoSalesFlow/','web/Vipinfo/salesFlow');          //按卡号查询销售纪录
Route::rule('webVipinfoSalesFlowInfo/','web/Vipinfo/salesFlowInfo');  //点击查看查询订单明细
 
/**
 * CRM---会员管理---积分流水
 */
Route::rule('webVipinfoIntegralSel/','web/Vipinfo/integralSel');  //按卡号查询积分流水

/**
 * CRM---会员管理---储值流水
 */
Route::rule('webVipinfoStoredSel/','web/Vipinfo/storedSel');  //按卡号查询储值流水

/**
 * CRM---会员管理---割肉流水
 */
Route::rule('webVipinfoStoredCutMeat/','web/Vipinfo/storedCutMeat');  //按卡号查询割肉流水

/**
 * CRM---会员管理---RFM
 */
Route::rule('webVipinfoRFMSel/','web/Vipinfo/rfmSel');  //按卡号查询RFM

/**
 * CRM---会员管理---返单计划
 */
Route::rule('webVipinfoOrderSel/','web/Vipinfo/orderSel');          //按卡号查询返单计划
Route::rule('webVipinfoOrderDillSel/','web/Vipinfo/orderDillSel');  //按返单计划的单号查询返单中的小返单

/**
 * CRM---会员管理---100天跟进表 
 */
Route::rule('webVipinfoHundredFootSel/','web/Vipinfo/hundredFootSel');    //按卡号查询100天跟进
Route::rule('webVipinfoHundredFootDill/','web/Vipinfo/hundredFootDill');  //按100天跟进的单号查询互动中的小100天跟进

/**
 * CRM---会员管理---365天跟进表 
 */
Route::rule('webVipinfoThreeFootSel/','web/Vipinfo/threeFootSel');    //按卡号查询365天跟进
Route::rule('webVipinfoThreeFootDill/','web/Vipinfo/threeFootDill');  //按365天跟进的单号查询互动中的小365天跟进

/**
 * CRM---会员管理---专场跟进表 
 */
Route::rule('webVipinfoFieldFootSel/','web/Vipinfo/fieldFootSel');    //按卡号查询专场跟进
Route::rule('webVipinfoFieldFootDill/','web/Vipinfo/fieldFootDill');  //按专场跟进的单号查询互动中的小跟进

/**
 * CRM---会员管理---商品明细 
 */
Route::rule('webVipinfoGoodInfo/','web/Vipinfo/goodInfo');  //按卡号查询商品明细

/**
 * CRM---会员管理---会员扩展
 */
Route::rule('webVipinfoMemberExtend/','web/Vipinfo/memberExtend');  //查询会员扩展信息

/**
 * CRM---会员管理---家属
 */
Route::rule('webVipinfoFamilySel/','web/Vipinfo/familySel');  //家属查表
Route::rule('webVipinfoFamilyAdd/','web/Vipinfo/familyAdd');  //家属添加
Route::rule('webVipinfoFamilyDel/','web/Vipinfo/familyDel');  //家属删除

/**
 * CRM---会员管理---会员服务记录
 */
Route::rule('webVipinfoServiceSelAll/','web/Vipinfo/serviceSelAll');  //会员服务记录查表

/**
 * CRM---会员管理---会员互动记录
 */
Route::rule('webVipinfoInteractiveRecord/','web/Vipinfo/interactiveRecord');  //按卡号查询会员互动记录
Route::rule('webVipinfoInteractiveDill/','web/Vipinfo/interactiveDill');      //按会员互动单号小会员互动详情


/**
 * CRM---会员管理---R时间 
 */
Route::rule('webLoywhile/','web/Loywhile/index');             //查询R时间表
Route::rule('webLoywhileEdit/','web/Loywhile/whileEdit');     //修改R指标区间
Route::rule('webLoywhileLook/','web/Loywhile/lookPeople');    //会员
Route::rule('webLoywhileSmsSend/','web/Loywhile/smsSend');    //批量短信

/**
 * CRM---会员管理---F次数 
 */
Route::rule('webLoyfrequency/','web/Loyfrequency/index');                   //查询F次数表
Route::rule('webLoyfrequencyEdit/','web/Loyfrequency/frequencyEdit');       //修改F指标区间
Route::rule('webLoyfrequencyDayEdit/','web/Loyfrequency/frequencyDayEdit'); //修改F周期
Route::rule('webLoyfrequencyLook/','web/Loyfrequency/lookPeople');          //会员
Route::rule('webLoyfrequencySmsSend/','web/Loyfrequency/smsSend');          //批量短信

/**
 * CRM---会员管理---M金额 
 */
Route::rule('webLoymoney/','web/Loymoney/index');               //查询M金额表
Route::rule('webLoymoneyEdit/','web/Loymoney/moneyEdit');       //修改M指标区间
Route::rule('webLoymoneyDayEdit/','web/Loymoney/moneyDayEdit'); //修改M周期
Route::rule('webLoymoneyLook/','web/Loymoney/lookPeople');      //会员
Route::rule('webLoymoneySmsSend/','web/Loymoney/smsSend');      //批量短信

/**
 * CRM---会员管理---I转介数 
 */
Route::rule('webLoyreferral/','web/Loyreferral/index');                  //查询I转介绍数表
Route::rule('webLoyreferralEdit/','web/Loyreferral/referralEdit');       //修改I指标区间
Route::rule('webLoyreferralDayEdit/','web/Loyreferral/referralDayEdit'); //修改I周期
Route::rule('webLoyreferralLook/','web/Loyreferral/lookPeople');         //会员
Route::rule('webLoyreferralSmsSend/','web/Loyreferral/smsSend');         //批量短信

/**
 * CRM---会员管理---N件数 
 */
Route::rule('webLoynumber/','web/Loynumber/index');                //查询N件数表
Route::rule('webLoynumberEdit/','web/Loynumber/numberEdit');       //修改N指标区间
Route::rule('webLoynumberDayEdit/','web/Loynumber/numberDayEdit'); //修改N周期
Route::rule('webLoynumberLook/','web/Loynumber/lookPeople');       //会员
Route::rule('webLoynumberSmsSend/','web/Loynumber/smsSend');       //批量短信

/**
 * CRM---会员管理---P客单价 
 */
Route::rule('webLoypassenger/','web/Loypassenger/index');                   //查P客单价表
Route::rule('webLoypassengerEdit/','web/Loypassenger/passengerEdit');       //修改P指标区间
Route::rule('webLoypassengerDayEdit/','web/Loypassenger/passengerDayEdit'); //修改P周期
Route::rule('webLoypassengerLook/','web/Loypassenger/lookPeople');          //会员
Route::rule('webLoypassengerSmsSend/','web/Loypassenger/smsSend');          //批量短信

/**
 * CRM---会员管理---A件数 
 */
Route::rule('webLoypiece/','web/Loypiece/index');               //查A件数表
Route::rule('webLoypieceEdit/','web/Loypiece/pieceEdit');       //修改A指标区间
Route::rule('webLoypieceDayEdit/','web/Loypiece/pieceDayEdit'); //修改A周期
Route::rule('webLoypieceLook/','web/Loypiece/lookPeople');      //会员
Route::rule('webLoypieceSmsSend/','web/Loypiece/smsSend');      //批量短信

/**
 * CRM---会员管理---J连带率 
 */
Route::rule('webLoyrate/','web/Loyrate/index');               //查J连带率表
Route::rule('webLoyrateEdit/','web/Loyrate/rateEdit');        //修改J指标区间
Route::rule('webLoyrateDayEdit/','web/Loyrate/rateDayEdit');  //修改J周期
Route::rule('webLoyrateLook/','web/Loyrate/lookPeople');      //会员
Route::rule('webLoyrateSmsSend/','web/Loyrate/smsSend');      //批量短信

/**
 * CRM---会员管理---C年消费 
 */
Route::rule('webLoyannual/','web/Loyannual/index');                 //查C年消费表
Route::rule('webLoyannualEdit/','web/Loyannual/annualEdit');        //修改C指标区间
Route::rule('webLoyannualDayEdit/','web/Loyannual/annualDayEdit');  //修改C周期
Route::rule('webLoyannualLook/','web/Loyannual/lookPeople');        //查询会员人数
Route::rule('webLoyannualSmsSend/','web/Loyannual/smsSend');        //批量短信


/**
  * CRM---互动管理---100天跟进 
  */
Route::rule('webInhundredSel/','web/Inhundred/hundredSel');               //查询100天跟进表
Route::rule('webInhundredDillSel/','web/Inhundred/hundredDillSel');       //点击查看按单号查询100天跟进小表
Route::rule('webInhundredAdd/','web/Inhundred/hundredAdd');               //100天跟进大表添加也添加互动小表
Route::rule('webInhundredEdit/','web/Inhundred/hundredEdit');             //修改互动名称与组织机构
Route::rule('webInhundredDel/','web/Inhundred/hundredDel');               //删除100天跟进大表也删除中的小表
Route::rule('webInhundredDelAll/','web/Inhundred/hundredDelAll');         //批量删除100天跟进大表也删除小表

Route::rule('webInhundredCacheSel/','web/Inhundred/hundredCacheSel');     //查询缓存数据写入表
Route::rule('webInhundredCacheAdd/','web/Inhundred/hundredCacheAdd');     //新建一条一条添加到緩存中
Route::rule('webInhundredCacheDel/','web/Inhundred/hundredCacheDel');     //从缓存中删除
Route::rule('webInhundredCacheClean/','web/Inhundred/hundredCacheClean'); //清除缓存
Route::rule('webInhundredEditSmall/','web/Inhundred/hundredRditSmall');   //修改小跟进


/**
 * CRM---互动管理---365天跟进 
 */
Route::rule('webInthdaysSel/','web/Inthdays/thdaysSel');               //查询365天跟进表
Route::rule('webInthdaysDillSel/','web/Inthdays/thdaysDillSel');       //点击查看按单号查询365天跟进小表
Route::rule('webInthdaysAdd/','web/Inthdays/thdaysAdd');               //365天跟进大表添加也添加互动小表
Route::rule('webInthdaysDel/','web/Inthdays/thdaysDel');               //删除365天跟进大表也删除中的小表
Route::rule('webInthdaysDelAll/','web/Inthdays/thdaysDelAll');         //批量删除365天跟进大表也删除中的小表
Route::rule('webInthdaysEdit/','web/Inthdays/thdaysEdit');             //修改互动名称与组织机构
Route::rule('webInthdaysTime/','web/Inthdays/thdaysTime');             //365天跟进表赋值时间

Route::rule('webInthdaysCacheSel/','web/Inthdays/thdaysCacheSel');     //查询缓存数据写入表
Route::rule('webInthdaysFestival/','web/Inthdays/festival');           //选择日子
Route::rule('webInthdaysCacheAdd/','web/Inthdays/thdaysCacheAdd');     //新建一条一条添加到緩存中
Route::rule('webInthdaysCacheDel/','web/Inthdays/thdaysCacheDel');     //从缓存中删除
Route::rule('webInthdaysCacheClean/','web/Inthdays/thdaysCacheClean'); //清除缓存

/**
  * CRM---互动管理---专场跟进 
  */
Route::rule('webInfieldSel/','web/Infield/fieldSel');               //查询专场跟进
Route::rule('webInfieldDillSel/','web/Infield/fieldDillSel');       //点击查看按单号查询专场跟进小表
Route::rule('webInfieldAdd/','web/Infield/fieldAdd');               //专场跟进大表添加也添加互动小表
Route::rule('webInfieldDel/','web/Infield/fieldDel');               //删除专场跟进大表也删除中的小表
Route::rule('webInfieldDelAll/','web/Infield/fieldDelAll');         //批量删除专场跟进大表也删除小表
Route::rule('webInfieldEdit/','web/Infield/fieldEdit');             //修改互动名称与组织机构

Route::rule('webInfieldCacheSel/','web/Infield/fieldCacheSel');     //查询缓存数据写入表
Route::rule('webInfieldEditSmall/','web/Infield/fieldEditSmall');   //修改小跟进
Route::rule('webInfieldCacheAdd/','web/Infield/fieldCacheAdd');     //新建一条一条添加到緩存中
Route::rule('webInfieldCacheDel/','web/Infield/fieldCacheDel');     //从缓存中删除
Route::rule('webInfieldCacheClean/','web/Infield/fieldCacheClean'); //清除缓存


/**
 * STM---门店管理---门店列表
 */
Route::rule('webStoreSel/','web/Store/storeSel');          //门店查表
Route::rule('webStoreAdd/','web/Store/storeAdd');          //门店添加
Route::rule('webStoreDel/','web/Store/delStore');          //门店删除
Route::rule('webStoreDelAll/','web/Store/delStoreAll');    //门店批量删除
Route::rule('webStoreEdit/','web/Store/editStore');        //门店修改
Route::rule('webCategoryList/','web/Store/categoryList');  //组织结构下拉框信息


/**
 * STM---员工管理---员工职位管理 
 */
Route::rule('webSfpositionSel/','web/Sfposition/positionSel');        //员工职位管理查询表
Route::rule('webSfpositionAdd/','web/Sfposition/addPosition');        //员工职位管理添加
Route::rule('webSfpositionDel/','web/Sfposition/delPosition');        //员工职位管理删除
Route::rule('webSfpositionDelAll/','web/Sfposition/delPositionAll');  //员工职位管理批量删除
Route::rule('webSfpositionEdit/','web/Sfposition/editPosition');      //员工职位管理修改


/**
 * STM---员工管理---员工列表
 */
Route::rule('webSfpersonnelSel/','web/Sfpersonnel/personnelSel');        //员工列表查询表
Route::rule('webSfpersonnelAdd/','web/Sfpersonnel/addPersonnel');        //员工列表员工添加
Route::rule('webSfpersonnelAddManage/','web/Sfpersonnel/addManage');     //员工列表管理添加
Route::rule('webSfpersonnelDel/','web/Sfpersonnel/delPersonnel');        //员工列表删除
Route::rule('webSfpersonnelDelAll/','web/Sfpersonnel/delPersonnelAll');  //员工列表批量删除
Route::rule('webSfpersonnelEdit/','web/Sfpersonnel/editPersonnel');      //员工列表修改
Route::rule('webSfpersonnelResetPass/','web/Sfpersonnel/resetPass');     //员工列表修改密码
Route::rule('webSfpersonnelPcAuthority/','web/Sfpersonnel/pcAuthority'); //员工列表查询PC权限复选框内容
Route::rule('webSfpersonnelMAuthority/','web/Sfpersonnel/mAuthority');   //员工列表查询手机端权限复选框内容
Route::rule('webSfpersonnelStoreSel/','web/Sfpersonnel/storeSel');       //员工列表查询门店下拉框内容
Route::rule('webSfpersonnelStaffTree/','web/Sfpersonnel/staffTree');     //员工列表查询门店下拉框内容

Route::rule('webSfpersonnelStaffSel/','web/Sfpersonnel/staffSel');       //查询员工下拉框内容
Route::rule('webSfpersonnelPcReplace/','web/Sfpersonnel/pcReplace');     //修改PC端状态
Route::rule('webSfpersonnelMReplace/','web/Sfpersonnel/mReplace');       //修改手机端状态

/**
 * STM---员工管理---员工提成记录
 */
Route::rule('webCommissionSel/', 'web/Commission/commissionSel');    //查询提成记录

/**
 * VIP---vip流程管理---返单计划营销
 */
Route::rule('webReturnProgramSel/','web/ReturnProgram/programSel');               //返单计划营销查询表
Route::rule('webReturnProgramReplace/','web/ReturnProgram/replace');              //返单计划营销修改审核状态
Route::rule('webReturnProgramReplaceC/','web/ReturnProgram/replaceConsumption');  //返单计划营销修改是否消费
Route::rule('webReturnProgramReplaceGS/','web/ReturnProgram/replaceGoShop');      //返单计划营销修改是否到店
Route::rule('webReturnProgramDel/','web/ReturnProgram/programDel');               //删除返单计划营销也删除中的小返单计划
Route::rule('webReturnProgramEdit/','web/ReturnProgram/programEdit');             //修改返单计划营销
Route::rule('webReturnProgramDelAll/','web/ReturnProgram/programDelAll');         //批量删除返单计划营销也删除中小返单计划营销

Route::rule('webReturnProgramDillDel/','web/ReturnProgram/programDillDel');       //返单计划营销中的小返单删除
Route::rule('webReturnProgramDillEdit/','web/ReturnProgram/programDillEdit');     //返单计划营销中的小返单修改

/**
 * VIP---vip流程管理---100天跟进
 */
Route::rule('webComeBackSel/','web/ComeBack/backSel');                //查询100天跟进列表
Route::rule('webComeBackEdit/','web/ComeBack/backEdit');              //修改大100天跟进营销
Route::rule('webComeBackDel/','web/ComeBack/backDel');                //删除100天跟进营销
Route::rule('webComeBackDelAll/','web/ComeBack/backDelAll');          //批量删除100天跟进营销
Route::rule('webComeBackStatus/','web/ComeBack/backStatus');          //修改互动状态
Route::rule('webComeBackDillStatus/','web/ComeBack/backDillStatus');  //修改小100天跟进审核状态
Route::rule('webComeBackDillEdit/','web/ComeBack/backDillEdit');      //修改100天跟进营销中的小回头
Route::rule('webComeBackDillDel/','web/ComeBack/backDillDel');        //删除100天跟进营销中的小回头

/**
 * VIP---vip流程管理---365天跟进
 */
Route::rule('webRthreeSel/','web/Rthree/threeSel');                //查询365天跟进列表
Route::rule('webRthreeEdit/','web/Rthree/threeEdit');              //修改大365天跟进营销
Route::rule('webRthreeDel/','web/Rthree/threeDel');                //删除365天跟进营销
Route::rule('webRthreeDelAll/','web/Rthree/threeDelAll');          //批量删除365天跟进营销
Route::rule('webRthreeStatus/','web/Rthree/threeStatus');          //修改互动状态
Route::rule('webRthreeDillStatus/','web/Rthree/threeDillStatus');  //修改小跟进审核状态
Route::rule('webRthreeDillEdit/','web/Rthree/threeDillEdit');      //修改365天跟进营销中的小365

/**
 * VIP---vip流程管理---专场跟进
 */
Route::rule('webRfieldSel/','web/Rfield/fieldSel');                //查询专场跟进互动列表
Route::rule('webRfieldEdit/','web/Rfield/fieldEdit');              //修改大专场跟进互动营销
Route::rule('webRfieldDel/','web/Rfield/fieldDel');                //删除专场跟进互动营销
Route::rule('webRfieldDelAll/','web/Rfield/fieldDelAll');          //批量删除专场跟进互动营销
Route::rule('webRfieldStatus/','web/Rfield/fieldStatus');          //修改互动状态
Route::rule('webRfieldDillStatus/','web/Rfield/fieldDillStatus');  //修改小跟进审核状态
Route::rule('webRfieldDillEdit/','web/Rfield/fieldDillEdit');      //修改专场跟进互动营销中的小专场跟进

/**
 * VIP---vip流程管理---今日100天跟进
 */
Route::rule('webDayBackSel/','web/DayBack/dayBackSel');  //查询100天跟进列表

/**
 * VIP---vip流程管理---今日365天跟进
 */
Route::rule('webDayThreeSel/','web/DayThree/dayThreeSel');    //查询今日365天跟进列表 
Route::rule('webDayThreeDill/','web/DayThree/dayThreeDill');  //按单号查询今日365天小跟进列表 

/**
 * VIP---vip流程管理---今日专场跟进
 */
Route::rule('webDayFieldSel/','web/DayField/dayFieldSel');    //查询今日专场跟进列表 
Route::rule('webDayFieldDill/','web/DayField/dayFieldDill');  //按单号查询今日专场小跟进列表 

/**
 * DAT---数据中心---互动报表
 */
/**返单计划报表 */
Route::rule('webPlanDat/','web/PlanDat/index');  //查表
Route::rule('webPlanstore/','web/PlanDat/planstore');  //查询门店
Route::rule('webPlanstaff/','web/PlanDat/planstaff');  //查询员工

/**返单互动报表 */
Route::rule('webReturnDat/','web/ReturnDat/index');  //查表

/**100天跟进报表 */
Route::rule('webHeadDat/','web/HeadDat/index');  //查表

/**365天跟进报表 */
Route::rule('webRthreeDat/','web/RthreeDat/index');  //查表