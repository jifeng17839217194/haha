
!function($) {
    "use strict";

    var SweetAlert = function() {};

    //examples 
    SweetAlert.prototype.init = function() {
        
    //Basic
    $('#sa-basic').click(function(){
        swal("您申请新店面成功,我们的工作人员会尽快与您取得联系!谢谢合作！");
    });

    //A title with a text under
    $('#sa-title').click(function(){
        swal("Here's a message!", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed lorem erat eleifend ex semper, lobortis purus sed.")
    });

    //Success Message
    $('#sa-success').click(function(){
        swal("Good job!", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed lorem erat eleifend ex semper, lobortis purus sed.", "error")
    });

    //Warning Message
    $('#sa-warning').click(function(){
        swal({   
            title: "Are you sure?",   
            text: "You will not be able to recover this imaginary file!",   
            type: "warning",   
            showCancelButton: true,   
            confirmButtonColor: "#DD6B55",   
            confirmButtonText: "Yes, delete it!",   
            closeOnConfirm: false 
        }, function(){   
            swal("Deleted!", "Your imaginary file has been deleted.", "success"); 
        });
    });

    //Parameter
    $('#sa-params').click(function(){
        swal({   
            title: "是否删除店面？",   
            text: "是否确定删除该店面,删除后店面信息将被销毁!",   
            type: "warning",   
            showCancelButton: true,   
            confirmButtonColor: "#DD6B55",   
            confirmButtonText: "确定",   
            cancelButtonText: "取消",   
            closeOnConfirm: false,   
            closeOnCancel: false 
        }, function(isConfirm){   
            if (isConfirm) {     
                swal("确定", "成功删除该店面", "success");   
            } else {     
                swal("取消", "取消当前操作", "error");   
            } 
        });
    });
	 $('#sa2-params').click(function(){
        swal({   
            title: "是否删除房间类型？",   
            text: "是否确定删除该房间类型,删除后该信息将被销毁!",   
            type: "warning",   
            showCancelButton: true,   
            confirmButtonColor: "#DD6B55",   
            confirmButtonText: "确定",   
            cancelButtonText: "取消",   
            closeOnConfirm: false,   
            closeOnCancel: false 
        }, function(isConfirm){   
            if (isConfirm) {     
                swal("确定", "成功删除该房间类型", "success");   
            } else {     
                swal("取消", "取消当前操作", "error");   
            } 
        });
    });
	 $('#sa3-params').click(function(){
        swal({   
            title: "删除技师类型",   
            text: "是否确定删除该技师类型？",   
            type: "warning",   
            showCancelButton: true,   
            confirmButtonColor: "#DD6B55",   
            confirmButtonText: "确定",   
            cancelButtonText: "取消",   
            closeOnConfirm: false,   
            closeOnCancel: false 
        }, function(isConfirm){   
            if (isConfirm) {     
                swal("确定", "成功删除该技师类型", "success");   
            } else {     
                swal("取消", "取消当前操作", "error");   
            } 
        });
    });
	 $('#sa4-params').click(function(){
        swal({   
            title: "删除会员类型",   
            text: "是否确定删除该会员类型？",   
            type: "warning",   
            showCancelButton: true,   
            confirmButtonColor: "#DD6B55",   
            confirmButtonText: "确定",   
            cancelButtonText: "取消",   
            closeOnConfirm: false,   
            closeOnCancel: false 
        }, function(isConfirm){   
            if (isConfirm) {     
                swal("确定", "成功删除该会员类型", "success");   
            } else {     
                swal("取消", "取消当前操作", "error");   
            } 
        });
    });
	 $('#sa5-params').click(function(){
        swal({   
            title: "删除产品目录",   
            text: "是否确定删除该产品目录？",   
            type: "warning",   
            showCancelButton: true,   
            confirmButtonColor: "#DD6B55",   
            confirmButtonText: "确定",   
            cancelButtonText: "取消",   
            closeOnConfirm: false,   
            closeOnCancel: false 
        }, function(isConfirm){   
            if (isConfirm) {     
                swal("确定", "成功删除该产品目录", "success");   
            } else {     
                swal("取消", "取消当前操作", "error");   
            } 
        });
    });
	 $('#sa6-params').click(function(){
        swal({   
            title: "删除产品",   
            text: "是否确定删除该产品:足浴套餐A?",   
            type: "warning",   
            showCancelButton: true,   
            confirmButtonColor: "#DD6B55",   
            confirmButtonText: "确定",   
            cancelButtonText: "取消",   
            closeOnConfirm: false,   
            closeOnCancel: false 
        }, function(isConfirm){   
            if (isConfirm) {     
                swal("确定", "成功删除该产品", "success");   
            } else {     
                swal("取消", "取消当前操作", "error");   
            } 
        });
    });	
		 $('#sa7-params').click(function(){
        swal({   
            title: "删除房间",   
            text: "是否确定删除该房间？",   
            type: "warning",   
            showCancelButton: true,   
            confirmButtonColor: "#DD6B55",   
            confirmButtonText: "确定",   
            cancelButtonText: "取消",   
            closeOnConfirm: false,   
            closeOnCancel: false 
        }, function(isConfirm){   
            if (isConfirm) {     
                swal("确定", "成功删除该房间", "success");   
            } else {     
                swal("取消", "取消当前操作", "error");   
            } 
        });
    });
	 $('#sa8-params').click(function(){
        swal({   
            title: "删除房间",   
            text: "是否确定删除所选房间？",   
            type: "warning",   
            showCancelButton: true,   
            confirmButtonColor: "#DD6B55",   
            confirmButtonText: "确定",   
            cancelButtonText: "取消",   
            closeOnConfirm: false,   
            closeOnCancel: false 
        }, function(isConfirm){   
            if (isConfirm) {     
                swal("确定", "成功删除所选房间", "success");   
            } else {     
                swal("取消", "取消当前操作", "error");   
            } 
        });
    });
    //Custom Image
    $('#sa-image').click(function(){
        swal({   
            title: "Govinda!",   
            text: "Recently joined twitter",   
            imageUrl: "../plugins/images/users/govinda.jpg" 
        });
    });

    //Auto Close Timer
    $('#sa-close').click(function(){
         swal({   
            title: "Auto close alert!",   
            text: "I will close in 2 seconds.",   
            timer: 2000,   
            showConfirmButton: false 
        });
    });


    },
    //init
    $.SweetAlert = new SweetAlert, $.SweetAlert.Constructor = SweetAlert
}(window.jQuery),

//initializing 
function($) {
    "use strict";
    $.SweetAlert.init()
}(window.jQuery);