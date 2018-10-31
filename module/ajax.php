<?php
header("Content-type:text/html; charset=UTF-8");
if(in_array('content',$config['plugins'])){
	if('load_more'==$path_array[2]){
		$action=$_POST['action'];
		if('new-content'==$action){
			$last_id=(int)$_POST['last_id'];
			$count=0;

			$find=array('_id'=>['$lt'=>$last_id],'status'=>0,'parent'=>['$exists'=>false]);
			$perpage=30;
			$offset=0;
			$sort=array('_id'=>-1);
			$rows=$mongo->executeQuery($config['db_prefix'].'.content',new MongoDB\Driver\Query($find,['sort'=>$sort,'limit'=>(int)$perpage,'skip'=>(int)$offset]));
			$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
			foreach($rows as $row){
				print preview_content($row);
				$count++;
			}
			if(0==$count){
				print 'none';
			}
			sleep(1);
		}
		if('feed-content'==$action){
			$last_id=(int)$_POST['last_id'];
			$user_id=$user_arr['_id'];
			if(isset($_POST['user'])){
				$user_id=get_user_id($_POST['user']);
			}
			$count=0;

			$perpage=30;
			$feed_arr=redis_read_feed($user_id,$last_id,$perpage);
			$rows=redis_read_feed($user_id,$last_id,$perpage);
			foreach($rows as $row){
				print preview_content_by_id($row);
				$count++;
			}
			if(0==$count){
				print 'none';
			}
			sleep(1);
		}
	}
	if('check_content'==$path_array[2]){
		$author_login=$_POST['author'];
		$author_id=get_user_id($author_login);
		$permlink=$_POST['permlink'];
		$content_arr=mongo_find('content',['author'=>(int)$author_id,'permlink'=>mongo_prepare($permlink)]);
		if($content_arr['_id']){
			if($content_arr['body']){
				print '{"status":"ok"}';
			}
			else{
				print '{"status":"none"}';
			}
		}
		else{
			print '{"status":"none"}';
		}
	}
	if('load_new_comments'==$path_array[2]){
		header('HTTP/1.1 200 Ok');
		if($auth){
			$content_id=(int)$_POST['content_id'];
			$last_id=(int)$_POST['last_id'];

			$find=array('content'=>(int)$content_id,'_id'=>['$gt'=>(int)$last_id]);
			$sort=array('sort'=>1);
			$rows=$mongo->executeQuery($config['db_prefix'].'.subcontent',new MongoDB\Driver\Query($find));
			$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
			foreach($rows as $row){
				print view_subcontent($row).PHP_EOL;
			}
		}
	}
}
if(in_array('session',$config['plugins'])){
	if('create_session'==$path_array[2]){
		$key=$_POST['key'];
		$cookie_time=time();
		$cookie=md5($cookie_time.'VIZ'.$key).md5($key.'WORLD'.date('d.m.Y'));
		$check_session_id=$redis->zscore('session_cookie',$cookie);
		if($check_session_id){
			$check_ip=$redis->hget('session:'.$check_session_id,'ip');
			if($check_ip==$ip){
				$redis->del('session:'.$check_session_id);
			}
		}
		$new_id=$redis->incr('id:session');
		if($new_id){
			$redis->zadd('session_cookie',$new_id,$cookie);
			$redis->zadd('session_key',$new_id,$key);
			$redis->hset('session:'.$new_id,'id',$new_id);
			$redis->hset('session:'.$new_id,'time',$cookie_time);
			$redis->hset('session:'.$new_id,'ip',$ip);
			$redis->hset('session:'.$new_id,'key',$key);
			$redis->hset('session:'.$new_id,'cookie',$cookie);
		}
		header('HTTP/1.1 200 Ok');
		print $cookie;
	}
	if('check_session'==$path_array[2]){
		if($auth){
			print json_encode($session_arr['id']);
		}
		else{
			$session_id=$_COOKIE['session_id'];
			$check_session_id=$redis->zscore('session_cookie',$session_id);
			if($check_session_id){
				$check_ip=$redis->hget('session:'.$check_session_id,'ip');
				if($check_ip==$ip){
					$session_arr=$redis->hgetall('session:'.$check_session_id);
				}
			}
			$current_time_offset=time();
			$session_arr['time']=intval($session_arr['time'])+90;
			if($current_time_offset>$session_arr['time']){
				print '{"error":"rebuild_session","error_str":"'.$current_time_offset.', '.$session_arr['time'].'"}';
			}
			else{
				print '{"error":"wait","error_str":"'.$current_time_offset.', '.$session_arr['time'].'"}';
			}
		}
	}
}
if('transfers_history_table'==$path_array[2]){
	$user_login=mongo_prepare($_POST['user']);
	$user_id=get_user_id($user_login);
	$count=500;
	if(isset($_POST['count'])){
		$count=$_POST['count'];
	}
	if($count>5000){
		$count=5000;
	}
	if($user_id!=0){
		$transfers_arr1=$redis->zrevrange('transfers_to:'.$user_id,'0',''.$count);
		$transfers_arr2=$redis->zrevrange('transfers_from:'.$user_id,'0',''.$count);
		$transfers_arr=array_merge($transfers_arr1,$transfers_arr2);
		$transfers_arr=array_unique($transfers_arr);
		rsort($transfers_arr);
		foreach($transfers_arr as $transfer_id){
			$m=$redis->hgetall('transfers:'.$transfer_id);
			print '<tr class="wallet-history-'.($m['from']==$user_id?'out':'in').'" data-transfer-id="'.$transfer_id.'">';
			print '<td><span class="timestamp" data-timestamp="'.$m['time'].'">'.date('d.m.Y H:i:s',$m['time']).'</span></td>';
			print '<td><span class="wallet-recipient-set">'.get_user_login($m['from']).'</span></td>';
			print '<td><span class="wallet-recipient-set">'.get_user_login($m['to']).'</span></td>';
			print '<td rel="amount"><span class="wallet-balance-set">'.((float)$m['amount']!=0?$m['amount']:'&mdash;').'</span></td>';
			print '<td><span class="wallet-asset-set">'.($currencies_id_arr[$m['currency']]?$currencies_id_arr[$m['currency']]:'&mdash;').'</span></td>';
			print '<td class="wallet-memo-set">'.text_to_view($m['memo']).'</td>';
			print '</tr>';
		}
	}
}
if('transfers_history'==$path_array[2]){
	$user_login=mongo_prepare($_POST['user']);
	$user_id=get_user_id($user_login);
	$target_login=mongo_prepare($_POST['target']);
	$target_id=get_user_id($target_login);
	$way=mongo_prepare($_POST['way']);
	$currency=(int)$_POST['currency'];
	$transfer_id=(int)$_POST['transfer_id'];

	$transfers_arr=array();
	$trnsfer_id_score=0;
	if('from'==$way){
		if($transfer_id){
			$transfer_id_score=$redis->zscore('transfers_way:'.$user_id.':'.$target_id,$transfer_id);
		}
		else{
			$transfers_id_arr=$redis->zrevrange('transfers_way:'.$user_id.':'.$target_id,0,100);
			$transfer_id=$transfers_id_arr[count($transfers_id_arr)];
			$transfer_id_score=$redis->zscore('transfers_way:'.$user_id.':'.$target_id,$transfer_id);
		}
		$transfers_arr=$redis->zrevrangebyscore('transfers_way:'.$user_id.':'.$target_id,'+inf','('.$transfer_id_score);
	}
	if('to'==$way){
		if($transfer_id){
			$transfer_id_score=$redis->zscore('transfers_way:'.$target_id.':'.$user_id,$transfer_id);
		}
		else{
			$transfers_id_arr=$redis->zrevrange('transfers_way:'.$target_id.':'.$user_id,0,100);
			$transfer_id=$transfers_id_arr[count($transfers_id_arr)];
			$transfer_id_score=$redis->zscore('transfers_way:'.$target_id.':'.$user_id,$transfer_id);
		}
		$transfers_arr=$redis->zrevrangebyscore('transfers_way:'.$target_id.':'.$user_id,'+inf','('.$transfer_id_score);
	}
	if('both'==$way){
		if($transfer_id){
			$transfer_id_score=$redis->zscore('transfers_way:'.$user_id.':'.$target_id,$transfer_id);
			if(!$transfer_id_score){
				$transfer_id_score=$redis->zscore('transfers_way:'.$target_id.':'.$user_id,$transfer_id);
			}
		}
		else{
			$transfers_id_arr1=$redis->zrevrange('transfers_way:'.$user_id.':'.$target_id,0,100);
			$transfers_id_arr2=$redis->zrevrange('transfers_way:'.$target_id.':'.$user_id,0,100);
			$transfers_id_arr=array_merge($transfers_id_arr1,$transfers_id_arr2);
			$transfers_id_arr=array_unique($transfers_id_arr);
			rsort($transfers_id_arr);
			$transfer_id=$transfers_id_arr[count($transfers_id_arr)];
			$transfer_id_score=$redis->zscore('transfers_way:'.$user_id.':'.$target_id,$transfer_id);
			if(!$transfer_id_score){
				$transfer_id_score=$redis->zscore('transfers_way:'.$target_id.':'.$user_id,$transfer_id);
			}
		}
		$transfers_arr1=$redis->zrevrangebyscore('transfers_way:'.$user_id.':'.$target_id,'+inf','('.$transfer_id_score);
		$transfers_arr2=$redis->zrevrangebyscore('transfers_way:'.$target_id.':'.$user_id,'+inf','('.$transfer_id_score);
		$transfers_arr=array_merge($transfers_arr1,$transfers_arr2);
		$transfers_arr=array_unique($transfers_arr);
		rsort($transfers_arr);
	}
	if('any'==$way){
		if($transfer_id){
			$transfer_id_score=$redis->zscore('transfers_from:'.$user_id,$transfer_id);
			if(!$transfer_id_score){
				$transfer_id_score=$redis->zscore('transfers_to:'.$target_id,$transfer_id);
			}
		}
		else{
			$transfers_id_arr=$redis->zrevrange('transfers_from:'.$user_id,0,100);
			$transfer_id=$transfers_id_arr[count($transfers_id_arr)];
			$transfer_id_score=$redis->zscore('transfers_from:'.$user_id,$transfer_id);
			if(!$transfer_id_score){
				$transfers_id_arr=$redis->zrevrange('transfers_to:'.$target_id,0,100);
				$transfer_id=$transfers_id_arr[count($transfers_id_arr)];
				$transfer_id_score=$redis->zscore('transfers_to:'.$target_id,$transfer_id);
			}
		}
		$transfers_arr1=array();
		if($user_id){
			$transfers_arr1=$redis->zrevrangebyscore('transfers_from:'.$user_id,'+inf','('.$transfer_id_score);
		}
		$transfers_arr2=array();
		if($target_id){
			$transfers_arr2=$redis->zrevrangebyscore('transfers_to:'.$target_id,'+inf','('.$transfer_id_score);
		}
		$transfers_arr=array_merge($transfers_arr1,$transfers_arr2);
		$transfers_arr=array_unique($transfers_arr);
		rsort($transfers_arr);
	}
	foreach($transfers_arr as $transfer_id){
		$m=$redis->hgetall('transfers:'.$transfer_id);
		if((0==$currency)||($currency==$m['currency'])){
			$res='';
			$res.='<tr data-transfer-id="'.$m['id'].'">';
			$res.='<td><span class="timestamp" data-timestamp="'.$m['time'].'">'.date('d.m.Y H:i:s',$m['time']).'</span></td>';
			$res.='<td>'.get_user_login($m['from']).'</td>';
			$res.='<td>'.get_user_login($m['to']).'</td>';
			$res.='<td>'.$m['amount'].'</td>';
			$res.='<td>'.($currencies_id_arr[$m['currency']]?$currencies_id_arr[$m['currency']]:'&mdash;').'</td>';
			$res.='<td>'.strip_tags(text_to_view($m['memo']),'<a>').'</td>';
			$res.='</tr>';
			$result_arr[]=$res;
		}
	}
	print implode('',$result_arr);
}
exit;