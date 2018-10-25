<?php
header("Content-type:text/html; charset=UTF-8");
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