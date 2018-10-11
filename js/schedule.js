$(document).ready(function(){
	var WebSocketWrapper;
	WebSocketWrapper=function(){
		var ws=WebSocketWrapper.prototype;
		function WebSocketWrapper(server){
			this.server=server;
			this.connection={};
			this.callbacks=[];
		}
		ws.connect=function(){
			var _this=this;
			return new Promise(function(resolve,reject){
				if('WebSocket' in window){
					_this.connection=new WebSocket(_this.server);
					_this.connection.onopen=function(){
						resolve(_this.connection);
					};
					_this.connection.onerror=function(error){
						reject(Error('Error connecting to server, please reload the page!' + error));
					};
					_this.connection.onmessage=function(data){
						var sdata=JSON.parse(data['data']);
						_this.callbacks[sdata['id']](sdata['result']);
					};
				} else{
					reject(Error('Your browser is too old, please get a recent one!'));
				}
			});
		};
		ws.send=function(data,callback){
			this.callbacks[data['id']]=callback;
			var json=JSON.stringify(data);
			this.connection.send(json);
		};
		return WebSocketWrapper;
	}();

	var api_wrapper;
	api_wrapper=function(){
		var gate=api_wrapper.prototype;
		function api_wrapper(ws){
			this.ws=ws;
			this.id=0;
		}
		gate.send=function(method,params,callback){
			++this.id;
			var data={
				"id": this.id,
				"jsonrpc": "2.0",
				"method": method,
				"params": params
			};
			this.ws.send(data,callback);
		};
		return api_wrapper;
	}();

	var next_shuffle_block_num=0;
	var current_virtual_time=0;
	var last_block_number=0;
	var last_virtual_time=0;
	var witness_rank=[];

	function main(){
		var server=api_gate;
		var ws=new WebSocketWrapper(server);
		ws.connect().then(function(response){
			var gate=new api_wrapper(ws);

			var wsocketblock=function(){
				gate.send("call",["database_api","get_dynamic_global_properties",[]],function(globprops){
					var block_number=globprops["head_block_number"];
					var witness=globprops["current_witness"];
					var time=moment().format('LTS');
					if(last_block_number != block_number){
						last_block_number=block_number;
						$(".witness:not(.highlighted)").css("color","").css("font-weight","");
						$("._witnessed").css("opacity", "0.3");
						$("[id='witness_" + witness + "']").find(".blocknum").html(block_number);
						$("[id='witness_" + witness + "']").addClass("_witnessed").css("color","green").css("font-weight","bold");
						if(next_shuffle_block_num != 0){
							var diff=next_shuffle_block_num - block_number;
							if(diff == 0) $('#shuffle').html('&middot;');
							else $('#shuffle').html('<em><small>-' + diff + '</small></em>');
						}
					}
					document.title=block_number + ' ' + witness + ' [' + time + ']';
					setTimeout(wsocketblock,1500);
				});
			};

			var wsschedule=function(){
				gate.send("call",["witness_api","get_witness_schedule",[]],function(schedule){
					current_virtual_time=schedule["current_virtual_time"];
					next_shuffle_block_num=schedule["next_shuffle_block_num"];
					var refreshed=moment().format('LTS');
					if(last_virtual_time != current_virtual_time){
						var delay=0;
						if(last_virtual_time != 0) delay=1500;
						last_virtual_time=current_virtual_time;
						setTimeout(function(){
							var code='<div style="text-align:center;">--- <em><small>очередь обновилась @ ' + refreshed + '</small></em> ---</div>';
							code += '<div style="line-height:0.7em;"><br></div>';
							code += '<div id="list">' + list + '</div><br>';
							code += '<div style="position:relative;">' + next_shuffle_block_num + '<div style="position:absolute; top:0; width:100%; padding-left:5em;" id="shuffle"></div></div>';
							code += '<div><em><small>(next_shuffle_block_num)</small></em></div><div style="line-height:0.5em;"><br></div>';
							code += '<div>' + current_virtual_time + '</div>';
							code += '<div><em><small>(current_virtual_time)</small></em></div>';
							$(".witness_schedule").html(code);
						},
						delay);
					}
					setTimeout(wsactive,1000);
				});
			};

			var list="";
			// a separate call to get the list of current shuffled witnesses, can remove after get_witness_schedule is fixed
			var wsactive=function(){
				gate.send("call", ["witness_api", "get_active_witnesses", []], function(witnesses){
					list="";
					for(var i=0; i < witnesses.length; i++){
						if(witnesses[i] == "") continue; // current bug in response
						var style='';
						for(key in users){
							if(key == witnesses[i]){
								style+=' style="color:orange;" class="highlighted"';
							}
						}
						list += '<div id="witness_' + witnesses[i] + '" class="witness" style="position:relative;">';
						list += '<span class="rank" style="position:absolute; left:0; font-style:italic; font-size:small;">.</span>';
						list += '<span' + style + '>' + witnesses[i] + '</span>';
						list += '<span class="blocknum" style="position:absolute; right:0;"></span></div>';
					}
					wsschedule();
				});

			};

			var wswitnesses=function(){
				witness_rank=[];
				gate.send("call", ["witness_api", "get_witnesses_by_vote", ["", "100"]], function(witness){
					for(var i=0; i < witness.length; i++){
						var owner=witness[i]['owner'];
						var virtual_scheduled_time=witness[i]['virtual_scheduled_time'];
						var signing_key=witness[i]['signing_key'];
						witness_rank.push({
							'rank': (i + 1),
							'owner': owner,
							'virtual_scheduled_time': virtual_scheduled_time,
							'signing_key': signing_key
						});
					}
					updateWitnessesList();
					setTimeout(wswitnesses, 1000);
				});
			};

			wsactive();
			wsocketblock();
			wswitnesses();
		});
	}

	function updateWitnessesList(){
		var refreshed=moment().format('LTS');
		var code='<div style="position:relative;"><span style="position:absolute; left:0;">--- <em><small>очередь обновилась @ ' + refreshed + '</small></em> ---</span><span>&nbsp;</span>';
		code += '<span style="position:absolute; right:0;"><em>(virtual_scheduled_time diff)</em></span></div><div style="line-height:0.7em;"><br></div>';
		witness_rank.sort(function(a, b){
			if(a.virtual_scheduled_time < b.virtual_scheduled_time) return -1;
			if(a.virtual_scheduled_time > b.virtual_scheduled_time) return 1;
			return 0;
		});

		var index=0;

		for(var i=0; i < witness_rank.length; i++){
			var rank=witness_rank[i]['rank'];
			var owner=witness_rank[i]['owner'];
			var virtual_scheduled_time=witness_rank[i]['virtual_scheduled_time'];
			var signing_key=witness_rank[i]['signing_key'];

			var style="";

			if(rank <= 11){
				style="display:none;";
				$("[id='witness_" + owner + "']").find(".rank").html(((rank < 10) ? "&nbsp; " : "") + "(" + rank + ")");

			}
			else{
				$("[id='witness_" + owner + "']").find(".rank").html('(' + rank + ') <span style="font-style:normal;">support</span>');
				if(signing_key !== empty_signing_key)
					index++;
			}

			for(key in users){
				if(key == owner){
					style+='color:orange;';
				}
			}

			if(signing_key !== empty_signing_key){
				code += '<div id="witness_rank_' + owner + '" class="witness_rank" style="position:relative;' + style + '">';
				code += '<span class="rank0" style="position:absolute; left:0;"><em><small>' + ((rank < 100) ? "&nbsp; " : "") + ((rank < 10) ? "&nbsp; " : "") + '(' + rank + ') </small></em> &nbsp; ';
				code += ((index <= 10) ? ' <span style="font-style:normal;">(next)</span> ' : '') + '<span class="owner">' + owner + '</span></span><span>&nbsp;</span>';
				var x=new BigNumber(virtual_scheduled_time);
				var y=new BigNumber(current_virtual_time);
				var diff=x.sub(y).toFixed();
				code += '<span class="scheduled" style="position:absolute; right:0;">' + diff;
				if(rank <= 11){
					code += ' &nbsp; <em><small>[' + ((index >= 10) ? '&nbsp; ' : '') + '&nbsp; ]</small></em>' + ((index < 10) ? ' &nbsp;' : '') + ((index < 100) ? ' &nbsp;' : '');
				}
				else{
					code += ' &nbsp; <em><small>[' + index + ']</small></em>' + ((index < 10) ? ' &nbsp;' : '') + ((index < 100) ? ' &nbsp;' : '');
				}
				code += '</span></div>';
			}
			$(".witness_support_queue").html(code);
		}
	}
	$(function(){
		if(window.WebSocket){
			main();
		}
		else{
			alert('You need a modern browser supporting websockets for this');
		}
	});
});