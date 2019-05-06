/*
#### **cascader参数说明**
> + elem：input容器
> + data：需要的静态数据，类型为数组，
> + url：异步获取的数据，类型为数组，（data与url两个参数二选一）
> + type：异步获取的方式，默认get，可省略
> + where：异步传入的参数，可省略
> + triggerType：触发方式，不填或其他都为click，可选参数"change"，即鼠标移入触发
> + showLastLevels：输入框是否只显示最后一级,默认false，即全显示
> + name：传入的初始值，类型为数组，值为data的name值
> + success：回调函数，选择完成之后的回调函数，返回值为value数组
*/
layui.define(["jquery","laytpl","layer"], function (exports) {
    var $ = layui.jquery;
    var laytpl = layui.laytpl;
    var layer = layui.layer;
    var zIndex=3000;    // 共用一个层级
    
    function Cascader(option) {
        this.option=option;     // 获取传入的数据
        this.domContent="";     // content节点
        this.textArr=[];        // 最终的text数组
        this.textStr="";        // 最终的text
        this.valueArr=[];        // 最终的value数组
        this.onOff=false;       // 是否显示
        this.positionArr=[];    // 当前点击的面板在数据中的下标位置
        this.blockData={};      // 当前点击的当前面板的数据
        this.count=0;           // 进入finishInitData的次数

        this.initOption();
    }

    Cascader.prototype={
        constructor: Cascader,
        // 初始化参数数据
        initOption: function () {
            var self=this;
            self.option.elem?(function(){
                self.elem=self.option.elem;
            })():(function() {
                throw "缺少elem节点选择器";
            })();

            self.triggerType=self.option.triggerType==="change"?"mouseenter":"click";

            // 判断data参数
            if(self.option.data){
                self.d=self.option.data;
                self.callback();
                return;
            }
            // 判断url参数
            if(self.option.url){
                $.ajax({
                    url: self.option.url,
                    type: self.option.type?self.option.type:"get",
                    data: self.option.where?self.option.where:{},
                    success: function(data){
                        if(data.Code===0){
                            self.d=data.Data;
                            self.callback();
                            return;
                        }
                        layer.alert(data.Msg, { title: "选择器"+self.elem+"获取数据失败", icon: 2 });
                    }
                });
                return;
            }
            throw "选择器"+self.elem+"缺少data或url参数";
        },
        // 初始化容器和标签
        init: function () {
            $(this.elem).after('<i class="layui-icon layui-icon-down"></i>');
            $(this.elem).after('<div class="urp-cascader-content"></div>');
        },
        // 初始化第一层
        initFirst: function () {
            var string =  laytpl(
                '<ul class="urp-cascader-child" style="display: inline-block;">'+
                    '{{# for(var i=0;i<d.length;i++){ }}'+
                        '<li>{{ d[i].name }}<i class="layui-icon layui-icon-right" ></i></li>'+
                    '{{# } }}'+
                '</ul>'
            ).render(this.d);
            $(this.elem).siblings(".urp-cascader-content").append(string);
            this.domContent=$(this.elem).siblings(".urp-cascader-content");
            this.domContent.find(".urp-cascader-child").hide();

            // 显示隐藏第一层的标签
            for(var i=0;i<this.d.length;i++){
                ("area" in this.d[i])?(
                    this.domContent.find("ul.urp-cascader-child li").eq(i).find("i").show()
                ):(
                    this.domContent.find("ul.urp-cascader-child li").eq(i).find("i").hide()
                );
            }
        },
        // 获取当前点击的当前面板的数据
        getBlockData: function (event,el) {
            event.stopPropagation();
            this.floor=$(el).parent().index();     // 当前点击的是第几层
            var index=$(el).index();              // 当前点击的是这一层的第几个

            this.positionArr.length=this.floor;
            this.positionArr.push(index);
            
            // 等同下方注释
            this.blockData = this.d[this.positionArr[0]];
            for(var i = 1; i<=this.floor; i++){
                this.blockData = this.blockData["area"][this.positionArr[i]];
            }

        },
        // 若有第二层则初始化第二层
        initChild: function () {
            // 删除后面的面板
            this.domContent.find(".urp-cascader-child:gt("+(this.floor)+")").remove();
            // 获取text值
            this.textArr.length=this.floor;
            this.textArr.push(this.blockData.name);
            this.valueArr.length=this.floor;
            this.valueArr.push(this.blockData.name);
            var string =  laytpl(
                '<ul class="urp-cascader-child">'+
                    '{{# for(var i=0;i< d.length;i++){ }}'+
                        '<li>{{ d[i].name }}<i class="layui-icon layui-icon-right"></i></li>'+
                    '{{# } }}'+
                '</ul>'
            ).render(this.blockData["area"]);
            this.domContent.append(string);

            // 显示隐藏第二层的标签
            for(var i=0;i<this.blockData["area"].length;i++){
                ("area" in this.blockData["area"][i])?(
                    this.domContent.find("ul.urp-cascader-child:gt("+(this.floor)+")").find("li").eq(i).find("i").show()
                ):(
                    this.domContent.find("ul.urp-cascader-child:gt("+(this.floor)+")").find("li").eq(i).find("i").hide()
                );
            }
        },
        // 结束之后拿取数据
        finishInitData: function () {
            this.domContent.find(".urp-cascader-child:gt("+(this.floor)+")").remove();
            
            this.textArr.length=this.floor;
            this.textArr.push(this.blockData.name);
            this.valueArr.length=this.floor;
            this.valueArr.push(this.blockData.name);
            // 文本拼接
            this.textStr=this.textArr.join("/");

            (this.option.showLastLevels)?(
                $(this.elem).val(this.textArr[this.textArr.length-1])
            ):(
                $(this.elem).val(this.textStr)
            );
            
            this.onOff = false;
            $(this.elem).siblings(".urp-cascader-content").find("ul").slideUp(100);
            $(this.elem).siblings("i").replaceWith('<i class="layui-icon layui-icon-down"></i>');

            // 如果有初始值，则第一次不回调
            this.count++;
            if(this.option.name && this.count===1 && this.option.success){
                return;
            }
            if(this.option.success) this.option.success(this.valueArr);
        },
        // 赋初值
        initValue: function() {
            var self=this;
            this.option.name?(function(){
                var value=self.option.name;
                $(self.elem).trigger("click");

                var arrr=[];    // 保存当前在data中的位置
                var data=self.d;     // 需要遍历的子数组
                // 等同于下面的注释
                
                value.forEach(function(val,index){
                    // console.log(data);
                    if(!data) throw "选择器"+self.elem+"初始化数据不匹配";
                    for(var i=0;i<data.length;i++){
                        if(data[i].name==val){
                            arrr.push(i);
                            self.domContent.find(".urp-cascader-child").eq(index).find("li").eq(i).trigger(self.triggerType,"initValue");
                            $(self.elem).siblings(".urp-cascader-content").find("ul").finish();      // 停止当前正在运行的动画
                        }
                    }
                    // 先判断数据是否存在，即是否有相匹配的数据
                    data[arrr[index]]?(function(){
                        data=data[arrr[index]].area;
                    })():(function(){
                        throw "选择器"+self.elem+"初始化数据不匹配";
                    })()
                })
            })():"";
        },
        callback: function () {
            // 初始化第一层
            this.init();
            this.initFirst();
            var self=this;      // Cascader对象
            // 每层点击时绑定事件
            self.domContent.on(self.triggerType,".urp-cascader-child li",function(event,triggerData){
                var _self=this;     // 点击的对象
                self.getBlockData(event,this);
                $(this).addClass("active").siblings("li").removeClass("active");
                // 判断当前是否存在子层
                ("area" in self.blockData)?(
                    // 初始化子层
                    self.initChild()
                ):(
                    // 判断触发方式
                    self.triggerType==="mouseenter"?(function() {
                        self.domContent.find(".urp-cascader-child:gt("+(self.floor)+")").remove();
                        // click事件先解除再定义，防止多次定义
                        $(_self).off("click").on("click",function() {
                            self.finishInitData();
                        })

                        // 赋初值时若为change则需要触发上方函数(判断是否是通过赋初值方式触发)
                        if(triggerData==="initValue"){
                            $(_self).trigger("click");
                        }
                    })():(
                        self.finishInitData()
                    )
                );
            })
                
            // input点击显示隐藏
            $(self.elem).on("click", function (event) {
                self.onOff = !self.onOff;
                event.stopPropagation();
                zIndex++;
                if (self.onOff) {
                    $(self.elem).siblings(".urp-cascader-content").find("ul").slideDown(100);
                    $(self.elem).siblings("i").replaceWith('<i class="layui-icon layui-icon-up"></i>');

                    self.domContent.css("zIndex",zIndex);
                } else {
                    $(self.elem).siblings(".urp-cascader-content").find("ul").slideUp(100);
                    $(self.elem).siblings("i").replaceWith('<i class="layui-icon layui-icon-down"></i>');
                }
            })
            // 点击外层文档隐藏
            $(document).on("click",function() {
                self.onOff = false;
                if(!self.onOff){
                    $(self.elem).siblings(".urp-cascader-content").find("ul").slideUp(100);
                    $(self.elem).siblings("i").replaceWith('<i class="layui-icon layui-icon-down"></i>');
                }
            })
            self.initValue();
        }
    }
    
    exports('cascader', function(option) {
        new Cascader(option);
    });
}).link(layui.setter.base + 'lib/extend/cascader.css','cascader');