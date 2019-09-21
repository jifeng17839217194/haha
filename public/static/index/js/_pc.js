/**
 * PC收银统一JS
 * @type {Object}
 */
var thisFrontApp = {
    init: function() {
        thisFrontApp.checkandlogin();
        thisFrontApp.goeasy.init();


        //初始化声音通知文件
        var oScript = document.createElement("script");
        oScript.type = "text/javascript";
        oScript.src = "/static/index/assets/js/soundmanager2-jsmin.js";
        //document.getElementsByTagName('HEAD').item(0).appendChild(oScript);
        $('head').append(oScript);
        window.soundPlayList = [];
        window.soundPlayIng = 0;
        //__初始化声音通知文件
    },
    getuid:function(){
    	var uid=$.cookie('user_id');
    	uid=Number(uid)||0;
    	return uid;
    },
    getutoken:function(){
    	var user_token=$.cookie('user_token');
    	user_token=$.trim(user_token);
    	return user_token;
    },
    checkandlogin: function() {
        if (thisFrontApp.islogin() == false) {
            thisFrontApp.poplogin();
        }
    },
    //声音播放,cash 金额
    sound: {
        paynotice: function(channel, cash,order_num) {
            if((window.ordernumstringcjh||"").indexOf(order_num)<0)//防止（同步返回、异步放回重复播放）
            {
                window.ordernumstringcjh+=","+order_num;
                if (channel.indexOf("_alipay") > 0) {
                    thisFrontApp.sound.getsoundfile("支付宝收钱" + cash + "元");
                }
                if (channel.indexOf("_wxpay") > 0) {
                    thisFrontApp.sound.getsoundfile("微信收钱" + cash + "元");
                }   
            }
        },
        getsoundfile: function(cash_num) {
            var postParam = {
                user_id: thisFrontApp.getuid(),
                cash_num: cash_num,
                version: 1,
                time: thisFrontApp.gettime()
            };
            var sign = thisFrontApp.getsign(postParam);
            postParam.sign = sign; //不这么传值下，有错误

            $.ajax({
                "url": window.pageconfig.domain + "/api/pay/getsoundfile",
                "data": postParam,
                "type": "post",
                "dataType": "json",
                "success": function(rs) {
                    soundPlayList.push(rs.data.filepath);
                    thisFrontApp.sound.play();
                }
            });
        },
        play: function() {

            soundManager.setup({
                debugMode:false,
            });
            //console.log(JSON.stringify(soundPlayList));
            //console.log(window.soundPlayIng);
            if (window.soundPlayIng == 1) return; //播放中，不要再播放了
            if (soundPlayList.length > 0) {
                window.soundPlayIng = 1;
                soundManager.createSound({
                    url: soundPlayList.shift(),
                    onfinish: function() {
                        window.soundPlayIng = 0;
                        thisFrontApp.sound.play();
                    }
                }).play();
            }

            // 
            // if (soundListIndex < soundListNum.length)
            // {
            //     if(soundListNum[soundListIndex]!="")
            //     {
            //         soundManager.createSound(
            //         {
            //             url: '/static/index/media/duyaya/' + soundListNum[soundListIndex] + '.mp3',
            //             onfinish: function()
            //             {
            //                 playsound();
            //             }
            //         }).play();
            //     }
            //     soundListIndex++;
            // }
        }
    },
    goeasy: {
        init: function() {
            thisFrontApp.goeasy.loadjs();
            if (typeof GoEasy == "undefined" || typeof GoEasy == undefined) {
                setTimeout(function() {
                    thisFrontApp.goeasy.init(); //等待JS异步加载完成
                }, 100);
            } else {
                if (typeof window.pageconfig.goEasy == "undefined" || typeof window.pageconfig.goEasy == undefined) {
                    layer.alert("JS参数“window.pageconfig.goEasy.subscribe_key”未配置");
                }
                window.goEasyObj = new GoEasy({
                    appkey: window.pageconfig.goEasy.subscribe_key,
                    onConnected: function() {
                        //console.log("成功连接Push服务器");
                    },
                    onDisconnected: function() {
                        layer.alert("与Push服务器连接断开。");
                    },
                    onConnectFailed: function(error) {
                        layer.alert("与Push服务器连接失败，错误编码：" + error.code + "错误信息：" + error.content);
                    }
                });
                thisFrontApp.goeasy.subscribe();
            }
        },
        subscribe: function() {
            if (thisFrontApp.islogin() == true) {
                //开启订阅
				var user_token=thisFrontApp.getutoken();
                goEasyObj.subscribe({
                    channel: user_token,
                    onMessage: function(message) {
                        if (message.channel == user_token) {
                            var rs = message.content;
                            console.log("Push收到："+rs);
                            if (rs) rs = JSON.parse(rs);
                            thisFrontApp.orderchange(rs);
                        }
                        //layer.alert("您有新消息：channel：" + message.channel + " 内容：" + message.content);
                    },
                    onSuccess: function() {
                        //console.log("Channel订阅成功");
                    },
                    onFailed: function(error) {
                        layer.alert("Channel订阅失败, 错误编码：" + error.code + " 错误信息：" + error.content)
                    }
                });

            }

        },
        loadjs: function() {

            //初始化GoEasy
            var loadGoEasyJs = function() {
                var oScript = document.createElement("script");
                oScript.type = "text/javascript";
                oScript.src = "//cdn-hangzhou.goeasy.io/goeasy.js";
                oScript.id = "idgoeasy";
                //oHead.appendChild(oScript);
                $('head').append(oScript);
            };
            if ($("#idgoeasy").length == 0) {
                loadGoEasyJs();
                return;
            }

            //__初始化GoEasy
        },
    },
    islogin: function() {
    	var uid=thisFrontApp.getuid();
        if (uid>0) {
            return true;
        } else {
            return false;
        }
    },
    poplogin: function() { //弹出登陆界面
      	window.parent.location.href=thisFrontApp.url("sign");
    	return false;
        /*layer.open({
            type: 2,
            area: ['500px', '300px'],
            title: '登入',
            shadeClose: false,
            maxmin: false, //允许全屏最小化
            content: thisFrontApp.url("sign")
        });*/
    },
    url: function(param) { //模拟TP5 URL合成,模拟的是pathinfo模式
        var paramArray = param.split("/"),
            thisurl = "";
            console.log(JSON.stringify(window.pageconfig));
        switch (paramArray.length) {
            case 3:
                thisurl = param;
                break;
            case 2:
                thisurl = window.pageconfig.module + "/" + param;
                break;
            case 1:
                thisurl = window.pageconfig.module + "/" + window.pageconfig.controller + "/" + param;
                break;
        }

        try {
            thisurl = thisurl.replace("/\?/g", "\/");
            thisurl = thisurl.replace("/\=/g", "\/");
        } catch (e) {

        }

        return window.pageconfig.scriptdir + "/" + thisurl;
        //return ;
    },
    gettime: function() {
        return Date.parse(new Date()) / 1000;
    },
    getsign: function(arrayParam) {
    	var user_token=thisFrontApp.getutoken();
        var res_for_simg = {};
        for (var key in arrayParam) {
            res_for_simg[key] = arrayParam[key]; //复制数组
        }

        var jiamistring = [];
        /*排序，加密*/
        var newarray = [];
        res_for_simg["signsecret"] = user_token; //加入token码
        for (var key in res_for_simg) {
            newarray.push(key);
        }
        newarray.sort();
        for (var key in newarray) {
            jiamistring.push(newarray[key] + "=" + res_for_simg[newarray[key]]);
        }
        //console.log('token'+user_token);
//      console.log(jiamistring.join("&").toLowerCase());
        return md5(jiamistring.join("&").toLowerCase());
        /*排序，加密*/
    },
    orderchange:function(rs){
    	layer.closeAll();
    	switch (rs.data.trade_status)
	    {
	        case 100: //成功
	        	layer.msg('支付成功',{time:900});
	            $("#pay_code").val("");
	            $("#pay_price").val("").focus();
	            $('.payment-primary').html('应收金额：<small>等待输入</small>');
	            
	            var order_no_list=window.ordernolist||'';
	            if(order_no_list.indexOf(rs.data.order_num)<0){
	            	window.ordernolist+=','+rs.data.order_num;
		            var html=thisFrontApp.getordertemplate(rs.data);
		            $('.order-nearly').children('div').last().fadeOut('normal',function(){
		            	$('.order-nearly').children('div').last().remove();
		            	$('.order-nearly').prepend(html);
		            });
	            }
	            //$("#order_num").val(rs.data.out_trade_no);
	            thisFrontApp.sound.paynotice(rs.data.channel, rs.data.total_amount, rs.data.order_num);
	            try
	            {
	                windowForm.jsprint(rs.data.printhtml, rs.data.out_trade_no);
	            }
	            catch (e)
	            {
	                //layer.alert(JSON.stringify(rs));
	            }
	            break;
	        case 600: //等待用户输入支付密码
	        case 500: //未知异常，调用查询接口确认支付结果
	            layer.msg('等待用户付款');
	            break;
	        case 400: //未知异常,调用查询接口确认支付结果
	            if (rs.message) layer.msg(rs.message);
	            layer.msg(rs.message || "支付关闭");
	            break;
	    }
    },
    getordertemplate:function(data,prefix){
    	if(typeof data !='object'){
    		//return '数据格式不正确'+(typeof data);
    		return '';
    	}
    	var total_amount,pay_time,channel,order_num;
    	if(prefix=='order'){
    		total_amount=data.order_total_amount;
    		pay_time=data.order_addtime;
    		channel=data.order_channel;
    		order_num=data.order_num;
    	}else{
    		total_amount=data.total_amount;
    		pay_time=data.pay_time;
    		channel=data.channel;
    		order_num='';
    	}
    	var html='';
    	if(channel=='face_alipay'||channel=='jsapi_alipay'){
    		html='<div class="col-xs-3 '+order_num+'">'+
					'<div class="panel panel-blue">'+
						'<div class="panel-heading"><span class="panel-title"><i class="m-icon iconfont icon-alipay"></i>支付宝</span></div>'+
						'<div class="panel-body">'+
							'<h3>¥'+total_amount+'</h3>'+
						'</div>'+
						'<div class="panel-footer text-right">'+pay_time+'</div>'+
					'</div>'+
				'</div>';
		}else if(channel=='face_wxpay'||channel=='jsapi_wxpay'){
			html='<div class="col-xs-3 '+order_num+'">'+
					'<div class="panel panel-green">'+
						'<div class="panel-heading"><span class="panel-title"><i class="m-icon iconfont icon-wechatpay"></i>微信</span></div>'+
						'<div class="panel-body">'+
							'<h3>¥'+total_amount+'</h3>'+
						'</div>'+
						'<div class="panel-footer text-right">'+pay_time+'</div>'+
					'</div>'+
				'</div>';
		}
		return html;
		
    },
    demo: function() {

    }
	

};

//支付业务逻辑
var thisPay = function() {

};


function md5(string) {
    function md5_RotateLeft(lValue, iShiftBits) {
        return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
    }

    function md5_AddUnsigned(lX, lY) {
        var lX4, lY4, lX8, lY8, lResult;
        lX8 = (lX & 0x80000000);
        lY8 = (lY & 0x80000000);
        lX4 = (lX & 0x40000000);
        lY4 = (lY & 0x40000000);
        lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
        if (lX4 & lY4) {
            return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
        }
        if (lX4 | lY4) {
            if (lResult & 0x40000000) {
                return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
            } else {
                return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
            }
        } else {
            return (lResult ^ lX8 ^ lY8);
        }
    }

    function md5_F(x, y, z) {
        return (x & y) | ((~x) & z);
    }

    function md5_G(x, y, z) {
        return (x & z) | (y & (~z));
    }

    function md5_H(x, y, z) {
        return (x ^ y ^ z);
    }

    function md5_I(x, y, z) {
        return (y ^ (x | (~z)));
    }

    function md5_FF(a, b, c, d, x, s, ac) {
        a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_F(b, c, d), x), ac));
        return md5_AddUnsigned(md5_RotateLeft(a, s), b);
    };

    function md5_GG(a, b, c, d, x, s, ac) {
        a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_G(b, c, d), x), ac));
        return md5_AddUnsigned(md5_RotateLeft(a, s), b);
    };

    function md5_HH(a, b, c, d, x, s, ac) {
        a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_H(b, c, d), x), ac));
        return md5_AddUnsigned(md5_RotateLeft(a, s), b);
    };

    function md5_II(a, b, c, d, x, s, ac) {
        a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_I(b, c, d), x), ac));
        return md5_AddUnsigned(md5_RotateLeft(a, s), b);
    };

    function md5_ConvertToWordArray(string) {
        var lWordCount;
        var lMessageLength = string.length;
        var lNumberOfWords_temp1 = lMessageLength + 8;
        var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
        var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
        var lWordArray = Array(lNumberOfWords - 1);
        var lBytePosition = 0;
        var lByteCount = 0;
        while (lByteCount < lMessageLength) {
            lWordCount = (lByteCount - (lByteCount % 4)) / 4;
            lBytePosition = (lByteCount % 4) * 8;
            lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
            lByteCount++;
        }
        lWordCount = (lByteCount - (lByteCount % 4)) / 4;
        lBytePosition = (lByteCount % 4) * 8;
        lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
        lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
        lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
        return lWordArray;
    };

    function md5_WordToHex(lValue) {
        var WordToHexValue = "",
            WordToHexValue_temp = "",
            lByte, lCount;
        for (lCount = 0; lCount <= 3; lCount++) {
            lByte = (lValue >>> (lCount * 8)) & 255;
            WordToHexValue_temp = "0" + lByte.toString(16);
            WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length - 2, 2);
        }
        return WordToHexValue;
    };

    function md5_Utf8Encode(string) {
        //alert(string);
        try {
            string = string.toString().replace(/\r\n/g, "\n");
        } catch (e) {

            layer.alert(string + "出错了");
        }
        var utftext = "";
        for (var n = 0; n < string.length; n++) {
            var c = string.charCodeAt(n);
            if (c < 128) {
                utftext += String.fromCharCode(c);
            } else if ((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            } else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }
        }
        return utftext;
    };
    var x = Array();
    var k, AA, BB, CC, DD, a, b, c, d;
    var S11 = 7,
        S12 = 12,
        S13 = 17,
        S14 = 22;
    var S21 = 5,
        S22 = 9,
        S23 = 14,
        S24 = 20;
    var S31 = 4,
        S32 = 11,
        S33 = 16,
        S34 = 23;
    var S41 = 6,
        S42 = 10,
        S43 = 15,
        S44 = 21;
    string = md5_Utf8Encode(string);
    x = md5_ConvertToWordArray(string);
    a = 0x67452301;
    b = 0xEFCDAB89;
    c = 0x98BADCFE;
    d = 0x10325476;
    for (k = 0; k < x.length; k += 16) {
        AA = a;
        BB = b;
        CC = c;
        DD = d;
        a = md5_FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
        d = md5_FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
        c = md5_FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
        b = md5_FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
        a = md5_FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
        d = md5_FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
        c = md5_FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
        b = md5_FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
        a = md5_FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
        d = md5_FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
        c = md5_FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
        b = md5_FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
        a = md5_FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
        d = md5_FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
        c = md5_FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
        b = md5_FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
        a = md5_GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
        d = md5_GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
        c = md5_GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
        b = md5_GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
        a = md5_GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
        d = md5_GG(d, a, b, c, x[k + 10], S22, 0x2441453);
        c = md5_GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
        b = md5_GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
        a = md5_GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
        d = md5_GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
        c = md5_GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
        b = md5_GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
        a = md5_GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
        d = md5_GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
        c = md5_GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
        b = md5_GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
        a = md5_HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
        d = md5_HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
        c = md5_HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
        b = md5_HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
        a = md5_HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
        d = md5_HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
        c = md5_HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
        b = md5_HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
        a = md5_HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
        d = md5_HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
        c = md5_HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
        b = md5_HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
        a = md5_HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
        d = md5_HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
        c = md5_HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
        b = md5_HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
        a = md5_II(a, b, c, d, x[k + 0], S41, 0xF4292244);
        d = md5_II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
        c = md5_II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
        b = md5_II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
        a = md5_II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
        d = md5_II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
        c = md5_II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
        b = md5_II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
        a = md5_II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
        d = md5_II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
        c = md5_II(c, d, a, b, x[k + 6], S43, 0xA3014314);
        b = md5_II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
        a = md5_II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
        d = md5_II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
        c = md5_II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
        b = md5_II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
        a = md5_AddUnsigned(a, AA);
        b = md5_AddUnsigned(b, BB);
        c = md5_AddUnsigned(c, CC);
        d = md5_AddUnsigned(d, DD);
    }
    return (md5_WordToHex(a) + md5_WordToHex(b) + md5_WordToHex(c) + md5_WordToHex(d)).toLowerCase();
}
