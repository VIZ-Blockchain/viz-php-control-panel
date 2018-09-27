<?php
class viz_plugin_blocks extends viz_plugin{
	function witness_reward($info,$data){
		$rows=$this->mongo->executeQuery('viz.auto_increment',new MongoDB\Driver\Query(['_id'=>'blocks']));
		$current_id=0;
		foreach($rows as $row){
			$current_id=$row->count;
		}
		if(0==$current_id){
			$bulk=new MongoDB\Driver\BulkWrite;
			$bulk->insert(['_id'=>'blocks','count'=>$info['block_id']]);
			$this->mongo->executeBulkWrite('viz.auto_increment',$bulk);
		}
		else{
			$bulk=new MongoDB\Driver\BulkWrite;
			$bulk->update(['_id'=>'blocks'],['$set'=>['count'=>$info['block_id']]]);
			$this->mongo->executeBulkWrite('viz.auto_increment',$bulk);
		}
		$block_arr=array(
			'_id'=>(int)$info['block_id'],
			'time'=>(int)$info['unixtime'],
			'witness'=>$data['witness'],
			'shares'=>(float)$data['shares']
		);
		$bulk=new MongoDB\Driver\BulkWrite;
		$bulk->insert($block_arr);
		$this->mongo->executeBulkWrite('viz.blocks',$bulk);
	}
}