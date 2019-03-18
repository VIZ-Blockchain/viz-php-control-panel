var gate=viz;
var api_ws_gates=['wss://viz.lexai.host/','wss://solox.world/ws','wss://ws.viz.ropox.app/'];
var api_http_gates=['https://rpc.viz.lexai.host/','https://solox.world/','https://rpc.viz.ropox.app/'];
var api_gates=api_http_gates;
var best_gate=-1;
var best_gate_latency=-1;
var api_gate;
api_gate=api_gates[Math.floor(Math.random()*api_gates.length)];
if(null!=localStorage.getItem('api_gate_default')){
	api_gate=localStorage.getItem('api_gate_default');
	gate.config.set('websocket',api_gate);
}
else{
	update_api_gate(api_gate);
	select_best_gate();
}

function update_api_gate(value=false){
	if(false==value){
		api_gate=api_gates[best_gate];
	}
	else{
		api_gate=value;
	}
	localStorage.setItem('api_gate_default',api_gate);
	gate.config.set('websocket',api_gate);
}

function select_best_gate(){
	for(i in api_gates){
		let current_gate=i;
		let latency_start=new Date().getTime();
		let latency=-1;

		let protocol='websocket';
		let gate_protocol=api_gates[i].substring(0,api_gates[i].indexOf(':'));

		if('http'==gate_protocol||'https'==gate_protocol){
			protocol='http';
		}
		if('websocket'==protocol){
			let socket = new WebSocket(api_gates[i]);
			socket.onmessage=function(event){
				latency=new Date().getTime() - latency_start;
				if(best_gate!=current_gate){
					if((best_gate_latency>latency)||(best_gate==-1)){
						best_gate=current_gate;
						best_gate_latency=latency;
						update_api_gate();
					}
				}
				socket.close();
			}
			socket.onopen=function(){
				socket.send('{"id":1,"method":"call","jsonrpc":"2.0","params":["database_api","get_dynamic_global_properties",[]]}');
			};
		}
		if('http'==protocol){
			let xhr = new XMLHttpRequest();
			xhr.open('GET',api_gates[i]);
			xhr.setRequestHeader('accept','application/json, text/plain, */*');
			xhr.setRequestHeader('content-type','application/json');
			xhr.onreadystatechange = function() {
				if(4==xhr.readyState && 200==xhr.status){
					latency=new Date().getTime() - latency_start;
					if(best_gate!=current_gate){
						if((best_gate_latency>latency)||(best_gate==-1)){
							best_gate=current_gate;
							best_gate_latency=latency;
							update_api_gate();
						}
					}
				}
			}
			xhr.send('{"id":1,"method":"call","jsonrpc":"2.0","params":["database_api","get_dynamic_global_properties",[]]}');
		}
	}
}

var dgp={};
var current_block=0;
var current_user='';
var users={};
var notify_id=0;
var empty_signing_key='VIZ1111111111111111111111111111111114T1Anm';
var domain='viz.world';
var modal=false;
var wysiwyg_active=false;

var global_scroll_top=0;
var wait_session_timer=0;
var update_comments_list_timer=0;
var update_comments_list_timeout=3500;

function check_json(text){
	if(typeof text!=='string'){
		return false;
	}
	try{
		JSON.parse(text);
		return true;
	}
	catch(error){
		return false;
	}
}
function compare_account_name(a,b) {
	if (a.account < b.account)
		return -1;
	if (a.account > b.account)
		return 1;
	return 0;
}
function del_notify(id){
	$('.notify-list .notify[rel="'+id+'"]').remove();
}
function fade_notify(id){
	$('.notify-list .notify[rel="'+id+'"]').css('opacity','0.0');
	window.setTimeout('del_notify("'+id+'")',300);
}
function add_notify(html,dark=false,fade_time=10000){
	notify_id++;
	var element_html='<div class="notify'+(dark?' notify-dark':'')+'" rel="'+notify_id+'">'+html+'</div>';
	$('.notify-list').append(element_html);
	window.setTimeout('fade_notify('+notify_id+')',fade_time);
}
function set_update_comments_list(update=true){
	if(update){
		update_comments_list_timeout=3500;
		window.clearTimeout(update_comments_list_timer);
		update_comments_list_timer=window.setTimeout(function(){update_comments_list();},update_comments_list_timeout);
	}
	else{
		window.clearTimeout(update_comments_list_timer);
	}
}
function update_comments_list(){
	var content_id=$('.page.content').attr('data-content-id');
	var newest_comment_id=0;
	$('.comments .comment').each(function(){
		var comment_id=parseInt($(this).attr('data-id'))
		if(newest_comment_id<comment_id){
			newest_comment_id=comment_id;
		}
	});
	$.ajax({
		type:'POST',
		url:'/ajax/load_new_comments/',
		data:{'content_id':content_id,'last_id':newest_comment_id},
		success:function(data_html){
			if(''!=data_html){
				$('.new-comments').css('display','none');
				$('.new-comments').html(data_html);
				window.setTimeout(function(){set_update_comments_list(false);},100);
				sort_new_comments_list();
			}
		},
	});
	window.clearTimeout(update_comments_list_timer);
	update_comments_list_timer=window.setTimeout(function(){update_comments_list();},update_comments_list_timeout);
	update_comments_list_timeout+=500;
	if(update_comments_list_timeout>20000){
		update_comments_list_timeout=20000;
	}
}
function sort_comment_find_next(id){
	var comment_level=$('.page.comments .comment[data-id='+id+']').attr('data-level');
	var current_id=0;
	var current_level=0;
	var find=0;
	$('.page.comments .comment[data-id='+id+']').nextAll('.comment').each(function(){
		if(0==find){
			current_id=$(this).attr('data-id');
			current_level=$(this).attr('data-level');
			if(current_level<=comment_level){
				find=parseInt(current_id);
			}
		}
	});
	return find;
}
function sort_new_comments_list(){
	$('.new-comments .comment').each(function(){
		var comment_id=parseInt($(this).attr('data-id'));
		$(this).addClass('new');
		if(0==$('.page.comments .comment[data-id='+comment_id+']').length){
			var parent_id=parseInt($(this).attr('data-parent'));
			if(0!=parent_id){
				var parent_comment_next=sort_comment_find_next(parent_id);
				if(0!=parent_comment_next){
					$('.comment[data-id='+parent_comment_next+']')[0].outerHTML=$(this)[0].outerHTML+$('.comment[data-id='+parent_comment_next+']')[0].outerHTML;
				}
				else{
					var last_comment_id=parseInt($('.comments .comment').last().attr('data-id'));
					if(last_comment_id){
						$('.comment[data-id='+last_comment_id+']')[0].outerHTML=$('.comment[data-id='+last_comment_id+']')[0].outerHTML+$(this)[0].outerHTML;
					}
					else{
						$('.page.comments').append($(this)[0].outerHTML);
					}
				}
			}
			else{
				var last_comment_id=parseInt($('.comments .comment').last().attr('data-id'));
				if(last_comment_id){
					$('.comment[data-id='+last_comment_id+']')[0].outerHTML=$('.comment[data-id='+last_comment_id+']')[0].outerHTML+$(this)[0].outerHTML;
				}
				else{
					$('.page.comments').append($(this)[0].outerHTML);
				}
			}
		}
	});
	$('.new-comments').html('');
	$('.page .content .addon .comments span').html($('.comments .comment').length);
	update_datetime();
}
function wait_session(){
	if(typeof users[current_user].session_verify == 'undefined'){
		session_generate();
		return;
	}
	if(0==users[current_user].session_verify){
		users[current_user].session_attempts++;
		if(users[current_user].session_attempts>20){
			users[current_user].session_attempts=0;
			if(users[current_user].shield){
				$('.shield-auth-error').html(l10n.sessions.auth_error);
				$('.shield-auth-action').removeClass('disabled');
			}
			else{
				$('.auth-error').html(l10n.sessions.auth_error);
				$('.auth-action').removeClass('disabled');
			}
			$('.header .account').html('<a href="/login/" class="icon" title="'+l10n.global.auth+'"><i class="fas fa-fw fa-sign-in-alt"></i></a>');
		}
		else{
			$('.header .account').html('<i class="fa fw-fw fa-spinner fa-spin"></i> '+l10n.global.loading+'&hellip;');
			if(users[current_user].shield){
				$('.shield-auth-error').html(l10n.sessions.init_session+' ('+l10n.global.attempt+' '+users[current_user].session_attempts+')');
			}
			else{
				$('.auth-error').html(l10n.sessions.init_session+' ('+l10n.global.attempt+' '+users[current_user].session_attempts+')');
			}
			$.ajax({
				type:'POST',
				url:'/ajax/check_session/',
				data:{},
				success:function(data_json){
					data_obj=JSON.parse(data_json);
					if(typeof data_obj.error !== 'undefined'){
						console.log(''+new Date().getTime()+': '+data_obj.error+' - '+data_obj.error_str);
						if('time'==data_obj.error){
							add_notify(l10n.sessions.time_error,true);
						}
						if('rebuild_session'==data_obj.error){
							session_generate();
						}
						else if('wait'==data_obj.error){
							wait_session_timer=window.setTimeout('wait_session()',1500);
						}
					}
					else
					if(typeof data_obj !== 'undefined'){
						wait_session_timer=0;
						users[current_user].session_verify=1;
						save_session();
						if(users[current_user].shield){
							$('.shield-auth-error').html(l10n.sessions.success);
							$('.shield-auth-action').removeClass('disabled');
						}
						else{
							$('.auth-error').html(l10n.sessions.success);
							$('.auth-action').removeClass('disabled');
						}
						//initialize user_session_status (feed status, notifications)
						if('/'==document.location.pathname){
							document.location='https://'+domain+'/media/';
						}
						else
						if('/login/'==document.location.pathname){
							document.location='https://'+domain+'/media/';
						}
						else{
							document.location=document.location;
						}
					}
					else{
						wait_session_timer=window.setTimeout('wait_session()',3000);
					}
				}
			});
		}
	}
}
function follow_user(user,proper_target){
	if(''!=current_user){
		let json=JSON.stringify(['follow',{follower:current_user,following:user,what:['blog']}]);

		let follow_success=function(result){
			add_notify(l10n.media.follow_success+' '+user);
			proper_target.html('<div class="unfollow unfollow-action">'+l10n.media.unfollow+'</div>');
		}
		let follow_failure=function(err){
			add_notify(l10n.media.follow_failure+' '+user,true);
			console.log(err);
		}
		if(users[current_user].shield){
			shield_action(current_user,'custom',{id:'follow',required_auths:[],required_posting_auths:[current_user],json:json},follow_success,follow_failure);
		}
		else{
			gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'follow',json,function(err,result){
				if(!err){
					follow_success(result);
				}
				else{
					follow_failure(err);
				}
			});
		}
	}
}
function unfollow_user(user,proper_target){
	if(''!=current_user){
		let json=JSON.stringify(['follow',{follower:current_user,following:user,what:[]}]);

		let unfollow_success=function(result){
			add_notify(l10n.media.unfollow_success+' '+user);
			proper_target.html('<div class="follow follow-action">'+l10n.media.follow+'</div><br><div class="ignore ignore-action">'+l10n.media.ignore+'</div>');
		}
		let unfollow_failure=function(err){
			add_notify(l10n.media.unfollow_success+' '+user,true);
			console.log(err);
		}
		if(users[current_user].shield){
			shield_action(current_user,'custom',{id:'follow',required_auths:[],required_posting_auths:[current_user],json:json},unfollow_success,unfollow_failure);
		}
		else{
			gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'follow',json,function(err,result){
				if(!err){
					unfollow_success(result);
				}
				else{
					unfollow_failure(err);
				}
			});
		}
	}
}
function ignore_user(user,proper_target){
	if(''!=current_user){
		let json=JSON.stringify(['follow',{follower:current_user,following:user,what:['ignore']}]);

		let ignore_success=function(result){
			add_notify(l10n.media.ignore_success+' '+user);
			proper_target.html('<div class="unfollow unfollow-action">'+l10n.media.stop_ignore+'</div>');
		}
		let ignore_failure=function(err){
			add_notify(l10n.media.ignore_failure+' '+user,true);
			console.log(err);
		}
		if(users[current_user].shield){
			shield_action(current_user,'custom',{id:'follow',required_auths:[],required_posting_auths:[current_user],json:json},ignore_success,ignore_failure);
		}
		else{
			gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'follow',json,function(err,result){
				if(!err){
					ignore_success(result);
				}
				else{
					ignore_failure(err);
				}
			});
		}
	}
}
function session_generate(){
	if(''!=current_user){
		var key=pass_gen(20,false);
		$.ajax({
			type:'POST',
			url:'/ajax/create_session/',
			data:{'key':key},
			success:function(session){
				users[current_user].session_id=session;
				users[current_user].session_verify=0;
				users[current_user].session_attempts=0;
				set_session_cookie();
				let session_success=function(result){
					save_session();
					wait_session_timer=window.setTimeout('wait_session()',3000);
				}
				let session_failure=function(err){
					if(users[current_user].shield){
						$('.shield-auth-error').html(l10n.sessions.custom_failure);
						$('.shield-auth-action').removeClass('disabled');
					}
					else{
						$('.auth-error').html(l10n.sessions.custom_failure);
						$('.auth-action').removeClass('disabled');
					}
					users[current_user].session_id=null;
					users[current_user].session_verify=0;
					users[current_user].session_attempts=0;
					$('.header .account').html('<a href="/login/" class="icon" title="'+l10n.global.auth+'"><i class="fas fa-fw fa-sign-in-alt"></i></a>');
					console.log(err);
				}
				if(users[current_user].shield){
					shield_action(current_user,'custom',{id:'session',required_auths:[],required_posting_auths:[current_user],json:'["auth",{"key":"'+key+'"}]'},session_success,session_failure);
				}
				else{
					gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'session','["auth",{"key":"'+key+'"}]',function(err,result){
						if(!err){
							session_success(result);
						}
						else{
							session_failure(err);
						}
					});
				}
			}
		});
	}
}
function set_session_cookie(){
	if(''==current_user){
		document.cookie='session_id=; path=/; domain='+domain+';';
	}
	else{
		var expire = new Date();
		expire.setTime(expire.getTime() + 350 * 24 * 3600 * 1000);
		document.cookie='session_id='+users[current_user].session_id+'; expires='+expire.toUTCString()+'; path=/; domain='+domain+';';
	}
}
function save_session(){
	let users_json=JSON.stringify(users);
	localStorage.setItem('users',users_json);
	localStorage.setItem('current_user',current_user);
	wait_session();
	set_session_cookie();
	view_session();
	session_control();
}
function load_session(){
	if(null!=localStorage.getItem('users')){
		users=JSON.parse(localStorage.getItem('users'));
	}
	if(null!=localStorage.getItem('current_user')){
		current_user=localStorage.getItem('current_user');
	}
	if(current_user){
		view_session();
		session_control();
		wait_session();
		if(users[current_user].shield){
			shield_check(()=>{},()=>{$('.start-shield-action').removeClass('hide');});
		}
	}
	wallet_control();
	witness_control();
	committee_control();
	delegation_control();
	profile_control();
	create_account_control();
	reset_account_control();
	invite_control();
	shield_control();
	paid_subscriptions_control();
}
function shield_status(id,success=()=>{},failure=()=>{}){
	var xhr=new XMLHttpRequest();
	xhr.open('POST','http://127.0.0.1:51280/status/'+id+'/');
	xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	xhr.onreadystatechange = function() {
		if(4==xhr.readyState && 200==xhr.status){
			json=JSON.parse(xhr.responseText);
			if(typeof json.error !== 'undefined'){
				failure(json.error);
			}
			else{
				if(0==json.status){//wait user decision
					setTimeout(()=>{shield_status(id,success,failure)},1000);
				}
				else
				if(1==json.status){//denied
					failure(json);
				}
				else
				if(2==json.status){//wait execution
					setTimeout(()=>{shield_status(id,success,failure)},1000);
				}
				else
				if(3==json.status){//error result
					failure(json);
				}
				else
				if(4==json.status){//success result
					success(json);
				}
			}
		}
	}
	xhr.onerror=function(){
		failure();
	}
	xhr.send();
}
function shield_check(success=()=>{},failure=()=>{}){
	var xhr=new XMLHttpRequest();
	xhr.open('POST','http://127.0.0.1:51280/accounts/');
	xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	xhr.onreadystatechange = function() {
		if(4==xhr.readyState && 200==xhr.status){
			json=JSON.parse(xhr.responseText);
			success(json);
		}
	}
	xhr.onerror=function(){
		if(4==xhr.readyState && 0==xhr.status){
			failure();
		}
	}
	xhr.send();
}
function shield_action(login,operation,data,success=()=>{},failure=()=>{}){
	var xhr=new XMLHttpRequest();
	xhr.open('POST','http://127.0.0.1:51280/action');
	xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	xhr.onreadystatechange = function() {
		if(4==xhr.readyState && 200==xhr.status){
			json=JSON.parse(xhr.responseText);
			if(typeof json.error !== 'undefined'){
				failure(json.error);
			}
			else{
				shield_status(json.action,success,failure);
			}
		}
	}
	xhr.onerror=function(){
		failure();
	}
	let xhr_data='login='+login+'&operation='+operation;
	for(i in data){
		if(typeof data[i] === 'object'){
			xhr_data+='&data['+i+']='+encodeURIComponent(JSON.stringify(data[i]));
		}
		else{
			xhr_data+='&data['+i+']='+encodeURIComponent(data[i]);
		}
	}
	xhr.send(xhr_data);
}
function shield_control(){
	if(0!=$('.shield-auth-control').length){
		let locked=function(){
			let view=$('.shield-auth-control');
			let result='';
			result+='<p>'+l10n.shield.locked+' <a class="shield-auth-control-action link">'+l10n.shield.link+'</a>.</p>';
			view.html(result);
		}
		let accounts_list=function(json=[]){
			let view=$('.shield-auth-control');
			let result='';
			result+='<p>'+l10n.shield.accounts+'</p>';
			result+='<p><select class="shield-auth-accounts round">';
			for(i in json.accounts){
				let account_login=escape_html(json.accounts[i]);
				result+='<option value="'+account_login+'"'+(json.default==account_login?' selected':'')+'>'+account_login+'</option>';
			}
			result+='</select><p>';
			result+='<p><span class="shield-auth-error"></span></p>';
			result+='<p><input type="button" class="shield-auth-action button" value="'+l10n.shield.login+'"></p>';
			view.html(result);
		}
		let xhr=new XMLHttpRequest();
		xhr.open('POST','http://127.0.0.1:51280/accounts/');
		xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		xhr.onreadystatechange = function() {
			if(4==xhr.readyState && 200==xhr.status){
				json=JSON.parse(xhr.responseText);
				if(typeof json.error !== 'undefined'){
					locked();
				}
				else{
					accounts_list(json);
				}
			}
		}
		xhr.onerror=function(){
			let view=$('.shield-auth-control');
			let result='';
			result+='<p>'+l10n.shield.not_found+'</p>';
			view.html(result);
		}
		xhr.send();
	}
}
function try_auth_shield(login){
	$('.shield-auth-action').addClass('disabled');
	$('.shield-auth-error').html('');
	let xhr=new XMLHttpRequest();
	xhr.open('POST','http://127.0.0.1:51280/accounts/'+login+'/');
	xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	xhr.onreadystatechange = function() {
		if(4==xhr.readyState && 200==xhr.status){
			json=JSON.parse(xhr.responseText);
			if(typeof json.error !== 'undefined'){
				$('.shield-auth-error').html(l10n.shield.miss_user);
				$('.shield-auth-action').removeClass('disabled');
				return;
			}
			else{
				users[login]={'posting_key':'','active_key':'','shield':true};
				if(json.posting){
					users[login].posting_key=true;
				}
				if(json.active){
					users[login].active_key=true;
				}
				current_user=login;
				session_generate();
			}
		}
	}
	xhr.onerror=function(){
		console.log('error',xhr.status);
		console.log('readyState',xhr.readyState);
	}
	xhr.send();

}
function view_session(){
	if(''!=current_user){
		let result='';
		if(users[current_user].shield){
			result+='<img class="start-shield-action hide" src="/shield-icon.svg">';
		}
		result+='<a href="/@'+current_user+'/">'+current_user+'</a>';
		result+=' <a class="auth-logout icon"><i class="fas fa-fw fa-sign-out-alt"></i></a>';
		$('.header .account').html(result);
		view_energy();
	}
	else{
		$('.header .account').html('<a href="/login/" class="icon" title="'+l10n.global.auth+'"><i class="fas fa-fw fa-sign-in-alt"></i></a>');
	}
}
function view_energy(){
	$('.header .energy').css('display','inline-block');
	$('.header .energy').html('&hellip;');
	if(''!=current_user){
		$('.header .energy').css('display','inline-block');
		gate.api.getAccounts([current_user],function(err,response){
			if(!err){
				if(typeof response[0] !== 'undefined'){
					let last_vote_time=Date.parse(response[0].last_vote_time);
					let delta_time=parseInt((new Date().getTime() - last_vote_time+(new Date().getTimezoneOffset()*60000))/1000);
					let energy=response[0].energy;
					let new_energy=parseInt(energy+(delta_time*10000/432000));//CHAIN_ENERGY_REGENERATION_SECONDS 5 days
					if(new_energy>10000){
						new_energy=10000;
					}
					let energy_icon='<i class="fas fa-battery-empty"></i>';
					if(new_energy>=2000){
						energy_icon='<i class="fas fa-battery-quarter"></i>';
					}
					if(new_energy>=4000){
						energy_icon='<i class="fas fa-battery-half"></i>';
					}
					if(new_energy>=6000){
						energy_icon='<i class="fas fa-battery-three-quarters"></i>';
					}
					if(new_energy>=9000){
						energy_icon='<i class="fas fa-battery-full"></i>';
					}
					let awarded_rshares=parseInt(response[0].awarded_rshares);
					$('.header .energy').html((new_energy/100)+'% '+energy_icon);
				}
			}
			else{
				if(typeof gate.api.ws == 'undefined'){
					select_best_gate();
				}
			}
		});
	}
	else{
		$('.header .energy').css('display','none');
	}
}
function wallet_withdraw_shares(disable=false){
	if(disable){
		let withdraw_shares_success=function(result){
			wallet_control(true);
			add_notify(l10n.wallet.withdraw_disabled);
		}
		let withdraw_shares_failure=function(err){
			add_notify(l10n.global.error,true);
			if(typeof err.payload !== 'undefined'){
				add_notify(err.payload.error.data.stack[0].format,true);
			}
			console.log(err);
		}
		if(users[current_user].shield){
			shield_action(current_user,'withdraw_vesting',{vesting_shares:'0.000000 SHARES'},withdraw_shares_success,withdraw_shares_failure);
		}
		else{
			gate.broadcast.withdrawVesting(users[current_user].active_key,current_user,'0.000000 SHARES',function(err,result){
				if(!err){
					withdraw_shares_success(result);
				}
				else{
					withdraw_shares_failure(err);
				}
			});
		}
	}
	else{
		gate.api.getAccounts([current_user],function(err,response){
			if(typeof response[0] !== 'undefined'){
				vesting_shares=parseFloat(response[0].vesting_shares);
				delegated_vesting_shares=parseFloat(response[0].delegated_vesting_shares);
				shares=vesting_shares-delegated_vesting_shares;
				let fixed_shares=''+shares.toFixed(6)+' SHARES';
				let withdraw_shares_success=function(result){
					wallet_control(true);
					add_notify(l10n.wallet.withdraw_enabled);
				}
				let withdraw_shares_failure=function(err){
					add_notify(l10n.global.error,true);
					if(typeof err.payload !== 'undefined'){
						add_notify(err.payload.error.data.stack[0].format,true);
					}
				}
				if(users[current_user].shield){
					shield_action(current_user,'withdraw_vesting',{vesting_shares:fixed_shares},withdraw_shares_success,withdraw_shares_failure);
				}
				else{
					gate.broadcast.withdrawVesting(users[current_user].active_key,current_user,fixed_shares,function(err,result){
						if(!err){
							withdraw_shares_success(result);
						}
						else{
							withdraw_shares_failure(err);
						}
					});
				}
			}
			else{
				add_notify(l10n.errors.user_not_found,true);
			}
		});
	}
}
function download(filename, text) {
	var link = document.createElement('a');
	link.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
	link.setAttribute('download', filename);

	if (document.createEvent) {
		var event = document.createEvent('MouseEvents');
		event.initEvent('click', true, true);
		link.dispatchEvent(event);
	}
	else {
		link.click();
	}
}
function invite_registration(secret_key,receiver,private_key){
	public_key=gate.auth.wifToPublic(private_key);

	let invite_success=function(result){
		add_notify(l10n.invite.reg_success);
		download('viz-registration.txt','VIZ.World registration\r\nAccount login: '+receiver+'\r\nPrivate key: '+private_key+'');
	}
	let invite_failure=function(err){
		add_notify(l10n.invite.reg_failure,true);
		gate.api.getAccounts([receiver],function(err,response){
			if(!err){
				add_notify(l10n.invite.login+' '+receiver+' '+l10n.invite.unavailable,true);
			}
		});
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	}
	let find_shield=false;
	if(current_user){
		if(users[current_user].shield){
			shield_action(current_user,'invite_registration',{new_account_name:receiver,invite_secret:secret_key,new_account_key:public_key},invite_success,invite_failure);
			find_shield=true;
		}
	}
	if(!find_shield){
		gate.broadcast.inviteRegistration('5KcfoRuDfkhrLCxVcE9x51J6KN9aM9fpb78tLrvvFckxVV6FyFW','invite',receiver,secret_key,public_key,function(err,result){
			if(!err){
				invite_success(result);
			}
			else{
				invite_failure(err);
			}
		});
	}
}
function paid_subscribe(account,level,amount,period,auto_renewal){
	let action_success=function(result){
		add_notify(l10n.ps.subscribe_success);
	}
	let action_failure=function(err){
		add_notify(l10n.ps.subscribe_failure,true);
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	}
	let find_shield=false;
	if(current_user){
		if(users[current_user].shield){
			shield_action(current_user,'paid_subscribe',{account:account,level:level,period:period,auto_renewal:auto_renewal},action_success,action_failure);
			find_shield=true;
		}
	}
	if(!find_shield){
		gate.broadcast.paidSubscribe(users[current_user].active_key,current_user,account,level,amount,period,auto_renewal,function(err,result){
			if(!err){
				action_success(result);
			}
			else{
				action_failure(err);
			}
		});
	}
}
function set_paid_subscription(url,levels,amount,period){
	let action_success=function(result){
		add_notify(l10n.ps.set_success);
	}
	let action_failure=function(err){
		add_notify(l10n.ps.set_failure,true);
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	}
	let find_shield=false;
	if(current_user){
		if(users[current_user].shield){
			shield_action(current_user,'set_paid_subscription',{url:url,levels:levels,amount:amount,period:period},action_success,action_failure);
			find_shield=true;
		}
	}
	if(!find_shield){
		gate.broadcast.setPaidSubscription(users[current_user].active_key,current_user,url,levels,amount,period,function(err,result){
			if(!err){
				action_success(result);
			}
			else{
				action_failure(err);
			}
		});
	}
}
function invite_claim(secret_key,receiver){
	let invite_success=function(result){
		add_notify(l10n.invite.claim_success);
	}
	let invite_failure=function(err){
		add_notify(l10n.invite.claim_failure,true);
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	}
	let find_shield=false;
	if(current_user){
		if(users[current_user].shield){
			shield_action(current_user,'claim_invite_balance',{receiver:receiver,invite_secret:secret_key},invite_success,invite_failure);
			find_shield=true;
		}
	}
	if(!find_shield){
		gate.broadcast.claimInviteBalance('5KcfoRuDfkhrLCxVcE9x51J6KN9aM9fpb78tLrvvFckxVV6FyFW','invite',receiver,secret_key,function(err,result){
			if(!err){
				invite_success(result);
			}
			else{
				invite_failure(err);
			}
		});
	}
}
function reset_account_with_general_key(account_login,owner_key,general_key){
	let auth_types = ['regular','active','master','memo'];
	let keys=gate.auth.getPrivateKeys(account_login,general_key,auth_types);
	let owner = {
		'weight_threshold': 1,
		'account_auths': [],
		'key_auths': [
			[keys.masterPubkey, 1]
		]
	};
	let active = {
		'weight_threshold': 1,
		'account_auths': [],
		'key_auths': [
			[keys.activePubkey, 1]
		]
	};
	let posting = {
		'weight_threshold': 1,
		'account_auths': [],
		'key_auths': [
			[keys.regularPubkey, 1]
		]
	};
	let memo_key=keys.memoPubkey;
	gate.api.getAccounts([account_login],function(err,response){
		if(0==response.length){
			err=true;
		}
		if(!err){
			let json_metadata=response[0].json_metadata;

			let account_success=function(result){
				add_notify(l10n.reset_account.success);
				download('viz-reset-account.txt','VIZ.World Account: '+account_login+'\r\nGeneral key (for private keys): '+general_key+'\r\nPrivate master key: '+keys.master+'\r\nPrivate active key: '+keys.active+'\r\nPrivate regular key: '+keys.regular+'\r\nPrivate memo key: '+keys.memo+'');
				if(typeof users[account_login] !== 'undefined'){
					if(''!=users[account_login].posting_key){
						users[account_login].posting_key=keys.regular;
					}
					if(''!=users[account_login].active_key){
						users[account_login].active_key=keys.active;
					}
				}
			}
			let account_failure=function(err){
				add_notify(l10n.reset_account.error,true);
				if(typeof err.message !== 'undefined'){
					add_notify(err.message,true);
				}
				else{
					if(typeof err.payload !== 'undefined'){
						add_notify(err.payload.error.data.stack[0].format,true);
					}
				}
			}
			let find_shield=false;
			if(current_user){
				if(users[current_user].shield){
					shield_action(current_user,'account_update',{owner:JSON.stringify(owner),active:JSON.stringify(active),posting:JSON.stringify(posting),memo_key:memo_key,json_metadata:json_metadata},account_success,account_failure);
					find_shield=true;
				}
			}
			if(!find_shield){
				gate.broadcast.accountUpdate(owner_key,account_login,owner,active,posting,memo_key,json_metadata,function(err,result){
					if(!err){
						account_success(result);
					}
					else{
						account_failure(err);
					}
				});
			}
		}
		else{
			add_notify(l10n.errors.user_not_found,true);
		}
	});
}
function create_account_with_general_key(account_login,token_amount,shares_amount,general_key){
	let fixed_token_amount=''+parseFloat(token_amount).toFixed(3)+' VIZ';
	let fixed_shares_amount=''+parseFloat(shares_amount).toFixed(6)+' SHARES';
	if(''==token_amount){
		fixed_token_amount='0.000 VIZ';
	}
	if(''==shares_amount){
		fixed_shares_amount='0.000000 SHARES';
	}
	let auth_types = ['regular','active','master','memo'];
	let keys=gate.auth.getPrivateKeys(account_login,general_key,auth_types);
	let owner = {
		'weight_threshold': 1,
		'account_auths': [],
		'key_auths': [
			[keys.masterPubkey, 1]
		]
	};
	let active = {
		'weight_threshold': 1,
		'account_auths': [],
		'key_auths': [
			[keys.activePubkey, 1]
		]
	};
	let posting = {
		"weight_threshold": 1,
		"account_auths": [],
		"key_auths": [
			[keys.regularPubkey, 1]
		]
	};
	let memo_key=keys.memoPubkey;
	let json_metadata='';
	let referrer='';

	let account_success=function(result){
		add_notify(l10n.create_account.success);
		download('viz-account.txt','VIZ.World Account: '+account_login+'\r\nGeneral key (for private keys): '+general_key+'\r\nPrivate master key: '+keys.master+'\r\nPrivate active key: '+keys.active+'\r\nPrivate regular key: '+keys.regular+'\r\nPrivate memo key: '+keys.memo+'');
		gate.api.getAccounts([current_user],function(err,response){
			if(!err){
				$('.control .create-account-control .token[data-symbol=VIZ] .amount').html(parseFloat(response[0]['balance']));
				$('.control .create-account-control .token[data-symbol=SHARES] .amount').html(parseFloat(response[0]['vesting_shares']));
			}
		});
	}
	let account_failure=function(err){
		add_notify(l10n.create_account.error,true);
		gate.api.getAccounts([account_login],function(err,response){
			if(!err){
				add_notify(l10n.create_account.login_is_not_available,true);
			}
		});
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	}
	if(users[current_user].shield){
		shield_action(current_user,'account_create',{referrer:current_user,fee:fixed_token_amount,delegation:fixed_shares_amount,new_account_name:account_login,owner:JSON.stringify(owner),active:JSON.stringify(active),posting:JSON.stringify(posting),memo_key:memo_key,json_metadata:json_metadata},account_success,account_failure);
	}
	else{
		gate.broadcast.accountCreate(users[current_user].active_key,fixed_token_amount,fixed_shares_amount,current_user,account_login,owner,active,posting,memo_key,json_metadata, referrer,[],function(err,result){
			if(!err){
				account_success(result);
			}
			else{
				account_failure(err);
			}
		});
	}
}
function invite_create(private_key,public_key,amount){
	amount=parseFloat(amount);
	let fixed_amount=''+amount.toFixed(3)+' VIZ';

	let invite_success=function(result){
		download('viz-invite.txt','VIZ Blockchain invite code with balance: '+fixed_amount+'\r\nPublic key (for check): '+public_key+'\r\nPrivate key (for activation): '+private_key+'\r\n\r\nYou can check code on https://viz.world/tools/invites/\r\n\r\nRegistration new VIZ account using invite-code balance: https://viz.world/tools/invites/registration/\r\nClaim invite-code balance to existed VIZ account: https://viz.world/tools/invites/claim/');
		add_notify(l10n.invite.create_success);
	}
	let invite_failure=function(err){
		add_notify(l10n.invite.create_failure,true);
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
		console.log(err);
	}
	if(users[current_user].shield){
		shield_action(current_user,'create_invite',{balance:fixed_amount,invite_key:public_key},invite_success,invite_failure);
	}
	else{
		gate.broadcast.createInvite(users[current_user].active_key,current_user,fixed_amount,public_key,function(err,result){
			if(!err){
				invite_success(result);
			}
			else{
				invite_failure(err);
			}
		});
	}
}
function wallet_delegate(recipient,amount){
	let login=recipient.toLowerCase();
	if('@'==login.substring(0,1)){
		login=login.substring(1);
	}
	login=login.trim();
	if(login){
		gate.api.getAccounts([login],function(err,response){
			if(typeof response[0] !== 'undefined'){
				amount=parseFloat(amount);
				let fixed_amount=''+amount.toFixed(6)+' SHARES';

				let delegate_success=function(result){
					add_notify(l10n.wallet.delegate_success);
					delegation_control();
				}
				let delegate_failure=function(err){
					add_notify(l10n.wallet.delegate_failure,true);
					if(typeof err.payload !== 'undefined'){
						add_notify(err.payload.error.data.stack[0].format,true);
					}
				}
				if(users[current_user].shield){
					shield_action(current_user,'delegate_vesting_shares',{delegatee:login,vesting_shares:fixed_amount},delegate_success,delegate_failure);
				}
				else{
					gate.broadcast.delegateVestingShares(users[current_user].active_key,current_user,login,fixed_amount,function(err,result){
						if(!err){
							delegate_success(result);
						}
						else{
							delegate_failure(err);
						}
					});
				}
			}
			else{
				add_notify(l10n.errors.user_not_found,true);
			}
		});
	}
}
function escape_html(text){
	if(typeof text !== 'undefined'){
		var map={
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g,function(m){return map[m];});
	}
	else{
		return '';
	}
}
function unescape_html(text){
	text=text.replace(/&#039;/g,'\'');
	text=text.replace(/&quot;/g,'"');
	text=text.replace(/&gt;/g,'>');
	text=text.replace(/&lt;/g,'<');
	text=text.replace(/&amp;/g,'&');
	return text;
}
function wallet_transfer(recipient,amount,memo){
	$('.wallet-control .wallet-transfer-action').addClass('disabled');
	let login=recipient.toLowerCase();
	if('@'==login.substring(0,1)){
		login=login.substring(1);
	}
	login=login.trim();
	if(login){
		gate.api.getAccounts([login],function(err,response){
			if(typeof response[0] !== 'undefined'){
				amount=parseFloat(amount);
				let fixed_amount=''+amount.toFixed(3)+' VIZ';
				let transfer_success=function(result){
					let tr_html='<tr class="wallet-history-out new"><td>'+date_str(-1,true,true,true)+'</td><td><span class="wallet-recipient-set">'+current_user+'</span></td><td><span class="wallet-recipient-set">'+recipient+'</span></td><td>'+amount.toFixed(3)+'</td><td>VIZ</td><td class="wallet-memo-set">'+escape_html(memo)+'</td></tr>';
					$('.wallet-history tbody').prepend(tr_html);
					$('.wallet-control input[name=amount]').val('0');
					wallet_control(true);
					$('.wallet-control .wallet-transfer-action').removeClass('disabled');
				}
				let transfer_failure=function(err){
					add_notify(l10n.wallet.transfer_failure,true);
					$('.wallet-control .wallet-transfer-action').removeClass('disabled');
					if(typeof err.payload !== 'undefined'){
						add_notify(err.payload.error.data.stack[0].format,true);
					}
				}
				var shares=$('.wallet-control input[name=shares]').prop('checked');
				if(shares){
					memo='TO VESTING SHARES';
					if(users[current_user].shield){
						shield_action(current_user,'transfer_to_vesting',{to:login,amount:fixed_amount},transfer_success,transfer_failure);
					}
					else{
						gate.broadcast.transferToVesting(users[current_user].active_key,current_user,login,fixed_amount,function(err,result){
							if(!err){
								transfer_success(result);
							}
							else{
								transfer_failure(err);
							}
						});
					}
				}
				else{
					if(users[current_user].shield){
						shield_action(current_user,'transfer',{to:login,amount:fixed_amount,memo:memo},transfer_success,transfer_failure);
					}
					else{
						gate.broadcast.transfer(users[current_user].active_key,current_user,login,fixed_amount,memo,function(err,result){
							if(!err){
								transfer_success(result);
							}
							else{
								transfer_failure(err);
							}
						});
					}
				}
			}
			else{
				add_notify(l10n.errors.user_not_found,true);
				$('.wallet-control .wallet-transfer-action').removeClass('disabled');
			}
		});
	}
	else{
		add_notify(l10n.errors.user_not_provided,true);
		$('.wallet-control .wallet-transfer-action').removeClass('disabled');
	}
}
function committee_worker_create_request(url,worker,min_amount,max_amount,duration){
	if(duration<=30){
		duration=duration*3600*24;
	}
	let committee_success=function(result){
		add_notify(l10n.committee.create_success);
		document.location='/committee/';
	}
	let committee_failure=function(err){
		add_notify(l10n.global.error,true);
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	}
	if(users[current_user].shield){
		shield_action(current_user,'committee_worker_create_request',{url:url,worker:worker,required_amount_min:min_amount,required_amount_max:max_amount,duration:duration},committee_success,committee_failure);
	}
	else{
		gate.broadcast.committeeWorkerCreateRequest(users[current_user]['posting_key'],current_user,url,worker,min_amount,max_amount,duration,function(err,result) {
			if(!err){
				committee_success(result);
			}
			else{
				committee_failure(err);
			}
		});
	}
}
function committee_cancel_request(request_id){
	let committee_success=function(result){
		committee_control();
		add_notify(l10n.committee.cancel_success);
	}
	let committee_failure=function(err){
		add_notify(l10n.global.error,true);
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	}
	if(users[current_user].shield){
		shield_action(current_user,'committee_worker_cancel_request',{request_id:request_id},committee_success,committee_failure);
	}
	else{
		gate.broadcast.committeeWorkerCancelRequest(users[current_user]['posting_key'],current_user,parseInt(request_id),function(err,result) {
			if(!err){
				committee_success(result);
			}
			else{
				committee_failure(err);
			}
		});
	}
}
function committee_vote_request(request_id,percent){
	let committee_success=function(result){
		add_notify(l10n.committee.vote_success);
	}
	let committee_failure=function(err){
		add_notify(l10n.global.error,true);
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	}
	if(users[current_user].shield){
		shield_action(current_user,'committee_vote_request',{request_id:request_id,vote_percent:percent*100},committee_success,committee_failure);
	}
	else{
		gate.broadcast.committeeVoteRequest(users[current_user]['posting_key'],current_user,parseInt(request_id),percent*100,function(err,result) {
			if(!err){
				committee_success(result);
			}
			else{
				committee_failure(err);
			}
		});
	}
}
function witness_update(witness_login,url,signing_key){
	if(current_user!=witness_login){
		add_notify(l10n.witness.invalid_user,true);
	}
	else{
		if(''==signing_key){
			signing_key=empty_signing_key;
		}
		let witness_success=function(result){
			witness_control();
			add_notify(l10n.witness.update_success);
		}
		let witness_failure=function(err){
			add_notify(l10n.global.error,true);
			if(typeof err.payload !== 'undefined'){
				add_notify(err.payload.error.data.stack[0].format,true);
			}
		}
		if(users[current_user].shield){
			shield_action(current_user,'witness_update',{url:url,block_signing_key:signing_key},witness_success,witness_failure);
		}
		else{
			gate.broadcast.witnessUpdate(users[current_user]['active_key'],current_user,url,signing_key,function(err,result){
				if(!err){
					witness_success(result);
				}
				else{
					witness_failure(err);
				}
			});
		}
	}
}
function witness_chain_properties_update(witness_login,url,signing_key){
	if(current_user!=witness_login){
		add_notify(l10n.witness.invalid_user,true);
	}
	else{
		gate.api.getWitnessByAccount(witness_login,function(err,response){
			if(!err){
				let props=response.props;
				props.account_creation_fee=$('.witness-control[data-witness='+witness_login+'] input[name=account_creation_fee]').val();
				props.create_account_delegation_ratio=parseInt($('.witness-control[data-witness='+witness_login+'] input[name=create_account_delegation_ratio]').val());
				props.create_account_delegation_time=parseInt($('.witness-control[data-witness='+witness_login+'] input[name=create_account_delegation_time]').val());
				props.bandwidth_reserve_percent=100*parseFloat($('.witness-control[data-witness='+witness_login+'] input[name=bandwidth_reserve_percent]').val());
				props.bandwidth_reserve_below=$('.witness-control[data-witness='+witness_login+'] input[name=bandwidth_reserve_below]').val();
				props.committee_request_approve_min_percent=100*parseFloat($('.witness-control[data-witness='+witness_login+'] input[name=committee_request_approve_min_percent]').val());
				props.flag_energy_additional_cost=100*parseFloat($('.witness-control[data-witness='+witness_login+'] input[name=flag_energy_additional_cost]').val());
				props.min_curation_percent=100*parseFloat($('.witness-control[data-witness='+witness_login+'] input[name=min_curation_percent]').val());
				props.max_curation_percent=100*parseFloat($('.witness-control[data-witness='+witness_login+'] input[name=max_curation_percent]').val());
				props.min_delegation=$('.witness-control[data-witness='+witness_login+'] input[name=min_delegation]').val();
				props.vote_accounting_min_rshares=parseInt($('.witness-control[data-witness='+witness_login+'] input[name=vote_accounting_min_rshares]').val());
				props.maximum_block_size=parseInt($('.witness-control[data-witness='+witness_login+'] input[name=maximum_block_size]').val());

				props.inflation_witness_percent=100*parseFloat($('.witness-control[data-witness='+witness_login+'] input[name=inflation_witness_percent]').val());
				props.inflation_ratio_committee_vs_reward_fund=100*parseFloat($('.witness-control[data-witness='+witness_login+'] input[name=inflation_ratio_committee_vs_reward_fund]').val());
				props.inflation_recalc_period=parseInt($('.witness-control[data-witness='+witness_login+'] input[name=inflation_recalc_period]').val());

				let properties_success=function(result){
					witness_control();
					add_notify(l10n.witness.properties_success);
				}
				let properties_failure=function(err){
					add_notify(l10n.global.error,true);
					if(typeof err.payload !== 'undefined'){
						add_notify(err.payload.error.data.stack[0].format,true);
					}
				}
				if(users[current_user].shield){
					shield_action(current_user,'versioned_chain_properties_update',{props:JSON.stringify([1,props])},properties_success,properties_failure);
				}
				else{
					gate.broadcast.versionedChainPropertiesUpdate(users[current_user]['active_key'],current_user,[1,props],function(err,result){
						if(!err){
							properties_success(result);
						}
						else{
							properties_failure(err);
						}
					});
				}
			}
		});
	}
}
function award_content(author,permlink,beneficiaries,target){
	let weight=10000/10/5;
	if($('.header-menu-el.energy').hasClass('powerup')){
		weight=10000/5;
		$('.header-menu-el.energy').removeClass('powerup');
	}
	let award_success=function(result){
		target.find('.award-action').addClass('active').attr('title',l10n.global.spend_energy+': '+(weight/100)+'%');
		let votes_count=target.find('.votes_count span');
		votes_count.html(1+parseInt(votes_count.html()));
		add_notify(l10n.success.award_author);
		view_energy();
	}
	let award_failure=function(err){
		add_notify(l10n.global.error,true);
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	}
	let memo=author+'/'+permlink;
	let beneficiaries_list=[];
	if(typeof beneficiaries !== 'undefined'){
		beneficiaries_list=JSON.parse(unescape_html(beneficiaries));
	}
	if(users[current_user].shield){
		shield_action(current_user,'award',{receiver:author,memo:memo,energy:weight,beneficiaries:beneficiaries_list},award_success,award_failure);
	}
	else{
		gate.broadcast.award(users[current_user].posting_key,current_user,author,weight,0,memo,beneficiaries_list,function(err,result){
			if(!err){
				award_success(result);
			}
			else{
				award_failure(err);
			}
		});
	}
}
function vote_witness(witness_login,value){
	let vote_witness_success=function(result){
		add_notify(l10n.witness.vote_success);
		witness_control();
	}
	let vote_witness_failure=function(err){
		add_notify(l10n.global.error,true);
		if(typeof err.payload !== 'undefined'){
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	}
	if(users[current_user].shield){
		shield_action(current_user,'account_witness_vote',{witness:witness_login,approve:value},vote_witness_success,vote_witness_failure);
	}
	else{
		gate.broadcast.accountWitnessVote(users[current_user]['active_key'],current_user,witness_login,value,function(err, result){
			if(!err){
				vote_witness_success(result);
			}
			else{
				vote_witness_failure(err);
			}
		});
	}
}
function witness_control(){
	if(0!=$('.witness-votes').length){
		if(''!==current_user){
			let view=$('.witness-votes');
			let result='';
			result+='<h3>'+l10n.witness.votes_list+'</h3>';
			view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> '+l10n.global.loading+'&hellip;</p>');
			gate.api.getAccounts([current_user],function(err,response){
				result+='<p>';
				for(vote_id in response[0].witness_votes){
					result+=(0==vote_id?'':', ')+'<a href="/witnesses/'+response[0].witness_votes[vote_id]+'/">'+response[0].witness_votes[vote_id]+'</a>';
				}
				result+='</p>';
				view.html(result);
			});
		}
	}
	if(0!=$('.control .witness-vote').length){
		$('.witness-vote').each(function(){
			let witness_login=$(this).attr('data-witness');
			let view=$(this);
			let result='';
			result+='<h3>'+l10n.witness.vote_caption+' '+witness_login+'</h3>';
			view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> '+l10n.global.loading+'&hellip;</p>');
			if(''==users[current_user].active_key){
				result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
				view.html(result);
			}
			else{
				gate.api.getAccounts([current_user],function(err,response){
					if(typeof response[0] !== 'undefined'){
						if(response[0].witness_votes.includes(witness_login)){
							result+='<input type="button" class="witness-vote-action button negative" data-value="false" value="'+l10n.witness.unvote_action+'">';
						}
						else{
							result+='<input type="button" class="witness-vote-action button" data-value="true" value="'+l10n.witness.vote_action+'">';
						}
						view.html(result);
					}
				});
			}
		});
	}
	if(0!=$('.control .witness-control').length){
		$('.witness-control').each(function(){
			let witness_login=$(this).attr('data-witness');
			if(current_user==witness_login){
				let view=$(this);
				let result='';
				result+='<h3>'+l10n.witness.manage_caption+' '+witness_login+'</h3>';
				view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> '+l10n.global.loading+'&hellip;</p>');
				if(''==users[current_user].active_key){
					result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
					view.html(result);
				}
				else{
					gate.api.getWitnessByAccount(witness_login,function(err,response){
						if(!err){
							result+='<label class="input-descr">'+l10n.witness.manage_url+':<input type="text" name="url" class="round wide" value="'+response.url+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.manage_public_key+':<input type="text" name="signing_key" class="round wide" value="'+response.signing_key+'" placeholder="'+empty_signing_key+'"></label>';
							result+='<input type="button" class="witness-update-action button" value="'+l10n.witness.manage_action+'">';

							result+='<h4>'+l10n.witness.params_caption+'</h4>';
							result+='<label class="input-descr">'+l10n.witness.params_account_creation_fee+':<input type="text" name="account_creation_fee" class="witness-chain-properties round wide" value="'+response.props.account_creation_fee+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.params_create_account_delegation_ratio+':<input type="text" name="create_account_delegation_ratio" class="witness-chain-properties round wide" value="'+response.props.create_account_delegation_ratio+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.params_create_account_delegation_time+':<input type="text" name="create_account_delegation_time" class="witness-chain-properties round wide" value="'+response.props.create_account_delegation_time+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.params_bandwidth_reserve_percent+':<input type="text" name="bandwidth_reserve_percent" class="witness-chain-properties round wide" value="'+response.props.bandwidth_reserve_percent/100+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.params_bandwidth_reserve_below+':<input type="text" name="bandwidth_reserve_below" class="witness-chain-properties round wide" value="'+response.props.bandwidth_reserve_below+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.params_committee_request_approve_min_percent+':<input type="text" name="committee_request_approve_min_percent" class="witness-chain-properties round wide" value="'+response.props.committee_request_approve_min_percent/100+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.params_min_delegation+':<input type="text" name="min_delegation" class="witness-chain-properties round wide" value="'+response.props.min_delegation+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.params_vote_accounting_min_rshares+':<input type="text" name="vote_accounting_min_rshares" class="witness-chain-properties round wide" value="'+response.props.vote_accounting_min_rshares+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.params_maximum_block_size+':<input type="text" name="maximum_block_size" class="witness-chain-properties round wide" value="'+response.props.maximum_block_size+'"></label>';
							result+='<hr>';
							if(typeof response.props.inflation_witness_percent == 'undefined'){
								response.props.inflation_witness_percent=2000;
							}
							if(typeof response.props.inflation_ratio_committee_vs_reward_fund == 'undefined'){
								response.props.inflation_ratio_committee_vs_reward_fund=5000;
							}
							if(typeof response.props.inflation_recalc_period == 'undefined'){
								response.props.inflation_recalc_period=806400;
							}
							result+='<label class="input-descr">'+l10n.witness.params_inflation_witness_percent+':<input type="text" name="inflation_witness_percent" class="witness-chain-properties round wide" value="'+response.props.inflation_witness_percent/100+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.params_inflation_ratio_committee_vs_reward_fund+':<input type="text" name="inflation_ratio_committee_vs_reward_fund" class="witness-chain-properties round wide" value="'+response.props.inflation_ratio_committee_vs_reward_fund/100+'"></label>';
							result+='<label class="input-descr">'+l10n.witness.params_inflation_recalc_period+':<input type="text" name="inflation_recalc_period" class="witness-chain-properties round wide" value="'+response.props.inflation_recalc_period+'"></label>';
							result+='<hr>';
							result+='<label class="input-descr" style="opacity:0.4">'+l10n.witness.params_min_curation_percent+':<input type="text" name="min_curation_percent" class="witness-chain-properties round wide" value="'+response.props.min_curation_percent/100+'"></label>';
							result+='<label class="input-descr" style="opacity:0.4">'+l10n.witness.params_max_curation_percent+':<input type="text" name="max_curation_percent" class="witness-chain-properties round wide" value="'+response.props.max_curation_percent/100+'"></label>';
							result+='<label class="input-descr" style="opacity:0.4">'+l10n.witness.params_flag_energy_additional_cost+':<input type="text" name="flag_energy_additional_cost" class="witness-chain-properties round wide" value="'+response.props.flag_energy_additional_cost/100+'"></label>';
							result+='<input type="button" class="witness-chain-properties-update-action button" value="'+l10n.witness.params_action+'">';
							view.html(result);
						}
					});
				}
			}
		});
	}
}
function pass_gen(length=100,to_wif=true){
	let charset='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+-=_:;.,@!^&*$';
	let ret='';
	for (var i=0,n=charset.length;i<length;++i){
		ret+=charset.charAt(Math.floor(Math.random()*n));
	}
	if(!to_wif){
		return ret;
	}
	let wif=gate.auth.toWif('',ret,'')
	return wif;
}
function generate_general_key(force=false){
	if(force){
		$('input.generate-general').val(pass_gen(50,false));
	}
	else{
		if(0<$('input.generate-general').length){
			if(''==$('input.generate-general').val()){
				$('input.generate-general').val(pass_gen(50,false));
			}
		}
	}
}
function generate_key(force=false){
	if(force){
		$('input.generate-private').val(pass_gen());
		if(0<$('input.generate-public').length){
			$('input.generate-public').val(gate.auth.wifToPublic($('input.generate-private').val()));
		}
	}
	else{
		if(0<$('input.generate-private').length){
			if(''==$('input.generate-private').val()){
				$('input.generate-private').val(pass_gen());
				$('input.generate-public').val(gate.auth.wifToPublic($('input.generate-private').val()));
			}
		}
	}
}
function reset_account_control(){
	if(0!=$('.control .reset-account-control').length){
		let view=$('.reset-account-control');
		let result='';
		view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> '+l10n.global.loading+'&hellip;</p>');
		result+='<p><label class="input-descr">'+l10n.reset_account.login+':<br><input type="text" name="account_login" class="round" value="'+current_user+'"></label></p>';
		result+='<p><label class="input-descr">'+l10n.reset_account.master_key+':<br><input type="text" name="owner_key" class="round wide"></label></p>';
		result+='<p class="input-descr">'+l10n.reset_account.global_password+' (<i class="fas fa-fw fa-random"></i> <a class="generate-general-action unselectable">'+l10n.global.generate_new+'</a>):<br><input type="text" name="general_key" class="generate-general round wide"></p>';
		result+='<p><a class="reset-account-action button">'+l10n.reset_account.action+'</a>';
		view.html(result);
		generate_general_key();
	}
}
function create_account_control(){
	if(0!=$('.control .create-account-control').length){
		let view=$('.create-account-control');
		let result='';
		if(''==current_user){
			result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
			view.html(result);
		}
		else{
			result+='<p>'+l10n.create_account.descr+'</p>';
			view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> '+l10n.global.loading+'&hellip;</p>');
			gate.api.getChainProperties(function(err,response){
				let props=response;
				gate.api.getAccounts([current_user],function(err,response){
					if(typeof response[0] !== 'undefined'){
						result+='<p>'+l10n.wallet.balance+': <span class="token" data-symbol="VIZ"><span class="amount">'+parseFloat(response[0]['balance'])+'</span> VIZ</span></p>';
						result+='<p>'+l10n.wallet.shares+': <span class="token" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['vesting_shares'])+'</span> SHARES</span></p>';
						if(''==users[current_user].active_key){
							result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
						}
						else{
							result+='<p><label class="input-descr">'+l10n.create_account.login+':<br><input type="text" name="account_login" class="round"></label></p>';
							result+='<p><label class="input-descr">'+l10n.create_account.tokens+':<br><input type="text" name="token_amount" class="round" placeholder="'+props.account_creation_fee+'" value="'+props.account_creation_fee+'"></label></p>';
							result+='<p><label class="input-descr">'+l10n.create_account.shares+':<br><input type="text" name="shares_amount" class="round" placeholder="'+(parseFloat(props.account_creation_fee)*props.create_account_delegation_ratio).toFixed(6)+' SHARES"></label></p>';
							result+='<p class="input-descr">'+l10n.create_account.global_password+' (<i class="fas fa-fw fa-random"></i> <a class="generate-general-action unselectable">'+l10n.global.generate_new+'</a>):<br><input type="text" name="general_key" class="generate-general round wide"></p>';
							result+='<p><a class="create-account-action button"><i class="fas fa-fw fa-plus-circle"></i> '+l10n.create_account.action+'</a>';
						}
						view.html(result);
						generate_general_key();
					}
				});
			});
		}
	}
}
function paid_subscriptions_control(){
	if(0!=$('.control .manage-subscription').length){
		let control=$('.manage-subscription');
		let result='';
		if(''==current_user){
			result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
		}
		else{
			result+='<p>'+l10n.ps.manage_list+': '+current_user+'.</p>';
			if(''==users[current_user].active_key){
				result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
			}
			result+='<div class="manage-subscription-list"></div>';
		}
		control.html(result);
		gate.api.getActivePaidSubscriptions(current_user,function(err, response){
			result='';
			if(!err){
				for(let i in response){
					result+='<p><a class="manage-subscription-action link" data-login="'+current_user+'" data-creator="'+response[i]+'">'+l10n.ps.contract_with+' '+response[i]+'</span></p>';
					result+='<div class="manage-subscription-item" data-login="'+current_user+'" data-creator="'+response[i]+'"></div>';
				}
				if(0==response.length){
					result+='<p>'+l10n.ps.account_prepand+' <a href="/@'+current_user+'/" target="_blank">'+current_user+'</a> '+l10n.ps.none_contracts+'</p>';
				}
				$('.manage-subscription-list').html(result);
			}
			else{
				add_notify(l10n.errors.api,true);
			}
		});
	}
	if(0!=$('.control .set-paid-subscribe').length){
		let control=$('.set-paid-subscribe');
		let result='';
		result+='<hr><p>'+l10n.ps.sign_offer_descr+'</p>';
		result+='<p><label class="input-descr">'+l10n.ps.sign_offer_login+':<br><input type="text" name="lookup-login" class="round wide"></label></p>';
		result+='<p><a class="set-paid-subscribe-lookup-action button"><i class="fas fa-fw fa-search"></i> '+l10n.ps.sign_offer_lookup+'</a>';
		result+='<div class="set-paid-subscribe-agreement"></div>';
		control.html(result);
	}
	if(0!=$('.control .set-paid-subscription').length){
		let control=$('.set-paid-subscription');
		let result='';
		result+='<h3>'+l10n.ps.set_offer_caption+'</h3>';
		if(''==current_user){
			result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
		}
		else{
			result+='<p>'+l10n.ps.set_offer_creator+': '+current_user+'.</p>';
			if(''==users[current_user].active_key){
				result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
			}
			else{
				result+='<p><label class="input-descr">'+l10n.ps.set_offer_url+':<br><input type="text" name="url" class="round wide" placeholder="https://"></label></p>';
				result+='<p><label class="input-descr">'+l10n.ps.set_offer_levels+'<br>('+l10n.ps.set_offer_levels_descr+'):<br><input type="text" name="levels" class="round wide" placeholder="0"></label></p>';
				result+='<p><label class="input-descr">'+l10n.ps.set_offer_amount+':<br><input type="text" name="amount" class="round wide" placeholder="0.000"></label></p>';
				result+='<p><label class="input-descr">'+l10n.ps.set_offer_period+':<br><input type="text" name="period" class="round wide" placeholder="0"></label></p>';
				result+='<p><a class="set-paid-subscription-action button"><i class="fas fa-fw fa-search"></i> '+l10n.ps.set_offer_action+'</a>';
			}
		}
		control.html(result);
	}
	if(0!=$('.control .paid-subscriptions-options').length){
		let control=$('.paid-subscriptions-options');
		let result='';
		result+='<h3>'+l10n.ps.options_caption+'</h3>';
		result+='<p>'+l10n.ps.options_descr+'</p>';
		result+='<p><label class="input-descr">'+l10n.ps.options_login+':<br><input type="text" name="lookup-login" class="round wide"></label></p>';
		result+='<p><a class="paid-subscriptions-options-action button"><i class="fas fa-fw fa-search"></i> '+l10n.ps.options_action+'</a>';
		result+='<div class="options-result"></div><hr>';
		control.html(result);
	}
	if(0!=$('.control .paid-subscriptions-lookup').length){
		let control=$('.paid-subscriptions-lookup');
		let result='';
		result+='<h3>'+l10n.ps.lookup_subscriptions_caption+'</h3>';
		result+='<p>'+l10n.ps.lookup_subscriptions_descr+'</p>';
		result+='<p><label class="input-descr">'+l10n.ps.lookup_subscriptions_login+':<br><input type="text" name="lookup-login" class="round wide"></label></p>';
		result+='<p><a class="paid-subscriptions-lookup-action button"><i class="fas fa-fw fa-search"></i> '+l10n.ps.lookup_subscriptions_action+'</a>';
		result+='<div class="lookup-result"></div><hr>';
		control.html(result);
	}
	if(0!=$('.control .paid-subscription-lookup').length){
		let control=$('.paid-subscription-lookup');
		let result='';
		result+='<h3>'+l10n.ps.lookup_contract_caption+'</h3>';
		result+='<p>'+l10n.ps.lookup_contract_descr+'</p>';
		result+='<p><label class="input-descr">'+l10n.ps.lookup_contract_subscriber+':<br><input type="text" name="lookup-login" class="round wide"></label></p>';
		result+='<p><label class="input-descr">'+l10n.ps.lookup_contract_creator+':<br><input type="text" name="lookup-creator" class="round wide"></label></p>';
		result+='<p><a class="paid-subscription-lookup-action button"><i class="fas fa-fw fa-search"></i> '+l10n.ps.lookup_contract_action+'</a>';
		result+='<div class="lookup-result"></div><hr>';
		control.html(result);
	}
}
function invite_control(){
	if(0!=$('.control .invite-control').length){
		let invite_control=$('.invite-control');
		let result='';
		result+='<h3>'+l10n.invite.create_caption+'</h3>';
		if(''==current_user){
			result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
			invite_control.html(result);
		}
		else{
			invite_control.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> '+l10n.global.loading+'&hellip;</p>');
			gate.api.getAccounts([current_user],function(err,response){
				if(typeof response[0] !== 'undefined'){
					result+='<p>'+l10n.wallet.balance+': <span class="token" data-symbol="VIZ"><span class="amount">'+parseFloat(response[0]['balance'])+'</span> VIZ</span></p>';
					if(''==users[current_user].active_key){
						result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
					}
					else{
						result+='<p>'+l10n.invite.create_descr+'</p>';
						result+='<p class="input-descr">'+l10n.invite.create_private_key+' (<i class="fas fa-fw fa-random"></i> <a class="generate-action unselectable">'+l10n.global.generate_new+'</a>):<br><input type="text" name="private" class="generate-private round wide"></p>';
						result+='<p class="input-descr">'+l10n.invite.create_public_key+':<br><input type="text" name="public" class="generate-public round wide"></p>';
						result+='<p><label class="input-descr">'+l10n.invite.create_amount+':<br><input type="text" name="amount" class="round"></label></p>';
						result+='<p><a class="invite-action button"><i class="fas fa-fw fa-plus-circle"></i> '+l10n.invite.create_action+'</a>';
					}
					invite_control.html(result);
					generate_key();
				}
			});
		}
	}
	if(0!=$('.control .invite-lookup').length){
		let invite_control=$('.invite-lookup');
		let result='';
		result+='<h3>'+l10n.invite.lookup_caption+'</h3>';
		result+='<p>'+l10n.invite.lookup_public_key+'</p>';
		result+='<p class="input-descr"><input type="text" name="public" class="round wide"></p>';
		result+='<p><a class="invite-lookup-action button"><i class="fas fa-fw fa-search"></i> '+l10n.invite.lookup_action+'</a>';
		result+='<div class="search-result"></div>';
		invite_control.html(result);
	}
	if(0!=$('.control .invite-claim').length){
		let invite_control=$('.invite-claim');
		let result='';
		result+='<p>'+l10n.invite.claim_descr+'</p>';
		result+='<p><label class="input-descr">'+l10n.invite.claim_private_key+':<br><input type="text" name="secret" class="round wide"></label></p>';
		result+='<p><label class="input-descr">'+l10n.invite.claim_receiver+':<br><input type="text" name="receiver" class="round" value="'+current_user+'"></label></p>';
		result+='<p><a class="invite-claim-action button"><i class="fas fa-fw fa-file-invoice-dollar"></i> '+l10n.invite.claim_action+'</a>';
		invite_control.html(result);
	}
	if(0!=$('.control .invite-registration').length){
		let invite_control=$('.invite-registration');
		let result='';
		result+='<p>'+l10n.invite.registration_descr+'</p>';
		result+='<p><label class="input-descr">'+l10n.invite.registration_private_key+':<br><input type="text" name="secret" class="round wide"></label></p>';
		result+='<p><label class="input-descr">'+l10n.invite.registration_account+':<br><input type="text" name="receiver" class="round wide"></label></p>';
		result+='<p class="input-descr">'+l10n.invite.registration_account_private_key+' (<i class="fas fa-fw fa-random"></i> <a class="generate-action unselectable">'+l10n.global.generate_new+'</a>):<br><input type="text" name="private" class="generate-private round wide"></p>';
		result+='<p><a class="invite-registration-action button"><i class="fas fa-fw fa-file-invoice-dollar"></i> '+l10n.invite.registration_action+'</a>';
		invite_control.html(result);
		generate_key();
	}
}
function delegation_control(){
	if(0!=$('.control .delegation-control').length){
		let delegation_control=$('.delegation-control');
		let result='';
		if(''==current_user){
			result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
			delegation_control.html(result);
		}
		else{
			delegation_control.html('<p><i class="fa fw-fw fa-spinner fa-spin"></i> '+l10n.global.loading+'&hellip;</p>');
			gate.api.getAccounts([current_user],function(err,response){
				if(typeof response[0] !== 'undefined'){
					result+='<p>'+l10n.wallet.shares+': <span class="token" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['vesting_shares'])+'</span> SHARES</span></p>';
					if(parseFloat(response[0]['delegated_vesting_shares'])){
						result+='<p>'+l10n.wallet.delegated+': <span class="delegated_vesting_shares" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['delegated_vesting_shares'])+'</span> SHARES</span></p>';
					}
					if(parseFloat(response[0]['received_vesting_shares'])){
						result+='<p>'+l10n.wallet.received_delegation+': <span class="received_vesting_shares" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['received_vesting_shares'])+'</span> SHARES</span></p>';
					}
					if(parseFloat(response[0]['received_vesting_shares']) || parseFloat(response[0]['delegated_vesting_shares'])){
						result+='<p>'+l10n.wallet.effective_shares+': <span class="token" data-symbol="SHARES"><span class="amount">'+(parseFloat(response[0]['vesting_shares'])+parseFloat(response[0]['received_vesting_shares'])-parseFloat(response[0]['delegated_vesting_shares']))+'</span> SHARES</span></p>';
					}
					result+='<h3>'+l10n.wallet.delegate_caption+'</h3>';
					if(''==users[current_user].active_key){
						result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
					}
					else{
						result+='<p>'+l10n.wallet.delegate_descr+'</p>';
						result+='<p><label><input type="text" name="recipient" class="round"> &mdash; '+l10n.wallet.delegate_receiver+'</label></p>';
						result+='<p><label><input type="text" name="amount" class="round"> &mdash; '+l10n.wallet.delegate_amount+' SHARES</label></p>';
						result+='<p><a class="delegation-action button"><i class="far fa-fw fa-credit-card"></i> '+l10n.wallet.delegate_action+'</a>';
					}
					delegation_control.html(result);
				}
			});
		}
	}
	if(0!=$('.control .delegation-returning-shares').length){
		let delegation_control=$('.delegation-returning-shares');
		let result='';
		if(''!=current_user){
			gate.api.getExpiringVestingDelegations(current_user,new Date().toISOString().substr(0,19),1000,function(err,response){
				if(!err){
					if(0!=response.length){
						result+='<h3>'+l10n.wallet.return_delegation_caption+'</h3>';
						for(delegation in response){
							result+='<p>'+response[delegation].expiration+' '+l10n.wallet.returning+' '+response[delegation].vesting_shares+'</p>';
						}
						delegation_control.html(result);
					}
				}
			});
		}
	}
	if(0!=$('.control .delegation-delegated-shares').length){
		let delegation_control=$('.delegation-delegated-shares');
		let result='';
		if(''!=current_user){
			gate.api.getVestingDelegations(current_user,0,1000,0,function(err,response){
				if(!err){
					result+='<h3>'+l10n.wallet.delegated_caption+'</h3>';
					if(0==response.length){
						result+='<p>'+l10n.wallet.none_delegated+'</p>';
					}
					for(delegation in response){
						result+='<p><a href="/@'+response[delegation].delegatee+'/">'+response[delegation].delegatee+'</a> '+l10n.wallet.delegated_hold+' '+response[delegation].vesting_shares+', '+l10n.wallet.delegated_expire+' '+response[delegation].min_delegation_time+'</p>';
					}
					delegation_control.html(result);
				}
			});
		}
	}
	if(0!=$('.control .delegation-received-shares').length){
		let delegation_control=$('.delegation-received-shares');
		let result='';
		if(''!=current_user){
			gate.api.getVestingDelegations(current_user,0,1000,1,function(err,response){
				if(!err){
					result+='<h3>'+l10n.wallet.received_delegation_caption+'</h3>';
					if(0==response.length){
						result+='<p>'+l10n.wallet.none_received_delegation+'</p>';
					}
					for(delegation in response){
						result+='<p>'+response[delegation].vesting_shares+' '+l10n.wallet.received_delegation_from+' <a href="/@'+response[delegation].delegatee+'/">'+response[delegation].delegator+'</a>, '+l10n.wallet.received_delegation_expire_time+' '+response[delegation].min_delegation_time+'</p>';
					}
					delegation_control.html(result);
				}
			});
		}
	}
}
function update_wallet_history(){
	if(0<$('.wallet-history').length){
		$('.wallet-history tbody').html('<tr><td colspan="6"><center><i class="fa fa-fw fa-spin fa-spinner" aria-hidden="true"></i> '+l10n.global.loading+'&hellip;</center></td></tr>');
		setTimeout(function(){
			$.ajax({
				type:'POST',
				url:'/ajax/transfers_history_table/',
				data:{'user':current_user},
				success:function(data_html){
					if(''!=data_html){
						$('.wallet-history tbody').html(data_html);
						update_datetime();
					}
					else{
						$('.wallet-history tbody').html('<tr><td colspan="6"><center>'+l10n.wallet.no_records+'</center></td></tr>');
					}
				},
			});
		},1000);
	}
}
function filter_wallet_history(){
	var filter=$('input[name=wallet-history-filter]').val();
	$('.wallet-history tbody tr').removeClass('filtered');
	$('.wallet-history tbody tr').each(function(){
		if('none'!=$(this).css('display')){
			let pos=$(this).text().toLowerCase().indexOf(filter);
			if(-1!==pos){

			}
			else{
				$(this).addClass('filtered');
			}
		}
	});
	var filter_amount=parseFloat(parseFloat($('input[name=wallet-history-filter-amount1]').val().replace(',','.')).toFixed(3));
	var filter_amount2=parseFloat(parseFloat($('input[name=wallet-history-filter-amount2]').val().replace(',','.')).toFixed(3));
	$('.wallet-history tbody tr').each(function(){
		var found_amount=parseFloat(parseFloat($(this).find('td[rel=amount]').text()).toFixed(3));
		if('none'!=$(this).css('display')){
			if(filter_amount>0){
				if(filter_amount>found_amount){
					$(this).addClass('filtered');
				}
			}
			if(filter_amount2>0){
				if(filter_amount2<found_amount){
					$(this).addClass('filtered');
				}
			}
		}
	});
}
function bind_filter_wallet_history(){
	$('input[name=wallet-history-filter]').bind('keyup',function(){
		filter_wallet_history();
	});
	$('input[name=wallet-history-filter-amount1]').bind('keyup',function(){
		filter_wallet_history();
	});
	$('input[name=wallet-history-filter-amount2]').bind('keyup',function(){
		filter_wallet_history();
	});
}
function profile_control(){
	if(0!=$('.control .profile-control').length){
		let control=$('.profile-control');
		let result='';
		if(''==current_user){
			result+='<p>'+l10n.global.need_auth+'</p>';
			control.html(result);
		}
		else{
			gate.api.getAccounts([current_user],function(err,response){
				if(typeof response[0] !== 'undefined'){
					result+='<p>'+l10n.profile.edit_descr+'</p>';
					result+='<p>'+l10n.profile.edit_warning+'</p>';
					result+='<p>'+l10n.profile.current_account+': <a href="/@'+current_user+'/">'+current_user+'</a></p>';
					console.log(response[0].json_metadata);
					let metadata;
					if(''==response[0].json_metadata){
						metadata={};
					}
					else{
						metadata=JSON.parse(response[0].json_metadata);
					}
					if(typeof metadata.profile == 'undefined'){
						metadata.profile={};
					}
					result+='<p>'+l10n.profile.edit_nickname+':<br><input type="text" class="profile-input round wide" name="nickname" data-category="profile" value="'+(typeof metadata.profile.nickname !== 'undefined'?metadata.profile.nickname:'')+'"></p>';
					result+='<p>'+l10n.profile.edit_about+':<br><input type="text" class="profile-input round wide" name="about" data-category="profile" value="'+(typeof metadata.profile.about !== 'undefined'?metadata.profile.about:'')+'"></p>';
					result+='<p>'+l10n.profile.edit_avatar+':<br><input type="text" class="profile-input round wide" name="avatar" data-category="profile" value="'+(typeof metadata.profile.avatar !== 'undefined'?metadata.profile.avatar:'')+'"></p>';
					result+='<p>'+l10n.profile.edit_gender+':<br><select class="profile-select round" name="gender" data-category="profile">'
					+'<option value=""'+(typeof metadata.profile.gender !== 'undefined'?((''==metadata.profile.gender)?' selected':''):'')+'>'+l10n.profile.gender_none+'</option>'
					+'<option value="male"'+(typeof metadata.profile.gender !== 'undefined'?(('male'==metadata.profile.gender)?' selected':''):'')+'>'+l10n.profile.gender_male+'</option>'
					+'<option value="female"'+(typeof metadata.profile.gender !== 'undefined'?(('female'==metadata.profile.gender)?' selected':''):'')+'>'+l10n.profile.gender_female+'</option>'
					+'</select></p>';
					result+='<p><input type="button" class="profile-action button" data-value="true" value="'+l10n.profile.edit_action+'"></p>';
					control.html(result);
				}
				else{
					add_notify(l10n.errors.user_not_found+' '+current_user,true);
				}
			});
		}
	}
}
function wallet_control(update=false){
	if(0!=$('.control .wallet-control').length){
		let wallet_control=$('.wallet-control');
		if(update){
			gate.api.getDynamicGlobalProperties(function(err,dgp){
				gate.api.getAccounts([current_user],function(err,response){
					if(typeof response[0] !== 'undefined'){
						wallet_control.find('.token[data-symbol=VIZ] .amount').html(parseFloat(response[0]['balance']));
						if('0.000000 SHARES'==response[0].vesting_withdraw_rate){
							wallet_control.find('.withdraw-shares-status').html('<a class="enable-withdraw-shares-action">'+l10n.wallet.enable_withdraw+'</a>');
						}
						else{
							let powerdown_time=Date.parse(response[0].next_vesting_withdrawal);
							let powerdown_icon='';
							if(powerdown_time>0){
								powerdown_icon='<i class="fas fa-fw fa-level-down-alt" title="'+date_str(powerdown_time-(new Date().getTimezoneOffset()*60000),true,false,true)+': '+response[0].vesting_withdraw_rate+'"></i> ';
							}
							wallet_control.find('.withdraw-shares-status').html(powerdown_icon+'<a class="disable-withdraw-shares-action">'+l10n.wallet.disable_withdraw+'</a>');
						}
						let network_share=100*(parseFloat(response[0]['vesting_shares'])/parseFloat(dgp.total_vesting_shares));
						wallet_control.find('.token[data-symbol=SHARES] .amount').html(parseFloat(response[0]['vesting_shares']));
						wallet_control.find('.network_share').html(network_share.toFixed(5));
						if(parseFloat(response[0]['delegated_vesting_shares'])){
							wallet_control.find('.delegated_vesting_shares[data-symbol=SHARES] .amount').html(parseFloat(response[0]['delegated_vesting_shares']));
						}
						if(parseFloat(response[0]['received_vesting_shares'])){
							wallet_control.find('.received_vesting_shares[data-symbol=SHARES] .amount').html(parseFloat(response[0]['received_vesting_shares']));
						}
						if(parseFloat(response[0]['received_vesting_shares']) || parseFloat(response[0]['delegated_vesting_shares'])){
							network_share=100*((parseFloat(response[0]['vesting_shares'])+parseFloat(response[0]['received_vesting_shares'])-parseFloat(response[0]['delegated_vesting_shares']))/parseFloat(dgp.total_vesting_shares));
							wallet_control.find('.effective_token[data-symbol=SHARES] .amount').html((parseFloat(response[0]['vesting_shares'])+parseFloat(response[0]['received_vesting_shares'])-parseFloat(response[0]['delegated_vesting_shares'])));
							wallet_control.find('.effective_network_share').html(network_share.toFixed(5));
						}
					}
				});
			});
			return;
		}
		let result='';
		if(''==current_user){
			result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
			wallet_control.html(result);
		}
		else{
			wallet_control.html('<p><i class="fa fw-fw fa-spinner fa-spin"></i> '+l10n.global.loading+'&hellip;</p>');
			gate.api.getDynamicGlobalProperties(function(err,dgp){
				gate.api.getAccounts([current_user],function(err,response){
					if(typeof response[0] !== 'undefined'){
						result+='<p>'+l10n.wallet.balance+': <span class="token" data-symbol="VIZ"><span class="amount">'+parseFloat(response[0]['balance'])+'</span> VIZ</span></p>';
						if('0.000000 SHARES'==response[0].vesting_withdraw_rate){
							result+='<div class="right withdraw-shares-status"><a class="enable-withdraw-shares-action">'+l10n.wallet.enable_withdraw+'</a></div>';
						}
						else{
							result+='<div class="right withdraw-shares-status">';
							let powerdown_time=Date.parse(response[0].next_vesting_withdrawal);
							if(powerdown_time>0){
								result+='<i class="fas fa-fw fa-level-down-alt" title="'+date_str(powerdown_time-(new Date().getTimezoneOffset()*60000),true,false,true)+': '+response[0].vesting_withdraw_rate+'"></i> ';
							}
							result+='<a class="disable-withdraw-shares-action">'+l10n.wallet.disable_withdraw+'</a></div>';
						}
						let network_share=100*(parseFloat(response[0]['vesting_shares'])/parseFloat(dgp.total_vesting_shares));
						result+='<p>'+l10n.wallet.shares+': <span class="token" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['vesting_shares'])+'</span> SHARES</span> (<span class="network_share">'+network_share.toFixed(5)+'</span>%)</p>';
						if(parseFloat(response[0]['delegated_vesting_shares'])){
							result+='<p>'+l10n.wallet.delegated+': <span class="delegated_vesting_shares" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['delegated_vesting_shares'])+'</span> SHARES</span></p>';
						}
						if(parseFloat(response[0]['received_vesting_shares'])){
							result+='<p>'+l10n.wallet.received_delegation+': <span class="received_vesting_shares" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['received_vesting_shares'])+'</span> SHARES</span></p>';
						}
						if(parseFloat(response[0]['received_vesting_shares']) || parseFloat(response[0]['delegated_vesting_shares'])){
							network_share=100*((parseFloat(response[0]['vesting_shares'])+parseFloat(response[0]['received_vesting_shares'])-parseFloat(response[0]['delegated_vesting_shares']))/parseFloat(dgp.total_vesting_shares));
							result+='<p>'+l10n.wallet.effective_shares+': <span class="effective_token" data-symbol="SHARES"><span class="amount">'+(parseFloat(response[0]['vesting_shares'])+parseFloat(response[0]['received_vesting_shares'])-parseFloat(response[0]['delegated_vesting_shares']))+'</span> SHARES</span> (<span class="effective_network_share">'+network_share.toFixed(5)+'</span>%)</p>';
						}
						result+='<h3>'+l10n.wallet.transfer_caption+'</h3>';
						if(''==users[current_user].active_key){
							result+='<p>'+l10n.global.need_auth_with_active_key+'</p>';
						}
						else{
							result+='<p><label><input type="text" name="recipient" class="round"> &mdash; '+l10n.wallet.transfer_receiver+'</label></p>';
							result+='<p><label><input type="text" name="amount" class="round"> &mdash; '+l10n.wallet.transfer_amount+' VIZ</label></p>';
							result+='<p><label><input type="text" name="memo" class="round"> &mdash; '+l10n.wallet.transfer_memo+'</label></p>';
							result+='<p><label><input type="checkbox" name="shares"> — '+l10n.wallet.transfer_in_shares+'</label></p>';
							result+='<p><a class="wallet-transfer-action button"><i class="far fa-fw fa-credit-card"></i> '+l10n.wallet.transfer_action+'</a>';
						}
						result+='<hr><h2>'+l10n.wallet.history_caption+'</h2>';
						result+='<input class="bubble small-size right" type="text" name="wallet-history-filter-amount2" placeholder="'+l10n.wallet.history_filter_range2+'&hellip;" tabindex="3">';
						result+='<input class="bubble small-size right" type="text" name="wallet-history-filter-amount1" placeholder="'+l10n.wallet.history_filter_range1+'&hellip;" tabindex="2">';
						result+='<input class="bubble small-size right" type="text" name="wallet-history-filter" placeholder="'+l10n.wallet.history_filter_text+'" tabindex="1">';
						result+='<div class="action-button wallet-history-filter-all"><i class="fa fa-fw fa-globe" aria-hidden="true"></i> '+l10n.wallet.history_filter_all+'</div>';
						result+='<div class="action-button wallet-history-filter-in"><i class="fa fa-fw fa-arrow-circle-down" aria-hidden="true"></i> '+l10n.wallet.history_filter_income+'</div>';
						result+='<div class="action-button wallet-history-filter-out"><i class="fa fa-fw fa-arrow-circle-up" aria-hidden="true"></i> '+l10n.wallet.history_filter_outcome+'</div>';
						result+='<div class="wallet-history"><table><thead><tr><th>'+l10n.wallet.history_date+'</th><th>'+l10n.wallet.history_sender+'</th><th>'+l10n.wallet.history_receiver+'</th><th>'+l10n.wallet.history_amount+'</th><th>'+l10n.wallet.history_token+'</th><th>'+l10n.wallet.history_memo+'</th></tr></thead><tbody></tbody></table></div>';
						wallet_control.html(result);
						update_wallet_history();
						bind_filter_wallet_history();
					}
				});
			});
		}
	}
}
function committee_control(){
	if(0!=$('.control .committee-control').length){
		$('.committee-control').each(function(){
			let request_id=$(this).attr('data-request-id');
			let creator=$(this).attr('data-creator');
			let status=$(this).attr('data-status');
			let committee_control=$(this);
			let result='';
			result+='<h3>'+l10n.committee.vote_caption+' #'+request_id+'</h3>';
			result+='<p>'+l10n.committee.vote_percent+': <input type="text" name="vote_percent" value="0" size="4" class="round" data-fixed="vote_percent_range"> <input type="range" name="vote_percent_range" min="-100" max="+100" value="0" data-fixed="vote_percent"><br>';
			result+='<input type="button" class="committee-vote-request-action button" value="'+l10n.committee.vote_action+'"></p>';
			if(current_user==creator){
				if(status==0){
					result+='<h3>'+l10n.committee.manage_request_caption+'</h3>';
					result+='<p><input type="button" class="committee-cancel-request-action button" value="'+l10n.committee.cancel_request_action+'"></p>';
				}
			}
			committee_control.html(result);
			bind_range();
		});
	}
	if(0!=$('.control .committee-create-request').length){
		let view=$('.control .committee-create-request');
		let result='';
		if(''==current_user){
			result+='<p>'+l10n.global.need_auth+'</p>';
			view.html(result);
		}
		else{
			view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> '+l10n.global.loading+'&hellip;</p>');
			result+='<p><label>'+l10n.committee.request_url+':<input type="text" name="url" class="round wide"></label></p>';
			result+='<p><label>'+l10n.committee.request_worker+': <input type="text" name="worker" class="round" value="'+current_user+'"></label></p>';
			result+='<p><label>'+l10n.committee.request_min_amount+': <input type="text" name="min_amount" class="round" value="0.000 VIZ"></label></p>';
			result+='<p><label>'+l10n.committee.request_max_amount+': <input type="text" name="max_amount" class="round" value="0.000 VIZ"></label></p>';
			result+='<p><label>'+l10n.committee.request_duration+': <input type="text" name="duration" class="round" value="5"></label></p>';
			result+='<p><a class="committee-worker-create-request-action button">'+l10n.committee.request_action+'</a>';
			view.html(result);
		}
	}
}
function session_control(){
	if(0!=$('.control .session-control').length){
		let session_html='';
		for(key in users){
			session_html+='<p class="clearfix">'+(1==users[key]['session_verify']?'<span class="right" title="'+l10n.sessions.verify+'"><i class="fas fa-fw fa-check"></i></span>':'')+(users[key]['active_key']!=''?'<span class="right" title="'+l10n.sessions.active_key+'"><i class="fas fa-fw fa-key"></i></span>':'')+(users[key]['shield']===true?'<span class="right" title="'+l10n.sessions.viz_shield+'"><img src="/shield-icon.svg"></span>':'')+'<a href="/@'+key+'/">'+key+'</a>, '+(current_user==key?'<b>'+l10n.sessions.active+'</b>':'<a href="#" class="auth-change" data-login="'+key+'">'+l10n.sessions.switch+'</a>')+', <a href="#" class="auth-logout" data-login="'+key+'">'+l10n.sessions.eject+'</a></p>';
		}
		$('.control .session-control').html(session_html);
	}
}
function logout(login='',redirect=true){
	$('.menu .avatar').remove();
	if(''==login){
		login=current_user;
	}
	if(typeof users[login] !== 'undefined'){
		delete users[login];
		if(typeof Object.keys(users)[0] !== 'undefined'){
			current_user=Object.keys(users)[0];
		}
		else{
			current_user='';
		}
		save_session();
		if(redirect){
			document.location='/';
		}
	}
}
function auth_signature_data(origin,action,login,authority,nonce){
	return origin+':'+action+':'+login+':'+authority+':'+Math.floor((new Date()).getTime() / 1000)+':'+nonce;
}
function auth_signature_check(hex){
	if('1f'==hex.substring(0,2)){
		return true;
	}
	return false;
}
function try_auth_signature(login,posting_key,active_key=''){
	$('.auth-action').addClass('disabled');
	$('.auth-error').html('');
	login=login.toLowerCase();
	if('@'==login.substring(0,1)){
		login=login.substring(1);
	}
	login=login.trim();
	if(login){
		if(!gate.auth.isWif(posting_key)){
			$('.auth-error').html(l10n.errors.invalid_regular_key);
			$('.auth-action').removeClass('disabled');
			return;
		}
		if(''!=active_key){
			if(!gate.auth.isWif(active_key)){
				$('.auth-error').html(l10n.errors.invalid_active_key);
				$('.auth-action').removeClass('disabled');
				return;
			}
		}
		var nonce=0;
		var data='';
		var signature='';
		while(!auth_signature_check(signature)){
			data=auth_signature_data('viz.world','auth',login,'posting',nonce);
			signature=gate.auth.signature.sign(data,posting_key).toHex();
			nonce++;
		}
		$.ajax({
			type:'POST',
			url:'/ajax/auth/',
			data:{'data':data,'signature':signature,'posting_key':posting_key},
			success:function(data_json){
				console.log(data_json);
				data_obj=JSON.parse(data_json);
				if(typeof data_obj.error !== 'undefined'){
					console.log(''+new Date().getTime()+': '+data_obj.error+' - '+data_obj.error_str);
					add_notify(data_obj.error_str,true);
					$('.auth-error').html(data_obj.error_str);
					$('.auth-action').removeClass('disabled');
					return;
				}
				else
				if(typeof data_obj !== 'undefined'){
					users[login]={'posting_key':posting_key,'active_key':active_key,'shield':false,'session_id':data_obj.session,'session_verify':1};
					current_user=login;
					save_session();
					set_session_cookie();
					$('.auth-error').html(l10n.sessions.success);
					if('/'==document.location.pathname){
						document.location='https://'+domain+'/media/';
					}
					else
					if('/login/'==document.location.pathname){
						document.location='https://'+domain+'/media/';
					}
					else{
						document.location=document.location;
					}
				}
				else{
					add_notify('Service unavailable',true);
					$('.auth-action').removeClass('disabled');
					return;
				}
			},
		});
	}
	else{
		$('.auth-error').html(l10n.errors.user_not_provided);
		$('.auth-action').removeClass('disabled');
		return;
	}
}
function try_auth(login,posting_key,active_key){
	$('.auth-custom-action').addClass('disabled');
	$('.auth-error').html('');
	login=login.toLowerCase();
	if('@'==login.substring(0,1)){
		login=login.substring(1);
	}
	login=login.trim();
	if(login){
		gate.api.getAccounts([login],function(err,response){
			if(typeof response[0] !== 'undefined'){
				let posting_valid=false;
				for(posting_check in response[0].active.key_auths){
					if(response[0].posting.key_auths[posting_check][1]>=response[0].posting.weight_threshold){
						try{
							if(gate.auth.wifIsValid(posting_key,response[0].posting.key_auths[posting_check][0])){
								posting_valid=true;
							}
						}
						catch(e){
							$('.auth-error').html(l10n.errors.invalid_regular_key);
							$('.auth-custom-action').removeClass('disabled');
							return;
						}
					}
				}
				if(!posting_valid){
					$('.auth-error').html(l10n.errors.failure_regular_key);
					$('.auth-custom-action').removeClass('disabled');
					return;
				}
				if(active_key){
					let active_valid=false;
					for(active_check in response[0].active.key_auths){
						if(response[0].active.key_auths[active_check][1]>=response[0].active.weight_threshold){
							try{
								if(gate.auth.wifIsValid(active_key,response[0].active.key_auths[active_check][0])){
									active_valid=true;
								}
							}
							catch(e){
								$('.auth-error').html(l10n.errors.invalid_active_key);
								$('.auth-custom-action').removeClass('disabled');
								return;
							}
						}
					}
					if(!active_valid){
						$('.auth-error').html(l10n.errors.failure_active_key);
						$('.auth-custom-action').removeClass('disabled');
						return;
					}
				}
				users[login]={'posting_key':posting_key,'active_key':active_key,'shield':false};
				current_user=login;
				session_generate();
			}
			else{
				$('.auth-error').html(l10n.errors.user_not_found);
				$('.auth-custom-action').removeClass('disabled');
				return;
			}
		});
	}
	else{
		$('.auth-error').html(l10n.errors.user_not_provided);
		$('.auth-custom-action').removeClass('disabled');
		return;
	}
}

function update_dgp(auto=false){
	gate.api.getDynamicGlobalProperties(function(e,r){
		if(r){
			dgp=r;
			current_block=r.head_block_number;
			$('.setter[rel=current_block]').html(current_block);
		}
	});
	setTimeout(function(){if(0==Object.keys(dgp).length){select_best_gate();}},10000);
	if(auto){
		setTimeout("update_dgp(true)",3000);
	}
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
function wait_content(author,permlink){
	$.ajax({
		type:'POST',
		url:'/ajax/check_content/',
		data:{'author':author,'permlink':permlink},
		success:function(data_json){
			data_obj=JSON.parse(data_json);
			if('ok'==data_obj.status){
				document.location='/@'+author+'/'+permlink+'/';
			}
			else{
				setTimeout(function(){wait_content(author,permlink)},1000);
			}
		},
	});
}
function post_subcontent(target){
	if(''!=current_user){
		target.addClass('disabled');
		let content_id=parseInt(target.parent().attr('data-reply-content'));
		let subcontent_id=parseInt(target.parent().attr('data-reply-subcontent'));
		let subcontent=target.parent().find('textarea[name=reply-text]').val();
		let parent_author='';
		let parent_permlink='';
		let permlink='';
		let title='';
		let json='';
		if(0<content_id){
			parent_author=$('.page.content[data-content-id='+content_id+']').attr('data-content-author');
			parent_permlink=$('.page.content[data-content-id='+content_id+']').attr('data-content-permlink');
			permlink='re-'+parent_author+'-'+parseInt(new Date().getTime()/1000);
		}
		if(0<subcontent_id){
			parent_author=$('.page.comments .comment[data-id='+subcontent_id+']').attr('data-author');
			parent_permlink=$('.page.comments .comment[data-id='+subcontent_id+']').attr('data-permlink');
			permlink='re-'+parent_author+'-'+parseInt(new Date().getTime()/1000);
		}
		if(''!=parent_author){
			let subcontent_success=function(result){
				$(target).parent().remove();
				set_update_comments_list();
				add_notify(l10n.media.comment_success);
			}
			let subcontent_failure=function(err){
				window.setTimeout(function(){set_update_comments_list(false)},100);
				add_notify(l10n.media.comment_failure,true);
				target.removeClass('disabled');
				console.log(err);
			}

			var custom_json=['content',{parent_author:parent_author,parent_permlink:parent_permlink,author:current_user,permlink:permlink,title:title,body:subcontent}];
			if(users[current_user].shield){
				shield_action(current_user,'custom',{id:'media',required_auths:[],required_posting_auths:[current_user],json:JSON.stringify(custom_json)},subcontent_success,subcontent_failure);
			}
			else{
				gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'media',JSON.stringify(custom_json),function(err,result){
					if(!err){
						subcontent_success(result);
					}
					else{
						subcontent_failure(err);
					}
				});
			}
		}
	}
}
function post_content(target){
	if(''!=current_user){
		target.addClass('disabled');
		var title=$('input[name=title]').val();
		var permlink=$('input[name=permlink]').val();
		if(''==$('input[name=permlink]').val()){
			let permlink_from_title=title;
			permlink_from_title=permlink_from_title.toLowerCase().trim();
			permlink_from_title=permlink_from_title.replace(/[^ а-яА-Яa-zA-Z0-9\-_]/g,'');
			permlink_from_title=permlink_from_title.replace(new RegExp(' ','g'),'-');
			permlink_from_title=permlink_from_title.replace(new RegExp('---','g'),'-');
			permlink_from_title=permlink_from_title.replace(new RegExp('--','g'),'-');
			$('input[name=permlink]').val(permlink_from_title);
			permlink=$('input[name=permlink]').val();
		}
		var content=$('textarea[name=content]').val();
		var tags=$('input[name=tags]').val();
		if(wysiwyg_active){
			content=tinyMCE.activeEditor.getContent();
		}
		content=content.replace(new RegExp(' rel="noopener"','g'),'');
		var foreword=$('input[name=foreword]').val().trim();
		var cover=$('input[name=cover]').val().trim();
		if(''==cover){
			let links_arr=content.match(/((https?:|)\/\/[^\s]+)/g);
			for(i in links_arr){
				let regExp = /^.*((youtube.com|youtu.be)\/(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?\"]*).*/;
				let match = links_arr[i].match(regExp);
				if(match && match[6].length == 11){
					cover='https://img.youtube.com/vi/'+match[6]+'/0.jpg';
					$('input[name=cover]').val(cover);
					break;
				}
			}
		}
		var tags_arr=tags.split(',');
		if(tags_arr.length>1){
			for(var i=0;i<tags_arr.length;i++){
				tags_arr[i]=tags_arr[i].trim();
			}
		}
		else{
			tags_arr=tags.split(' ');
		}

		var beneficiaries_list=[];
		var beneficiaries_summary_weight=0;

		$('.add-beneficiaries-item').each(function(i,el){
			if(beneficiaries_summary_weight<10000){
				let account=$(el).find('input[name=account]').val();
				if(''!=account){
					let weight=parseInt(parseFloat($(el).find('input[name=weight]').val().replace(',','.'))*100);
					if(0<weight){
						if(beneficiaries_summary_weight+weight<=10000){
							beneficiaries_list.push({account,weight})
							beneficiaries_summary_weight+=weight;
						}
					}
				}
			}
		});
		beneficiaries_list.sort(compare_account_name);

		var json_object={'tags':tags_arr,'cover':cover,'foreword':foreword};
		var json=JSON.stringify(json_object);
		var parent_permlink='';
		if(0<$('input[name=parent_permlink]').length){
			parent_permlink=$('input[name=parent_permlink]').val();
		}
		$('input[name=permlink]').attr('disabled','disabled');
		$(target).html(l10n.media.posting_status+'&hellip;');
		$.ajax({
			type:'POST',
			url:'/ajax/check_content/',
			data:{'author':current_user,'permlink':permlink},
			success:function(data_json){
				data_obj=JSON.parse(data_json);
				if('ok'==data_obj.status){//content already exist
					if(confirm(l10n.media.content_rewrite)){
						let edit_success=function(result){
							add_notify(l10n.media.content_rewrite_success);
							setTimeout(function(){wait_content(current_user,permlink)},6000);
						}
						let edit_failure=function(err){
							add_notify(l10n.global.error,true);
							$('input[name=permlink]').removeAttr('disabled');
							target.removeClass('disabled');
							target.html(l10n.media.save_changes);
							console.log(err);
						}
						var custom_json=['content',{parent_permlink:parent_permlink,author:current_user,permlink:permlink,title:title,body:content,beneficiaries:beneficiaries_list,metadata:json_object}];
						if(users[current_user].shield){
							shield_action(current_user,'custom',{id:'media',required_auths:[],required_posting_auths:[current_user],json:JSON.stringify(custom_json)},edit_success,edit_failure);
						}
						else{
							gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'media',JSON.stringify(custom_json),function(err,result){
								if(!err){
									edit_success(result);
								}
								else{
									edit_failure(err);
								}
							});
						}
					}
					else{
						$('input[name=permlink]').removeAttr('disabled');
						target.removeClass('disabled');
						target.html(l10n.media.save_changes);
					}
				}
				else{
					let content_success=function(result){
						add_notify(l10n.media.content_success);
						setTimeout(function(){wait_content(current_user,permlink)},3500);
					}
					let content_failure=function(err){
						add_notify(l10n.media.content_error,true);
						$('input[name=permlink]').removeAttr('disabled');
						target.removeClass('disabled');
						target.html(l10n.media.content_publication);
						console.log(err);
					}

					var custom_json=['content',{parent_permlink:parent_permlink,author:current_user,permlink:permlink,title:title,body:content,beneficiaries:beneficiaries_list,metadata:json_object}];
					if(users[current_user].shield){
						shield_action(current_user,'custom',{id:'media',required_auths:[],required_posting_auths:[current_user],json:JSON.stringify(custom_json)},content_success,content_failure);
					}
					else{
						gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'media',JSON.stringify(custom_json),function(err,result){
							if(!err){
								content_success(result);
							}
							else{
								content_failure(err);
							}
						});
					}
				}
			}
		});
	}
}
function save_profile(target){
	if(''!=current_user){
		target.addClass('disabled');
		gate.api.getAccounts([current_user],function(err,response){
			if(typeof response[0] !== 'undefined'){
				let metadata;
				if(''==response[0].json_metadata){
					metadata={};
				}
				else{
					metadata=JSON.parse(response[0].json_metadata);
				}
				if(typeof metadata.profile == 'undefined'){
					metadata.profile={};
				}
				var control=$('.profile-control');
				control.find('.profile-input').each(function(i){
					if($(this).val()){
						if(typeof $(this).attr('data-category') !== 'undefined'){
							metadata[$(this).attr('data-category')][$(this).attr('name')]=$(this).val();
						}
						else{
							metadata[$(this).attr('name')]=$(this).val();
						}
					}
				});
				control.find('.profile-select').each(function(i){
					if(typeof $(this).attr('data-category') !== 'undefined'){
						metadata[$(this).attr('data-category')][$(this).attr('name')]=$(this).val();
					}
					else{
						metadata[$(this).attr('name')]=$(this).val();
					}
				});
				let metadata_success=function(result){
					add_notify(l10n.profile.edit_success);
					target.removeClass('disabled');
				}
				let metadata_failure=function(err){
					add_notify(l10n.global.error,true);
					target.removeClass('disabled');
					console.log(err);
				}
				if(users[current_user].shield){
					shield_action(current_user,'account_metadata',{json_metadata:JSON.stringify(metadata)},metadata_success,metadata_failure);
				}
				else{
					gate.broadcast.accountMetadata(users[current_user].posting_key,current_user,JSON.stringify(metadata),function(err, result){
						if(!err){
							metadata_success(result);
						}
						else{
							metadata_failure(err);
						}
					});
				}
			}
			else{
				add_notify(l10n.errors.user_not_found+' '+current_user,true);
				target.removeClass('disabled');
			}
		});
	}
}
function wysiwyg_activation(){
	tinymce.init({
		selector: "textarea",
		plugins: [
			"advlist autolink link image lists anchor codesample",
			"wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
			"table contextmenu directionality textcolor paste textcolor colorpicker textpattern hr"
		],

		toolbar1: "undo redo | removeformat | subscript superscript | bold italic strikethrough | alignleft aligncenter alignright alignjustify | styleselect",
		toolbar2: "bullist numlist | outdent indent blockquote codesample | link unlink anchor image media hr | forecolor | fullscreen code",

		menubar: false,
		toolbar_items_size: "small",
		relative_urls : false,
		remove_script_host : false,
		browser_spellcheck:true,
		language : "ru",
		language_url : "/js/tinymce_ru.js",
		style_formats: [
			{title: l10n.wysiwyg.center, block: 'center'},
			{title: l10n.wysiwyg.spoiler, inline : 'span', classes : 'spoiler'},
			{title: l10n.wysiwyg.header+' 1', block: 'h1'},
			{title: l10n.wysiwyg.header+' 2', block: 'h2'},
			{title: l10n.wysiwyg.header+' 3', block: 'h3'},
			{title: l10n.wysiwyg.header+' 4', block: 'h4'},
		],
		content_css : "/css/wysiwyg.css?" + new Date().getTime(),
	});
	wysiwyg_active=true;
}
function show_modal(selector,fixed=false){
	$('html').addClass('modal-open');
	$('.modal-overlay').addClass('active');
	outer_width=$(selector).outerWidth();
	outer_height=$(selector).outerHeight();
	if(fixed){
		$(selector).css('position','fixed');
	}
	$(selector).css('margin-left','-'+(outer_width/2)+'px');
	$(selector).css('margin-top','-'+(outer_height/2)+'px');
	$(selector).addClass('active');
	modal=selector;
}
function close_modal(){
	$('html').removeClass('modal-open');
	$('.modal').removeClass('active');
	$('.modal-overlay').removeClass('active');
	if(false!==modal){
		$(modal).css('position','absolute');
		modal=false;
	}
}
function bind_drag_and_drop_image(){
	window.ondragover=function(e){
		e.preventDefault();
		show_modal('.drop-file',true);
	}
	window.ondrop = function(e){
		e.preventDefault();
		try_upload_image(e.dataTransfer.files[0]);
	}
}
function try_upload_percent(e){
	var percent = parseInt(e.loaded / e.total * 100);
	$('.drop-file').html('<i class="fa fa-fw fa-spinner fa-spin" aria-hidden="true"></i> Uploading ('+percent+'%)&hellip;');
}
function try_upload_image(file,input_name=''){
	if(file.type.match(/image.*/)){
		$('.drop-file').html('<i class="fa fa-fw fa-spinner fa-spin" aria-hidden="true"></i> Uploading&hellip;');
		var post_form = new FormData();
		post_form.append('image',file);
		var xhr=new XMLHttpRequest();
		xhr.upload.addEventListener('progress',try_upload_percent,false);
		xhr.open('POST','https://api.imgur.com/3/image.json');
		xhr.onload=function(){
			if(200==xhr.status){
				var img_url = JSON.parse(xhr.responseText).data.link;
				img_url=img_url.replace('http://','https://');
				console.log(xhr.img_url);
				if(''==input_name){
					if(''==$('input[name=cover]').val()){
						$('input[name=cover]').val(img_url);
					}
					if(wysiwyg_active){
						tinyMCE.execCommand('mceInsertContent',false,'\n<img src="'+img_url+'" alt="">\n');
					}
					else{
						$('textarea[name=content]').val($('textarea[name=content]').val()+'\n'+img_url+'\n');
						$('textarea[name=content]').focus();
					}
				}
				else{
					$('input[name='+input_name+']').val(img_url);
				}
				close_modal();
				$('.drop-file').html('<i class="fas fa-fw fa-file-upload"></i> Drop file here&hellip;');
			}
			else{
				add_notify('<strong>'+l10n.global.error_caption+'</strong> '+l10n.errors.xhr_upload+' '+xhr.status+'',true);
				close_modal();
				$('.drop-file').html('<i class="fas fa-fw fa-file-upload"></i> Drop file here&hellip;');
			}
		}
		xhr.onerror=function(){
			close_modal();
			$('.drop-file').html('<i class="fas fa-fw fa-file-upload"></i> Drop file here&hellip;');
		}
		xhr.setRequestHeader('Authorization','Client-ID f1adac24a4d5691');//viz-world public gate
		xhr.send(post_form);
	}
}
function bind_range(){
	$('input[type=range]').each(function(i){
		if(typeof $(this).attr('data-fixed') !== 'undefined'){
			let fixed_name=$(this).attr('data-fixed');
			let fixed_min=parseInt($(this).attr('min'));
			let fixed_max=parseInt($(this).attr('max'));
			$(this).unbind('change');
			$(this).bind('change',function(){
				if($(this).is(':focus')){
					$('input[name='+fixed_name+']').val($(this).val());
				}
			});
			$('input[name='+fixed_name+']').unbind('change');
			$('input[name='+fixed_name+']').bind('change',function(){
				let fixed_name=$(this).attr('data-fixed');
				let val=parseInt($(this).val());
				if(val>fixed_max){
					val=fixed_max;
				}
				if(val<fixed_min){
					val=fixed_min;
				}
				$(this).val(val);
				$('input[name='+fixed_name+']').val($(this).val());
			});
		}
	});
}
function app_keyboard(e){
	if(!e)e=window.event;
	var key=(e.charCode)?e.charCode:((e.keyCode)?e.keyCode:((e.which)?e.which:0));
	if(key==27){
		if(false!==modal){
			e.preventDefault();
			close_modal();
		}
	}
}
function app_mouse(e){
	if(!e)e=window.event;
	var target=e.target || e.srcElement;
	if($(target).hasClass('start-shield-action')){
		window.open('viz-shield://open/');
	}
	if($(target).closest('.go-top-left-wrapper').length>0){
		scroll_top_action();
	}
	if($(target).hasClass('shield-auth-action')){
		e.preventDefault();
		try_auth_shield($('.shield-auth-accounts').val());
	}
	if($(target).hasClass('shield-auth-control-action')){
		e.preventDefault();
		shield_control();
	}
	if($(target).hasClass('post-content-action')){
		e.preventDefault();
		if(!$(target).hasClass('disabled')){
			post_content($(target));
		}
	}
	if($(target).hasClass('save-localization-action')){
		e.preventDefault();
		let code2=$(target).attr('rel');
		let data=$('textarea.localization[rel='+code2+']').html();
		data=data.split("\n").join("\r\n");
		download('viz-localization-'+code2+'.txt',unescape_html(data));
	}
	if($(target).hasClass('upload-image-action')){
		e.preventDefault();
		$('#upload-file').unbind('change');
		$('#upload-file').bind('change',function(e){
			e.preventDefault();
			var files = this.files;
			var file = files[0];
			show_modal('.drop-file',true);
			try_upload_image(file);
		});
		$('#upload-file').click();
	}
	if($(target).hasClass('beneficiaries-action') || $(target).parent().hasClass('beneficiaries-action')){
		e.preventDefault();
		if('none'==$('.add-beneficiaries').css('display')){
			$('.add-beneficiaries').css('display','block');
		}
		else{
			$('.add-beneficiaries').css('display','none');
		}
	}
	if($(target).hasClass('add-beneficiaries-action') || $(target).parent().hasClass('add-beneficiaries-action')){
		e.preventDefault();
		let item=$('.add-beneficiaries').find('.add-beneficiaries-item')[0].outerHTML;
		$('.add-beneficiaries').find('.add-beneficiaries-button').before(item);
	}
	if($(target).hasClass('wysiwyg-action') || $(target).parent().hasClass('wysiwyg-action')){
		e.preventDefault();
		var proper_target=$(target);
		if($(target).parent().hasClass('wysiwyg-action')){
			proper_target=$(target).parent();
		}
		proper_target.remove();
		wysiwyg_activation();
	}
	if($(target).hasClass('profile-action')){
		e.preventDefault();
		if(!$(target).hasClass('disabled')){
			save_profile($(target));
		}
	}
	if($(target).hasClass('follow-action')){
		e.preventDefault();
		proper_target=$(target).closest('.actions');
		if(typeof proper_target.attr('data-user-login') !== 'undefined'){
				follow_user(proper_target.attr('data-user-login'),proper_target);
		}
	}
	if($(target).hasClass('unfollow-action')){
		e.preventDefault();
		proper_target=$(target).closest('.actions');
		if(typeof proper_target.attr('data-user-login') !== 'undefined'){
				unfollow_user(proper_target.attr('data-user-login'),proper_target);
		}
	}
	if($(target).hasClass('ignore-action')){
		e.preventDefault();
		proper_target=$(target).closest('.actions');
		if(typeof proper_target.attr('data-user-login') !== 'undefined'){
				ignore_user(proper_target.attr('data-user-login'),proper_target);
		}
	}
	if($(target).hasClass('award-action')){
		e.preventDefault();
		proper_target=$(target).closest('.page');
		if(typeof proper_target.attr('data-content-author') !== 'undefined'){
			if($(target).hasClass('active')){
				add_notify(l10n.errors.awarded_content);
			}
			else{
				award_content(proper_target.attr('data-content-author'),proper_target.attr('data-content-permlink'),proper_target.attr('data-beneficiaries'),proper_target);
			}
		}
	}
	if($(target).hasClass('award-subcontent-action')){
		e.preventDefault();
		proper_target=$(target).closest('.comment');
		if(typeof proper_target.attr('data-author') !== 'undefined'){
			if($(target).hasClass('active')){
				add_notify(l10n.errors.awarded_content);
			}
			else{
				award_content(proper_target.attr('data-author'),proper_target.attr('data-permlink'),proper_target.attr('data-beneficiaries'),proper_target);
			}
		}
	}
	if($(target).hasClass('auth-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			if(!$(target).hasClass('disabled')){
				try_auth_signature($('input[name=login]').val(),$('input[name=posting_key]').val(),$('input[name=active_key]').val());
			}
		}
	}
	if($(target).hasClass('auth-custom-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			if(!$(target).hasClass('disabled')){
				try_auth($('input[name=login]').val(),$('input[name=posting_key]').val(),$('input[name=active_key]').val());
			}
		}
	}
	if($(target).hasClass('generate-general-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			generate_general_key(true);
		}
	}
	if($(target).hasClass('generate-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			generate_key(true);
		}
	}

	if($(target).hasClass('energy') || $(target).parent().hasClass('energy')){
		if($('.header-menu-el.energy').hasClass('powerup')){
			$('.header-menu-el.energy').removeClass('powerup');
		}
		else{
			$('.header-menu-el.energy').addClass('powerup');
		}
	}
	if($(target).hasClass('wallet-history-filter-all') || $(target).parent().hasClass('wallet-history-filter-all')){
		$('.wallet-history tbody tr').css('display','table-row');
	}
	if($(target).hasClass('wallet-history-filter-in') || $(target).parent().hasClass('wallet-history-filter-in')){
		$('.wallet-history tbody tr').css('display','none');
		$('.wallet-history tbody tr.wallet-history-in').css('display','table-row');
	}
	if($(target).hasClass('wallet-history-filter-out') || $(target).parent().hasClass('wallet-history-filter-out')){
		$('.wallet-history tbody tr').css('display','none');
		$('.wallet-history tbody tr.wallet-history-out').css('display','table-row');
	}
	if($(target).hasClass('witness-chain-properties-update-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let witness_login=$(target).closest('.witness-control').attr('data-witness');
			witness_chain_properties_update(witness_login);
		}
	}
	if($(target).hasClass('witness-update-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let witness_login=$(target).closest('.witness-control').attr('data-witness');
			let url=$(target).closest('.witness-control').find('input[name=url]').val();
			let signing_key=$(target).closest('.witness-control').find('input[name=signing_key]').val();
			witness_update(witness_login,url,signing_key);
		}
	}
	if($(target).hasClass('witness-vote-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let witness_login=$(target).closest('.witness-vote').attr('data-witness');
			let value=('true'==$(target).attr('data-value'));
			vote_witness(witness_login,value);
		}
	}
	if($(target).hasClass('committee-vote-request-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let request_id=$(target).closest('.committee-control').attr('data-request-id');
			let percent=$(target).closest('.committee-control').find('input[name=vote_percent]').val();
			committee_vote_request(request_id,percent);
		}
	}
	if($(target).hasClass('committee-worker-create-request-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let url=$('.committee-create-request input[name=url]').val();
			let worker=$('.committee-create-request input[name=worker]').val();
			let min_amount=$('.committee-create-request input[name=min_amount]').val();
			let max_amount=$('.committee-create-request input[name=max_amount]').val();
			let duration=$('.committee-create-request input[name=duration]').val();
			committee_worker_create_request(url,worker,min_amount,max_amount,duration);
		}
	}
	if($(target).hasClass('committee-cancel-request-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let request_id=$(target).closest('.committee-control').attr('data-request-id');
			committee_cancel_request(request_id);
		}
	}
	if($(target).hasClass('invite-registration-action') || $(target).parent().hasClass('invite-registration-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let secret_key=$('.invite-registration input[name=secret]').val();
			let receiver=$('.invite-registration input[name=receiver]').val();
			let private_key=$('.invite-registration input[name=private]').val();
			invite_registration(secret_key,receiver,private_key);
		}
	}
	if($(target).hasClass('invite-claim-action') || $(target).parent().hasClass('invite-claim-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let secret_key=$('.invite-claim input[name=secret]').val();
			let receiver=$('.invite-claim input[name=receiver]').val();
			invite_claim(secret_key,receiver);
		}
	}
	if($(target).hasClass('set-paid-subscription-action') || $(target).parent().hasClass('set-paid-subscription-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let url=$('.set-paid-subscription input[name=url]').val();
			let levels=parseInt($('.set-paid-subscription input[name=levels]').val());
			let amount=$('.set-paid-subscription input[name=amount]').val();
			amount=(parseInt(parseFloat(amount)*1000)/1000).toFixed(3);
			amount=amount+' VIZ';
			let period=parseInt($('.set-paid-subscription input[name=period]').val());
			set_paid_subscription(url,levels,amount,period);
		}
	};
	if($(target).hasClass('manage-subscription-update-action') || $(target).parent().hasClass('manage-subscription-update-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let selected=$(target).closest('.manage-subscription-item');
			let account=selected.find('input[name=account]').val();
			let level=parseInt(selected.find('input[name=level]').val());
			let amount=(parseInt(selected.find('input[name=amount]').val())/1000).toFixed(3);
			amount=amount+' VIZ';
			let period=parseInt(selected.find('input[name=period]').val());
			let auto_renewal=selected.find('input[name=auto_renewal]').prop('checked');
			paid_subscribe(account,level,amount,period,auto_renewal);
		}
	}
	if($(target).hasClass('set-paid-subscribe-action') || $(target).parent().hasClass('set-paid-subscribe-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let account=$('.set-paid-subscribe .set-paid-subscribe-agreement input[name=account]').val();
			let level=parseInt($('.set-paid-subscribe .set-paid-subscribe-agreement select[name=level]').val());
			let amount=(parseInt($('.set-paid-subscribe .set-paid-subscribe-agreement input[name=amount]').val())/1000).toFixed(3);
			amount=amount+' VIZ';
			let period=parseInt($('.set-paid-subscribe .set-paid-subscribe-agreement input[name=period]').val());
			let auto_renewal=$('.set-paid-subscribe .set-paid-subscribe-agreement input[name=auto_renewal]').prop('checked');
			paid_subscribe(account,level,amount,period,auto_renewal);
		}
	}
	if($(target).hasClass('set-paid-subscribe-lookup-action') || $(target).parent().hasClass('set-paid-subscribe-lookup-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let login=$('.set-paid-subscribe input[name=lookup-login]').val();
			$('.set-paid-subscribe .set-paid-subscribe-agreement').html('');
			gate.api.getPaidSubscriptionOptions(login,function(err, response){
				let result='';
				if(!err){
					let update_time=Date.parse(response.update_time);
					result+='<p>'+l10n.ps.sign_offer_creator+': <a href="/@'+response.creator+'" target="_blank">'+response.creator+'</a></p>';
					result+='<input type="hidden" name="account" value="'+response.creator+'">';
					if(0<response.url.length){
						result+='<p>'+l10n.ps.sign_offer_url+': <a href="'+encodeURI(response.url)+'" target="_blank">'+response.url+'</a></p>';
					}
					result+='<p>'+l10n.ps.sign_offer_update_date+': '+date_str(update_time-(new Date().getTimezoneOffset()*60000),true,false,false)+'</p>';
					if(0==response.levels){
						result+='<p><b>'+l10n.ps.sign_offer_closed+'</b></p>';
					}
					result+='<p>'+l10n.ps.sign_offer_levels+': '+response.levels+'</p>';
					result+='<p>'+l10n.ps.sign_offer_amount_per_level+': '+(response.amount/1000)+' VIZ</p>';
					result+='<input type="hidden" name="amount" value="'+response.amount+'">';
					result+='<p>'+l10n.ps.sign_offer_period+': '+response.period+'</p>';
					result+='<input type="hidden" name="period" value="'+response.period+'">';
					if(0<response.levels){
						result+='<hr><p>'+l10n.ps.sign_offer_approve+'</p>';
						result+='<p>'+l10n.ps.sign_offer_level+': <select name="level" class="round">';
						for(let i=1;i<=response.levels;i++){
							result+='<option value="'+i+'"'+(1==i?' selected':'')+'>'+i+' ('+l10n.ps.sign_offer_cost+' '+((i*response.amount)/1000)+' VIZ)</option>';
						}
						result+='</select></p>';
						result+='<p><label><input type="checkbox" name="auto_renewal"> &mdash; '+l10n.ps.sign_offer_auto_renewal+'</label></p>';
						result+='<p><a class="set-paid-subscribe-action button"><i class="fas fa-fw fa-file-signature"></i> '+l10n.ps.sign_offer_action+'</a>';
					}
				}
				else{
					result='<p>'+l10n.ps.sign_offer_none+'</p>';
				}
				$('.set-paid-subscribe .set-paid-subscribe-agreement').html(result);
			});
		}
	}
	if($(target).hasClass('paid-subscriptions-options-action') || $(target).parent().hasClass('paid-subscriptions-options-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let login=$('.paid-subscriptions-options input[name=lookup-login]').val();
			$('.paid-subscriptions-options .lookup-result').html('');
			gate.api.getPaidSubscriptionOptions(login,function(err, response){
				let result='';
				if(!err){
					let update_time=Date.parse(response.update_time);
					result+='<p>'+l10n.ps.view_offer_creator+': <a href="/@'+response.creator+'" target="_blank">'+response.creator+'</a></p>';
					if(0<response.url.length){
						result+='<p>'+l10n.ps.view_offer_url+': <a href="'+encodeURI(response.url)+'" target="_blank">'+response.url+'</a></p>';
					}
					result+='<p>'+l10n.ps.view_offer_update+': '+date_str(update_time-(new Date().getTimezoneOffset()*60000),true,false,false)+'</p>';
					if(0==response.levels){
						result+='<p><b>'+l10n.ps.view_offer_closed+'</b></p>';
					}
					result+='<p>'+l10n.ps.view_offer_levels+': '+response.levels+'</p>';
					result+='<p>'+l10n.ps.view_offer_amount_per_level+': '+(response.amount/1000)+' VIZ</p>';
					result+='<p>'+l10n.ps.view_offer_period+': '+response.period+'</p>';
					if(0<response.active_subscribers_count){
						result+='<h3>'+l10n.ps.view_offer_addon_caption+'</h3>';
						if(0<response.active_subscribers_with_auto_renewal_count){
							result+='<p>'+l10n.ps.view_offer_active_auto_renewal+': '+response.active_subscribers_with_auto_renewal_count+'</p>';
							result+='<p>'+l10n.ps.view_offer_active_auto_renewal_summary_amount+': '+(response.active_subscribers_with_auto_renewal_summary_amount/1000)+' VIZ</p>';
						}
						result+='<p>'+l10n.ps.view_offer_active+': '+response.active_subscribers_count+'</p>';
						result+='<p>'+l10n.ps.view_offer_active_summary_amount+': '+(response.active_subscribers_summary_amount/1000)+' VIZ</p>';
					}
				}
				else{
					result='<p>'+l10n.ps.view_offer_none+'</p>';
				}
				$('.paid-subscriptions-options .options-result').html(result);
			});
		}
	}
	if($(target).hasClass('paid-subscriptions-lookup-action') || $(target).parent().hasClass('paid-subscriptions-lookup-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let login=$('.paid-subscriptions-lookup input[name=lookup-login]').val();
			$('.paid-subscriptions-lookup .lookup-result').html('');
			gate.api.getActivePaidSubscriptions(login,function(err, response){
				if(!err){
					let result='<h3>'+l10n.ps.contracts_caption+'</h3>';
					for(let i in response){
						result+='<p><a class="paid-subscription-lookup-action link" data-login="'+login+'" data-creator="'+response[i]+'">'+l10n.ps.contracts_with_creator+' '+response[i]+'</span></p>';
					}
					if(0==response.length){
						result+='<p>'+l10n.ps.account_prepand+' <a href="/@'+login+'/" target="_blank">'+login+'</a> '+l10n.ps.contracts_none+'</p>';
					}
					$('.paid-subscriptions-lookup .lookup-result').html($('.paid-subscriptions-lookup .lookup-result').html()+result);
					gate.api.getInactivePaidSubscriptions(login,function(err, response){
						if(!err){
							let result='<h3>'+l10n.ps.inactive_contracts_caption+'</h3>';
							for(let i in response){
								result+='<p><a class="paid-subscription-lookup-action link" data-login="'+login+'" data-creator="'+response[i]+'">'+l10n.ps.contracts_with_creator+' '+response[i]+'</span></p>';
							}
							if(0==response.length){
								result+='<p>'+l10n.ps.account_prepand+' <a href="/@'+login+'/" target="_blank">'+login+'</a> '+l10n.ps.inactive_contracts_none+'</p>';
							}
							$('.paid-subscriptions-lookup .lookup-result').html($('.paid-subscriptions-lookup .lookup-result').html()+result);
						}
						else{
							add_notify(l10n.errors.api,true);
						}
					});
				}
				else{
					add_notify(l10n.errors.api,true);
				}
			});
		}
	}
	if($(target).hasClass('manage-subscription-action') || $(target).parent().hasClass('manage-subscription-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let login=$(target).attr('data-login');
			let creator=$(target).attr('data-creator');
			$('.manage-subscription-item[data-creator='+creator+'][data-login='+login+']').html('');
			gate.api.getPaidSubscriptionStatus(login,creator,function(err, response){
				let result='';
				if(!err){
					result+='<input type="hidden" name="account" value="'+response.creator+'">';
					result+='<p>'+l10n.ps.manage_status+': '+(response.active?l10n.ps.manage_status_active:'<span class="red">'+l10n.ps.manage_status_inactive+'</span>')+'</p>';
					result+='<p>'+l10n.ps.manage_auto_renewal+': '+(response.auto_renewal?l10n.ps.manage_auto_renewal_enabled:'<span class="red">'+l10n.ps.manage_auto_renewal_disabled+'</span>')+'</p>';
					result+='<p>'+l10n.ps.manage_level+': '+response.level+'</p>';
					result+='<input type="hidden" name="level" value="'+response.level+'">';
					result+='<p>'+l10n.ps.manage_amount_per_level+': '+(response.amount/1000)+' VIZ</p>';
					result+='<input type="hidden" name="amount" value="'+response.amount+'">';
					result+='<p>'+l10n.ps.manage_period+': '+response.period+'</p>';
					result+='<input type="hidden" name="period" value="'+response.period+'">';
					let start_time=Date.parse(response.start_time);
					result+='<p>'+l10n.ps.manage_start_date+': '+date_str(start_time-(new Date().getTimezoneOffset()*60000),true,false,false)+'</p>';
					if(response.active){
						let next_time=Date.parse(response.next_time);
						result+='<p>'+l10n.ps.manage_next_date+': '+date_str(next_time-(new Date().getTimezoneOffset()*60000),true,false,false)+'</p>';
					}
					result+='<p><label><input type="checkbox" name="auto_renewal"'+(response.auto_renewal?' checked':'')+'> &mdash; '+l10n.ps.manage_set_auto_renewal+'</label></p>';
					result+='<p><a class="manage-subscription-update-action button"><i class="fas fa-fw fa-file-signature"></i> '+l10n.ps.manage_action+'</a>';
				}
				else{
					result+='<p>'+l10n.ps.manage_none+'</p>';
				}
				result+='<hr>';
				$('.manage-subscription-item[data-creator='+creator+'][data-login='+login+']').html(result);
			});
		}
	}
	if($(target).hasClass('paid-subscription-lookup-action') || $(target).parent().hasClass('paid-subscription-lookup-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let login=$('.paid-subscription-lookup input[name=lookup-login]').val();
			let creator=$('.paid-subscription-lookup input[name=lookup-creator]').val();
			if(typeof $(target).attr('data-login') !== 'undefined'){
				login=$(target).attr('data-login');
				$('.paid-subscription-lookup input[name=lookup-login]').val(login);
			}
			if(typeof $(target).attr('data-creator') !== 'undefined'){
				creator=$(target).attr('data-creator');
				$('.paid-subscription-lookup input[name=lookup-creator]').val(creator);
			}
			$('.paid-subscription-lookup .lookup-result').html('');
			gate.api.getPaidSubscriptionStatus(login,creator,function(err, response){
				let result='';
				if(!err){
					result+='<p>'+l10n.ps.contract_between_subscriber+' <a href="/@'+login+'/" target="_blank">'+login+'</a> '+l10n.ps.contract_between_creator+' <a href="/@'+creator+'/" target="_blank">'+creator+'</a></p>';
					result+='<p>'+l10n.ps.contract_status+': '+(response.active?l10n.ps.contract_status_active:'<span class="red">'+l10n.ps.contract_status_inactive+'</span>')+'</p>';
					result+='<p>'+l10n.ps.contract_auto_renewal+': '+(response.auto_renewal?l10n.ps.contract_auto_renewal_enabled:'<span class="red">'+l10n.ps.contract_auto_renewal_disabled+'</span>')+'</p>';
					result+='<p>'+l10n.ps.contract_level+': '+response.level+'</p>';
					result+='<p>'+l10n.ps.contract_amount_per_level+': '+(response.amount/1000)+' VIZ</p>';
					result+='<p>'+l10n.ps.contract_period+': '+response.period+'</p>';
					let start_time=Date.parse(response.start_time);
					result+='<p>'+l10n.ps.contract_start_date+': '+date_str(start_time-(new Date().getTimezoneOffset()*60000),true,false,false)+'</p>';
					if(response.active){
						let next_time=Date.parse(response.next_time);
						result+='<p>'+l10n.ps.contract_next_date+': '+date_str(next_time-(new Date().getTimezoneOffset()*60000),true,false,false)+'</p>';
					}
					else{
						let end_time=Date.parse(response.end_time);
						result+='<p>'+l10n.ps.contract_end_date+': '+date_str(end_time-(new Date().getTimezoneOffset()*60000),true,false,false)+'</p>';
					}
				}
				else{
					result+='<p>'+l10n.ps.contract_none+'</p>';
				}
				$('.paid-subscription-lookup .lookup-result').html(result);
			});
		}
	}
	if($(target).hasClass('invite-lookup-action') || $(target).parent().hasClass('invite-lookup-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let public_key=$('.invite-lookup input[name=public]').val();
			gate.api.getInviteByKey(public_key,function(err, response){
				if(!err){
					let result='';
					result+='<p>'+l10n.invite.lookup_creator+': <a href="/@'+response.creator+'/">'+response.creator+'</a></p>';
					result+='<p>'+l10n.invite.lookup_create_date+': '+response.create_time+'</p>';
					result+='<p>'+l10n.invite.lookup_balance+': '+response.balance+'</p>';
					if(0==response.status){
						result+='<p>'+l10n.invite.lookup_status+': '+l10n.invite.lookup_status_unused+'</p>';
					}
					if(1==response.status){
						result+='<p>'+l10n.invite.lookup_status+': '+l10n.invite.lookup_status_activated+' '+response.claim_time+', '+l10n.invite.lookup_claimed+' '+response.receiver+'</p>';
						result+='<p>'+l10n.invite.lookup_claimed_balance+': '+response.claimed_balance+'</p>';
						result+='<p>'+l10n.invite.lookup_secret_key+': '+response.invite_secret+'</p>';
					}
					if(2==response.status){
						result+='<p>'+l10n.invite.lookup_status+': '+l10n.invite.lookup_status_activated+' '+response.claim_time+', '+l10n.invite.lookup_registered+' '+response.receiver+'</p>';
						result+='<p>'+l10n.invite.lookup_claimed_balance+': '+response.claimed_balance+'</p>';
						result+='<p>'+l10n.invite.lookup_secret_key+': '+response.invite_secret+'</p>';
					}
					$('.invite-lookup .search-result').html(result);
				}
				else{
					add_notify(l10n.global.error,true);
				}
			});
		}
	}
	if($(target).hasClass('reset-account-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let general_key=$('.reset-account-control input[name=general_key]').val();
			let account_login=$('.reset-account-control input[name=account_login]').val().toLowerCase();
			let owner_key=$('.reset-account-control input[name=owner_key]').val();
			reset_account_with_general_key(account_login,owner_key,general_key);
		}
	}
	if($(target).hasClass('create-account-action') || $(target).parent().hasClass('create-account-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let general_key=$('.create-account-control input[name=general_key]').val();
			let account_login=$('.create-account-control input[name=account_login]').val().toLowerCase();
			let token_amount=$('.create-account-control input[name=token_amount]').val();
			let shares_amount=$('.create-account-control input[name=shares_amount]').val();
			create_account_with_general_key(account_login,token_amount,shares_amount,general_key);
		}
	}
	if($(target).hasClass('invite-action') || $(target).parent().hasClass('invite-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let private_key=$('.invite-control input[name=private]').val();
			let public_key=$('.invite-control input[name=public]').val();
			let amount=$('.invite-control input[name=amount]').val();
			invite_create(private_key,public_key,amount);
		}
	}
	if($(target).hasClass('delegation-action') || $(target).parent().hasClass('delegation-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			var proper_target=$(target);
			if($(target).parent().hasClass('delegation-action')){
				proper_target=$(target).parent();
			}
			wallet_delegate($('.delegation-control input[name=recipient]').val(),$('.delegation-control input[name=amount]').val());
		}
	}
	if($(target).hasClass('disable-withdraw-shares-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			wallet_withdraw_shares(true);
		}
	}
	if($(target).hasClass('enable-withdraw-shares-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			wallet_withdraw_shares();
		}
	}
	if($(target).hasClass('wallet-transfer-action') || $(target).parent().hasClass('wallet-transfer-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			var proper_target=$(target);
			if($(target).parent().hasClass('wallet-transfer-action')){
				proper_target=$(target).parent();
			}
			if(!$('.wallet-control .wallet-transfer-action').hasClass('disabled')){
				wallet_transfer($('.wallet-control input[name=recipient]').val(),$('.wallet-control input[name=amount]').val(),$('.wallet-control input[name=memo]').val());
			}
		}
	}
	if(0<$(target).closest('.wallet-memo-set').length){
		$('.wallet-control input[name=memo]').val($(target).closest('.wallet-memo-set').text());
	}
	if($(target).hasClass('wallet-amount-set')){
		$('.wallet-control input[name=amount]').val(parseFloat($(target).text()));
	}
	if($(target).hasClass('wallet-recipient-set')){
		$('.wallet-control input[name=recipient]').val($(target).text());
	}
	if($(target).hasClass('auth-change')){
		e.preventDefault();
		let login=$(target).attr('data-login');
		if(typeof users[login] !== 'undefined'){
			current_user=login;
			$('.menu .avatar').remove();
			save_session();
		}
	}
	if($(target).hasClass('auth-logout') || $(target).parent().hasClass('auth-logout')){
		e.preventDefault();
		var proper_target=$(target);
		if($(target).parent().hasClass('auth-logout')){
			proper_target=$(target).parent();
		}
		let login=proper_target.attr('data-login');
		logout(login,login?false:true);
	}
	if($(target).hasClass('reply-execute')){
		e.preventDefault();
		if(!$(target).hasClass('disabled')){
			post_subcontent($(target));
		}
	}
	if($(target).hasClass('reply-action') || $(target).parent().hasClass('reply-action')){
			e.preventDefault();
			var proper_target=$(target);
			if($(target).parent().hasClass('reply-action')){
				proper_target=$(target).parent();
			}
			if(1==users[current_user].session_verify){
				window.clearTimeout(update_comments_list_timer);
				var content_id=0;
				var subcontent_id=0;
				if(proper_target.hasClass('content-reply')){
					content_id=parseInt($('.page.content').attr('data-content-id'));
				}
				if(proper_target.hasClass('subcontent-reply')){
					subcontent_id=parseInt(proper_target.closest('.comment').attr('data-id'));
				}
				var comment_form='<div class="reply-form" data-reply-content="'+content_id+'" data-reply-subcontent="'+subcontent_id+'"><textarea name="reply-text" class="round" placeholder="'+l10n.media.reply_placeholder+'"></textarea><input type="button" class="button reply-execute" value="'+l10n.media.reply_action+'"></div>'
				if(subcontent_id){
					if(0==$('.reply-form[data-reply-subcontent='+subcontent_id+']').length){
						proper_target.closest('.addon').after(comment_form);
						proper_target.closest('.addon').parent().find('.reply-form textarea[name=reply-text]').focus();
					}
					else{
						$('.reply-form[data-reply-subcontent='+subcontent_id+']').remove();
					}
				}
				if(content_id){
					if(0==$('.reply-form[data-reply-content='+content_id+']').length){
						proper_target.closest('.comments').find('.subtitle').after(comment_form);
						proper_target.closest('.comments').find('.reply-form textarea[name=reply-text]').focus();
					}
					else{
						$('.reply-form[data-reply-content='+content_id+']').remove();
					}
				}
			}
		}
}
function scroll_top_action(){
	if(0!=$(window).scrollTop()){
		global_scroll_top=$(window).scrollTop();
		$(window).scrollTop(0);
	}
	else{
		$(window).scrollTop(global_scroll_top);
	}
}
function check_load_more(){
	var scroll_top=$(window).scrollTop();
	var window_height=window.innerHeight;
	if(0==scroll_top){
		if($('.go-top-button').length>0){
			if(0==global_scroll_top){
				$('.go-top-button').css('display','none');
			}
			$('.go-top-button i').addClass('fa-chevron-down');
			$('.go-top-button i').removeClass('fa-chevron-up');
		}
	}
	else{
		if($('.go-top-button').length>0){
			$('.go-top-button').css('display','block');
			$('.go-top-button i').addClass('fa-chevron-up');
			$('.go-top-button i').removeClass('fa-chevron-down');
		}
	}
	$('.load-more').each(function(){
		var indicator=$(this);
		if('1'!=indicator.attr('data-busy')){
			var offset=indicator.offset();
			if((scroll_top+window_height)>(offset.top-10)){
				if('new-content'==indicator.attr('data-action')){
					var content_list=indicator.parent();
					indicator.attr('data-busy','1');
					indicator.find('.fa-spinner').addClass('fa-spin');
					var last_content_id=-1;
					content_list.find('.page.preview').each(function(){
						var find_content_id=parseInt($(this).attr('data-content-id'))
						if(find_content_id<last_content_id){
							last_content_id=find_content_id;
						}
						if(-1==last_content_id){
							last_content_id=find_content_id;
						}
					});
					$.ajax({
						type:'POST',
						url:'/ajax/load_more/',
						data:{action:indicator.attr('data-action'),last_id:last_content_id},
						success:function(data_html){
							if('none'==data_html){
								indicator.css('display','none');
							}
							else{
								indicator.before(data_html);
								update_datetime();
								indicator.find('.fa-spinner').removeClass('fa-spin');
								indicator.attr('data-busy','0');
							}
						}
					});
				}
				if('feed-content'==indicator.attr('data-action')){
					var content_list=indicator.parent();
					indicator.attr('data-busy','1');
					indicator.find('.fa-spinner').addClass('fa-spin');
					var last_content_id=99999999999;
					content_list.find('.page.preview').each(function(){
						var find_content_id=parseInt($(this).attr('data-content-id'));
						if(typeof $(this).attr('data-repost-id') !== 'undefined'){
							find_content_id=parseInt($(this).attr('data-repost-id'));
						}
						if(find_content_id<last_content_id){
							last_content_id=find_content_id;
						}
					});
					$.ajax({
						type:'POST',
						url:'/ajax/load_more/',
						data:{action:indicator.attr('data-action'),last_id:last_content_id,user:indicator.attr('data-user-login')},
						success:function(data_html){
							if('none'==data_html){
								indicator.css('display','none');
							}
							else{
								indicator.before(data_html);
								update_datetime();
								indicator.find('.fa-spinner').removeClass('fa-spin');
								indicator.attr('data-busy','0');
							}
						}
					});
				}
				if('user-content'==indicator.attr('data-action')){
					var content_list=indicator.parent();
					indicator.attr('data-busy','1');
					indicator.find('.fa-spinner').addClass('fa-spin');
					var last_content_id=99999999999;
					content_list.find('.page.preview').each(function(){
						var find_content_id=parseInt($(this).attr('data-content-id'));
						if(typeof $(this).attr('data-repost-id') !== 'undefined'){
							find_content_id=parseInt($(this).attr('data-repost-id'));
						}
						if(find_content_id<last_content_id){
							last_content_id=find_content_id;
						}
					});
					$.ajax({
						type:'POST',
						url:'/ajax/load_more/',
						data:{action:indicator.attr('data-action'),last_id:last_content_id,user:indicator.attr('data-user-login')},
						success:function(data_html){
							if('none'==data_html){
								indicator.css('display','none');
							}
							else{
								indicator.before(data_html);
								update_datetime();
								indicator.find('.fa-spinner').removeClass('fa-spin');
								indicator.attr('data-busy','0');
							}
						}
					});
				}
				if('tag-content'==indicator.attr('data-action')){
					var content_list=indicator.parent();
					indicator.attr('data-busy','1');
					indicator.find('.fa-spinner').addClass('fa-spin');
					var last_content_id=99999999999;
					content_list.find('.page.preview').each(function(){
						var find_content_id=parseInt($(this).attr('data-content-id'));
						if(find_content_id<last_content_id){
							last_content_id=find_content_id;
						}
					});
					$.ajax({
						type:'POST',
						url:'/ajax/load_more/',
						data:{action:indicator.attr('data-action'),tag:indicator.attr('data-tag'),last_id:last_content_id},
						success:function(data_html){
							if('none'==data_html){
								indicator.css('display','none');
							}
							else{
								indicator.before(data_html);
								update_datetime();
								indicator.find('.fa-spinner').removeClass('fa-spin');
								indicator.attr('data-busy','0');
							}
						}
					});
				}
			}
		}
	});
}
function activate_parallax(){
	document.onmousemove=function(e){
		let x=e.clientX;
		let y=e.clientY;
		let max_x=$(window).width();
		let max_y=$(window).height();
		let rotate_vertical=((x-(max_x/2))/max_x)*30;
		let rotate_horizontal=((y-(max_y/2))/max_y)*30;
		$('.parallax-active').css('transform','rotateY('+rotate_vertical+'deg) rotateX('+rotate_horizontal+'deg) rotateZ(0deg) scale(1)');
		let move_vertical=-1*((x-(max_x/2))/max_x)*120;
		let move_horizontal=-1*((y-(max_y/2))/max_y)*120;
		$('.parralax-glare').css('transform','translate('+move_vertical+'%,'+move_horizontal+'%)');
	}
}
$(document).ready(function(){
	load_session();
	var hash_load=window.location.hash;
	if(''!=hash_load){
		window.location.hash='';
		window.location.hash=hash_load;
	}
	document.addEventListener('click', app_mouse, false);
	document.addEventListener('tap', app_mouse, false);
	document.addEventListener('keyup', app_keyboard, false);
	update_dgp();
	update_datetime();
	check_load_more();
	$(window).scroll(function(){
		check_load_more();
	});
	$(window).resize(function(){
		check_load_more();
	});
	if(0<$('input[type=range]').length){
		bind_range();
	}
	if(0<$('.page.comments').length){
		update_comments_list_timer=window.setTimeout(function(){update_comments_list()},update_comments_list_timeout);
	}
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
	if(0<$('.parallax-active').length){
		activate_parallax();
	}
	if(0<$('body.landing .info-bubbles a.item').length){
		$('body.landing .info-bubbles a.item').bind('click',function(){
			$('body.landing .info-bubbles a.item').removeClass('active');
			$(this).addClass('active');
			$('body.landing .info-block.bubble-item').css('display','none');
			$('body.landing .info-block.bubble-item[id='+$(this).attr('rel')+']').css('display','block');
			if(780>=$(window).width()){
				$('body,html').animate({scrollTop:$('#'+$(this).attr('rel')).offset().top},1500);
				window.location.hash=$(this).attr('rel');
			}
		});
	}
});