<?php
class viz_plugin_links extends viz_plugin{
	function award($info,$data){
		global $config;
		if(in_array('content',$config['plugins'])){
			if(in_array('votes',$config['plugins_extensions']['links'])){
				$memo=$data['memo'];
				if(false!==mb_strpos($memo,'/')){
					$author=mb_substr($memo,0,strpos($memo,'/'));
					if($data['receiver']==$author){
						$permlink=mb_substr($memo,mb_strpos($memo,'/')+1);
						$weight=(int)$data['energy'];
						$author_id=get_user_id($author);
						if($author_id){
							$user_login=$data['initiator'];
							$user_id=get_user_id($user_login);
							if($user_id){
								if(in_array('users',$config['plugins'])){
									redis_add_ulist('update_user',$user_login);
									redis_add_ulist('update_user',$author);
									$this->redis->zadd('users_action_time',$info['unixtime'],$user_login);

									$bulk=new MongoDB\Driver\BulkWrite;
									$bulk->update(['_id'=>(int)$user_id],['$inc'=>['awards_outcome_count'=>1]]);
									$this->mongo->executeBulkWrite($config['db_prefix'].'.users',$bulk);
								}
								$find_content=mongo_find_id('content',array('author'=>(int)$author_id,'permlink'=>mongo_prepare($permlink)));
								if($find_content){
									$find_content_vote=mongo_find_id('content_votes',['parent'=>(int)$find_content,'user'=>(int)$user_id]);
									if($find_content_vote){
										$bulk=new MongoDB\Driver\BulkWrite;
										$bulk->update(['_id'=>(int)$find_content_vote],['$set'=>['weight'=>(int)$weight,'time'=>(int)$info['unixtime']]]);
										$this->mongo->executeBulkWrite($config['db_prefix'].'.content_votes',$bulk);
									}
									else{
										$link_arr=array('_id'=>(int)mongo_counter('content_votes',true),'parent'=>(int)$find_content,'author'=>(int)$author_id,'user'=>(int)$user_id,'weight'=>(int)$weight,'time'=>(int)$info['unixtime']);
										$bulk=new MongoDB\Driver\BulkWrite;
										$bulk->insert($link_arr);
										$this->mongo->executeBulkWrite($config['db_prefix'].'.content_votes',$bulk);
									}
									unset($find_content);
								}
								else{
									$find_subcontent=mongo_find_id('subcontent',array('author'=>(int)$author_id,'permlink'=>mongo_prepare($permlink)));
									if($find_subcontent){
										$find_subcontent_vote=mongo_find_id('subcontent_votes',['parent'=>(int)$find_subcontent,'user'=>(int)$user_id]);
										if($find_subcontent_vote){
											$bulk=new MongoDB\Driver\BulkWrite;
											$bulk->update(['_id'=>(int)$find_subcontent_vote],['$set'=>['weight'=>(int)$weight,'time'=>(int)$info['unixtime']]]);
											$this->mongo->executeBulkWrite($config['db_prefix'].'.subcontent_votes',$bulk);
										}
										else{
											$link_arr=array('_id'=>(int)mongo_counter('subcontent_votes',true),'parent'=>(int)$find_subcontent,'author'=>(int)$author_id,'user'=>(int)$user_id,'weight'=>(int)$weight,'time'=>(int)$info['unixtime']);
											$bulk=new MongoDB\Driver\BulkWrite;
											$bulk->insert($link_arr);
											$this->mongo->executeBulkWrite($config['db_prefix'].'.subcontent_votes',$bulk);
										}
										unset($find_content);
									}
								}
							}
						}
					}
				}
			}
		}
	}
	function receive_award($info,$data){
		global $config;
		if(in_array('content',$config['plugins'])){
			if(in_array('votes',$config['plugins_extensions']['links'])){
				$memo=$data['memo'];
				if(false!==strpos($memo,'/')){
					$author=substr($memo,0,strpos($memo,'/'));
					if($data['receiver']==$author){
						$permlink=substr($memo,strpos($memo,'/')+1);
						$author_id=get_user_id($author);
						if($author_id){
							$find_content=mongo_find_id('content',array('author'=>(int)$author_id,'permlink'=>mongo_prepare($permlink)));
							if($find_content){
								$award_shares=(int)(floatval($data['shares'])*1000000);
								$bulk=new MongoDB\Driver\BulkWrite;
								$bulk->update(['_id'=>(int)$find_content],['$inc'=>['receive_award'=>$award_shares]]);
								$this->mongo->executeBulkWrite($config['db_prefix'].'.content',$bulk);
								unset($find_content);
							}
							else{
								$find_subcontent=mongo_find_id('subcontent',array('author'=>(int)$author_id,'permlink'=>mongo_prepare($permlink)));
								if($find_subcontent){
									$award_shares=(int)(floatval($data['shares'])*1000000);
									$bulk=new MongoDB\Driver\BulkWrite;
									$bulk->update(['_id'=>(int)$find_subcontent],['$inc'=>['receive_award'=>$award_shares]]);
									$this->mongo->executeBulkWrite($config['db_prefix'].'.content',$bulk);
									unset($find_content);
								}
							}
						}
					}
				}
			}
		}
	}
	function vote($info,$data){
		global $config;
		if(in_array('content',$config['plugins'])){
			if(in_array('votes',$config['plugins_extensions']['links'])){
				$author=$data['author'];
				$permlink=$data['permlink'];
				$weight=(int)$data['weight'];
				$author_id=get_user_id($author);
				if($author_id){
					$user_login=$data['voter'];
					$user_id=get_user_id($user_login);
					if($user_id){
						if(in_array('users',$config['plugins'])){
							redis_add_ulist('update_user',$user_login);
							$this->redis->zadd('users_action_time',$info['unixtime'],$user_login);
						}
						$find_content=mongo_find_id('content',array('author'=>(int)$author_id,'permlink'=>mongo_prepare($permlink)));
						if($find_content){
							$find_content_vote=mongo_find_id('content_votes',['parent'=>(int)$find_content,'user'=>(int)$user_id]);
							if($find_content_vote){
								$bulk=new MongoDB\Driver\BulkWrite;
								$bulk->update(['_id'=>(int)$find_content_vote],['$set'=>['weight'=>(int)$weight,'time'=>(int)$info['unixtime']]]);
								$this->mongo->executeBulkWrite($config['db_prefix'].'.content_votes',$bulk);
							}
							else{
								$link_arr=array('_id'=>(int)mongo_counter('content_votes',true),'parent'=>(int)$find_content,'author'=>(int)$author_id,'user'=>(int)$user_id,'weight'=>(int)$weight,'time'=>(int)$info['unixtime']);
								$bulk=new MongoDB\Driver\BulkWrite;
								$bulk->insert($link_arr);
								$this->mongo->executeBulkWrite($config['db_prefix'].'.content_votes',$bulk);
							}
							redis_add_ulist('update_content',$find_content);
							unset($find_content);
						}
						else{
							$find_subcontent=mongo_find_id('subcontent',array('author'=>(int)$author_id,'permlink'=>mongo_prepare($permlink)));
							if($find_subcontent){
								$find_subcontent_vote=mongo_find_id('subcontent_votes',['parent'=>(int)$find_subcontent,'user'=>(int)$user_id]);
								if($find_subcontent_vote){
									$bulk=new MongoDB\Driver\BulkWrite;
									$bulk->update(['_id'=>(int)$find_subcontent_vote],['$set'=>['weight'=>(int)$weight,'time'=>(int)$info['unixtime']]]);
									$this->mongo->executeBulkWrite($config['db_prefix'].'.subcontent_votes',$bulk);
								}
								else{
									$link_arr=array('_id'=>(int)mongo_counter('subcontent_votes',true),'parent'=>(int)$find_subcontent,'author'=>(int)$author_id,'user'=>(int)$user_id,'weight'=>(int)$weight,'time'=>(int)$info['unixtime']);
									$bulk=new MongoDB\Driver\BulkWrite;
									$bulk->insert($link_arr);
									$this->mongo->executeBulkWrite($config['db_prefix'].'.subcontent_votes',$bulk);
								}
								redis_add_ulist('update_subcontent',$find_subcontent);
								unset($find_content);
							}
						}
					}
				}
			}
		}
	}
	function custom($info,$data){
		global $config;
		$custom_name=$data['id'];
		$required_posting_auths=$data['required_posting_auths'];
		$required_auths=$data['required_auths'];
		$json=$data['json'];
		$json=json_decode($json,true);
		if(in_array('users',$config['plugins'])){
			if('follow'==$custom_name){
				$custom_action=$json[0];
				$custom_data=$json[1];
				if('follow'==$custom_action){
					$user_1=get_user_id($custom_data['follower']);
					if(in_array($custom_data['follower'],$required_posting_auths)){
						$user_2=get_user_id($custom_data['following']);
						if($user_1==$user_2){
							return;
						}
						$what=$custom_data['what'];
						if(0==count($what)){
							$bulk=new MongoDB\Driver\BulkWrite;
							$bulk->delete(['user_1'=>(int)$user_1,'user_2'=>(int)$user_2]);
							$this->mongo->executeBulkWrite($config['db_prefix'].'.users_links',$bulk);
							if(mongo_exist('users_links',['user_2'=>(int)$user_1,'user_1'=>(int)$user_2,'mutually'=>1])){
								$bulk=new MongoDB\Driver\BulkWrite;
								$bulk->update(['user_2'=>(int)$user_1,'user_1'=>(int)$user_2],['$set'=>['mutually'=>0]]);
								$this->mongo->executeBulkWrite($config['db_prefix'].'.users_links',$bulk);
							}
						}
						else{
							$what=$what[0];
							if('blog'==$what){
								$what=1;
								$mutually=0;

								$bulk=new MongoDB\Driver\BulkWrite;
								$bulk->delete(['user_1'=>(int)$user_1,'user_2'=>(int)$user_2]);
								$this->mongo->executeBulkWrite($config['db_prefix'].'.users_links',$bulk);

								if(mongo_exist('users_links',['user_2'=>(int)$user_1,'user_1'=>(int)$user_2,'value'=>$what])){
									$mutually=1;
									$bulk=new MongoDB\Driver\BulkWrite;
									$bulk->update(['user_2'=>(int)$user_1,'user_1'=>(int)$user_2],['$set'=>['mutually'=>1]]);
									$this->mongo->executeBulkWrite($config['db_prefix'].'.users_links',$bulk);
								}

								$link_arr=array('_id'=>(int)mongo_counter('users_links',true),'user_1'=>(int)$user_1,'user_2'=>(int)$user_2,'value'=>$what,'mutually'=>$mutually);
								$bulk=new MongoDB\Driver\BulkWrite;
								$bulk->insert($link_arr);
								$this->mongo->executeBulkWrite($config['db_prefix'].'.users_links',$bulk);
							}
							elseif('ignore'==$what){
								$what=2;

								$bulk=new MongoDB\Driver\BulkWrite;
								$bulk->delete(['user_1'=>(int)$user_1,'user_2'=>(int)$user_2]);
								$this->mongo->executeBulkWrite($config['db_prefix'].'.users_links',$bulk);

								$mutually=0;
								if(mongo_exist('users_links',['user_2'=>(int)$user_1,'user_1'=>(int)$user_2,'value'=>$what])){
									$mutually=1;
									$bulk=new MongoDB\Driver\BulkWrite;
									$bulk->update(['user_2'=>(int)$user_1,'user_1'=>(int)$user_2],['$set'=>['mutually'=>1]]);
									$this->mongo->executeBulkWrite($config['db_prefix'].'.users_links',$bulk);
								}

								$link_arr=array('_id'=>(int)mongo_counter('users_links',true),'user_1'=>(int)$user_1,'user_2'=>(int)$user_2,'value'=>$what,'mutually'=>$mutually);
								$bulk=new MongoDB\Driver\BulkWrite;
								$bulk->insert($link_arr);
								$this->mongo->executeBulkWrite($config['db_prefix'].'.users_links',$bulk);
							}
						}
					}
				}
			}
		}
	}
}