<?php
class viz_plugin_content extends viz_plugin{
	function custom($info,$data){
		global $config;
		if(in_array('repost',$config['plugins_extensions']['content'])){
			$custom_name=$data['id'];
			$required_posting_auths=$data['required_posting_auths'];
			$required_auths=$data['required_auths'];
			$json=$data['json'];
			$json=json_decode($json,true);

			if('follow'==$custom_name){
				$custom_action=$json[0];
				$custom_data=$json[1];
				if('reblog'==$custom_action){
					$author=$custom_data['author'];
					$author_id=get_user_id($author);
					if($author_id){
						$user_login=$custom_data['account'];
						if(in_array($user_login,$required_posting_auths)){
							$user_id=get_user_id($user_login);
							if($user_id){
								$permlink=$custom_data['permlink'];
								$find_content=mongo_find_id('content',array('author'=>(int)$author_id,'permlink'=>mongo_prepare($permlink)));
								if($find_content){
									$reblog_id=mongo_counter('content',true);
									$data_arr=array('_id'=>(int)$reblog_id,'parent'=>(int)$find_content,'author'=>(int)$user_id,'time'=>(int)$info['unixtime']);
									if(isset($custom_data['comment'])){
										$data_arr['comment']=mongo_prepare($custom_data['comment']);
									}
									$bulk=new MongoDB\Driver\BulkWrite;
									$bulk->insert($data_arr);
									$this->mongo->executeBulkWrite($config['db_prefix'].'.content',$bulk);

									if(in_array('links',$config['plugins'])){
										$rows=$this->mongo->executeQuery($config['db_prefix'].'.users_links',new MongoDB\Driver\Query(['user_2'=>(int)$user_id,'value'=>1]));
										$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
										foreach($rows as $row){
											if(!mongo_exist('users_links',['user_1'=>(int)$row['user_1'],'user_2'=>(int)$author_id,'value'=>2])){
												redis_add_feed($row['user_1'],$reblog_id);
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	function delete_content($info,$data){
		global $config;
		$user_id=get_user_id($data['author']);
		$permlink=mongo_prepare($data['permlink']);
		$find_content=mongo_find_id('content',array('author'=>(int)$user_id,'permlink'=>$permlink));
		if($find_content){
			$data=array('status'=>1,'delete_time'=>(int)$info['unixtime']);
			$bulk=new MongoDB\Driver\BulkWrite;
			$bulk->update(['_id'=>(int)$find_subcontent],['$set'=>$data]);
			$this->mongo->executeBulkWrite($config['db_prefix'].'.content',$bulk);
		}
		else{
			$find_subcontent=mongo_find_id('subcontent',array('author'=>(int)$user_id,'permlink'=>$permlink));
			if($find_subcontent){
				$data=array('status'=>1,'delete_time'=>(int)$info['unixtime']);
				$bulk=new MongoDB\Driver\BulkWrite;
				$bulk->update(['_id'=>(int)$find_subcontent],['$set'=>$data]);
				$this->mongo->executeBulkWrite($config['db_prefix'].'.subcontent',$bulk);
			}
		}
	}
	function content($info,$data){
		global $config;
		if(''==$data['parent_author']){//content
			$parent_permlink=mongo_prepare($data['parent_permlink']);

			$user_id=get_user_id($data['author']);
			$permlink=mongo_prepare($data['permlink']);
			$user_status=mongo_find_attr('users','status',array('_id'=>(int)$user_id));

			$data_arr=array(
				'title'=>mongo_prepare($data['title']),
				'body'=>mongo_prepare($data['body']),
				'curation_percent'=>(int)$data['curation_percent'],
				'curation_percent'=>(int)$data['curation_percent'],
				'status'=>$user_status,
				'parse_time'=>(int)time()
			);

			$json_metadata_encoded=json_decode($data['json_metadata'],true);
			if(isset($json_metadata_encoded['cover'])){
				$data_arr['cover']=mongo_prepare($json_metadata_encoded['cover']);
			}
			if(isset($json_metadata_encoded['foreword'])){
				$data_arr['foreword']=mongo_prepare($json_metadata_encoded['foreword']);
			}

			$find_content=mongo_find_id('content',array('author'=>(int)$user_id,'permlink'=>$permlink));
			$content_id=0;
			$bulk=new MongoDB\Driver\BulkWrite;
			if($find_content){
				$content_id=$find_content;
				$data_arr['update_time']=(int)$info['unixtime'];
				$bulk->update(['_id'=>(int)$content_id],['$set'=>$data_arr]);
			}
			else{
				$content_id=mongo_counter('content',true);
				$data_arr['_id']=(int)$content_id;
				$data_arr['author']=(int)$user_id;
				$data_arr['permlink']=$permlink;
				$data_arr['parent_permlink']=$parent_permlink;
				$data_arr['time']=(int)$info['unixtime'];
				$bulk->insert($data_arr);
			}
			$this->mongo->executeBulkWrite($config['db_prefix'].'.content',$bulk);

			if(in_array('tags',$config['plugins_extensions']['content'])){
				if($find_content){
					$bulk=new MongoDB\Driver\BulkWrite;
					$bulk->delete(['content'=>(int)$find_content]);
					$this->mongo->executeBulkWrite($config['db_prefix'].'.content_tags',$bulk);
				}
				if(isset($json_metadata_encoded['tags'])){
					foreach($json_metadata_encoded['tags'] as $tag){
						$tag=trim($tag," \r\n\t");
						$tag_id=mongo_find_id('tags',array('value'=>$tag));
						if(!$tag_id){
							$tag_id=mongo_counter('tags',true);
							$tag_arr=array('_id'=>(int)$tag_id,'value'=>mongo_prepare($tag),'count'=>1);
							$bulk=new MongoDB\Driver\BulkWrite;
							$bulk->insert($tag_arr);
							$this->mongo->executeBulkWrite($config['db_prefix'].'.tags',$bulk);
						}
						else{
							$bulk=new MongoDB\Driver\BulkWrite;
							$bulk->update(['_id'=>(int)$tag_id],['$inc'=>['count'=>1]]);
							$this->mongo->executeBulkWrite($config['db_prefix'].'.tags',$bulk);
						}
						$content_tag_arr=array(
							'_id'=>(int)mongo_counter('content_tags',true),
							'tag'=>(int)$tag_id,
							'content'=>(int)$content_id
						);
						$bulk=new MongoDB\Driver\BulkWrite;
						$bulk->insert($content_tag_arr);
						$this->mongo->executeBulkWrite($config['db_prefix'].'.content_tags',$bulk);
					}
				}
			}

			if(in_array('links',$config['plugins'])){
				if(in_array('feed',$config['plugins_extensions']['content'])){
					if(!$find_content){
						$rows=$this->mongo->executeQuery($config['db_prefix'].'.users_links',new MongoDB\Driver\Query(['user_2'=>(int)$user_id,'value'=>1]));
						$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
						foreach($rows as $row){
							redis_add_feed($row['user_1'],$content_id);
						}
					}
				}
			}
		}
		else{//subcontent
			$parent_user_id=get_user_id($data['parent_author']);
			$parent_permlink=mongo_prepare($data['parent_permlink']);
			$parent_content=mongo_find_id('content',array('author'=>(int)$parent_user_id,'permlink'=>$parent_permlink));
			$parent_subcontent=0;
			$level=0;
			$sort=0;
			if(!$parent_content){
				$parent_subcontent=mongo_find_id('subcontent',array('author'=>(int)$parent_user_id,'permlink'=>$parent_permlink));
				if(!$parent_subcontent){
					$parent_subcontent=0;
				}
			}

			if($parent_subcontent){
				$parent_subcontent_arr=mongo_find('subcontent',array('_id'=>(int)$parent_subcontent));
				$parent_content=$parent_subcontent_arr['content'];

				$level=1+$parent_subcontent_arr['level'];

				$parent_subcontent_next_sort=mongo_find_attr('subcontent','sort',
					array(
						'content'=>(int)$parent_content,
						'sort'=>array('$gt'=>(int)$parent_subcontent_arr['sort']),
						'level'=>array('$lte'=>(int)$parent_subcontent_arr['level']),
					),
					array(
						'sort'=>array('sort'=>1),
						'limit'=>1
					)
				);
				if($parent_subcontent_next_sort){
					$sort=$parent_subcontent_next_sort;
				}
				else{
					$sort=(int)mongo_find_attr('subcontent','sort',
						array(
							'content'=>(int)$parent_content
							),
						array(
							'sort'=>array('sort'=>-1),
							'limit'=>1
						)
					);
					$sort++;
				}
				$bulk=new MongoDB\Driver\BulkWrite;
				$bulk->update(['content'=>(int)$parent_content,'sort'=>array('$gte'=>(int)$sort)],['$inc'=>['sort'=>1]]);
				$this->mongo->executeBulkWrite($config['db_prefix'].'.subcontent',$bulk);
			}
			else{
				$sort=(int)mongo_find_attr('subcontent','sort',
					array(
						'content'=>(int)$parent_content
						),
					array(
						'sort'=>array('sort'=>-1),
						'limit'=>1
					)
				);
				$sort++;
			}

			$user_id=get_user_id($data['author']);
			$permlink=mongo_prepare($data['permlink']);
			$user_status=mongo_find_attr('users','status',array('_id'=>(int)$user_id));

			$data_arr=array(
				'title'=>mongo_prepare($data['title']),
				'body'=>mongo_prepare($data['body']),
				'curation_percent'=>(int)$data['curation_percent'],
				'status'=>$user_status,
				'level'=>$level,
				'sort'=>$sort,
				'parse_time'=>(int)time()
			);

			$json_metadata_encoded=json_decode($data['json_metadata'],true);
			if(isset($json_metadata_encoded['cover'])){
				$data_arr['cover']=mongo_prepare($json_metadata_encoded['cover']);
			}
			if(isset($json_metadata_encoded['foreword'])){
				$data_arr['foreword']=mongo_prepare($json_metadata_encoded['foreword']);
			}

			$find_subcontent=mongo_find_id('subcontent',array('author'=>(int)$user_id,'permlink'=>$permlink));
			$bulk=new MongoDB\Driver\BulkWrite;
			if($find_subcontent){
				$bulk->update(['_id'=>(int)$find_subcontent],['$set'=>$data_arr]);
			}
			else{
				$data_arr['_id']=(int)mongo_counter('subcontent',true);
				$data_arr['author']=(int)$user_id;
				$data_arr['content']=(int)$parent_content;
				$data_arr['permlink']=$permlink;
				$data_arr['parent_author']=(int)$parent_user_id;
				$data_arr['parent_permlink']=$parent_permlink;
				$data_arr['time']=(int)$info['unixtime'];
				$bulk->insert($data_arr);
			}
			$this->mongo->executeBulkWrite($config['db_prefix'].'.subcontent',$bulk);
		}
	}
}