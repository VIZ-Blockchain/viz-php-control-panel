<?php
class viz_plugin_blocks extends viz_plugin{
	function witness_reward($info,$data){
		$current_id=mongo_counter('blocks');

		$block_arr=array(
			'_id'=>(int)$info['block_id'],
			'time'=>(int)$info['unixtime'],
			'tx_count'=>(int)$info['block_tx_count'],
			'witness'=>$data['witness'],
			'shares'=>(float)$data['shares']
		);
		$bulk=new MongoDB\Driver\BulkWrite;
		$bulk->insert($block_arr);
		$this->mongo->executeBulkWrite('viz.blocks',$bulk);

		mongo_counter_set('blocks',$info['block_id']);
	}
	function witness_update($info,$data){
		redis_add_ulist('update_witness',$data['owner']);
	}
}