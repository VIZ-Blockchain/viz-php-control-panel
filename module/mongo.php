<?php
ob_start();
if('mongo'==$path_array[1] && $admin){
	$replace['title']=htmlspecialchars($l10n['mongo']['caption']).' - '.$replace['title'];
	if(isset($_GET['action'])){
		if('add_index'==$_GET['action']){
			if('text'!=$_POST['index']){
				$_POST['index']=(int)$_POST['index'];
			}
			$result=$mongo->executeCommand($_POST['db'],new MongoDB\Driver\Command(
				[
					'createIndexes'=>$_POST['collection'],
					'indexes'=>[
						[
							'name'=>$_POST['attr'].'_index'.$_POST['index'],
							'key'=>[$_POST['attr']=>$_POST['index']],
							'ns'=>$_POST['db'].'.'.$_POST['collection']
						]
					]
				])
			);
		}
		if('drop_index'==$_GET['action']){
			if(isset($_GET['index'])){
				$result=$mongo->executeCommand($_GET['db'],new MongoDB\Driver\Command(
					[
						'dropIndexes'=>$_GET['collection'],
						'index'=>$_GET['index']
					])
				);
			}
		}
		header('location:'.$_SERVER['HTTP_REFERER']);
		exit;
	}
	if(''!=$path_array[2]){
		$collection=$path_array[2];
		$collection_count=mongo_count($collection);
		if($collection_count){
			$replace['title']=htmlspecialchars($collection).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/mongo/">&larr; '.$l10n['mongo']['return'].'</a>
			<h1>'.$collection.'</h1>
			<div class="article">';
			print '<p>'.$l10n['mongo']['rows-count'].': '.$collection_count.'</p>';
			$perpage=25;
			$offset=0;
			if(isset($_GET['offset'])){
				$offset=(int)$_GET['offset'];
			}
			$pages=ceil($collection_count/$perpage);
			$page=$offset/$perpage;
			$prev_page=$page-1;
			$next_page=$page+1;
			$prev_page=max($prev_page,0);
			$next_page=min($next_page,$pages);

			$find=array();
			$sort=array('_id'=>1);
			$sort_str='';
			if(isset($_GET['sort_attr'])){
				$sort=array($_GET['sort_attr']=>(int)$_GET['sort_asc']);
				$sort_str='&sort_attr='.$_GET['sort_attr'].'&sort_asc='.$_GET['sort_asc'];
			}
			$rows=$mongo->executeQuery($config['db_prefix'].'.'.$collection,new MongoDB\Driver\Query($find,['sort'=>$sort,'limit'=>(int)$perpage,'skip'=>(int)$offset]));
			$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
			foreach($rows as $row){
				print '<p>';
				print_r($row);
				print '</p>';
			}
			print '<div class="pages">';
			print '<a>'.$l10n['mongo']['current-page'].': '.($page+1).'</a>';
			if($offset>0){
				print '<a href="?offset='.($perpage*$prev_page).$sort_str.'">&larr; '.$l10n['mongo']['prev-page'].'</a>';
			}
			if($next_page<$pages){
				print '<a href="?offset='.($perpage*$next_page).$sort_str.'">'.$l10n['mongo']['next-page'].' &rarr;</a>';
			}
			print '</div>';
			$indexes=$mongo->executeCommand($config['db_prefix'],new MongoDB\Driver\Command(['listIndexes'=>$collection]));
			$indexes->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
			print '<h3>'.$l10n['mongo']['indexes-caption'].'</h3><ul>';
			foreach($indexes as $index){
				$sort_attr='';
				$sort_asc=1;
				foreach($index['key'] as $key=>$asc){
					$sort_attr=$key;
					$sort_asc=$asc;
					break;
				}
				print '<li class="clearfix"><a class="right" href="/mongo/?action=drop_index&db='.$config['db_prefix'].'&collection='.$collection.'&index='.$index['name'].'">'.$l10n['mongo']['remove-index'].' '.$index['name'].'</a>'.$index['name'].', '.$l10n['mongo']['index-keys'].': '.json_encode($index['key']).($index['weights']?', '.$l10n['mongo']['index-keys-weights'].': '.json_encode($index['weights']):'').', <a href="?sort_attr='.$sort_attr.'&sort_asc='.$sort_asc.'">'.$l10n['mongo']['index-keys-sort'].'</a></li>';
			}
			print '</ul>';
			print '</div></div>';
		}
	}
	if(''==$path_array[2]){
		print '<div class="page content">
		<h1>'.$l10n['mongo']['caption'].': '.$config['db_prefix'].'</h1>
		<div class="article">';
		$collections=$mongo->executeCommand($config['db_prefix'],new MongoDB\Driver\Command(['listCollections'=>1,'sort'=>['name'=>1]]));
		$collections->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
		foreach($collections as $collection){
			print '<p>';
			print '<a href="/mongo/'.$collection['name'].'/">'.$collection['name'].'</a>';
			print ', '.$l10n['mongo']['rows-count'].': '.mongo_count($collection['name']);
			print '</p>';
		}
		print '<h3>'.$l10n['mongo']['add-index-caption'].'</h3>';
		print '<form action="/mongo/?action=add_index" method="POST"><p>
		'.$l10n['mongo']['add-index-database'].': <input type="text" name="db" value="'.$config['db_prefix'].'" class="round"><br>
		'.$l10n['mongo']['add-index-collection'].': <input type="text" name="collection" value="" class="round"><br>
		'.$l10n['mongo']['add-index-attr'].': <input type="text" name="attr" value="" class="round"><br>
		'.$l10n['mongo']['add-index-value'].': <input type="text" name="index" value="" class="round"><br>
		<input type="submit" class="button" value="'.$l10n['mongo']['add-index-action'].'">
		</p></form>';
		print '</div></div>';
	}
}
$content=ob_get_contents();
ob_end_clean();