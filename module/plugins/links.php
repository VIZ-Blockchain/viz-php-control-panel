<?php
class viz_plugin_links extends viz_plugin{
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