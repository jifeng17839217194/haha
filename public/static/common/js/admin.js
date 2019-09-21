/**
 * 后台通用表单控制器
 * 前置：引入JQ
 * @type {Object}
 */
var thisApp = {
    ajaxForm: function() {
        //普通表达转ajax
        $( 'form.ajaxForm' ).submit( function() {
            var thisobj=$(this);
            var showResponse = function( rs ) {
                layer.close( window.thisApplayerload );
                if((typeof thisobj.attr("successfun")) !="undefined")//自定义回调
                {
                    eval(thisobj.attr("successfun"))(rs) ;
                    return;
                }
                thisApp.afterAjax( rs );
                if ( rs.code == 0 ) {
                    $( "img[autoRefresh='true']" ).click();
                    $( "input[onErrorAutoClear='true']" ).val( '' );
                }
            };
            var showRequest = function() {
                window.thisApplayerload = layer.load();
                if(typeof window.beforeSubmit =="function")
                {
                    window.beforeSubmit();
                }
            };
            $( this ).ajaxSubmit( {
                //target: '#output', //把服务器返回的内容放入id为output的元素中
                beforeSubmit: showRequest, //提交前的回调函数
                success: showResponse, //提交后的回调函数
                error: function( error ) { 
                    //layer.close( window.loading );
                    layer.closeAll();
                    alert( JSON.stringify(error) ); 
                },
                //url: url,                 //默认是form的action， 如果申明，则会覆盖
                //type: type,               //默认是form的method（get or post），如果申明，则会覆盖
                dataType: "json", //html(默认), xml, script, json...接受服务端返回的类型
                //clearForm: true,          //成功提交后，清除所有表单元素的值
                //resetForm: true,          //成功提交后，重置所有表单元素的值
                timeout: 1000*120 //限制请求的时间，当请求大于30秒后，跳出请求
            } );
            return false; //阻止表单默认提交
        } );
    },
    //表达返回
    //{"code":0,"message":"用户名不可为空","data":"","url":"","wait":3}
    //
    afterAjax: function( JsonObj ) {
        if ( JsonObj.wait ) //自动关闭的
        {
            if ( JsonObj.message ) //消息确认
            {
                layer.msg( JsonObj.message );
            } else {
                parent.window.location.href = JsonObj.url.replace(/&amp;/,"&") || '#';
                return false;
            }
            if ( JsonObj.url ) //url
            {
                setTimeout( function() {
                    if(JsonObj.url.indexOf("=self"))//当前窗口刷新
                    {
                        window.location.href = JsonObj.url.replace(/&amp;/,"&") || '#';
                    }
                    else
                    {
                    }
                }, JsonObj.wait && JsonObj.wait * 1000 || 0 );
            }
        } else //长时间等待
        {
            if ( JsonObj.message) //消息确认
            {
                window.systemAlert=layer.alert( JsonObj.message, function() {
                    if ( JsonObj.url ) //url
                    {
                        if(JsonObj.url.indexOf("=self"))//当前窗口刷新
                        {
                            window.location.href = JsonObj.url.replace(/&amp;/,"&") || '#';
                        }
                        else
                        {
                        }
                    }
                    layer.close(window.systemAlert);
                } );
            }else if(JsonObj.msg){
                window.systemAlert=layer.alert( JsonObj.msg, function() {
                    if ( JsonObj.url ) //url
                    {
                        if(JsonObj.url.indexOf("=self"))//当前窗口刷新
                        {
                            window.location.href = JsonObj.url.replace(/&amp;/,"&") || '#';
                        }
                        else
                        {
                        }
                    }
                    layer.close(window.systemAlert);
                } );
            } else {
                parent.window.location.href = JsonObj.url.replace(/&amp;/,"&") || '#';
            }
        }
    },
    ajaxButton: function() {
        $( document ).on( "click", "[ajax*='{']", function() {
            //{'confirm':'确定要退出吗？','url':'{:url('index/logout')}'}
            //{"confirm":"删除吗？","data":"menu_id=123","url":"http:/www.baidu.com","prompt":"text/password/textarea"}
            //
            // data-form 某表单的所有数据
            //{"confirm":"删除吗？","data":"menu_id=123","data-form":"#form2","url":"http:/www.baidu.com","prompt":"text/password/textarea"}
            //<a href=":;" ajax='{"window":"iframe","width":"500/auto","height":"400/auto","url":"http:/www.baidu.com"}' title="这是标题/非必须">button</a>
            //新增支持2017-9-22 <a href="http://www.baidu.com" title="窗口标题" ajax='{"window":"iframe"}'>文字</a>
            var attrJSON = $( this ).attr( "ajax" );

            if ( attrJSON ) {
                //规则提取
                var attrJSONObj = JSON.parse( attrJSON );
                //__规则提取
                //post function
                var doStep2 = function( _this ) {
                    attrJSONObj.url= attrJSONObj.url || _this.attr( "href" );
                    if ( attrJSONObj.window ) {
                        width = attrJSONObj.width || "auto";
                        height = attrJSONObj.height || "auto";
                        window.poplayerobj = top.layer.open( {
                            type: 2,
                            title: _this.attr( "title" ) || false,
                            area: [ width == "auto" ? "96%" : ( isNaN( width ) ? width : width + "px" ), height == "auto" ? "94%" : ( isNaN( height ) ? height : height + "px" ) ], //注意iframe 不支持auto
                            border: [ 2, 0.3, '#000' ],
                            shade: [ 0.5, '#000' ],
                            shift: 1,
                            closeBtn: 1,
                            shadeClose: true,
                            content: attrJSONObj.url
                        } );
                        return false;
                    }

                    if ( attrJSONObj.prompt ) //如果有要求弹出prompt
                    {
                        var formType = 0;
                        if ( attrJSONObj.prompt == "password" ) formType = 1;
                        if ( attrJSONObj.prompt == "textarea" ) formType = 2;
                        layer.prompt( {
                            formType: formType,
                            title: _this.attr( "title" ) || "请输入",
                            value: _this.attr( "value" ) || "",
                            //area: [ '800px', '350px' ] //自定义文本域宽高
                        }, function( value, index, elem ) {
                            layer.close( index );
                            window.thisApplayerload = layer.load();
                            doStep3( attrJSONObj.url, ( attrJSONObj.data || '' ) + "&"+ (_this.attr( "name" ) || "promptvalue") +"=" + value );
                            //layer.close( index );
                        } );
                    } else {
                        window.thisApplayerload = layer.load();
                        doStep3( attrJSONObj.url, attrJSONObj.data || '' );
                    }
                };
                //__post function
                var doStep3 = function( url, data ) {
                    $.ajax( {
                        type: "POST",
                        timeout:1000*120,//120秒超时
                        url: url,
                        data: ( attrJSONObj[ "data-form" ] ? $( attrJSONObj[ "data-form" ] ).serialize() + "&" : "" ) + data,
                        success: function( rs ) {
                            layer.close( window.thisApplayerload );
                            thisApp.afterAjax( rs );
                        }
                    } );
                }
                //有操作确认
                if ( attrJSONObj.confirm ) {
                    layer.confirm( attrJSONObj.confirm, {
                        btn: [ '确定', '取消' ] //按钮
                    }, function() {
                        doStep2( $( this ) );
                    }, function() {
                    } );
                } else
                //没有操作确认
                {
                    doStep2( $( this ) );
                }
            }
            event.preventDefault();
        } )
    },
    selectByChecked: function( thisJqobj, targetInputName ) { //使用input:Checked控制全选/反选，限当前表单内
        thisJqobj.parents( "form" ).find( ":checkbox[name='" + targetInputName + "[]']" ).prop( 'checked', thisJqobj.prop( 'checked' ) ); //prop jq1.7新增
    },
    //按钮绑定上传事件
    //<script type="text/javascript" src="/static/common/js/uploadify/jquery.uploadifive.min.js"></script>
    //<link rel="stylesheet" type="text/css" href="/static/common/js/uploadify/uploadifive.css">
    //param:id->按钮ID(仅限ID)
    //param:call_fun(JqObj,imgSrc)->回调函数(按钮的JQ对象,图片服务器地址)
    buttonBindUploadPlus:function(uploadfileFun,domID,call_fun){
        var thisObj =$("#"+(domID.replace("#","")));
        if(thisObj.prop("uploadifive")!="true")//防止重复
        {
            if(typeof thisObj.uploadifive =="undefined")
            {
                layer.alert("该页面使用了buttonBindUploadPlus<br />但未引入uploadifive.min.js、uploadifive.css");
                return false;
            }
            thisObj.uploadifive({
                'buttonText': '上传',
                'uploadScript': uploadfileFun,
                'fileType': '*.gif; *.jpg; *.png; *.xml; *.xlsx',
                'fileSizeLimit': '50MB',
                'multi': false,
                'onUploadComplete': function(file, data) {
                    var rs = JSON.parse(data);
                    if (rs.code == 0) {
                        layer.alert(rs.message);
                        setTimeout(function() {
                            $(".uploadifive-queue").hide();
                        }, 2000);
                    } else {
                        //图片预览
                        $(".uploadifive-queue").hide();
                        $("a[data-rel='colorbox']").colorbox();
                        if(typeof call_fun =="function")
                        {
                            call_fun(thisObj,rs.data);
                        }
                    }
                }
            });
            thisObj.prop("uploadifive","true");
        }
    },
    //文件异步上传
    //file change 触发此动作
    doUploadFile: function( inputJqObj, fieldJqObj, posturl ) {
        // var from = $( "<form name='from12' enctype='multipart/form-data' method='post' ></form>" );
        // var filename = "fileinput" + (new Date().valueOf());
        // from.append( fieldJqObj.clone().attr("name",filename) );
        // from.append($("<input name='filename' type='hidden' value='"+ filename +"'>"));
        // from.ajaxSubmit( {
        //     //提交前的回调函数
        //     beforeSubmit: function() {
        //         window.loading = layer.load( 2 );
        //         layer.msg( "上传中" );
        //         //$(thisJqobj).val('上传中,请稍等');
        //     },
        //     //提交后的回调函数
        //     success: function( rs ) {
        //         layer.close( window.loading );
        //         if ( rs.code == 1 ) {
        //             $( inputJqObj ).val( rs.data );
        //         } else {
        //             layer.msg( rs.message );
        //         }
        //     },
        //     error: function( error ) { 
        //         layer.close( window.loading );
        //         alert( JSON.stringify(error) ); 
        //     },
        //     url: posturl,
        //     /*设置post提交到的页面*/
        //     type: "post",
        //     /*设置表单以post方法提交*/
        //     dataType: "json" /*设置返回值类型为文本*/
        // } );
    },
}
$( document ).ready( function( e ) {
    thisApp.ajaxForm();
    thisApp.ajaxButton();
} )
// Example:
// alert( readCookie("myCookie") );
//加法函数
function accAdd( arg1, arg2 ) {
    var r1, r2, m;
    try {
        r1 = arg1.toString().split( "." )[ 1 ].length;
    } catch ( e ) {
        r1 = 0;
    }
    try {
        r2 = arg2.toString().split( "." )[ 1 ].length;
    } catch ( e ) {
        r2 = 0;
    }
    m = Math.pow( 10, Math.max( r1, r2 ) );
    return ( arg1 * m + arg2 * m ) / m;
}
//给Number类型增加一个add方法，，使用时直接用 .add 即可完成计算。
Number.prototype.add = function( arg ) {
    return accAdd( arg, this );
};
//减法函数
function Subtr( arg1, arg2 ) {
    var r1, r2, m, n;
    try {
        r1 = arg1.toString().split( "." )[ 1 ].length;
    } catch ( e ) {
        r1 = 0;
    }
    try {
        r2 = arg2.toString().split( "." )[ 1 ].length;
    } catch ( e ) {
        r2 = 0;
    }
    m = Math.pow( 10, Math.max( r1, r2 ) );
    //last modify by deeka
    //动态控制精度长度
    n = ( r1 >= r2 ) ? r1 : r2;
    return ( ( arg1 * m - arg2 * m ) / m ).toFixed( n );
}
//给Number类型增加一个add方法，，使用时直接用 .sub 即可完成计算。
Number.prototype.sub = function( arg ) {
    return Subtr( this, arg );
};
//乘法函数
function accMul( arg1, arg2 ) {
    var m = 0,
        s1 = arg1.toString(),
        s2 = arg2.toString();
    try {
        m += s1.split( "." )[ 1 ].length;
    } catch ( e ) {}
    try {
        m += s2.split( "." )[ 1 ].length;
    } catch ( e ) {}
    return Number( s1.replace( ".", "" ) ) * Number( s2.replace( ".", "" ) ) / Math.pow( 10, m );
}
//给Number类型增加一个mul方法，使用时直接用 .mul 即可完成计算。
Number.prototype.mul = function( arg ) {
    return accMul( arg, this );
};
//除法函数
function accDiv( arg1, arg2 ) {
    var t1 = 0,
        t2 = 0,
        r1, r2;
    try {
        t1 = arg1.toString().split( "." )[ 1 ].length;
    } catch ( e ) {}
    try {
        t2 = arg2.toString().split( "." )[ 1 ].length;
    } catch ( e ) {}
    with( Math ) {
        r1 = Number( arg1.toString().replace( ".", "" ) );
        r2 = Number( arg2.toString().replace( ".", "" ) );
        return ( r1 / r2 ) * pow( 10, t2 - t1 );
    }
}
//给Number类型增加一个div方法，，使用时直接用 .div 即可完成计算。
Number.prototype.div = function( arg ) {
    return accDiv( this, arg );
};