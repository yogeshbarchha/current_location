jQuery(document).ready(function() {
    var pgurl = window.location.href.substr(window.location.href.lastIndexOf("/")+1);
     jQuery("#dashboadfilterlink a ").each(function(){
     	  var link = jQuery(this).attr("href");
     	  var lurl = link.substr(link.lastIndexOf("/")+1);
          if(lurl == pgurl || lurl == '' )
          jQuery(this).parent().addClass('active').siblings().removeClass('active');
	 	  jQuery(this).addClass('active').siblings().removeClass('active');
     });
});