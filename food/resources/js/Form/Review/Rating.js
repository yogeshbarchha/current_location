/*
 * Star Rating
 * by CodexWorld
 *
 * More info:
 * http://www.codexworld.com
 *
 * Copyright 2015 CodexWorld
 * Released under the MIT license
 * 
 */
 
(function(a){
    a.fn.codexworld_rating_widget = function(p){
        var p = p||{};
        var b = p&&p.starLength?p.starLength:"5";
        var c = p&&p.callbackFunctionName?p.callbackFunctionName:"";
        var e = p&&p.initialValue?p.initialValue:"0";
        var d = p&&p.imageDirectory?p.imageDirectory:"images";
        var r = p&&p.inputAttr?p.inputAttr:"";
        var f = e;
        var g = a(this);
        b = parseInt(b);
        init();
        g.next("ul").children("li").hover(function(){
            jQuery(this).parent().children("li").css('background-position','0px 0px');
            var a = jQuery(this).parent().children("li").index(jQuery(this));
            jQuery(this).parent().children("li").slice(0,a+1).css('background-position','0px -28px')
        },function(){});
        g.next("ul").children("li").click(function(){
            var a = jQuery(this).parent().children("li").index(jQuery(this));
            var attrVal = (r != '')?g.attr(r):'';
            f = a+1;
            g.val(f);
            if(c != ""){
                eval(c+"("+g.val()+", "+attrVal+")")
            }
        });
        g.next("ul").hover(function(){},function(){
            if(f == ""){
                jQuery(this).children("li").slice(0,f).css('background-position','0px 0px')
            }else{
                jQuery(this).children("li").css('background-position','0px 0px');
                jQuery(this).children("li").slice(0,f).css('background-position','0px -28px')
            }
        });
        function init(){
            jQuery('<div style="clear:both;"></div>').insertAfter(g);
            g.css("float","left");
            var a = jQuery("<ul>");
            a.addClass("codexworld_rating_widget");
            for(var i=1;i<=b;i++){
                a.append('<li style="background-image:url('+d+'/widget_star.gif)"><span>'+i+'</span></li>')
            }
            a.insertAfter(g);
            if(e != ""){
                f = e;
                g.val(e);
                g.next("ul").children("li").slice(0,f).css('background-position','0px -28px')
            }
        }
    }
})(jQuery);