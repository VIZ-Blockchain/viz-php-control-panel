<?php
ob_start();
$api=new viz_jsonrpc_web('https://testnet.viz.world/');
if('@'==mb_substr($path_array[1],0,1)){
	if($path_array[2]){
		$author=mb_substr($path_array[1],1);
		$permlink=urldecode($path_array[2]);
		$cache_name=md5($author.$permlink);
		if($buf=$cache->get($cache_name)){
			print $buf;
		}
		else{
			$content=$api->execute_method('get_content',array($author,$permlink,-1));
			if($content['body']){
				$buf='';

				$date=date_parse_from_format('Y-m-d\TH:i:s',$content['created']);
				$content_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
				$json=json_decode($content['json_metadata'],true);
				$content_image=false;
				if(isset($json['image'][0])){
					$content_image=$json['image'][0];
				}
				$replace['title']=htmlspecialchars($content['title']).' - '.$replace['title'];

				$text=text_to_view($content['body'],($json['format']=='markdown'?true:false));
				$replace['description']=mb_substr(strip_tags($text),0,250).'...';
				$replace['description']=str_replace("\n",' ',$replace['description']);
				$replace['description']=str_replace('  ',' ',$replace['description']);

				$replace['head_addon'].='
				<meta property="og:url" content="https://viz.world/@'.$content['author'].'/'.$content['permlink'].'/" />
				<meta name="og:title" content="'.htmlspecialchars($content['title']).'" />
				<meta name="twitter:title" content="'.htmlspecialchars($content['title']).'" />
				<meta name="twitter:card" content="summary_large_image" />';
				if($content_image){
					if(!preg_match('~^https://~iUs',$content_image)){
						$content_image='https://i.goldvoice.club/0x0/'.$content_image;
					}
					$replace['head_addon'].='
<link rel="image_src" href="'.$content_image.'" />
<meta property="og:image" content="'.$content_image.'" />
<meta name="twitter:image" content="'.$content_image.'" />';
					$buf.='<img src="'.$content_image.'" itemprop="image" class="schema">';
				}

				$buf.='<div class="page content">
				<h1>'.htmlspecialchars($content['title']).'</h1>
				<div class="info">
					<div class="author"><a href="/@'.$content['author'].'/" class="avatar" style=""></a><a href="/@'.$content['author'].'/">@'.$content['author'].'</a></div>
					<div class="timestamp" data-timestamp="'.$content_time.'">'.date('d.m.Y H:i:s',$content_time).'</div>
				</div>
				<div class="article">';
				$buf.=$text;
				$buf.='</b></strong></em></i>';//fix styles

				/*$buf.='<pre>';
				$buf.=print_r($content,true);
				$buf.='</pre>';*/
				$buf.='
				</div>';
				$tags=$json['tags'];
				if($tags){
					$tags_list=array();
					foreach($tags as $tag){
						$tags_list[]='<a href="/tags/'.htmlspecialchars($tag).'/">'.htmlspecialchars($tag).'</a>';
					}
					$buf.='<div class="tags">'.implode($tags_list).'</div>';
				}
				$buf.='<hr>
				<div class="addon">
					<div class="right"><div class="comments">'.$content['children'].'<a href="#comments" class="icon"><i class="far fa-comment"></i></a></div></div>
					<a class="award"></a>
					<div class="votes_count">Получено '.$content['active_votes_count'].' голосов</div>
					<!--<div class="flag right"></div>-->
				</div>
				</div>';


				$buf.='<div class="page comments" id="comments">
<div class="actions"><div class="reply reply-action post-reply">Оставить комментарий</div></div>
<div class="subtitle">Комментарии</div>
<hr>';

				$comment_tree=new comments_tree();
				$replies=$api->execute_method('get_all_content_replies',array($author,$permlink,-1));
				$comment_arr=array();
				$i=1;
				foreach($replies as $reply){
					$comment_arr[$i]=new comments_tree($reply);
					$comment_tree->add($comment_arr[$i],true);
					$i++;
				}
				$buf.=$comment_tree->tree();
				$buf.='</div>';
				$buf.='test cache: '.time();
				$cache->set($cache_name,$buf,5);
				print $buf;
			}
		}
	}
}
if('tags'==$path_array[1]){
	if(''==$path_array[2]){
		$cache_name='tags';
		print '<div class="page content">
	<h1>Популярные тэги</h1>
	<div class="article">';
		if($buf=$cache->get($cache_name)){
			print $buf;
		}
		else{
			$buf='';
			$tags=$api->execute_method('get_trending_tags',array('',1000));
			$num=1;
			foreach($tags as $tag){
				$buf.='<p id="'.$num.'">#'.$num.' <a href="/tags/'.htmlspecialchars($tag['name']).'/">'.htmlspecialchars($tag['name']).'</a>, количество отметок: '.$tag['top_posts'].', суммарная награда: '.$tag['total_payouts'].'</p>';
				$num++;
			}
			$buf.='test cache: '.time();
			$cache->set($cache_name,$buf,5);
			print $buf;
		}
		print '</div>';
	}
	else{
		$tag=urldecode($path_array[2]);
		$cache_name='tags-'.md5($tag.urldecode($_GET['start_permlink']).urldecode($_GET['start_author']));
		if($buf=$cache->get($cache_name)){
			print $buf;
		}
		else{
			$content_arr=array();
			if(isset($_GET['start_author']) && isset($_GET['start_permlink'])){
				$content_arr=$api->execute_method(
					'get_discussions_by_created',
					array(
						array(
						'select_tags'=>array($tag),
						'limit'=>25,
						'start_author'=>urldecode($_GET['start_author']),
						'start_permlink'=>urldecode($_GET['start_permlink']),
						'raw'=>1
						)
					)
				);
			}
			else{
				$content_arr=$api->execute_method(
					'get_discussions_by_created',
					array(
						array(
						'select_tags'=>array($tag),
						'limit'=>25,
						'raw'=>1
						)
					)
				);
			}
			if($content_arr){
				$buf='';
				$buf.='
				<div class="page content">
					<a class="right" href="/tags/">&larr; Вернуться к тэгам</a>
					<h1>Тэг: '.htmlspecialchars($tag).'</h1>
				</div>';
				$last_author='';
				$last_permlink='';
				foreach($content_arr as $k=>$content){
					$last_author=$content['author'];
					$last_permlink=$content['permlink'];
					$date=date_parse_from_format('Y-m-d\TH:i:s',$content['created']);
					$content_time=mktime($date['hour'],$date['minute'],$date['second'],$date['month'],$date['day'],$date['year']);
					$json=json_decode($content['json_metadata'],true);

					$cover=false;
					if($json['image'][0]){
						$cover=$json['image'][0];
					}
					//$buf.=print_r($json['image'],true);
					$preview_text=mb_substr($content['body'],0,1024);
					$preview_text=str_replace('<br>',"\n",$preview_text);
					$preview_text=str_replace('<hr>',"\n",$preview_text);
					$preview_text=htmlspecialchars_decode($preview_text);
					$preview_text=str_replace('&nbsp;',' ',$preview_text);
					$preview_text=str_replace('<br />',"\n",$preview_text);
					$preview_text=str_replace('<br/>',"\n",$preview_text);
					$preview_text=str_replace('<p>',"\n",$preview_text);
					$preview_text=str_replace('</p>',"\n",$preview_text);
					$preview_text=mb_ereg_replace("\r",'',$preview_text);
					$preview_text=mb_ereg_replace("\t",'',$preview_text);
					$preview_text=str_replace('  ',' ',$preview_text);
					$preview_text=str_replace("\n\n","\n",$preview_text);
					$preview_text=strip_tags($preview_text);
					$preview_text=trim($preview_text,"\r\n\t ");
					$preview_text_arr=explode("\n",$preview_text);
					$preview_text_final=$preview_text_arr[0];
					if(mb_strlen($preview_text_final)<250){
						if($preview_text_arr[1]){
							$preview_text_final.='</p><p>'.(mb_strlen($preview_text_arr[1])>250?mb_substr($preview_text_arr[1],0,255,'utf-8').'&hellip;':$preview_text_arr[1]);
						}
					}
					if(mb_strlen($preview_text_final)<250){
						if($preview_text_arr[2]){
							$preview_text_final.='</p><p>'.(mb_strlen($preview_text_arr[2])>250?mb_substr($preview_text_arr[2],0,255,'utf-8').'&hellip;':$preview_text_arr[2]);
						}
					}
					else{
						$preview_text_final=mb_substr($preview_text_final,0,255,'utf-8').'&hellip;';
					}
					if($preview_text_final){
						$preview_text_final='<p>'.$preview_text_final.'</p>';
					}

					$buf.='
					<div class="page preview">
						<a href="/@'.$content['author'].'/'.htmlspecialchars($content['permlink']).'/" class="subtitle">'.htmlspecialchars($content['title']).'</a>';
					if($cover){
						$buf.='<div class="cover"><img src="https://i.goldvoice.club/0x0/'.htmlspecialchars($cover).'" alt=""></div>';
					}
					$buf.='
						<div class="article'.($cover?' cover-exist clearfix':'').'">';
					$buf.=$preview_text_final;
					$buf.='</div>';
					$tags=$json['tags'];
					if($tags){
						$tags_list=array();
						foreach($tags as $tag){
							$tags_list[]='<a href="/tags/'.htmlspecialchars($tag).'/">'.htmlspecialchars($tag).'</a>';
						}
						$buf.='<div class="tags">'.implode($tags_list).'</div>';
					}
					$buf.='<div class="info">
						<div class="author"><a href="/@'.$content['author'].'/" class="avatar" style=""></a><a href="/@'.$content['author'].'/">@'.$content['author'].'</a></div>
						<div class="timestamp" data-timestamp="'.$content_time.'">'.date('d.m.Y H:i:s',$content_time).'</div>
						<div class="right">
							<a class="award"></a>
							<a class="flag"></a>
							<div class="votes_count">'.$content['active_votes_count'].' голосов</div>
							<div class="comments">'.$content['children'].'<a href="/@'.$content['author'].'/'.htmlspecialchars($content['permlink']).'/#comments" class="icon"><i class="far fa-comment"></i></a></div>
						</div>
					</div>
				</div>';
				}
				if($last_author){
					$buf.='<div class="page">';
					$buf.='<a class="load_more" href="?start_author='.$last_author.'&start_permlink='.htmlspecialchars($last_permlink).'">Загрузить еще&hellip;</a>';
					$buf.='</div>';
				}
				$buf.='test cache: '.time();
				$cache->set($cache_name,$buf,5);
				print $buf;
			}
		}
	}
}
if(''==$path_array[1]){
	//API examples
	print '<div class="page content">
	<h1>API get_dynamic_global_properties</h1>
	<div class="article">';
	print '<pre>';
	print_r($api->execute_method('get_dynamic_global_properties'));
	print '</pre>';
	print '
	</div>
	</div>';

	print '<div class="page content">
	<h1>API get_account_history</h1>
	<div class="article">';
	print '<pre>';
	print_r($api->execute_method('get_account_history',array('viz','0','0')));
	print '</pre>';
	print '
	</div>
	</div>';

	print '<div class="page content">
	<h1>API get_ops_in_block</h1>
	<div class="article">';
	print '<pre>';
	print_r($api->execute_method('get_ops_in_block',array('13',true)));
	print '</pre>';
	print '
	</div>
	</div>';

	print '<div class="page content">
	<h1>API get_discussions_by_created</h1>
	<div class="article">';
	print '<pre>';
	print_r($api->execute_method('get_discussions_by_created',array(array('limit'=>10,'raw'=>1))));
	print '</pre>';
	print '
	</div>
	</div>';

	print '<div class="page content">
	<h1>API get_content</h1>
	<div class="article">';
	print '<pre>';
	print_r($api->execute_method('get_content',array('viz','permlink🍪',-1)));
	print '</pre>';
	print '
	</div>
	</div>';
}
$content=ob_get_contents();
ob_end_clean();