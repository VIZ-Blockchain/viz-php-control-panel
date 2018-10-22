<?php
class viz_plugin_transfers extends viz_plugin{
	public $currencies_arr=array(
		'SHARES'=>1,
		'VIZ'=>2
	);
	function transfer($info,$data){
		$from_id=get_user_id($data['from']);
		$to_id=get_user_id($data['to']);
		$amount_arr=explode(' ',$data['amount']);
		$amount=(float)$amount_arr[0];
		$currency_str=$amount_arr[1];
		$currency=-1;
		if($this->currencies_arr[$currency_str]){
			$currency=$this->currencies_arr[$currency_str];
		}
		$memo=$arr['memo'];

		$transfer_id=$this->redis->incr('id:transfers');

		$this->redis->hmset('transfers:'.$transfer_id,
			array(
				'id'=>$transfer_id,
				'from'=>$from_id,
				'to'=>$to_id,
				'amount'=>$amount,
				'currency'=>$currency,
				'memo'=>$memo,
				'time'=>$info['unixtime']
			)
		);

		$this->redis->zadd('transfers_from:'.$from_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to:'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_way:'.$from_id.':'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to_currency:'.$to_id.':'.$currency,$info['unixtime'],$transfer_id);
	}
	function transfer_to_vesting($info,$data){
		$this->currencies_arr=array(
			'SHARES'=>1,
			'VIZ'=>2
		);
		$from_id=get_user_id($data['from']);
		$to_id=get_user_id($data['to']);
		$amount_arr=explode(' ',$data['amount']);
		$amount=(float)$amount_arr[0];
		$currency_str=$amount_arr[1];
		$currency=-1;
		if($this->currencies_arr[$currency_str]){
			$currency=$this->currencies_arr[$currency_str];
		}
		$memo='TO VESTING SHARES';

		$transfer_id=$this->redis->incr('id:transfers');

		$this->redis->hmset('transfers:'.$transfer_id,
			array(
				'id'=>$transfer_id,
				'from'=>$from_id,
				'to'=>$to_id,
				'amount'=>$amount,
				'currency'=>$currency,
				'memo'=>$memo,
				'time'=>$info['unixtime']
			)
		);

		$this->redis->zadd('transfers_from:'.$from_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to:'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_way:'.$from_id.':'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to_currency:'.$to_id.':'.$currency,$info['unixtime'],$transfer_id);
	}
	function delegate_vesting_shares($info,$data){
		$from_id=get_user_id($data['delegator']);
		$to_id=get_user_id($data['delegatee']);
		$amount_arr=explode(' ',$data['vesting_shares']);
		$amount=(float)$amount_arr[0];
		$currency_str=$amount_arr[1];
		$currency=-1;
		if($this->currencies_arr[$currency_str]){
			$currency=$this->currencies_arr[$currency_str];
		}
		$memo='DELEGATE SHARES';

		$transfer_id=$this->redis->incr('id:transfers');

		$this->redis->hmset('transfers:'.$transfer_id,
			array(
				'id'=>$transfer_id,
				'from'=>$from_id,
				'to'=>$to_id,
				'amount'=>$amount,
				'currency'=>$currency,
				'memo'=>$memo,
				'time'=>$info['unixtime']
			)
		);

		$this->redis->zadd('transfers_from:'.$from_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to:'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_way:'.$from_id.':'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to_currency:'.$to_id.':'.$currency,$info['unixtime'],$transfer_id);
	}
	function withdraw_vesting($info,$data){
		$from_id=get_user_id($data['account']);
		$to_id=get_user_id($data['account']);
		$amount_arr=explode(' ',$data['vesting_shares']);
		$amount=(float)$amount_arr[0];
		$currency_str=$amount_arr[1];
		$currency=-1;
		if($this->currencies_arr[$currency_str]){
			$currency=$this->currencies_arr[$currency_str];
		}
		$memo='INIT WITHDRAW VESTING SHARES';

		$transfer_id=$this->redis->incr('id:transfers');

		$this->redis->hmset('transfers:'.$transfer_id,
			array(
				'id'=>$transfer_id,
				'from'=>$from_id,
				'to'=>$to_id,
				'amount'=>$amount,
				'currency'=>$currency,
				'memo'=>$memo,
				'time'=>$info['unixtime']
			)
		);

		$this->redis->zadd('transfers_from:'.$from_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to:'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_way:'.$from_id.':'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to_currency:'.$to_id.':'.$currency,$info['unixtime'],$transfer_id);
	}
	function fill_vesting_withdraw($info,$data){
		$from_id=get_user_id($data['from_account']);
		$to_id=get_user_id($data['to_account']);
		$amount_arr=explode(' ',$data['deposited']);
		$amount=(float)$amount_arr[0];
		$currency_str=$amount_arr[1];
		$currency=-1;
		if($this->currencies_arr[$currency_str]){
			$currency=$this->currencies_arr[$currency_str];
		}
		$memo='WITHDRAW VESTING SHARES';

		$transfer_id=$this->redis->incr('id:transfers');

		$this->redis->hmset('transfers:'.$transfer_id,
			array(
				'id'=>$transfer_id,
				'from'=>$from_id,
				'to'=>$to_id,
				'amount'=>$amount,
				'currency'=>$currency,
				'memo'=>$memo,
				'time'=>$info['unixtime']
			)
		);

		$this->redis->zadd('transfers_from:'.$from_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to:'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_way:'.$from_id.':'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to_currency:'.$to_id.':'.$currency,$info['unixtime'],$transfer_id);
	}
	function return_vesting_delegation($info,$data){
		$from_id=get_user_id($data['account']);
		$to_id=get_user_id($data['account']);
		$amount_arr=explode(' ',$data['vesting_shares']);
		$amount=(float)$amount_arr[0];
		$currency_str=$amount_arr[1];
		$currency=-1;
		if($this->currencies_arr[$currency_str]){
			$currency=$this->currencies_arr[$currency_str];
		}
		$memo='RETURN DELEGATED SHARES';

		$transfer_id=$this->redis->incr('id:transfers');

		$this->redis->hmset('transfers:'.$transfer_id,
			array(
				'id'=>$transfer_id,
				'from'=>$from_id,
				'to'=>$to_id,
				'amount'=>$amount,
				'currency'=>$currency,
				'memo'=>$memo,
				'time'=>$info['unixtime']
			)
		);

		$this->redis->zadd('transfers_from:'.$from_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to:'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_way:'.$from_id.':'.$to_id,$info['unixtime'],$transfer_id);
		$this->redis->zadd('transfers_to_currency:'.$to_id.':'.$currency,$info['unixtime'],$transfer_id);
	}
}