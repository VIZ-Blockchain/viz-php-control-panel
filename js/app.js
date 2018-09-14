var gate=viz;
var current_block=0;
//gate.config.set('websocket','wss://testnet.viz.world');
gate.api.stop();

function update_dgp(){
	gate.api.getDynamicGlobalProperties(function(e,r){
		if(r){
			current_block=r.head_block_number;
			$('.setter[rel=current_block]').html(current_block);
		}
	});
	setTimeout("update_dgp()",3000);
}

function fast_str_replace(search,replace,str){
	return str.split(search).join(replace);
}

function date_str(timestamp,add_time,add_seconds,remove_today=false){
	if(-1==timestamp){
		var d=new Date();
	}
	else{
		var d=new Date(timestamp);
	}
	var day=d.getDate();
	if(day<10){
		day='0'+day;
	}
	var month=d.getMonth()+1;
	if(month<10){
		month='0'+month;
	}
	var minutes=d.getMinutes();
	if(minutes<10){
		minutes='0'+minutes;
	}
	var hours=d.getHours();
	if(hours<10){
		hours='0'+hours;
	}
	var seconds=d.getSeconds();
	if(seconds<10){
		seconds='0'+seconds;
	}
	var datetime_str=day+'.'+month+'.'+d.getFullYear();
	if(add_time){
		datetime_str=datetime_str+' '+hours+':'+minutes;
		if(add_seconds){
			datetime_str=datetime_str+':'+seconds;
		}
	}
	if(remove_today){
		datetime_str=fast_str_replace(date_str(-1)+' ','',datetime_str);
	}
	return datetime_str;
}

function update_datetime(){
	$('.timestamp').each(function(){
		$(this).html(date_str($(this).attr('data-timestamp')*1000,true,false,true));
	});
}

$(window).on('hashchange',function(e){
	e.preventDefault();
	if(''!=window.location.hash){
		$(window).scrollTop($(window.location.hash).offset().top - 64 - 12);
	}
	else{
		$(window).scrollTop(0);
	}
});

function app_mouse(e){
	if(!e)e=window.event;
	var target=e.target || e.srcElement;
	if($(target).hasClass('reply-action') || $(target).parent().hasClass('reply-action')){
			e.preventDefault();
			var proper_target=$(target);
			if($(target).parent().hasClass('reply-action')){
				proper_target=$(target).parent();
			}
			//if(1==user.verify){
				//window.clearTimeout(update_comments_list_timer);
				var post_id=0;
				var comment_id=0;
				if(proper_target.hasClass('post-reply')){
					post_id=1;//parseInt(proper_target.attr('data-post-id'));
				}
				if(proper_target.hasClass('comment-reply')){
					comment_id=1;//parseInt(proper_target.attr('data-comment-id'));
				}
				var comment_form='<div class="reply-form" data-reply-post="'+post_id+'" data-reply-comment="'+comment_id+'"><textarea name="reply-text" placeholder="Введите ваш ответ..."></textarea><input type="button" class="reply-execute" value="Ответить"></div>'
				if(comment_id){
					if(0==$('.reply-form[data-reply-comment='+comment_id+']').length){
						proper_target.closest('.addon').after(comment_form);
						proper_target.closest('.addon').parent().find('.reply-form textarea[name=reply-text]').focus();
					}
					else{
						$('.reply-form[data-reply-comment='+comment_id+']').remove();
					}
				}
				if(post_id){
					if(0==$('.reply-form[data-reply-post='+post_id+']').length){
						proper_target.closest('.comments').find('.subtitle').after(comment_form);
						proper_target.closest('.comments').find('.reply-form textarea[name=reply-text]').focus();
					}
					else{
						$('.reply-form[data-reply-post='+post_id+']').remove();
					}
				}
			//}
		}
}
$(document).ready(function(){
	var hash_load=window.location.hash;
	if(''!=hash_load){
		window.location.hash='';
		window.location.hash=hash_load;
	}
	document.addEventListener('click', app_mouse, false);
	document.addEventListener('tap', app_mouse, false);
	update_dgp();
	update_datetime();
	$('a.menu-expand').bind('click',function(){
		if($('a.menu-expand').hasClass('active')){
			$('a.menu-expand').removeClass('active');
			$('.menu').removeClass('active');
			$('.main').removeClass('menu-expand');
		}
		else{
			$('a.menu-expand').addClass('active');
			$('.menu').addClass('active');
			$('.main').addClass('menu-expand');
		}
	});
});