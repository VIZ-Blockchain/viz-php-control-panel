var gate=viz;
var api_gates=['wss://lexai.host/ws','wss://api.viz.blckchnd.com/ws','wss://ws.viz.ropox.tools/']
var best_gate=-1;
var best_gate_latency=-1;
var api_gate;
api_gate=api_gates[Math.floor(Math.random()*api_gates.length)];
if(null!=localStorage.getItem('api_gate_default')){
	api_gate=localStorage.getItem('api_gate_default');
	gate.config.set('websocket',api_gate);
	gate.api.stop();
}
else{
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
	gate.api.stop();
}

function select_best_gate(){
	for(i in api_gates){
		let current_gate=i;
		let latency_start=new Date().getTime();
		let latency=-1;
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
			$('.auth-error').html('Ошибка при инициализации сессии, попробуйте авторизоваться повторно позже');
			$('.auth-action').removeClass('disabled');
		}
		else{
			$('.header .account').html('<i class="fa fw-fw fa-spinner fa-spin"></i> Загрузка&hellip;');
			$('.auth-error').html('Вы успешно авторизованы, инициализируем сессию, подождите (попытка '+users[current_user].session_attempts+')');
			$.ajax({
				type:'POST',
				url:'/ajax/check_session/',
				data:{},
				success:function(data_json){
					data_obj=JSON.parse(data_json);
					if(typeof data_obj.error !== 'undefined'){
						console.log(''+new Date().getTime()+': '+data_obj.error+' - '+data_obj.error_str);
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
						$('.auth-error').html('Вы успешно авторизованы, сессия инициализирована');
						$('.auth-action').removeClass('disabled');
						//initialize user_session_status (feed status, notifications)
						if('/'==document.location.pathname){
							document.location='https://'+domain+'/feed/';
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
		gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'follow',json,function(err,result){
			if(!err){
				add_notify('Вы успешно подписались на '+user);
				proper_target.html('<div class="unfollow unfollow-action">Отписаться</div>');
			}
			else{
				add_notify('Не удается отправить операцию подписки на '+user,true);
				console.log(err);
			}
		});
	}
}
function unfollow_user(user,proper_target){
	if(''!=current_user){
		let json=JSON.stringify(['follow',{follower:current_user,following:user,what:[]}]);
		gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'follow',json,function(err,result){
			if(!err){
				add_notify('Вы стали соблюдать нейтралитет с '+user);
				proper_target.html('<div class="follow follow-action">Подписаться</div><br><div class="ignore ignore-action">Игнорировать</div>');
			}
			else{
				add_notify('Не удается отправить операцию нейтралитета с '+user,true);
				console.log(err);
			}
		});
	}
}
function ignore_user(user,proper_target){
	if(''!=current_user){
		let json=JSON.stringify(['follow',{follower:current_user,following:user,what:['ignore']}]);
		gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'follow',json,function(err,result){
			if(!err){
				add_notify('Вы успешно начали игнорировать '+user);
				proper_target.html('<div class="unfollow unfollow-action">Перестать игнорировать</div>');
			}
			else{
				add_notify('Не удается отправить операцию игнорирования '+user,true);
				console.log(err);
			}
		});
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
				gate.broadcast.custom(users[current_user].posting_key,[],[current_user],'session','["auth",{"key":"'+key+'"}]',function(err,result){
					if(!err){
						console.log(result);
						save_session();
						wait_session_timer=window.setTimeout('wait_session()',3000);
					}
					else{
						$('.auth-error').html('Не удается отправить custom операцию для инициализации сессии');
						$('.auth-action').removeClass('disabled');
						console.log(err);
					}
				});
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
		witness_control();
		wallet_control();
		committee_control();
		delegation_control();
		wait_session();
		profile_control();
	}
	create_account_control();
	reset_account_control();
	invite_control();
}
function view_session(){
	if(''!=current_user){
		$('.header .account').html('<a href="/@'+current_user+'/">'+current_user+'</a> <a class="auth-logout icon"><i class="fas fa-fw fa-sign-out-alt"></i></a>');
		view_energy();
	}
	else{
		$('.header .account').html('<a href="/login/" class="icon" title="Авторизация"><i class="fas fa-fw fa-sign-in-alt"></i></a>');
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
					let awarded_votes=parseInt(awarded_rshares/parseInt(parseFloat(response[0].vesting_shares)*1000000/10/5));
					$('.header .energy').html((new_energy/100)+'%'+(0<awarded_votes?'<span title="Доступно апов из сокровищницы: '+awarded_votes+'">+</span>':'')+' '+energy_icon);
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
		gate.broadcast.withdrawVesting(users[current_user].active_key,current_user,'0.000000 SHARES',function(err,response){
			if(!err){
				wallet_control(true);
				add_notify('Понижение доли отменено');
			}
			else{
				add_notify('Ошибка',true);
				add_notify(err.payload.error.data.stack[0].format,true);
			}
		});
	}
	else{
		gate.api.getAccounts([current_user],function(err,response){
			if(typeof response[0] !== 'undefined'){
				vesting_shares=parseFloat(response[0].vesting_shares);
				delegated_vesting_shares=parseFloat(response[0].delegated_vesting_shares);
				shares=vesting_shares-delegated_vesting_shares;
				let fixed_shares=''+shares.toFixed(6)+' SHARES';
				gate.broadcast.withdrawVesting(users[current_user].active_key,current_user,fixed_shares,function(err,response){
					if(!err){
						wallet_control(true);
						add_notify('Понижение доли запущено');
					}
					else{
						add_notify('Ошибка',true);
						add_notify(err.payload.error.data.stack[0].format,true);
					}
				});
			}
			else{
				add_notify('Информация по аккаунту не получена',true);
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
function invite_register(secret_key,receiver,private_key){
	public_key=gate.auth.wifToPublic(private_key);
	gate.broadcast.inviteRegistration('5KcfoRuDfkhrLCxVcE9x51J6KN9aM9fpb78tLrvvFckxVV6FyFW','invite',receiver,secret_key,public_key,function(err,result){
		if(!err){
			add_notify('Код успешно активирован');
			download('viz-registration.txt','VIZ.World registration\r\nAccount login: '+receiver+'\r\nPrivate key: '+private_key+'');
		}
		else{
			add_notify('Ошибка при активации кода',true);
			gate.api.getAccounts([receiver],function(err,response){
				if(!err){
					add_notify('Логин '+receiver+' недоступен',true);
				}
			});
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	});
}
function invite_claim(secret_key,receiver){
	gate.broadcast.claimInviteBalance('5KcfoRuDfkhrLCxVcE9x51J6KN9aM9fpb78tLrvvFckxVV6FyFW','invite',receiver,secret_key,function(err,result){
		if(!err){
			add_notify('Код успешно активирован');
		}
		else{
			add_notify('Ошибка при активации кода',true);
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	});
}
function reset_account_with_general_key(account_login,owner_key,general_key){
	let auth_types = ['posting','active','owner','memo'];
	let keys=gate.auth.getPrivateKeys(account_login,general_key,auth_types);
	let owner = {
		"weight_threshold": 1,
		"account_auths": [],
		"key_auths": [
			[keys.ownerPubkey, 1]
		]
	};
	let active = {
		"weight_threshold": 1,
		"account_auths": [],
		"key_auths": [
			[keys.activePubkey, 1]
		]
	};
	let posting = {
		"weight_threshold": 1,
		"account_auths": [],
		"key_auths": [
			[keys.postingPubkey, 1]
		]
	};
	let memo_key=keys.memoPubkey;
	gate.api.getAccounts([account_login],function(err,response){
		if(!err){
			let json_metadata=response[0].json_metadata;
			gate.broadcast.accountUpdate(owner_key,account_login,owner,active,posting,memo_key,json_metadata,function(err,result){
				if(!err){
					add_notify('Данные аккаунта успешно обновлены');
					download('viz-reset-account.txt','VIZ.World Account: '+account_login+'\r\nGeneral key (for private keys): '+general_key+'\r\nPrivate owner key: '+keys.owner+'\r\nPrivate active key: '+keys.active+'\r\nPrivate posting key: '+keys.posting+'\r\nPrivate memo key: '+keys.memo+'');
					if(typeof users[account_login] !== 'undefined'){
						if(''!=users[account_login].posting_key){
							users[account_login].posting_key=keys.posting;
						}
						if(''!=users[account_login].active_key){
							users[account_login].active_key=keys.active;
						}
					}
				}
				else{
					add_notify('Ошибка при обновлении аккаунта',true);
					if(typeof err.message !== 'undefined'){
						add_notify(err.message,true);
					}
					else{
						add_notify(err.payload.error.data.stack[0].format,true);
					}
				}
			});
		}
		else{
			add_notify('Ошибка в получении аккаунта '+account_login,true);
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
	let auth_types = ['posting','active','owner','memo'];
	let keys=gate.auth.getPrivateKeys(account_login,general_key,auth_types);
	let owner = {
		"weight_threshold": 1,
		"account_auths": [],
		"key_auths": [
			[keys.ownerPubkey, 1]
		]
	};
	let active = {
		"weight_threshold": 1,
		"account_auths": [],
		"key_auths": [
			[keys.activePubkey, 1]
		]
	};
	let posting = {
		"weight_threshold": 1,
		"account_auths": [],
		"key_auths": [
			[keys.postingPubkey, 1]
		]
	};
	let memo_key=keys.memoPubkey;
	let json_metadata='';
	let referrer='';
	gate.broadcast.accountCreate(users[current_user].active_key,fixed_token_amount,fixed_shares_amount,current_user,account_login,owner,active,posting,memo_key,json_metadata, referrer,[],function(err,result){
		if(!err){
			add_notify('Аккаунт успешно создан');
			download('viz-account.txt','VIZ.World Account: '+account_login+'\r\nGeneral key (for private keys): '+general_key+'\r\nPrivate owner key: '+keys.owner+'\r\nPrivate active key: '+keys.active+'\r\nPrivate posting key: '+keys.posting+'\r\nPrivate memo key: '+keys.memo+'');
			gate.api.getAccounts([current_user],function(err,response){
				if(!err){
					$('.control .create-account-control .token[data-symbol=VIZ] .amount').html(parseFloat(response[0]['balance']));
					$('.control .create-account-control .token[data-symbol=SHARES] .amount').html(parseFloat(response[0]['vesting_shares']));
				}
			});
		}
		else{
			add_notify('Ошибка при создании аккаунта',true);
			gate.api.getAccounts([account_login],function(err,response){
				if(!err){
					add_notify('Логин '+account_login+' недоступен',true);
				}
			});
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	});
}
function invite_create(private_key,public_key,amount){
	amount=parseFloat(amount);
	let fixed_amount=''+amount.toFixed(3)+' VIZ';
	gate.broadcast.createInvite(users[current_user].active_key,current_user,fixed_amount,public_key,function(err,result){
		if(!err){
			download('viz-invite.txt','VIZ.World Invite code with amount: '+fixed_amount+'\r\nPublic key (for check): '+public_key+'\r\nPrivate key (for activation): '+private_key+'\r\nYou can check code and claim or use it on https://viz.world/tools/invites/');
			add_notify('Инвайт код создан успешно');
		}
		else{
			add_notify('Ошибка при создании инвайт кода',true);
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	});
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
				gate.broadcast.delegateVestingShares(users[current_user].active_key,current_user,login,fixed_amount,function(err,result){
					if(!err){
						delegation_control();
					}
					else{
						add_notify('Ошибка в переводе',true);
						add_notify(err.payload.error.data.stack[0].format,true);
					}
				});
			}
			else{
				add_notify('Получатель не найден',true);
			}
		});
	}
}
function wallet_transfer(recipient,amount,memo){
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
				var shares=$('.wallet-control input[name=shares]').prop('checked');
				if(shares){
					gate.broadcast.transferToVesting(users[current_user].active_key,current_user,login,fixed_amount,function(err,result){
						if(!err){
							wallet_control(true);
						}
						else{
							add_notify('Ошибка в переводе',true);
							add_notify(err.payload.error.data.stack[0].format,true);
						}
					});
				}
				else{
					gate.broadcast.transfer(users[current_user].active_key,current_user,login,fixed_amount,memo,function(err,result){
						if(!err){
							wallet_control(true);
						}
						else{
							add_notify('Ошибка в переводе',true);
							add_notify(err.payload.error.data.stack[0].format,true);
						}
					});
				}
			}
			else{
				add_notify('Получатель не найден',true);
			}
		});
	}
}
function committee_worker_create_request(url,worker,min_amount,max_amount,duration){
	if(duration<=30){
		duration=duration*3600*24;
	}
	gate.broadcast.committeeWorkerCreateRequest(users[current_user]['posting_key'],current_user,url,worker,min_amount,max_amount,duration,function(err,result) {
		if(err){
			add_notify('Ошибка',true);
			add_notify(err.payload.error.data.stack[0].format,true);
		}
		else{
			add_notify('Вы успешно создали заявку');
			document.location='/committee/';
		}
	});
}
function committee_cancel_request(request_id){
	gate.broadcast.committeeWorkerCancelRequest(users[current_user]['posting_key'],current_user,parseInt(request_id),function(err,result) {
		if(err){
			add_notify('Ошибка',true);
		}
		else{
			committee_control();
			add_notify('Вы успешно отменили заявку');
		}
	});
}
function committee_vote_request(request_id,percent){
	gate.broadcast.committeeVoteRequest(users[current_user]['posting_key'],current_user,parseInt(request_id),percent*100,function(err,result) {
		if(err){
			add_notify('Ошибка при голосовании',true);
		}
		else{
			add_notify('Вы успешно проголосовали');
		}
	});
}
function witness_update(witness_login,url,signing_key){
	if(current_user!=witness_login){
		add_notify('Текущий пользователь не совпадает с делегатом для обновления',true);
	}
	else{
		if(''==signing_key){
			signing_key=empty_signing_key;
		}
		gate.broadcast.witnessUpdate(users[current_user]['active_key'],current_user,url,signing_key,function(err,result){
			if(!err){
				witness_control();
				add_notify('Данные успешно транслированы в сеть');
			}
			else{
				add_notify('Ошибка',true);
			}
		});
	}
}
function witness_chain_properties_update(witness_login,url,signing_key){
	if(current_user!=witness_login){
		add_notify('Текущий пользователь не совпадает с делегатом для обновления',true);
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
				gate.broadcast.chainPropertiesUpdate(users[current_user]['active_key'],current_user,props,function(err,response){
					if(!err){
						witness_control();
						add_notify('Параметры успешно транслированы в сеть');
					}
					else{
						add_notify('Ошибка',true);
						add_notify(err.payload.error.data.stack[0].format,true);
					}
				});
			}
		});
	}
}
function unvote_subcontent(author,permlink,target){
	gate.broadcast.vote(users[current_user].posting_key,current_user,author,permlink,0,function(err,result){
		if(!err){
			target.find('.flag-subcontent-action').removeClass('active').attr('title','');
			target.find('.award-subcontent-action').removeClass('active').attr('title','');
			add_notify('Вы успешно сняли голос');
			view_energy();
		}
		else{
			add_notify('Ошибка',true);
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	});
}
function upvote_subcontent(author,permlink,target){
	let weight=10000/10;
	if($('.header-menu-el.energy').hasClass('powerup')){
		weight=10000;
		$('.header-menu-el.energy').removeClass('powerup');
	}
	gate.broadcast.vote(users[current_user].posting_key,current_user,author,permlink,weight,function(err,result){
		if(!err){
			target.find('.flag-subcontent-action').removeClass('active').attr('title','');
			target.find('.award-subcontent-action').addClass('active').attr('title','Вы проголосовали с силой '+(weight/100)+'%');
			add_notify('Вы успешно проголосовали');
			view_energy();
		}
		else{
			add_notify('Ошибка',true);
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	});
}
function unvote_content(author,permlink,target){
	gate.broadcast.vote(users[current_user].posting_key,current_user,author,permlink,0,function(err,result){
		if(!err){
			target.find('.flag-action').removeClass('active').attr('title','');
			target.find('.award-action').removeClass('active').attr('title','');
			let votes_count=target.find('.votes_count span');
			votes_count.html(parseInt(votes_count.html())-1);
			add_notify('Вы успешно сняли голос');
			view_energy();
		}
		else{
			add_notify('Ошибка',true);
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	});
}
function upvote_content(author,permlink,target){
	let weight=10000/10;
	if($('.header-menu-el.energy').hasClass('powerup')){
		weight=10000;
		$('.header-menu-el.energy').removeClass('powerup');
	}
	gate.broadcast.vote(users[current_user].posting_key,current_user,author,permlink,weight,function(err,result){
		if(!err){
			target.find('.flag-action').removeClass('active').attr('title','');
			target.find('.award-action').addClass('active').attr('title','Вы проголосовали с силой '+(weight/100)+'%');
			let votes_count=target.find('.votes_count span');
			votes_count.html(1+parseInt(votes_count.html()));
			add_notify('Вы успешно проголосовали');
			view_energy();
		}
		else{
			add_notify('Ошибка',true);
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	});
}
function flag_content(author,permlink,target){
	let weight=10000/10;
	if($('.header-menu-el.energy').hasClass('powerup')){
		weight=10000;
		$('.header-menu-el.energy').removeClass('powerup');
	}
	gate.broadcast.vote(users[current_user].posting_key,current_user,author,permlink,-weight,function(err,result){
		if(!err){
			target.find('.award-action').removeClass('active').attr('title','');
			target.find('.flag-action').addClass('active').attr('title','Вы поставили флаг с силой '+(weight/100)+'%');
			let votes_count=target.find('.votes_count span');
			votes_count.html(1+parseInt(votes_count.html()));
			add_notify('Вы успешно поставили флаг');
			view_energy();
		}
		else{
			add_notify('Ошибка',true);
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	});
}
function vote_witness(witness_login,value){
	gate.broadcast.accountWitnessVote(users[current_user]['active_key'],current_user,witness_login,value,function(err, result){
		if(!err){
			witness_control();
		}
		else{
			add_notify('Вы не можете голосовать',true);
			add_notify(err.payload.error.data.stack[0].format,true);
		}
	});
}
function witness_control(){
	if(0!=$('.witness-votes').length){
		let view=$('.witness-votes');
		let result='';
		result+='<h3>Ваши голоса</h3>';
		view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> Загрузка&hellip;</p>');
		gate.api.getAccounts([current_user],function(err,response){
			result+='<p>';
			for(vote_id in response[0].witness_votes){
				result+=(0==vote_id?'':', ')+'<a href="/witnesses/'+response[0].witness_votes[vote_id]+'/">'+response[0].witness_votes[vote_id]+'</a>';
			}
			result+='</p>';
			view.html(result);
		});
	}
	if(0!=$('.control .witness-vote').length){
		$('.witness-vote').each(function(){
			let witness_login=$(this).attr('data-witness');
			let view=$(this);
			let result='';
			result+='<h3>Голосование за делегата '+witness_login+'</h3>';
			view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> Загрузка&hellip;</p>');
			if(''==users[current_user].active_key){
				result+='Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.';
				view.html(result);
			}
			else{
				gate.api.getAccounts([current_user],function(err,response){
					if(typeof response[0] !== 'undefined'){
						if(response[0].witness_votes.includes(witness_login)){
							result+='<input type="button" class="witness-vote-action button negative" data-value="false" value="Снять голос с делегата">';
						}
						else{
							result+='<input type="button" class="witness-vote-action button" data-value="true" value="Отдать голос за делегата">';
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
				result+='<h3>Управление делегатом '+witness_login+'</h3>';
				view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> Загрузка&hellip;</p>');
				if(''==users[current_user].active_key){
					result+='Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.';
					view.html(result);
				}
				else{
					gate.api.getWitnessByAccount(witness_login,function(err,response){
						if(!err){
							result+='<label class="input-descr">URL заявления о намерениях:<input type="text" name="url" class="round wide" value="'+response.url+'"></label>';
							result+='<label class="input-descr">Публичный ключ подписи:<input type="text" name="signing_key" class="round wide" value="'+response.signing_key+'" placeholder="'+empty_signing_key+'"></label>';
							result+='<input type="button" class="witness-update-action button" value="Сохранить">';
							result+='<h4>Параметры сети</h4>';
							result+='<label class="input-descr">Передаваемая комиссия при создании аккаунта:<input type="text" name="account_creation_fee" class="witness-chain-properties round wide" value="'+response.props.account_creation_fee+'"></label>';
							result+='<label class="input-descr">Коэффициент делегирования при создании аккаунта:<input type="text" name="create_account_delegation_ratio" class="witness-chain-properties round wide" value="'+response.props.create_account_delegation_ratio+'"></label>';
							result+='<label class="input-descr">Время делегирования при создании аккаунта (секунд):<input type="text" name="create_account_delegation_time" class="witness-chain-properties round wide" value="'+response.props.create_account_delegation_time+'"></label>';
							result+='<label class="input-descr">Доля сети, выделяемая для резервной пропускной способности (процент):<input type="text" name="bandwidth_reserve_percent" class="witness-chain-properties round wide" value="'+response.props.bandwidth_reserve_percent/100+'"></label>';
							result+='<label class="input-descr">Резервная пропускная способность действует для аккаунтов с долей сети до порога:<input type="text" name="bandwidth_reserve_below" class="witness-chain-properties round wide" value="'+response.props.bandwidth_reserve_below+'"></label>';
							result+='<label class="input-descr">Минимальный процент доли сети голосующих необходимый для принятия решения по заявке в комитете:<input type="text" name="committee_request_approve_min_percent" class="witness-chain-properties round wide" value="'+response.props.committee_request_approve_min_percent/100+'"></label>';
							result+='<label class="input-descr">Дополнительная трата энергии на флаг (процент):<input type="text" name="flag_energy_additional_cost" class="witness-chain-properties round wide" value="'+response.props.flag_energy_additional_cost/100+'"></label>';
							result+='<label class="input-descr">Минимально-допустимый процент кураторской награды:<input type="text" name="min_curation_percent" class="witness-chain-properties round wide" value="'+response.props.min_curation_percent/100+'"></label>';
							result+='<label class="input-descr">Максимально-допустимый процент кураторской награды:<input type="text" name="max_curation_percent" class="witness-chain-properties round wide" value="'+response.props.max_curation_percent/100+'"></label>';
							result+='<label class="input-descr">Минимальное количество токенов при делегировании:<input type="text" name="min_delegation" class="witness-chain-properties round wide" value="'+response.props.min_delegation+'"></label>';
							result+='<label class="input-descr">Минимальный вес голоса для учета при голосовании за контент (rshares):<input type="text" name="vote_accounting_min_rshares" class="witness-chain-properties round wide" value="'+response.props.vote_accounting_min_rshares+'"></label>';
							result+='<label class="input-descr">Максимальный размер блока в сети (байт):<input type="text" name="maximum_block_size" class="witness-chain-properties round wide" value="'+response.props.maximum_block_size+'"></label>';
							result+='<input type="button" class="witness-chain-properties-update-action button" value="Установить параметры сети делегата">';
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
		view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> Загрузка&hellip;</p>');
		result+='<p><label class="input-descr">Логин:<br><input type="text" name="account_login" class="round" value="'+current_user+'"></label></p>';
		result+='<p><label class="input-descr">Приватный ключ владельца (owner):<br><input type="text" name="owner_key" class="round wide"></label></p>';
		result+='<p class="input-descr">Главный пароль (<i class="fas fa-fw fa-random"></i> <a class="generate-general-action unselectable">сгенерировать новый</a>):<br><input type="text" name="general_key" class="generate-general round wide"></p>';
		result+='<p><a class="reset-account-action button">Установить новый доступ</a>';
		view.html(result);
		generate_general_key();
	}
}
function create_account_control(){
	if(0!=$('.control .create-account-control').length){
		let view=$('.create-account-control');
		let result='';
		if(''==current_user){
			result+='<p>Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.</p>';
			view.html(result);
		}
		else{
			result+='<p>Для того чтобы создать аккаунт заполните количество токенов (которые вы передадите новому аккаунту), количество доли (которую делегируете аккаунту) и сгенерируйте главный пароль (приватные ключи будут сформированы автоматически).</p>';
			view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> Загрузка&hellip;</p>');
			gate.api.getChainProperties(function(err,response){
				let props=response;
				gate.api.getAccounts([current_user],function(err,response){
					if(typeof response[0] !== 'undefined'){
						result+='<p>Баланс: <span class="token" data-symbol="VIZ"><span class="amount">'+parseFloat(response[0]['balance'])+'</span> VIZ</span></p>';
						result+='<p>Доля сети: <span class="token" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['vesting_shares'])+'</span> SHARES</span></p>';
						if(''==users[current_user].active_key){
							result+='<p>Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.</p>';
						}
						else{
							result+='<p><label class="input-descr">Логин:<br><input type="text" name="account_login" class="round"></label></p>';
							result+='<p><label class="input-descr">Количество передаваемых VIZ:<br><input type="text" name="token_amount" class="round" placeholder="'+props.account_creation_fee+'" value="'+props.account_creation_fee+'"></label></p>';
							result+='<p><label class="input-descr">Количество SHARES для делегирования:<br><input type="text" name="shares_amount" class="round" placeholder="'+(parseFloat(props.account_creation_fee)*props.create_account_delegation_ratio).toFixed(6)+' SHARES"></label></p>';
							result+='<p class="input-descr">Главный пароль (<i class="fas fa-fw fa-random"></i> <a class="generate-general-action unselectable">сгенерировать новый</a>):<br><input type="text" name="general_key" class="generate-general round wide"></p>';
							result+='<p><a class="create-account-action button"><i class="fas fa-fw fa-plus-circle"></i> Создать аккаунт</a>';
						}
						view.html(result);
						generate_general_key();
					}
				});
			});
		}
	}
}
function invite_control(){
	if(0!=$('.control .invite-control').length){
		let invite_control=$('.invite-control');
		let result='';
		result+='<h3>Создание нового инвайт кода</h3>';
		if(''==current_user){
			result+='<p>Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.</p>';
			invite_control.html(result);
		}
		else{
			invite_control.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> Загрузка&hellip;</p>');
			gate.api.getAccounts([current_user],function(err,response){
				if(typeof response[0] !== 'undefined'){
					result+='<p>Баланс: <span class="token" data-symbol="VIZ"><span class="amount">'+parseFloat(response[0]['balance'])+'</span> VIZ</span></p>';
					if(''==users[current_user].active_key){
						result+='<p>Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.</p>';
					}
					else{
						result+='<p>Для того чтобы создать инвайт код заполните количество токенов которые вы потратите и сгенерируйте пару ключей (приватный для передачи другому пользователю, публичный для проверки кода).</p>';
						result+='<p class="input-descr">Приватный ключ (<i class="fas fa-fw fa-random"></i> <a class="generate-action unselectable">сгенерировать новый</a>):<br><input type="text" name="private" class="generate-private round wide"></p>';
						result+='<p class="input-descr">Публичный ключ (для проверки):<br><input type="text" name="public" class="generate-public round wide"></p>';
						result+='<p><label class="input-descr">Количество VIZ:<br><input type="text" name="amount" class="round"></label></p>';
						result+='<p><a class="invite-action button"><i class="fas fa-fw fa-plus-circle"></i> Создать код</a>';
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
		result+='<h3>Проверка инвайт кода</h3>';
		result+='<p>Введите публичный код для проверки:</p>';
		result+='<p class="input-descr"><input type="text" name="public" class="round wide"></p>';
		result+='<p><a class="invite-lookup-action button"><i class="fas fa-fw fa-search"></i> Поиск и проверка кода</a>';
		result+='<div class="search-result"></div>';
		invite_control.html(result);
	}
	if(0!=$('.control .invite-claim').length){
		let invite_control=$('.invite-claim');
		let result='';
		result+='<p>Введите код и имя аккаунта, куда перевести баланс кода:</p>';
		result+='<p><label class="input-descr">Код:<br><input type="text" name="secret" class="round wide"></label></p>';
		result+='<p><label class="input-descr">Получатель:<br><input type="text" name="receiver" class="round" value="'+current_user+'"></label></p>';
		result+='<p><a class="invite-claim-action button"><i class="fas fa-fw fa-file-invoice-dollar"></i> Активировать код</a>';
		invite_control.html(result);
	}
	if(0!=$('.control .invite-register').length){
		let invite_control=$('.invite-register');
		let result='';
		result+='<p>Введите код, имя аккаунта и приватный ключ для него (сформирован автоматически):</p>';
		result+='<p><label class="input-descr">Код:<br><input type="text" name="secret" class="round wide"></label></p>';
		result+='<p><label class="input-descr">Имя аккаунта:<br><input type="text" name="receiver" class="round wide"></label></p>';
		result+='<p class="input-descr">Приватный ключ (<i class="fas fa-fw fa-random"></i> <a class="generate-action unselectable">сгенерировать новый</a>):<br><input type="text" name="private" class="generate-private round wide"></p>';
		result+='<p><a class="invite-register-action button"><i class="fas fa-fw fa-file-invoice-dollar"></i> Активировать код</a>';
		invite_control.html(result);
		generate_key();
	}
}
function delegation_control(){
	if(0!=$('.control .delegation-control').length){
		let delegation_control=$('.delegation-control');
		let result='';
		if(''==current_user){
			result+='<p>Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.</p>';
			delegation_control.html(result);
		}
		else{
			delegation_control.html('<p><i class="fa fw-fw fa-spinner fa-spin"></i> Загрузка&hellip;</p>');
			gate.api.getAccounts([current_user],function(err,response){
				if(typeof response[0] !== 'undefined'){
					result+='<p>Доля сети: <span class="token" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['vesting_shares'])+'</span> SHARES</span></p>';
					if(parseFloat(response[0]['delegated_vesting_shares'])){
						result+='<p>Делегировано: <span class="delegated_vesting_shares" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['delegated_vesting_shares'])+'</span> SHARES</span></p>';
					}
					if(parseFloat(response[0]['received_vesting_shares'])){
						result+='<p>Получено делегированием: <span class="received_vesting_shares" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['received_vesting_shares'])+'</span> SHARES</span></p>';
					}
					if(parseFloat(response[0]['received_vesting_shares']) || parseFloat(response[0]['delegated_vesting_shares'])){
						result+='<p>Эффективная доля сети: <span class="token" data-symbol="SHARES"><span class="amount">'+(parseFloat(response[0]['vesting_shares'])+parseFloat(response[0]['received_vesting_shares'])-parseFloat(response[0]['delegated_vesting_shares']))+'</span> SHARES</span></p>';
					}
					result+='<h3>Назначить делегирование</h3>';
					if(''==users[current_user].active_key){
						result+='<p>Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.</p>';
					}
					else{
						result+='<p>Для того чтобы отозвать делегирование, укажите в количестве SHARES нулевое значение. Возврат делегированной доли может занять время.</p>';
						result+='<p><label><input type="text" name="recipient" class="round"> &mdash; получатель</label></p>';
						result+='<p><label><input type="text" name="amount" class="round"> &mdash; количество SHARES</label></p>';
						result+='<p><a class="delegation-action button"><i class="far fa-fw fa-credit-card"></i> Делегировать</a>';
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
						result+='<h3>Возврат делегированной доли</h3>';
						for(delegation in response){
							result+='<p>'+response[delegation].expiration+' вернется '+response[delegation].vesting_shares+'</p>';
						}
						delegation_control.html(result);
					}
				}
			});
		}
	}
	if(0!=$('.control .delegation-received-shares').length){
		let delegation_control=$('.delegation-received-shares');
		let result='';
		if(''!=current_user){
			gate.api.getVestingDelegations(current_user,0,1000,0,function(err,response){
				if(!err){
					result+='<h3>Список делегированной доли</h3>';
					if(0==response.length){
						result+='<p>Вы никому не делегировали долю.</p>';
					}
					for(delegation in response){
						result+='<p><a href="/@'+response[delegation].delegatee+'/">'+response[delegation].delegatee+'</a> держит '+response[delegation].vesting_shares+', отозвать можно '+response[delegation].min_delegation_time+'</p>';
					}
					delegation_control.html(result);
				}
			});
		}
	}
	if(0!=$('.control .delegation-delegated-shares').length){
		let delegation_control=$('.delegation-delegated-shares');
		let result='';
		if(''!=current_user){
			gate.api.getVestingDelegations(current_user,0,1000,1,function(err,response){
				if(!err){
					result+='<h3>Держание доли</h3>';
					if(0==response.length){
						result+='<p>Никто не делегировал вам долю.</p>';
					}
					for(delegation in response){
						result+='<p>'+response[delegation].vesting_shares+' от <a href="/@'+response[delegation].delegatee+'/">'+response[delegation].delegator+'</a>, отзыв возможен с '+response[delegation].min_delegation_time+'</p>';
					}
					delegation_control.html(result);
				}
			});
		}
	}
}
function update_wallet_history(){
	if(0<$('.wallet-history').length){
		$('.wallet-history tbody').html('<tr><td colspan="6"><center><i class="fa fa-fw fa-spin fa-spinner" aria-hidden="true"></i> Загрузка&hellip;</center></td></tr>');
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
						$('.wallet-history tbody').html('<tr><td colspan="6"><center>Записи отсутствуют</center></td></tr>');
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
			result+='<p>Вам необходимо <a href="/login/">авторизоваться</a>.</p>';
			control.html(result);
		}
		else{
			gate.api.getAccounts([current_user],function(err,response){
				if(typeof response[0] !== 'undefined'){
					result+='<p>Вы можете изменить публичный профиль в блокчейне заполнив форму ниже.</p><p><b>Внимание!</b> После внесенных и сохраненных изменений никто не сможет удалить эти данные из интернета.</p>';
					result+='<p>Активный аккаунт: <a href="/@'+current_user+'/">'+current_user+'</a></p>';
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
					result+='<p>Псевдоним (nickname):<br><input type="text" class="profile-input round wide" name="nickname" data-category="profile" value="'+(typeof metadata.profile.nickname !== 'undefined'?metadata.profile.nickname:'')+'"></p>';
					result+='<p>Про аккаунт (about):<br><input type="text" class="profile-input round wide" name="about" data-category="profile" value="'+(typeof metadata.profile.about !== 'undefined'?metadata.profile.about:'')+'"></p>';
					result+='<p>Аватар (ссылка, avatar):<br><input type="text" class="profile-input round wide" name="avatar" data-category="profile" value="'+(typeof metadata.profile.avatar !== 'undefined'?metadata.profile.avatar:'')+'"></p>';
					result+='<p>Пол/тип аккаунта (gender):<br><select class="profile-select round" name="gender" data-category="profile">'
					+'<option value=""'+(typeof metadata.profile.gender !== 'undefined'?((''==metadata.profile.gender)?' selected':''):'')+'>Не указан</option>'
					+'<option value="male"'+(typeof metadata.profile.gender !== 'undefined'?(('male'==metadata.profile.gender)?' selected':''):'')+'>Мужской</option>'
					+'<option value="female"'+(typeof metadata.profile.gender !== 'undefined'?(('female'==metadata.profile.gender)?' selected':''):'')+'>Женский</option>'
					+'</select></p>';
					result+='<p><input type="button" class="profile-action button" data-value="true" value="Сохранить профиль"></p>';
					control.html(result);
				}
				else{
					add_notify('Ошибка в получении пользователя '+current_user,true);
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
							wallet_control.find('.withdraw-shares-status').html('<a class="enable-withdraw-shares-action">Включить понижение</a>');
						}
						else{
							let powerdown_time=Date.parse(response[0].next_vesting_withdrawal);
							let powerdown_icon='';
							if(powerdown_time>0){
								powerdown_icon='<i class="fas fa-fw fa-level-down-alt" title="'+date_str(powerdown_time-(new Date().getTimezoneOffset()*60000),true,false,true)+': '+response[0].vesting_withdraw_rate+'"></i> ';
							}
							wallet_control.find('.withdraw-shares-status').html(powerdown_icon+'<a class="disable-withdraw-shares-action">Отключить понижение</a>');
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
			result+='<p>Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.</p>';
			wallet_control.html(result);
		}
		else{
			wallet_control.html('<p><i class="fa fw-fw fa-spinner fa-spin"></i> Загрузка&hellip;</p>');
			gate.api.getDynamicGlobalProperties(function(err,dgp){
				gate.api.getAccounts([current_user],function(err,response){
					if(typeof response[0] !== 'undefined'){
						result+='<p>Баланс: <span class="token" data-symbol="VIZ"><span class="amount">'+parseFloat(response[0]['balance'])+'</span> VIZ</span></p>';
						if('0.000000 SHARES'==response[0].vesting_withdraw_rate){
							result+='<div class="right withdraw-shares-status"><a class="enable-withdraw-shares-action">Включить понижение</a></div>';
						}
						else{
							result+='<div class="right withdraw-shares-status">';
							let powerdown_time=Date.parse(response[0].next_vesting_withdrawal);
							if(powerdown_time>0){
								result+='<i class="fas fa-fw fa-level-down-alt" title="'+date_str(powerdown_time-(new Date().getTimezoneOffset()*60000),true,false,true)+': '+response[0].vesting_withdraw_rate+'"></i> ';
							}
							result+='<a class="disable-withdraw-shares-action">Отключить понижение</a></div>';
						}
						let network_share=100*(parseFloat(response[0]['vesting_shares'])/parseFloat(dgp.total_vesting_shares));
						result+='<p>Доля сети: <span class="token" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['vesting_shares'])+'</span> SHARES</span> (<span class="network_share">'+network_share.toFixed(5)+'</span>%)</p>';
						if(parseFloat(response[0]['delegated_vesting_shares'])){
							result+='<p>Делегировано: <span class="delegated_vesting_shares" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['delegated_vesting_shares'])+'</span> SHARES</span></p>';
						}
						if(parseFloat(response[0]['received_vesting_shares'])){
							result+='<p>Получено делегированием: <span class="received_vesting_shares" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['received_vesting_shares'])+'</span> SHARES</span></p>';
						}
						if(parseFloat(response[0]['received_vesting_shares']) || parseFloat(response[0]['delegated_vesting_shares'])){
							network_share=100*((parseFloat(response[0]['vesting_shares'])+parseFloat(response[0]['received_vesting_shares'])-parseFloat(response[0]['delegated_vesting_shares']))/parseFloat(dgp.total_vesting_shares));
							result+='<p>Эффективная доля сети: <span class="effective_token" data-symbol="SHARES"><span class="amount">'+(parseFloat(response[0]['vesting_shares'])+parseFloat(response[0]['received_vesting_shares'])-parseFloat(response[0]['delegated_vesting_shares']))+'</span> SHARES</span> (<span class="effective_network_share">'+network_share.toFixed(5)+'</span>%)</p>';
						}
						result+='<h3>Выполнить перевод</h3>';
						if(''==users[current_user].active_key){
							result+='<p>Вам необходимо <a href="/login/">авторизоваться</a> с Active ключом.</p>';
						}
						else{
							result+='<p><label><input type="text" name="recipient" class="round"> &mdash; получатель</label></p>';
							result+='<p><label><input type="text" name="amount" class="round"> &mdash; количество VIZ</label></p>';
							result+='<p><label><input type="text" name="memo" class="round"> &mdash; заметка</label></p>';
							result+='<p><label><input type="checkbox" name="shares"> — перевод в долю сети</label></p>';
							result+='<p><a class="wallet-transfer-action button"><i class="far fa-fw fa-credit-card"></i> Отправить перевод</a>';
						}
						result+='<hr><h2>История переводов</h2>';
						result+='<input class="bubble small-size right" type="text" name="wallet-history-filter-amount2" placeholder="До&hellip;" tabindex="3">';
						result+='<input class="bubble small-size right" type="text" name="wallet-history-filter-amount1" placeholder="От&hellip;" tabindex="2">';
						result+='<input class="bubble small-size right" type="text" name="wallet-history-filter" placeholder="Фильтр" tabindex="1">';
						result+='<div class="action-button wallet-history-filter-all"><i class="fa fa-fw fa-globe" aria-hidden="true"></i> Все</div>';
						result+='<div class="action-button wallet-history-filter-in"><i class="fa fa-fw fa-arrow-circle-down" aria-hidden="true"></i> Входящие</div>';
						result+='<div class="action-button wallet-history-filter-out"><i class="fa fa-fw fa-arrow-circle-up" aria-hidden="true"></i> Исходящие</div>';
						result+='<div class="wallet-history"><table><thead><tr><th>Дата</th><th>Отправитель</th><th>Получатель</th><th>Количество</th><th>Токен</th><th>Заметка</th></tr></thead><tbody></tbody></table></div>';
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
			result+='<h3>Голосование за заявку #'+request_id+'</h3>';
			result+='<p>Процент от максимальной суммы заявки: <input type="text" name="vote_percent" value="0" size="4" class="round" data-fixed="vote_percent_range"> <input type="range" name="vote_percent_range" min="-100" max="+100" value="0" data-fixed="vote_percent"><br>';
			result+='<input type="button" class="committee-vote-request-action button" value="Проголосовать"></p>';
			if(current_user==creator){
				if(status==0){
					result+='<h3>Управление заявкой</h3>';
					result+='<p><input type="button" class="committee-cancel-request-action button" value="Отменить заявку"></p>';
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
			result+='<p>Вам необходимо <a href="/login/">авторизоваться</a>.</p>';
			view.html(result);
		}
		else{
			view.html(result+'<p><i class="fa fw-fw fa-spinner fa-spin"></i> Загрузка&hellip;</p>');
			result+='<p><label>URL заявки:<input type="text" name="url" class="round wide"></label></p>';
			result+='<p><label>Аккаунт-воркер: <input type="text" name="worker" class="round" value="'+current_user+'"></label></p>';
			result+='<p><label>Минимальная сумма токенов: <input type="text" name="min_amount" class="round" value="0.000 VIZ"></label></p>';
			result+='<p><label>Максимальная сумма токенов: <input type="text" name="max_amount" class="round" value="0.000 VIZ"></label></p>';
			result+='<p><label>Длительность заявки в днях (от 5 до 30): <input type="text" name="duration" class="round" value="5"></label></p>';
			result+='<p><a class="committee-worker-create-request-action button">Создать заявку</a>';
			view.html(result);
		}
	}
}
function session_control(){
	if(0!=$('.control .session-control').length){
		let session_html='';
		for(key in users){
			session_html+='<p class="clearfix">'+(1==users[key]['session_verify']?'<span class="right" title="Сессия подтверждена"><i class="fas fa-fw fa-check"></i></span>':'')+(users[key]['active_key']!=''?'<span class="right" title="Сохранен Active ключ"><i class="fas fa-fw fa-key"></i></span>':'')+'<a href="/@'+key+'/">'+key+'</a>, '+(current_user==key?'<b>используется</b>':'<a href="#" class="auth-change" data-login="'+key+'">переключиться</a>')+', <a href="#" class="auth-logout" data-login="'+key+'">отключить</a></p>';

		}
		$('.control .session-control').html(session_html);
	}
}
function logout(login='',redirect=true){
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
function try_auth(login,posting_key,active_key){
	$('.auth-action').addClass('disabled');
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
							$('.auth-error').html('Posting ключ не валидный');
							$('.auth-action').removeClass('disabled');
							return;
						}
					}
				}
				if(!posting_valid){
					$('.auth-error').html('Posting ключ не подходит');
					$('.auth-action').removeClass('disabled');
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
								$('.auth-error').html('Active ключ не валидный');
								$('.auth-action').removeClass('disabled');
								return;
							}
						}
					}
					if(!active_valid){
						$('.auth-error').html('Active ключ не подходит');
						$('.auth-action').removeClass('disabled');
						return;
					}
				}
				users[login]={'posting_key':posting_key,'active_key':active_key};
				current_user=login;
				session_generate();
			}
			else{
				$('.auth-error').html('Пользователь не найден');
				$('.auth-action').removeClass('disabled');
				return;
			}
		});
	}
	else{
		$('.auth-error').html('Пользователь не указан');
		$('.auth-action').removeClass('disabled');
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
		let curation_percent=0;
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
			gate.broadcast.content(users[current_user].posting_key,parent_author,parent_permlink,current_user,permlink,title,subcontent,curation_percent,json,[],function(err,result){
				if(!err){
					$(target).parent().remove();
					set_update_comments_list();
					add_notify('Комментарий отправлен',5000);
				}
				else{
					console.log(err);
					window.setTimeout(function(){set_update_comments_list(false)},100);
					add_notify('Ошибка при отправке комментария',true);
					target.removeClass('disabled');
				}
			});
		}
	}
}
function post_content(target){
	if(''!=current_user){
		target.addClass('disabled');
		var title=$('input[name=title]').val();
		var permlink=$('input[name=permlink]').val();
		if(''==$('input[name=permlink]').val()){
			$('input[name=permlink]').val(title);
			permlink=$('input[name=permlink]').val();
		}
		var content=$('textarea[name=content]').val();
		var tags=$('input[name=tags]').val();
		if(wysiwyg_active){
			content=tinyMCE.activeEditor.getContent();
		}
		content=content.replace(' rel="noopener"','');
		var foreword=$('input[name=foreword]').val().trim();
		var curation_percent=parseInt($('input[name=curation_percent]').val())*100;
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
		var json_object={'tags':tags_arr,'cover':cover,'foreword':foreword};
		var json=JSON.stringify(json_object);
		var parent_permlink='';
		if(0<$('input[name=parent_permlink]').length){
			parent_permlink=$('input[name=parent_permlink]').val();
		}
		$('input[name=permlink]').attr('disabled','disabled');
		$(target).html('Отправка&hellip;');
		$.ajax({
			type:'POST',
			url:'/ajax/check_content/',
			data:{'author':current_user,'permlink':permlink},
			success:function(data_json){
				data_obj=JSON.parse(data_json);
				if('ok'==data_obj.status){//content already exist
					if(confirm('Контент с таким URL уже существует, вы хотите заменить его?')){
						gate.api.getContent(current_user,permlink,0,function(err, result){
							if(!err){
								let old_beneficiaries=result.beneficiaries;
								let old_extensions=[];
								/*
								//not necessary
								if(0<old_beneficiaries.length){
									let old_extensions=[[0,{'beneficiaries':old_beneficiaries}]];
								}
								*/
								let old_json=JSON.parse(result.json_metadata);
								for(key in json_object){
									old_json[key]=json_object[key];
								}
								let new_json=JSON.stringify(old_json);
								gate.broadcast.content(users[current_user].posting_key,result.parent_author,result.parent_permlink,current_user,permlink,title,content,result.curation_percent,new_json,old_extensions,function(err,result){
									if(!err){
										add_notify('Публикация успешно изменена, переадресация через 6 секунд&hellip;');
										setTimeout(function(){wait_content(current_user,permlink)},6000);
									}
									else{
										console.log(err);
										add_notify('Ошибка при  получении публикации',true);
										$('input[name=permlink]').removeAttr('disabled');
										target.removeClass('disabled');
										target.html('Опубликовать');
									}
								});
							}
							else{
								console.log(err);
								add_notify('Не получается запросить публикацию с публичной ноды',true);
								target.removeClass('disabled');
								target.html('Сохранить изменения');
							}
						});
					}
				}
				else{
					gate.broadcast.content(users[current_user].posting_key,'',parent_permlink,current_user,permlink,title,content,curation_percent,json,[],function(err,result){
						if(!err){
							add_notify('Публикация прошла успешно, переадресация&hellip;');
							setTimeout(function(){wait_content(current_user,permlink)},3500);
						}
						else{
							console.log(err);
							add_notify('Ошибка при публикации',true);
							$('input[name=permlink]').removeAttr('disabled');
							target.removeClass('disabled');
							target.html('Опубликовать');
						}
					});
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
				gate.broadcast.accountMetadata(users[current_user].posting_key,current_user,JSON.stringify(metadata),function(err, result){
					if(!err){
						add_notify('Профиль аккаунта '+current_user+' изменен');
						target.removeClass('disabled');
					}
					else{
						console.log(err);
						add_notify('Ошибка в сохранение метаданных '+current_user+'');
						target.removeClass('disabled');
					}
				});
			}
			else{
				add_notify('Ошибка в получении пользователя '+current_user,true);
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
			{title: "Центрирование", block: "center"},
			{title: 'Спойлер', inline : 'span', classes : 'spoiler'},
			{title: "Заголовок 1", block: "h1"},
			{title: "Заголовок 2", block: "h2"},
			{title: "Заголовок 3", block: "h3"},
			{title: "Заголовок 4", block: "h4"},
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
	if($(target).closest('.go-top-left-wrapper').length>0){
		scroll_top_action();
	}
	if($(target).hasClass('post-content-action')){
		e.preventDefault();
		if(!$(target).hasClass('disabled')){
			post_content($(target));
		}
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
				unvote_content(proper_target.attr('data-content-author'),proper_target.attr('data-content-permlink'),proper_target);
			}
			else{
				upvote_content(proper_target.attr('data-content-author'),proper_target.attr('data-content-permlink'),proper_target);
			}
		}
	}
	if($(target).hasClass('award-subcontent-action')){
		e.preventDefault();
		proper_target=$(target).closest('.comment');
		if(typeof proper_target.attr('data-author') !== 'undefined'){
			if($(target).hasClass('active')){
				unvote_subcontent(proper_target.attr('data-author'),proper_target.attr('data-permlink'),proper_target);
			}
			else{
				upvote_subcontent(proper_target.attr('data-author'),proper_target.attr('data-permlink'),proper_target);
			}
		}
	}
	if($(target).hasClass('flag-action')){
		e.preventDefault();
		proper_target=$(target).closest('.page');
		if(typeof proper_target.attr('data-content-author') !== 'undefined'){
			if($(target).hasClass('active')){
				unvote_content(proper_target.attr('data-content-author'),proper_target.attr('data-content-permlink'),proper_target);
			}
			else{
				flag_content(proper_target.attr('data-content-author'),proper_target.attr('data-content-permlink'),proper_target);
			}
		}
	}
	if($(target).hasClass('auth-action')){
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
	if($(target).hasClass('invite-register-action') || $(target).parent().hasClass('invite-register-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let secret_key=$('.invite-register input[name=secret]').val();
			let receiver=$('.invite-register input[name=receiver]').val();
			let private_key=$('.invite-register input[name=private]').val();
			invite_register(secret_key,receiver,private_key);
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
	if($(target).hasClass('invite-lookup-action') || $(target).parent().hasClass('invite-lookup-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let public_key=$('.invite-lookup input[name=public]').val();
			gate.api.getInviteByKey(public_key,function(err, response){
				if(!err){
					let result='';
					result+='<p>Создатель: <a href="/@'+response.creator+'/">'+response.creator+'</a></p>';
					result+='<p>Дата создания: '+response.create_time+'</p>';
					result+='<p>Баланс кода: '+response.balance+'</p>';
					if(0==response.status){
						result+='<p>Статус: ожидает активации</p>';
					}
					if(1==response.status){
						result+='<p>Статус: активирован '+response.claim_time+', баланс переведен пользователю '+response.receiver+'</p>';
						result+='<p>Использованный баланс: '+response.claimed_balance+'</p>';
						result+='<p>Проверочный приватный ключ: '+response.invite_secret+'</p>';
					}
					if(2==response.status){
						result+='<p>Статус: активирован '+response.claim_time+', зарегистрирован пользователь '+response.receiver+'</p>';
						result+='<p>Использованный баланс: '+response.claimed_balance+'</p>';
						result+='<p>Проверочный приватный ключ: '+response.invite_secret+'</p>';
					}
					$('.invite-lookup .search-result').html(result);
				}
				else{
					add_notify('Ошибка',true);
				}
			});
		}
	}
	if($(target).hasClass('reset-account-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let general_key=$('.reset-account-control input[name=general_key]').val();
			let account_login=$('.reset-account-control input[name=account_login]').val();
			let owner_key=$('.reset-account-control input[name=owner_key]').val();
			reset_account_with_general_key(account_login,owner_key,general_key);
		}
	}
	if($(target).hasClass('create-account-action') || $(target).parent().hasClass('create-account-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			let general_key=$('.create-account-control input[name=general_key]').val();
			let account_login=$('.create-account-control input[name=account_login]').val();
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
			wallet_transfer($('.wallet-control input[name=recipient]').val(),$('.wallet-control input[name=amount]').val(),$('.wallet-control input[name=memo]').val());
		}
	}
	if($(target).hasClass('auth-change')){
		e.preventDefault();
		let login=$(target).attr('data-login');
		if(typeof users[login] !== 'undefined'){
			current_user=login;
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
				var comment_form='<div class="reply-form" data-reply-content="'+content_id+'" data-reply-subcontent="'+subcontent_id+'"><textarea name="reply-text" class="round" placeholder="Введите ваш ответ..."></textarea><input type="button" class="button reply-execute" value="Ответить"></div>'
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
});