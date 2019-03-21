<?php
ob_start();
if('@'==mb_substr($path_array[2],0,1)){
	$account_id=get_user_id(mb_substr($path_array[2],1));
	if(0<$account_id){
		$account_arr=get_user_by_id($account_id);
		if($_GET['manage_user'] && $admin){
			if($_POST['show_user']){
				$bulk=new MongoDB\Driver\BulkWrite;
				$bulk->update(['_id'=>(int)$account_id],['$set'=>['status'=>0]]);
				$mongo->executeBulkWrite($config['db_prefix'].'.users',$bulk);
				header('location:/media/@'.$account_arr['login'].'/?manage_user=1');
				exit;
			}
			if($_POST['hide_user']){
				$bulk=new MongoDB\Driver\BulkWrite;
				$bulk->update(['_id'=>(int)$account_id],['$set'=>['status'=>1]]);
				$mongo->executeBulkWrite($config['db_prefix'].'.users',$bulk);
				header('location:/media/@'.$account_arr['login'].'/?manage_user=1');
				exit;
			}
			if($_POST['hide_user_content']){
				$bulk=new MongoDB\Driver\BulkWrite;
				$bulk->update(['author'=>(int)$account_id],['$set'=>['status'=>1]],['multi'=>true]);
				$mongo->executeBulkWrite($config['db_prefix'].'.content',$bulk);
				header('location:/media/@'.$account_arr['login'].'/?manage_user=1');
				exit;
			}
			if($_POST['hide_user_subcontent']){
				$bulk=new MongoDB\Driver\BulkWrite;
				$bulk->update(['author'=>(int)$account_id],['$set'=>['status'=>1]],['multi'=>true]);
				$mongo->executeBulkWrite($config['db_prefix'].'.subcontent',$bulk);
				header('location:/media/@'.$account_arr['login'].'/?manage_user=1');
				exit;
			}
			$replace['title']=$l10n['media']['user-manage-caption'].' - @'.$account_arr['login'].' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/media/@'.$account_arr['login'].'/">&larr; '.$l10n['media']['return'].'</a>
			<h1>'.$l10n['media']['user-manage-caption'].' - @'.$account_arr['login'].'</h1>';
			print '<p>'.$l10n['media']['user-manage-id'].': '.$account_id.'</p>';
			print '<p>'.$l10n['media']['user-manage-status'].': '.$account_arr['status'].'</p>';
			print '<form action="?manage_user=1" method="POST">';
			if(1==$account_arr['status']){
				print '<input type="submit" class="button" name="show_user" value="'.$l10n['media']['user-manage-show'].'">';
			}
			else{
				print '<input type="submit" class="button" name="hide_user" value="'.$l10n['media']['user-manage-hide'].'">';
			}
			print '<br><input type="submit" class="button" name="hide_user_content" value="'.$l10n['media']['user-manage-hide-content'].'">';
			print '<br><input type="submit" class="button" name="hide_user_subcontent" value="'.$l10n['media']['user-manage-hide-subcontent'].'">';
			print '</form>';
			print '</div>';
		}
		else
		if(0==$account_arr['status']){
			if($path_array[3] || isset($path_array[4])){
				$permlink=urldecode($path_array[3]);
				$data=get_content($account_id,$permlink);
				if(isset($data['_id'])){
					$content_title=stripcslashes($data['title']);
					$account_arr['nickname']=str_replace('@','',$account_arr['nickname']);
					if($account_arr['nickname']){
						$replace['title']=htmlspecialchars($account_arr['nickname']).' @'.$account_arr['login'].' - '.$replace['title'];
					}
					else{
						$replace['title']='@'.$account_arr['login'].' - '.$replace['title'];
					}
					$replace['title']=htmlspecialchars($content_title).' - '.$replace['title'];

					if(('edit'==$path_array[4])&&($auth)&&(($data['author']==$user_arr['_id'])||$admin)){
						$replace['title']=htmlspecialchars($l10n['media']['edit-caption']).' - '.$replace['title'];
						print $config['wysiwyg'];
						print '<div class="page content">
						<a class="right" href="/media/@'.$account_arr['login'].'/'.htmlspecialchars(stripcslashes($data['permlink'])).'/">&larr; '.$l10n['media']['return'].'</a>
						<h1>'.$l10n['media']['edit-caption'].'</h1>
						<div class="article post-content control">';
						print '<p><input type="hidden" name="parent_permlink" value="'.htmlspecialchars(stripcslashes($data['parent_permlink']?$data['parent_permlink']:'')).'"></p>';
						print '<p><input type="text" name="permlink" class="round wide" placeholder="'.$l10n['media']['publication-caption'].'" value="'.htmlspecialchars(stripcslashes($data['permlink'])).'" disabled="disabled"></p>';
						print '<p><input type="text" name="title" class="round wide" placeholder="'.$l10n['media']['publication-title'].'" value="'.htmlspecialchars(stripcslashes($data['title'])).'"></p>';
						print '<p><input type="text" name="foreword" class="round wide" placeholder="'.$l10n['media']['publication-foreword'].'" value="'.htmlspecialchars(stripcslashes($data['foreword'])).'"></p>';
						print '<p><input type="text" name="cover" class="round wide" placeholder="'.$l10n['media']['publication-cover'].'" value="'.htmlspecialchars(stripcslashes($data['cover'])).'"></p>';
						print '<p><textarea name="content" rows="20" class="round wide" placeholder="'.$l10n['media']['publication-content'].'">'.htmlspecialchars(stripcslashes($data['body'])).'</textarea></p>';
						print '<p><input id="upload-file" type="file"><a class="upload-image-action action-button"><i class="fas fa-fw fa-file-image"></i> '.$l10n['media']['publication-upload-image'].'</a> <a class="wysiwyg-action action-button"><i class="fas fa-fw fa-pen-square"></i> '.$l10n['media']['publication-wysiwyg'].'</a> <a class="beneficiaries-action action-button unselectable"><i class="fas fa-fw fa-money-bill-wave"></i> '.$l10n['media']['publication-beneficiaries'].'</a></p>';

						print '<div class="add-beneficiaries">
						<h4>'.$l10n['media']['beneficiaries-caption'].'</h4>
						<p>'.$l10n['media']['beneficiaries-descr'].'</p>';
						if($data['beneficiaries']){
							$beneficiaries=json_decode($data['beneficiaries'],true);
							foreach($beneficiaries as $item){
								print '<p class="add-beneficiaries-item"><input type="text" name="account" class="round" placeholder="'.$l10n['media']['beneficiaries-account-login'].'" value="'.$item['account'].'"> <input type="text" name="weight" class="round" placeholder="'.$l10n['media']['beneficiaries-weight'].'" value="'.($item['weight']/100).'"></p>';
							}
						}
						else{
							print '<p class="add-beneficiaries-item"><input type="text" name="account" class="round" placeholder="'.$l10n['media']['beneficiaries-account-login'].'"> <input type="text" name="weight" class="round" placeholder="'.$l10n['media']['beneficiaries-weight'].'"></p>';
						}
						print '<p class="add-beneficiaries-button"><a class="add-beneficiaries-action action-button"><i class="fas fa-fw fa-plus-circle"></i> '.$l10n['media']['beneficiaries-add'].'</a></p></div>';

						$tags_list=array();
						$tags=$mongo->executeQuery($config['db_prefix'].'.content_tags',new MongoDB\Driver\Query(['content'=>(int)$data['_id']],['sort'=>array('_id'=>1),'limit'=>(int)100]));
						$tags->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
						foreach($tags as $tag_id){
							$tag=get_tag($tag_id['tag']);
							if($tag){
								$tags_list[]=htmlspecialchars(stripcslashes($tag));
							}
						}

						print '<hr><p><input type="text" name="tags" class="round wide" placeholder="'.$l10n['media']['publication-tags'].'" value="'.implode(',',$tags_list).'"></p>';
						print '<p><a class="post-content-action button">'.$l10n['media']['edit-action'].'</a></p>';
						print '</div></div>';
					}
					else{
						if(''!=$path_array[4]){
							header('location:/media/@'.$account_arr['login'].'/'.stripcslashes($data['permlink']).'/');
							exit;
						}
						$descr='';
						if(isset($data['foreword'])){
							$descr=mb_substr(strip_tags(stripcslashes($data['foreword'])),0,250).'...';
						}
						else{
							$descr=mb_substr(strip_tags(stripcslashes($data['body'])),0,250).'...';
						}
						$replace['description']=htmlspecialchars(trim($descr," \r\n\t"));
						$replace['description']=str_replace("\n",' ',$replace['description']);
						$replace['description']=str_replace('  ',' ',$replace['description']);

						$replace['head_addon'].='
						<meta property="og:url" content="https://viz.world/media/@'.$account_arr['login'].'/'.$data['permlink'].'/" />
						<meta name="og:title" content="'.htmlspecialchars($content_title).'" />
						<meta name="twitter:title" content="'.htmlspecialchars($content_title).'" />';

						$cover=false;
						if(isset($data['cover'])){
							$cover=$data['cover'];
							if(!preg_match('~^https://~iUs',$cover)){
								$cover='https://i.goldvoice.club/0x0/'.$cover;
							}
							$replace['head_addon'].='
				<link rel="image_src" href="'.$cover.'" />
				<meta property="og:image" content="'.$cover.'" />
				<meta name="twitter:image" content="'.$cover.'" />
				<meta name="twitter:card" content="summary_large_image" />';
							print '<img src="'.$cover.'" itemprop="image" class="schema">';
						}

						if(!isset($data['cashout_time'])){
							redis_add_ulist('update_content',$data['_id']);
						}

						print view_content($data);

						print '<div class="page comments" id="comments">
			<div class="actions"><div class="reply reply-action content-reply unselectable">'.$l10n['media']['comments-button'].'</div></div>
			<div class="subtitle">'.$l10n['media']['comments-caption'].'</div>
			<hr>';

						$find=array('content'=>(int)$data['_id']);
						$sort=array('sort'=>['sort'=>1],'limit'=>5000);
						$rows=$mongo->executeQuery($config['db_prefix'].'.subcontent',new MongoDB\Driver\Query($find,$sort));
						$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
						foreach($rows as $row){
							print view_subcontent($row);
						}
						print '</div>';
						print '<div class="new-comments"></div>';
					}
				}
			}
			else
			if(''==$path_array[3]){
				if(!isset($account_arr['shares'])){
					redis_add_ulist('update_user',$account_arr['login']);
				}
				$account_arr['nickname']=str_replace('@','',$account_arr['nickname']);
				if($account_arr['nickname']){
					$replace['title']=htmlspecialchars($account_arr['nickname']).' @'.$account_arr['login'].' - '.$replace['title'];
				}
				else{
					$replace['title']='@'.$account_arr['login'].' - '.$replace['title'];
				}

				print user_badge($account_arr);

				print '<div class="page content">
				<h2>'.$l10n['media']['content-caption'].'</h2>
				</div>';
				$find=array('author'=>(int)get_user_id($account_arr['login']),'status'=>0);
				$perpage=50;
				$offset=0;
				$sort=array('_id'=>-1);
				$rows=$mongo->executeQuery($config['db_prefix'].'.content',new MongoDB\Driver\Query($find,['sort'=>$sort,'limit'=>(int)$perpage,'skip'=>(int)$offset]));
				$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
				foreach($rows as $row){
					print preview_content($row);
				}
				print '<div class="page content load-more" data-action="user-content" data-user-login="'.$account_arr['login'].'"><i class="fa fw-fw fa-spinner" aria-hidden="true"></i> '.$l10n['template']['loading'].'&hellip;</div></div>';
			}
		}
	}
}
else
if('publication'==$path_array[2]){
	$replace['title']=htmlspecialchars($l10n['media']['publication-caption']).' - '.$replace['title'];
	print $config['wysiwyg'];
	print '<div class="page content">
	<h1><i class="fas fa-fw fa-plus-circle"></i> '.$l10n['media']['publication-caption'].'</h1>
	<div class="article post-content control">';
	print '<p><input type="text" name="permlink" class="round wide" placeholder="'.$l10n['media']['publication-caption'].'"></p>';
	print '<p><input type="text" name="title" class="round wide" placeholder="'.$l10n['media']['publication-title'].'"></p>';
	print '<p><input type="text" name="foreword" class="round wide" placeholder="'.$l10n['media']['publication-foreword'].'"></p>';
	print '<p><input type="text" name="cover" class="round wide" placeholder="'.$l10n['media']['publication-cover'].'"></p>';
	print '<p><textarea name="content" rows="20" class="round wide" placeholder="'.$l10n['media']['publication-content'].'"></textarea></p>';
	print '<p><input id="upload-file" type="file"><a class="upload-image-action action-button unselectable"><i class="fas fa-fw fa-file-image"></i> '.$l10n['media']['publication-upload-image'].'</a> <a class="wysiwyg-action action-button unselectable"><i class="fas fa-fw fa-pen-square"></i> '.$l10n['media']['publication-wysiwyg'].'</a> <a class="beneficiaries-action action-button unselectable"><i class="fas fa-fw fa-money-bill-wave"></i> '.$l10n['media']['publication-beneficiaries'].'</a></p>';
	print '<div class="add-beneficiaries">
	<h4>'.$l10n['media']['beneficiaries-caption'].'</h4>
	<p>'.$l10n['media']['beneficiaries-descr'].'</p>
	<p class="add-beneficiaries-item"><input type="text" name="account" class="round" placeholder="'.$l10n['media']['beneficiaries-account-login'].'"> <input type="text" name="weight" class="round" placeholder="'.$l10n['media']['beneficiaries-weight'].'"></p>
	<p class="add-beneficiaries-button"><a class="add-beneficiaries-action action-button"><i class="fas fa-fw fa-plus-circle"></i> '.$l10n['media']['beneficiaries-add'].'</a></p></div>';
	print '<hr><p><input type="text" name="tags" class="round wide" placeholder="'.$l10n['media']['publication-tags'].'"></p>';
	print '<p><a class="post-content-action button">'.$l10n['media']['publication-action'].'</a></p>';
	print '<p>'.$l10n['media']['publication-warning'].'</p>';
	print '</div></div>';
}
else
if('profile'==$path_array[2]){
	$replace['title']=htmlspecialchars($l10n['media']['profile-caption']).' - '.$replace['title'];
	print '<div class="page content">
	<h1><i class="fas fa-fw fa-user-circle"></i> '.$l10n['media']['profile-caption'].'</h1>
	<div class="article control">';
	print '<div class="profile-control"></div>';
	print '</div></div>';
}
else
if('tags'==$path_array[2]){
	$replace['title']=$l10n['media']['tags-title'].' - '.$replace['title'];
	if(''==$path_array[3]){
		print '<div class="page content">
	<h1>'.$l10n['media']['tags-popular'].'</h1>
	<div class="article">';
		$cache_name='tags';
		if($buf=$cache->get($cache_name)){
			$tags=json_decode($buf,true);
		}
		else{
			$tags=$api->execute_method('get_trending_tags',array('',1000));
			$cache->set($cache_name,json_encode($tags),5);
		}
		$num=1;
		foreach($tags as $tag){
			print '<p id="'.$num.'">#'.$num.' <a href="/media/tags/'.htmlspecialchars($tag['name']).'/">'.htmlspecialchars($tag['name']).'</a>, '.$l10n['media']['tags-count'].': '.$tag['top_posts'].', '.$l10n['media']['tags-awards'].': '.$tag['total_payouts'].'</p>';
			$num++;
		}
		print '</div>';
	}
	else{
		$tag=urldecode($path_array[3]);
		$tag_id=get_tag_id($tag);
		if($tag_id){
			$replace['title']=htmlspecialchars($tag).' - '.$replace['title'];
			print '<div class="page content">
			<a class="right" href="/media/tags/">&larr; '.$l10n['media']['return'].'</a>
			<h1>'.$l10n['media']['tags-caption'].$tag.$l10n['media']['tags-caption-append'].'</h1>
			</div>';
			$find=array('tag'=>(int)$tag_id);
			$perpage=50;
			$offset=0;
			$sort=array('_id'=>-1);
			$rows=$mongo->executeQuery($config['db_prefix'].'.content_tags',new MongoDB\Driver\Query($find,['sort'=>$sort,'limit'=>(int)$perpage,'skip'=>(int)$offset]));
			$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
			foreach($rows as $row){
				print preview_content_by_id($row['content']);
			}
			print '<div class="page content load-more" data-action="tag-content" data-tag="'.htmlspecialchars($tag).'"><i class="fa fw-fw fa-spinner" aria-hidden="true"></i> '.$l10n['template']['loading'].'&hellip;</div></div>';
		}
	}
}
else
if('feed'==$path_array[2]){
	print '<div class="page content">
	<h1>'.$l10n['media']['feed-caption'].'</h1>';
	if(!$auth){
		print '<p>'.$l10n['media']['feed-need-auth'].'</p>';
		print '</div>';
	}
	else{
		$perpage=30;
		$rows=redis_read_feed($user_arr['_id'],0,$perpage);
		if(0==count($rows)){
			print '<p>'.$l10n['media']['feed-none'].'</p>';
		}
		else{
			foreach($rows as $row){
				print preview_content_by_id($row);
			}
			print '<div class="page content load-more" data-action="feed-content" data-user-login="'.$user_arr['login'].'"><i class="fa fw-fw fa-spinner" aria-hidden="true"></i> '.$l10n['template']['loading'].'&hellip;</div></div>';
		}
		print '</div>';
	}
}
else
if('users'==$path_array[2]){
	$replace['title']=htmlspecialchars($l10n['media']['users-caption']).' - '.$replace['title'];
	if(''==$path_array[3]){
		print '<div class="page content">
		<h1><i class="fas fa-fw fa-users"></i> '.$l10n['media']['users-caption'].'</h1>';
		$find=['status'=>0,'shares'=>['$ne'=>0]];
		$sort=['login'=>1];
		$page=1;
		$users_count=mongo_count('users',$find);
		$perpage=20;
		$pages=ceil($users_count/$perpage);
		if($_GET['page']){
			$page=(int)$_GET['page'];
		}
		if($page<1){
			$page=1;
		}
		if($page>$pages){
			$page=$pages;
		}
		$offset=$perpage*($page - 1);
		$prev_page=$page-1;
		$next_page=$page+1;
		print '<p>'.$l10n['media']['users-count'].': '.$users_count.', '.$l10n['media']['users-current-page'].': '.$page.', '.$l10n['media']['users-pages-count'].': '.$pages.'</p>';
		print '<p>'.$l10n['media']['users-descr'].'</p>';
		print '<hr>';
		print '<div class="pages clearfix">';
		if($prev_page>0){
			print '<a href="?page='.$prev_page.'">&larr; '.$l10n['media']['users-prev-page'].'</a>';
		}
		if($next_page<=$pages){
			print '<a class="right" href="?page='.$next_page.'">'.$l10n['media']['users-next-page'].' &rarr;</a>';
		}
		print '</div>';
		print '</div>';
		$rows=$mongo->executeQuery($config['db_prefix'].'.users',new MongoDB\Driver\Query($find,['sort'=>$sort,'limit'=>(int)$perpage,'skip'=>(int)$offset]));
		$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
		foreach($rows as $row){
			print user_badge($row);
		}
		print '<div class="page content">';
		print '<div class="pages clearfix">';
		if($prev_page>0){
			print '<a href="?page='.$prev_page.'">&larr; '.$l10n['media']['users-prev-page'].'</a>';
		}
		if($next_page<=$pages){
			print '<a class="right" href="?page='.$next_page.'">'.$l10n['media']['users-next-page'].' &rarr;</a>';
		}
		print '</div>';
		print '</div>';
	}
}
else
if(''==$path_array[2]){
	$find=array('status'=>0,'parent'=>['$exists'=>false]);
	$perpage=50;
	$offset=0;
	$sort=array('_id'=>-1);
	$rows=$mongo->executeQuery($config['db_prefix'].'.content',new MongoDB\Driver\Query($find,['sort'=>$sort,'limit'=>(int)$perpage,'skip'=>(int)$offset]));
	$rows->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
	foreach($rows as $row){
		print preview_content($row);
	}
	print '<div class="page content load-more" data-action="new-content"><i class="fa fw-fw fa-spinner" aria-hidden="true"></i> '.$l10n['template']['loading'].'&hellip;</div></div>';
}
$content=ob_get_contents();
ob_end_clean();