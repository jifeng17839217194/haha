var DOM = {
	id:'',
    g: function(el, selector) {
        if (arguments.length === 1 && typeof arguments[0] == 'string') {
            if (document.querySelector) {
                return document.querySelector(arguments[0]);
            }
        } else if (arguments.length === 1 && typeof arguments[0] == 'object') {
            return el;
        } else if (arguments.length === 2 && typeof arguments[0] == 'string') {
            return document.querySelector(arguments[0]).querySelector(selector)
        } else if (arguments.length === 2) {
            if (el.querySelector) {
                return el.querySelector(selector);
            }
        }
    },
    gAll: function(el, selector) {
        if (arguments.length === 1 && typeof arguments[0] == 'string') {
            if (document.querySelector) {
                return document.querySelectorAll(arguments[0]);
            }
        } else if (arguments.length === 1 && typeof arguments[0] == 'object') {
            return el;
        } else if (arguments.length === 2 && typeof arguments[0] == 'string') {
            return document.querySelector(arguments[0]).querySelectorAll(selector)
        } else if (arguments.length === 2) {
            if (el.querySelector) {
                return el.querySelectorAll(selector);
            }
        }
    },
    remove: function(el) {
        if (el && el.parentNode) {
            el.parentNode.removeChild(el);
        }
        return this;
    },
    addListen: function(id, type, fn) {
        var dom = typeof id === "string" ? DOM.g(id) : id;
        dom.addEventListener(type, fn);
        return this;
    },
    toggle: function(self) {
        var dom = typeof self === "string" ? DOM.g(self) : self;
        dom.style.display = dom.style.display === "none" ? "block" : "none";
        return this;
    },
    toggleClass: function(self, className) {
        var dom = typeof self === "string" ? DOM.g(self) : self;
        if (dom.className.indexOf(className) > -1) {
            dom.classList.remove(className);
        } else {
            dom.classList.add(className);
        }
    },
    setAttr: function(id, type, value) {
        var dom = typeof id === "string" ? DOM.g(id) : id;
        dom.setAttribute(type, value);
        return this;
    },
    getAttr: function(id, type) {
        var dom = typeof id === "string" ? DOM.g(id) : id;
        return dom.setAttribute(type);
    },
    setHtml: function(id, html) {
        var dom = typeof id === "string" ? DOM.g(id) : id;
        dom.innerHTML = html;
        return this;
    },
    getHtml: function(id, html) {
        var dom = typeof id === "string" ? DOM.g(id) : id;
        return dom.innerHTML;
    },
    addHtml: function(id, html) {
        var dom = typeof id === "string" ? DOM.g(id) : id;
        dom.innerHTML += html;
        return this;
    },
    appHtml: function(id,el,html) {
    	var dom = typeof id === "string" ? DOM.g(id) : id;
    	el.innerHTML = html;
        dom.appendChild(el);
        return this;
    },
    toast:function(text){
        var el = document.createElement('div');
            el.style = "position:fixed;top:50%;left:50%;display:flex;transform:translate(-50%,-50%);-webkit-transform:translate(-50%,-50%);border-radius:5px;padding:10px;background:rgba(0,0,0,0.6);width:200px;min-height:40px;align-items:center;justify-content:center;color:#FFFFFF;font-size:0.7rem;animation:1s ease-in-out toast;-webkit-animation:1s ease-in-out toast;";
        var html  = '<div class="txt">'+text+'</div>';   
        DOM.appHtml("body",el,html);
        setTimeout(function(){
          DOM.g('body').removeChild(el);
        },1500);
    },
    openWin: function(url, ags, bool, subType, animation) {
    	var ags = ags || "";
//  	var pageAgs = "&windowID="+url+ags;
//  	if(typeof ags === "string"){
//  		var pageAgs = ags.split('&')||"";pageAgs = ags.join(',')||"";	
//  	};
		if(ags === ""){
			mui.openWindow({url:url+'.html',id:'url',styles:{scrollIndicator:'none'},show:{aniShow:'pop-in'},waiting:{autoShow:false}});
		}else{
			mui.openWindow({url:url+'.html?'+ags,id:'url',styles:{scrollIndicator:'none'},show:{aniShow:'pop-in'},waiting:{autoShow:false}});	
		}
    },
    btnOff:function(id){
    	var dom = typeof id === "string" ? DOM.g(id) : id;
    	if(dom.disabled == "disabled"){
    		return false;
    	};
    	dom.disabled="disabled";
    	setTimeout(function(){
    		dom.disabled="";
    	},1500);
    	return this;
    },
    isPhone: function(phone) {
        try {
            var reg = /^((1[0-9]{2})+\d{8})$/i;
            if (!reg.test(phone)) {
                return false;
            }
            return true;
        } catch (e) {
            return false;
        }
    },
    isEmail: function(email) {
        try {
            var reg = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            if (!reg.test(email)) {
                return false;
            }
            return true;
        } catch (e) {
            return false;
        }
    },
    ajax:function(url,data,fn,isn){
		var data = data,token = window.localStorage.token || '',user_id = window.localStorage.userID;
		data.token = token;
		data.user_id = user_id;
		mui.ajax({
		    type:"post",
		    url:url,
		    data:{data:data},
		    dataType:"json",
		    beforeSend:function (request) {
		        request.setRequestHeader("ACUID", window.localStorage.UID);
		        request.setRequestHeader("ACTIME", (new Date()).getTime());
		    },
		    success:function(res){
		        if(res.code == 1){
		        	fn&&fn(res);
		        }else{
		        	var b = isn || "true";
		        	if(b){
		        		alert(res.msg);	
		        	};
		        }
		    },error:function(a,b,c){
		    	DOM.toast("获取网络失败"); 
		    }
		});
	},
};
var win = {
    pageParam: {},
};
function isLogin(){
	var user_id = db2.getVal('user_id'); 
	if(user_id != "" && user_id != null){
		return true;
	};
	return false;
};
(function(){
	var url = window.location.search;
	if (url.indexOf("?") != -1) {  
		var str = url.substr(1); 
		var tempAgs = str.split('&'),maxIndex = tempAgs.length,tempObj = {};
		if(tempAgs == "" || tempAgs.length < 1){return false;} 
		for(var i = 0;i < maxIndex; i ++){
			var temp = tempAgs[i].split('=');
			if((typeof temp[0] === 'string'&& temp[0] != "") || typeof temp[0] === "object"){
				tempObj[temp[0]] = decodeURI(temp[1]);	 
			};
		};
		win.pageParam = tempObj;
		tempAgs = null;maxIndex = null;tempObj = null; 
	};	
})();
