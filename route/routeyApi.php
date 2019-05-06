<?php

/**
 * 数据对接接口
 */
Route::rule('api_only_data_insert/','api/OnlyData/insert');
Route::rule('api_only_data_update/','api/OnlyData/update');
Route::rule('api_only_data_delete/','api/OnlyData/delete');

/**
 * 使用销售接口 采用部分权益
 */
Route::rule('api_sale/','api/Sale/sale');

/**
 * 采集使用接口
 */
Route::rule('api_collect/','api/Collect/index');