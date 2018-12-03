<?php
class viz_jsonrpc_web{
	public $endpoint='';
	public $debug=false;
	public $request_arr=array();
	public $result_arr=array();
	public $post_num=1;
	private $api=array(
		//https://github.com/VIZ-World/viz-world/blob/master/plugins/account_by_key/account_by_key_plugin.cpp
		'get_key_references'=>'account_by_key',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/account_history/plugin.cpp
		'get_account_history'=>'account_history',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/auth_util/plugin.cpp
		'check_authority_signature'=>'auth_util',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/block_info/plugin.cpp
		'get_block_info'=>'block_info',
		'get_blocks_with_info'=>'block_info',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/follow/plugin.cpp
		'get_followers'=>'follow',
		'get_following'=>'follow',
		'get_follow_count'=>'follow',
		'get_feed_entries'=>'follow',
		'get_feed'=>'follow',
		'get_blog_entries'=>'follow',
		'get_blog'=>'follow',
		'get_reblogged_by'=>'follow',
		'get_blog_authors'=>'follow',

		'get_inbox'=>'private_message',
		'get_outbox'=>'private_message',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/operation_history/plugin.cpp
		'get_ops_in_block'=>'operation_history',
		'get_transaction'=>'operation_history',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/witness_api/plugin.cpp
		/* Witnesses */
		'get_current_median_history_price'=>'witness_api',
		'get_witness_schedule'=>'witness_api',
		'get_witnesses'=>'witness_api',
		'get_witness_by_account'=>'witness_api',
		'get_witnesses_by_vote'=>'witness_api',
		'get_witness_count'=>'witness_api',
		'lookup_witness_accounts'=>'witness_api',
		'get_active_witnesses'=>'witness_api',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/database_api/api.cpp
		/* Blocks and transactions */
		'get_block_header'=>'database_api',
		'get_block'=>'database_api',
		'set_block_applied_callback'=>'database_api',
		'get_config'=>'database_api',
		'get_dynamic_global_properties'=>'database_api',
		'get_chain_properties'=>'database_api',
		'get_hardfork_version'=>'database_api',
		'get_next_scheduled_hardfork'=>'database_api',
		/* Accounts */
		'get_accounts'=>'database_api',
		'lookup_account_names'=>'database_api',
		'lookup_accounts'=>'database_api',
		'get_account_count'=>'database_api',
		'get_owner_history'=>'database_api',
		'get_recovery_request'=>'database_api',
		'get_escrow'=>'database_api',
		'get_withdraw_routes'=>'database_api',
		'get_account_bandwidth'=>'database_api',
		/* Authority / validation */
		'get_transaction_hex'=>'database_api',
		'get_required_signatures'=>'database_api',
		'get_potential_signatures'=>'database_api',
		'verify_authority'=>'database_api',
		'verify_account_authority'=>'database_api',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/social_network/social_network.cpp
		'get_content'=>'social_network',
		'get_replies_by_last_update'=>'social_network',
		'get_active_votes'=>'social_network',
		'get_content_replies'=>'social_network',
		'get_all_content_replies'=>'social_network',
		'get_account_votes'=>'social_network',
		/* Committee */
		'get_committee_request'=>'social_network',
		'get_committee_request_votes'=>'social_network',
		'get_committee_requests_list'=>'social_network',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/tags/plugin.cpp
		'get_trending_tags'=>'tags',
		'get_tags_used_by_author'=>'tags',
		'get_discussions_by_payout'=>'tags',
		'get_discussions_by_trending'=>'tags',
		'get_discussions_by_created'=>'tags',
		'get_discussions_by_active'=>'tags',
		'get_discussions_by_cashout'=>'tags',
		'get_discussions_by_votes'=>'tags',
		'get_discussions_by_children'=>'tags',
		'get_discussions_by_hot'=>'tags',
		'get_discussions_by_feed'=>'tags',
		'get_discussions_by_blog'=>'tags',
		'get_discussions_by_contents'=>'tags',
		'get_discussions_by_author_before_date'=>'tags',
		'get_languages'=>'tags',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/raw_block/plugin.cpp
		'get_raw_block'=>'raw_block',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/private_message/private_message_plugin.cpp
		'get_inbox'=>'private_message_plugin',
		'get_outbox'=>'private_message_plugin',

		//https://github.com/VIZ-World/viz-world/blob/master/plugins/network_broadcast_api/network_broadcast_api.cpp
		'broadcast_transaction'=>'network_broadcast_api',
		'broadcast_transaction_synchronous'=>'network_broadcast_api',
		'broadcast_block'=>'network_broadcast_api',
		'broadcast_transaction_with_callback'=>'network_broadcast_api',
	);
	function viz_jsonrpc_web($endpoint='',$debug=false){
		$this->endpoint=$endpoint;
		$this->debug=$debug;
		$this->request_arr=array();
		$this->result_arr=array();
	}
	function get_url($url,$post=array()){
		$this->last_url=$url;
		$method='GET';
		if($post){
			$method='POST';
		}
		preg_match('#://(.*)/#iUs',$url,$stock);
		preg_match('#://'.$stock[1].'/(.*)$#iUs',$url,$stock2);
		$host=$stock[1];
		$use_port=false;
		if(false!==strpos($host,':')){
			$use_port=intval(substr($host,strpos($host,':')+1));
			$host=substr($host,0,strpos($host,':'));
		}
		$path=$stock2[1];
		$request=$method." /".$path." HTTP/1.1\r\n";
		$request.="Host: ".$host."\r\n";
		$request.="Connection: close\r\n";
		$request.="Content-Type: application/x-www-form-urlencoded\r\n";
		$request.="Content-Length: ".strlen($post)."\r\n\r\n";
		$request.=$post;
		$request.="\r\n";
		$request.="\r\n";
		if($this->debug){
			$this->request_arr[]=$request;
		}
		$result='';
		$port=80;
		if(false!==strpos($url,'https://')){
			$port=443;
			$host='ssl://'.$host;
		}
		if(false!==strpos($url,'wss://')){
			$port=443;
			$host='ssl://'.$host;
		}
		if(false!==$use_port){
			$port=$use_port;
		}
		if($sock=fsockopen($host, $port, $errno, $errstr, 1)){
			fwrite($sock,$request,strlen($request));
			while(!feof($sock)){
				$result.=fread($sock,1024);
			}
			fclose($sock);
		}
		else{
			return false;
		}
		if($this->debug){
			$this->result_arr[]=$result;
		}
		return $result;
	}
	function parse_web_result($fp){
		$headers=mb_substr($fp,0,mb_strpos($fp,"\r\n\r\n"));
		$clear_r=mb_substr($fp,mb_strpos($fp,"\r\n\r\n")+4);
		if(false!==strpos($headers,'Transfer-Encoding: chunked')){$clear_r=clear_chunked($clear_r);}
		if(false!==strpos($headers,'Content-Encoding: gzip')){$clear_r=gzdecode($clear_r);}
		return array($headers,$clear_r);
	}
	function build_method($method,$params){
		$params_arr=array();
		$params_str='';
		if(count($params)>0){
			foreach($params as $k => $v){
				if(is_array($v)){
					if(isset($v['raw'])){
						unset($v['raw']);
						$params_arr[]=json_encode($v);
						break;
					}
					$v='["'.implode('","',$v).'"]';
				}
				else{
					if(is_bool($v)){
						if($v){
							$v='true';
						}
						else{
							$v='false';
						}
					}
					else{
						if(!is_int($v)){
							$v='"'.$v.'"';
						}
					}
				}
				if(is_int($k)){
					$params_arr[]=$v;
				}
				else{
					$params_arr[]='"'.$k.'":'.$v.'';
				}
			}
			$params_str=implode(',',$params_arr);
		}
		$return='{"id":'.$this->post_num.',"jsonrpc":"2.0","method":"call","params":["'.$this->api[$method].'","'.$method.'",['.(($params)?$params_str:'').']]}';
		$this->post_num++;
		return $return;
	}
	function execute_method($method,$params=array(),$debug=false){
		$jsonrpc_query=$this->build_method($method,$params);
		$result=$this->get_url($this->endpoint,$jsonrpc_query);
		if(false!==$result){
			list($header,$result)=$this->parse_web_result($result);
			if($debug||$this->debug){
				print PHP_EOL.'<!-- ENDPOINT: '.$this->endpoint.' -->'.PHP_EOL;
				print '<!-- QUERY: '.$jsonrpc_query.' -->'.PHP_EOL;
				print '<!-- HEADER: '.$header.' -->'.PHP_EOL;
				print '<!-- RESULT: '.$result.' -->'.PHP_EOL;
			}
			$result_arr=json_decode($result,true);
			if(isset($result_arr['result'])){
				return $result_arr['result'];
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}
}