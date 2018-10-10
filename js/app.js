var gate=viz;
var dgp={};
var current_block=0;
var current_user='';
var users={};
var notify_id=0;
var empty_signing_key='VIZ1111111111111111111111111111111114T1Anm';
var api_gate='wss://testnet.viz.world';
gate.config.set('websocket',api_gate);
gate.api.stop();

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

function save_session(){
	let users_json=JSON.stringify(users);
	localStorage.setItem('users',users_json);
	localStorage.setItem('current_user',current_user);
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
	}
	invite_control();
}
function view_session(){
	if(''!=current_user){
		$('.header .account').html('<a href="/@'+current_user+'/">'+current_user+'</a> <a class="auth-logout icon"><i class="fas fa-fw fa-sign-out-alt"></i></a>');
	}
	else{
		$('.header .account').html('<a href="/login/" class="icon" title="Авторизация"><i class="fas fa-fw fa-sign-in-alt"></i></a>');
	}
	view_energy();
}
function view_energy(){
	$('.header .energy').html('&hellip;');
	if(''!=current_user){
		$('.header .energy').css('display','inline-block');
		gate.api.getAccounts([current_user],function(err,response){
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
				$('.header .energy').html((new_energy/100)+'% '+energy_icon);
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
				wallet_control();
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
						wallet_control();
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
							wallet_control();
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
							wallet_control();
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
function committee_cancel_request(request_id){
	gate.broadcast.committeeWorkerCancelRequest(users[current_user]['posting_key'],current_user,request_id,function(err,result) {
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
				props.create_account_delegation_ratio=$('.witness-control[data-witness='+witness_login+'] input[name=create_account_delegation_ratio]').val();
				props.create_account_delegation_time=$('.witness-control[data-witness='+witness_login+'] input[name=create_account_delegation_time]').val();
				props.bandwidth_reserve_percent=100*parseInt($('.witness-control[data-witness='+witness_login+'] input[name=bandwidth_reserve_percent]').val());
				props.bandwidth_reserve_below=$('.witness-control[data-witness='+witness_login+'] input[name=bandwidth_reserve_below]').val();
				props.committee_request_approve_min_percent=100*parseInt($('.witness-control[data-witness='+witness_login+'] input[name=committee_request_approve_min_percent]').val());
				props.flag_energy_additional_cost=100*parseInt($('.witness-control[data-witness='+witness_login+'] input[name=flag_energy_additional_cost]').val());
				props.min_curation_percent=100*parseInt($('.witness-control[data-witness='+witness_login+'] input[name=min_curation_percent]').val());
				props.max_curation_percent=100*parseInt($('.witness-control[data-witness='+witness_login+'] input[name=max_curation_percent]').val());
				props.min_delegation=$('.witness-control[data-witness='+witness_login+'] input[name=min_delegation]').val();
				props.vote_accounting_min_rshares=$('.witness-control[data-witness='+witness_login+'] input[name=vote_accounting_min_rshares]').val();
				props.maximum_block_size=$('.witness-control[data-witness='+witness_login+'] input[name=maximum_block_size]').val();
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
	if(0!=$('.control .witness-votes').length){
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
function pass_gen(){
	let length=100;
	let charset='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+-=_:;.,@!^&*$';
	let ret='';
	for (var i=0,n=charset.length;i<length;++i){
		ret+=charset.charAt(Math.floor(Math.random()*n));
	}
	let wif=gate.auth.toWif('',ret,'')
	return wif;
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
function wallet_control(){
	if(0!=$('.control .wallet-control').length){
		let wallet_control=$('.wallet-control');
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
							result+='<div class="right"><a class="enable-withdraw-shares-action">Включить понижение</a></div>';
						}
						else{
							result+='<div class="right">';
							let powerdown_time=Date.parse(response[0].next_vesting_withdrawal);
							if(powerdown_time>0){
								result+='<i class="fas fa-fw fa-level-down-alt" title="'+date_str(powerdown_time-(new Date().getTimezoneOffset()*60000),true,false,true)+': '+response[0].vesting_withdraw_rate+'"></i> ';
							}
							result+='<a class="disable-withdraw-shares-action">Отключить понижение</a></div>';
						}
						let network_share=100*(parseFloat(response[0]['vesting_shares'])/parseFloat(dgp.total_vesting_shares));
						result+='<p>Доля сети: <span class="token" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['vesting_shares'])+'</span> SHARES</span> ('+network_share.toFixed(5)+'%)</p>';
						if(parseFloat(response[0]['delegated_vesting_shares'])){
							result+='<p>Делегировано: <span class="delegated_vesting_shares" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['delegated_vesting_shares'])+'</span> SHARES</span></p>';
						}
						if(parseFloat(response[0]['received_vesting_shares'])){
							result+='<p>Получено делегированием: <span class="received_vesting_shares" data-symbol="SHARES"><span class="amount">'+parseFloat(response[0]['received_vesting_shares'])+'</span> SHARES</span></p>';
						}
						if(parseFloat(response[0]['received_vesting_shares']) || parseFloat(response[0]['delegated_vesting_shares'])){
							network_share=100*((parseFloat(response[0]['vesting_shares'])+parseFloat(response[0]['received_vesting_shares'])-parseFloat(response[0]['delegated_vesting_shares']))/parseFloat(dgp.total_vesting_shares));
							result+='<p>Эффективная доля сети: <span class="token" data-symbol="SHARES"><span class="amount">'+(parseFloat(response[0]['vesting_shares'])+parseFloat(response[0]['received_vesting_shares'])-parseFloat(response[0]['delegated_vesting_shares']))+'</span> SHARES</span> ('+network_share.toFixed(5)+'%)</p>';
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
						wallet_control.html(result);
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
			result+='<p><input type="range" name="vote_percent_range" min="-100" max="+100" value="0">';
			result+=' <input type="text" name="vote_percent" value="0" size="4" class="round"> процентов от максимальной суммы заявки<br>';
			result+='<input type="button" class="committee-vote-request-action button" value="Проголосовать"></p>';
			committee_control.find('input[name=vote_percent_range]').bind('change',function(){
				committee_control.find('input[name=vote_percent]').val($(this).val());
			});
			committee_control.find('input[name=vote_percent]').bind('change',function(){
				let percent=parseInt($(this).val());
				if(percent>100){
					percent=100;
				}
				if(percent<-100){
					percent=-100;
				}
				$(this).val(percent);
				committee_control.find('input[name=vote_percent_range]').val(percent);
			});
			if(current_user==creator){
				if(status==0){
					result+='<h3>Управление заявкой</h3>';
					result+='<p><input type="button" class="committee-cancel-request-action button" value="Отменить заявку"></p>';
				}
			}
			committee_control.html(result);
		});
	}
}
function session_control(){
	if(0!=$('.control .session-control').length){
		let session_html='';
		for(key in users){
			session_html+='<p class="clearfix">'+(users[key]['active_key']!=''?'<span class="right" title="Сохранен Active ключ"><i class="fas fa-fw fa-key"></i></span>':'')+'<a href="/@'+key+'/">'+key+'</a>, '+(current_user==key?'<b>используется</b>':'<a class="auth-change" data-login="'+key+'">переключиться</a>')+', <a class="auth-logout" data-login="'+key+'">отключить</a></p>';
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
							return;
						}
					}
				}
				if(!posting_valid){
					$('.auth-error').html('Posting ключ не подходит');
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
								return;
							}
						}
					}
					if(!active_valid){
						$('.auth-error').html('Active ключ не подходит');
						return;
					}
				}
				users[login]={'posting_key':posting_key,'active_key':active_key};
				current_user=login;
				save_session();
				$('.auth-error').html('Вы успешно авторизованы!');
				document.location='/';
			}
			else{
				$('.auth-error').html('Пользователь не найден');
			}
		});
	}
	else{
		$('.auth-error').html('Пользователь не указан');
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

function app_mouse(e){
	if(!e)e=window.event;
	var target=e.target || e.srcElement;
	if($(target).hasClass('auth-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			try_auth($('input[name=login]').val(),$('input[name=posting_key]').val(),$('input[name=active_key]').val());
		}
	}
	if($(target).hasClass('generate-action')){
		e.preventDefault();
		if($(target).closest('.control').length){
			generate_key(true);
		}
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
		if($(target).parent().hasClass('reply-action')){
			proper_target=$(target).parent();
		}
		let login=proper_target.attr('data-login');
		logout(login,login?false:true);
	}
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
	load_session();
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