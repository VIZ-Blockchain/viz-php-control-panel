<?php
$replace['title']='VIZ.World';
$replace['description']='';
$replace['head_addon']='';
$replace['header_menu']='';
$replace['menu']='';
$replace['script_change_time']=filemtime($site_root.'/js/app.js');
$replace['css_change_time']=filemtime($site_root.'/css/app.css');

$api_ws_arr=array(
	//'https://api.viz.blckchnd.com/',
	'https://testnet.viz.world/',
	//'https://ws.viz.ropox.tools/',
);
$api=new viz_jsonrpc_web($api_ws_arr[array_rand($api_ws_arr)]);

$currencies_arr=array(
	'SHARES'=>1,
	'VIZ'=>2
);
$currencies_id_arr=array(
	1=>'SHARES',
	2=>'VIZ'
);

$users_arr=array();
function get_user_id($login){
	global $users_arr,$api,$mongo,$config;
	if(!isset($users_arr[$login])){
		$rows=$mongo->executeQuery($config['db_prefix'].'.users',new MongoDB\Driver\Query(['login'=>$login],['limit'=>1]));
		$key=false;
		foreach($rows as $row){
			$key=(int)$row->_id;
		}
		if($key){
			$users_arr[$login]=$key;
		}
		else{
			$check_user=$api->execute_method('get_accounts',array(array($login)))[0];
			if($check_user['id']){
				$bulk=new MongoDB\Driver\BulkWrite;
				$bulk->insert(['_id'=>$check_user['id'],'login'=>$check_user['name']]);
				$mongo->executeBulkWrite($config['db_prefix'].'.users',$bulk);
				redis_add_ulist('update_user',$check_user['name']);
				$users_arr[$check_user['name']]=$check_user['id'];
			}
			else{
				return false;
			}
		}
	}
	return $users_arr[$login];
}
function mongo_find($collection,$find){
	global $mongo,$config;
	$rows=$mongo->executeQuery($config['db_prefix'].'.'.$collection,new MongoDB\Driver\Query($find,['limit'=>1]));
	foreach($rows as $row){
		return $row->toArray();
	}
	return false;
}
function mongo_exist($collection,$find){
	global $mongo,$config;
	$rows=$mongo->executeQuery($config['db_prefix'].'.'.$collection,new MongoDB\Driver\Query($find,['limit'=>1]));
	foreach($rows as $row){
		return true;
	}
	return false;
}
function mongo_count($collection,$find=array()){
	global $mongo,$config;
	$rows=$mongo->executeCommand($config['db_prefix'],new MongoDB\Driver\Command(['count'=>$collection,'query'=>$find]));
	foreach($rows as $row){
		return $row->n;
	}
	return false;
}
function get_user_login($id){
	global $users_arr,$mongo,$config;
	$key=array_search($id,$users_arr);
	if(false===$key){
		$rows=$mongo->executeQuery($config['db_prefix'].'.users',new MongoDB\Driver\Query(['_id'=>(int)$id],['limit'=>1]));
		foreach($rows as $row){
			$key=$row->login;
			if($key){
				$users_arr[$key]=(int)$id;
			}
			else{
				return false;
			}
		}
	}
	return $key;
}
function get_user_link($user_1,$user_2,$what=1){
	global $mongo,$config;
	$rows=$mongo->executeQuery($config['db_prefix'].'.users_links',new MongoDB\Driver\Query(['user_1'=>(int)$user_1,'user_2'=>(int)$user_2,'what'=>(int)$what],['limit'=>1]));
	foreach($rows as $row){
		return $row->toArray();
	}
	return false;
	//return $db->sql_row("SELECT `id`,`mutually`,`value` FROM `users_links` WHERE `user_1`='".(int)$user_1."' AND `user_2`='".(int)$user_2."' AND `value`='".(int)$what."' LIMIT 1");
}
function redis_add_ulist($name,$value){//unique list in set
	global $redis;
	if(!$redis->sismember($name.':ulist',$value)){
		$redis->sadd($name.':ulist',$value);
	}
}
function redis_get_ulist($name){
	global $redis;
	return $redis->spop($name.':ulist');
}
function mongo_prepare($text){
	return str_replace(array('\\',"\0","\n","\r","'",'"',"\x1a"),array('\\\\','\\0','\\n','\\r',"\\'",'\\"','\\Z'),$text);
}
function mongo_counter($collection_name,$increase=false){
	global $mongo,$config;
	$rows=$mongo->executeQuery($config['db_prefix'].'.auto_increment',new MongoDB\Driver\Query(['_id'=>$collection_name]));
	$count=0;
	foreach($rows as $row){
		if($increase){
			$bulk=new MongoDB\Driver\BulkWrite;
			$bulk->update(['_id'=>$collection_name],['$inc'=>['count'=>1]]);
			$mongo->executeBulkWrite($config['db_prefix'].'.auto_increment',$bulk);
			return ++$row->count;
		}
		else{
			return $row->count;
		}
		$count++;
	}
	if(0==$count){
		$bulk=new MongoDB\Driver\BulkWrite;
		$bulk->insert(['_id'=>$collection_name,'count'=>1]);
		$mongo->executeBulkWrite($config['db_prefix'].'.auto_increment',$bulk);
		return 1;
	}
}
function mongo_counter_del($collection_name){
	global $mongo,$config;
	$bulk=new MongoDB\Driver\BulkWrite;
	$bulk->delete(['_id'=>$collection_name]);
	$mongo->executeBulkWrite($config['db_prefix'].'.auto_increment',$bulk);
	return true;
}
function mongo_counter_set($collection_name,$count){
	global $mongo,$config;
	$bulk=new MongoDB\Driver\BulkWrite;
	$bulk->update(['_id'=>$collection_name],['$set'=>['count'=>(int)$count]]);
	$mongo->executeBulkWrite($config['db_prefix'].'.auto_increment',$bulk);
	return true;
}

// looking html and try close opened tags
function repair_html_tags($html){
	$tags_counter=array();
	preg_match_all('~<(.*)>~iUs',$html,$matches);
	foreach($matches[1] as $k=>$v){
		$v=trim($v," \r\n\t");
		$ignore=false;
		if('/'==mb_substr($v,mb_strlen($v)-1)){
			$ignore=true;
		}
		if(!$ignore){
			$v_arr=explode(' ',$v);
			if('/'==$v_arr[0][0]){
				$tag_buf=trim($v_arr[0]," \r\n\t/");
				if(!isset($tags_counter[$tag_buf])){
					$tags_counter[$tag_buf]=0;
				}
				$tags_counter[$tag_buf]--;
			}
			else{
				$tag_buf=trim($v_arr[0]," \r\n\t");
				if(!isset($tags_counter[$tag_buf])){
					$tags_counter[$tag_buf]=0;
				}
				$tags_counter[$tag_buf]++;
			}
		}
	}
	unset($tags_counter['img']);
	unset($tags_counter['br']);
	unset($tags_counter['hr']);
	unset($tags_counter['input']);
	foreach($tags_counter as $k=>$v){
		$v=(int)$v;
		if(0>$v){
			for($i=$v;$i<0;$i++){
				$html='<'.$k.'>'.$html;
			}
		}
		if(0<$v){
			for($i=$v;$i>0;$i--){
				$html.='</'.$k.'>';
			}
		}
	}
	return $html;
}
// looking html and remove tag
function clear_html_tag($html,$tag){
	preg_match_all('~<'.$tag.'(.*)>~iUs',$html,$matches);
	foreach($matches[0] as $k=>$v){
		$html=str_replace($v,'',$html);
	}
	return $html;
}
// looking html and clear all tags, styles, classes by rules
function clear_html_tags($text){
	$allowed_attr_arr=array('href','target','src','alt','width','style','id','class','colspan','rowspan');
	$allowed_style_arr=array('text-align','float','text-indent','clear','margin-left','margin-right','margin-top','padding-left','margin-bottom','display','list-style-type','text-decoration','color','font-style','font-size');
	$allowed_class_arr=array('spoiler','pull-left','pull-right','language-markup','language-javascript','language-css','language-php','language-ruby','language-python','language-java','language-c','language-csharp','language-cpp','text-justify');
	$denied_tags=array('script','style');
	$denied_href_arr=array('javascript:');
	preg_match_all('~<(.[^>]*)>~iUs',$text,$matches);
	foreach($matches[1] as $match_k=>$match){
		$full_match=$matches[0][$match_k];
		$closing=false;
		$tag_name=$match;
		if(false!==strpos($match,' ')){
			$tag_name=substr($match,0,strpos($match,' '));
		}
		if('/'==$tag_name[0]){
			$closing=true;
			$tag_name=substr($tag_name,1);
		}
		if(in_array($tag_name,$denied_tags)){
			$full_match='';
		}
		else{
			preg_match_all('~(.[^= ]*)="(.*)"~iUs',$match,$attr_arr);
			foreach($attr_arr[1] as $attr_k=>$attr){
				$attr=trim($attr);
				if(!in_array($attr,$allowed_attr_arr)){
					$change=true;
					if('iframe'==$tag_name){
						if('height'==$attr){
							$change=false;
						}
						if('frameborder'==$attr){
							$change=false;
						}
						if('allowfullscreen'==$attr){
							$change=false;
						}
					}
					if($change){
						$full_match=str_replace($attr_arr[0][$attr_k],'',$full_match);
					}
				}
				if('style'==$attr){
					$full_styles=$attr_arr[2][$attr_k];
					$styles_arr=explode(';',$full_styles);
					foreach($styles_arr as $style_k=>$style){
						if($style){
							$style_arr=explode(':',$style);
							$style_arr[0]=trim($style_arr[0]);
							$style_arr[1]=trim($style_arr[1]);
							if(!in_array($style_arr[0],$allowed_style_arr)){
								unset($styles_arr[$style_k]);
							}
						}
					}
					$full_styles=implode(';',$styles_arr);
					$full_match=str_replace($attr_arr[2][$attr_k],$full_styles,$full_match);
				}
				if('class'==$attr){
					$full_classes=$attr_arr[2][$attr_k];
					$classes_arr=explode(' ',$full_classes);
					foreach($classes_arr as $class_k=>$class){
						if($class){
							$class=trim($class);
							if(!in_array($class,$allowed_class_arr)){
								unset($classes_arr[$class_k]);
							}
						}
					}
					$full_classes=implode(' ',$classes_arr);
					if($attr_arr[2][$attr_k]!=$full_classes){
						$full_match=str_replace($attr_arr[2][$attr_k],$full_classes,$full_match);
					}
				}
				if('href'==$attr){
					$full_link=$attr_arr[2][$attr_k];
					$styles_arr=explode(';',$full_styles);
					foreach($denied_href_arr as $denied_href){
						if(strpos($full_link,$denied_href)!==false){
							$full_match=str_replace($attr_arr[2][$attr_k],str_replace($denied_href,'',$full_link),$full_match);
						}
					}
				}
			}
			preg_match_all('~(.[^= ]*)=""~iUs',$full_match,$attr_arr);
			foreach($attr_arr[0] as $free_attr){
				$full_match=str_replace($free_attr,'',$full_match);
			}
		}
		$text=str_replace($matches[0][$match_k],$full_match,$text);
	}
	return $text;
}
function text_to_view($text,$set_markdown=false){
	global $parsedownextra;

	$replace_arr=array();
	$replace_num=1;
	$text=clear_html_tags($text);

	$markdown=true;
	if(false!==strpos($text,'<html>')){
		$markdown=false;
	}
	elseif(false!==strpos($text,'</p>')){
		$markdown=false;
	}
	elseif(false!==strpos($text,'</li>')){
		$markdown=false;
	}
	elseif(false!==strpos($text,'</code>')){
		$markdown=false;
	}
	if($set_markdown){
		$markdown=true;
	}
	/*
	//fix absolute links
	$text=str_replace('https://golos.io/','https://goldvoice.club/',$text);
	$text=str_replace('https://www.golos.io/','https://goldvoice.club/',$text);
	$text=str_replace('https://golos.blog/','https://goldvoice.club/',$text);
	$text=str_replace('https://www.golos.blog/','https://goldvoice.club/',$text);
	*/

	/*
	//fix absolute image gateways links
	$text=preg_replace('~https:\/\/imgp\.golos\.io\/([x0-9]*)\/~is','',$text);
	$text=preg_replace('~https:\/\/steemitimages\.com\/([x0-9]*)\/~is','',$text);
	*/

	/* convert tags to replacer arr */
	preg_match_all('~<img (.*)>~iUs',$text,$matches);
	foreach($matches[0] as $k=>$match){
		preg_match('~src=(\"|\')(.*)(\"|\')~iUs',$matches[1][$k],$img_arr);
		if($img_arr[2]){
			//$new_img='<img src="https://imgp.golos.io/0x0/'.$img_arr[2].'">';
			$new_img_src='src="https://i.goldvoice.club/0x0/'.$img_arr[2].'"';
			if(preg_match('~^https://goldvoice\.club/~iUs',$img_arr[2])){
				$new_img_src='src="'.$img_arr[2].'"';
			}
			if(preg_match('~^https://i.goldvoice\.club/~iUs',$img_arr[2])){
				$new_img_src='src="'.$img_arr[2].'"';
			}
			if(preg_match('~^https://imgp\.golos\.io/~iUs',$img_arr[2])){
				$new_img_src='src="'.$img_arr[2].'"';
			}
			if(preg_match('~^https://images\.golos\.io/~iUs',$img_arr[2])){
				$new_img_src='src="'.$img_arr[2].'"';
			}
			$new_img=str_replace($img_arr[0],$new_img_src,$match);
			$replace_arr[$replace_num]=$new_img;
			$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
	}

	preg_match_all('~<a(.*)>(.*)</a>~iUs',$text,$matches);
	foreach($matches[0] as $k=>$match){
		$replace_arr[$replace_num]=$matches[0][$k];
		$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
		$replace_num++;
	}
	/* convert &#0000; to links */
	preg_match_all('~&#([0-9a-z]*);~ius',$text,$matches);
	foreach($matches[0] as $k=>$match){
		if($matches[1][$k]){
			$replace_arr[$replace_num]=$matches[0][$k];
			$text=str_replace($match,'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
	}
	/* convert :#hex; to replacer */
	preg_match_all('~(:|: )#([0-9abcdef]*);~ius',$text,$matches);
	foreach($matches[0] as $k=>$match){
		if($matches[2][$k]){
			$replace_arr[$replace_num]='#'.$matches[2][$k];
			$text=str_replace($match,':{replacerQarrQ'.$replace_num.'};',$text);
			$replace_num++;
		}
	}
	/* convert :#hex" to replacer */
	preg_match_all('~(:|: )#([0-9abcdef]*)"~ius',$text,$matches);
	foreach($matches[0] as $k=>$match){
		if($matches[2][$k]){
			$replace_arr[$replace_num]='#'.$matches[2][$k].'"';
			$text=str_replace($match,':{replacerQarrQ'.$replace_num.'};',$text);
			$replace_num++;
		}
	}

	if($markdown){
		$text=str_replace('<p><center>','<center>',$text);
		$text=str_replace('</center><p>','</center>',$text);
		$text=str_replace("</td>\n",'</td>',$text);
		$text=str_replace("</th>\n",'</th>',$text);
		$text=preg_replace("~</th>([ ]*)<th>~iUs",'</th><th>',$text);
		$text=preg_replace("~</td>([ ]*)<td>~iUs",'</td><td>',$text);
		$test_text=$parsedownextra->text($text);
		if(strlen($test_text)/strlen($text)>0.5){
			$text=$test_text;
		}
		preg_match_all('~\<p\>(.*)\<\/p\>~iUs',$text,$matches);
		foreach($matches[1] as $k=>$v){
			$text=str_replace($matches[1][$k],str_replace("\n","<br>",$matches[1][$k]),$text);
		}
		$text=str_replace("<p>\n",'<p>',$text);
		$text=str_replace("<li>\n",'<li>',$text);
		$text=str_replace("\n</li>",'</li>',$text);
		preg_match_all('~\<li\>\<p\>(.*)\<\/p\>\<\/li\>~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($match,'<li>'.$matches[1][$k].'</li>',$text);
		}
		/* additional converts im markdown */
		preg_match_all('~\!\[\]\((.[^\n\[\]]*)\)~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$buf_html='<img src="'.htmlspecialchars($matches[1][$k]).'" alt="">';
			$replace_arr[$replace_num]=$buf_html;
			$text=str_replace($match,'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
		preg_match_all('~\!\[(.[^\n\[\]]*)\]\((.[^\n\[\]]*)\)~is',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$buf_html='<img src="'.htmlspecialchars($matches[2][$k]).'" alt="'.htmlspecialchars($matches[1][$k]).'">';
			$replace_arr[$replace_num]=$buf_html;
			$text=str_replace($match,'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
		preg_match_all('~\[(.[^\n\[\]\(\)]*)\]\((.[^\n\[\]\(\)]*)\)~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$buf_html='<a href="'.htmlspecialchars($matches[2][$k]).'" target="_blank">'.$matches[1][$k].'</a>';
			$replace_arr[$replace_num]=$buf_html;
			$text=str_replace($match,'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
		preg_match_all('~\[([.]*)\]\(\)~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],$matches[1][$k],$text);
		}
		preg_match_all('~\[\]\(([.]*)\)~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],$matches[1][$k],$text);
		}
		preg_match_all('~\[\]\(\)~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'',$text);
		}
		preg_match_all('~\!\[\]\(\)~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'',$text);
		}
		/* change auto links from markdown to images */
		preg_match_all('~<a href="(.*)">(.*)</a>~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			if($matches[1][$k]==$matches[2][$k]){
				if(preg_match('~\.(jpg|jpeg|gif|png|psd|tiff|webp)$~is',$matches[1][$k],$link_arr)){
					$image_text='<img src="https://i.goldvoice.club/0x0/'.$matches[1][$k].'" class="convert-link-image" alt="">';
					if(preg_match('~^https://~iUs',$matches[1][$k])){
						$image_text='<img src="https://i.goldvoice.club/0x0/'.$matches[1][$k].'" class="convert-link-image" alt="">';
					}
					$replace_arr[$replace_num]=$image_text;
					$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
					$replace_num++;
				}
				else{//parsedownextra make a links, convert it back for use link rules below
					$text=str_replace($matches[0][$k],$matches[1][$k],$text);
				}
			}
		}
		/* remove steem/golos images gates */
		$text=preg_replace('~https:\/\/imgp\.golos\.io\/([tx0-9]*)\/~is','',$text);
		$text=preg_replace('~https:\/\/steemitimages\.com\/([tx0-9]*)\/~is','',$text);
		/* convert tags to replacer arr */
		preg_match_all('~<img (.*)>~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			preg_match('~src=(\"|\')(.*)(\"|\')~iUs',$matches[1][$k],$img_arr);
			if($img_arr[2]){
				$new_img_src='src="https://i.goldvoice.club/0x0/'.$img_arr[2].'"';
				if(preg_match('~^https://goldvoice\.club/~iUs',$img_arr[2])){
					$new_img_src='src="'.$img_arr[2].'"';
				}
				if(preg_match('~^https://i.goldvoice\.club/~iUs',$img_arr[2])){
					$new_img_src='src="'.$img_arr[2].'"';
				}
				if(preg_match('~^https://imgp\.golos\.io/~iUs',$img_arr[2])){
					$new_img_src='src="'.$img_arr[2].'"';
				}
				if(preg_match('~^https://images\.golos\.io/~iUs',$img_arr[2])){
					$new_img_src='src="'.$img_arr[2].'"';
				}
				$new_img=str_replace($img_arr[0],$new_img_src,$match);
				$replace_arr[$replace_num]=$new_img;
				$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
				$replace_num++;
			}
		}
		preg_match_all('~<a(.*)>(.*)</a>~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$replace_arr[$replace_num]=$matches[0][$k];
			$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
		preg_match_all('~<iframe (.*)>~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$replace_arr[$replace_num]=$matches[0][$k];
			$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
	}

	//~~~ embed:FXvxiCDl7p0 youtube ~~~
	preg_match_all('|\~\~\~ embed\:(.*) \~\~\~|iUs',$text,$matches);
	foreach($matches[1] as $k=>$match){
		if($match){
			if(false!==strpos($match,'youtube')){
				preg_match('~([a-zA-Z0-9_\-]*) youtube~is',$match,$link_arr);
				$youtube_code=$link_arr[1];
				$youtube_image='https://i.ytimg.com/vi/'.$youtube_code.'/sddefault.jpg';
				$youtube_text='<div class="youtube_wrapper" data-youtube-code="'.$youtube_code.'"><iframe src="https://www.youtube.com/embed/'.$youtube_code.'" width="768" height="576" frameborder="0" allowfullscreen="allowfullscreen"></iframe></div>';
				$replace_arr[$replace_num]=$youtube_text;
				$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
				$replace_num++;
				unset($youtube_code);
				unset($youtube_image);
				unset($youtube_text);
			}
		}
	}
	preg_match_all('~<code>(.*)</code>~iUs',$text,$matches);
	foreach($matches[0] as $k=>$match){
		$replace_arr[$replace_num]=$matches[0][$k];
		$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
		$replace_num++;
	}
	preg_match_all('~<a (.*)>(.*)</a>~iUs',$text,$matches);
	foreach($matches[0] as $k=>$match){
		$replace_arr[$replace_num]=$matches[0][$k];
		$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
		$replace_num++;
	}
	preg_match_all('~<iframe (.*)>(.*)</iframe>~iUs',$text,$matches);
	foreach($matches[0] as $k=>$match){
		$replace_arr[$replace_num]=$matches[0][$k];
		$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
		$replace_num++;
	}
	/* convert links to youtube/images */
	preg_match_all('~(https|http)://([0-9a-zA-Z:;_\#\-+,!\@=&\%\.\/\?]*)~is',$text,$matches);
	usort($matches[0],'sort_by_value_length');
	foreach($matches[0] as $k=>$match){
		if(false!==strpos($matches[0][$k],'//coub.com/view/')){
			preg_match('~/view/([a-zA-Z0-9_\-]*)~is',$matches[0][$k],$link_arr);
			$coub_code=$link_arr[1];
			$coub_text='<div class="coub_wrapper" data-coub-code="'.$youtube_code.'"><iframe src="//coub.com/embed/'.$coub_code.'?muted=false&autostart=false&originalSize=false&startWithHD=false" allowfullscreen="true" frameborder="0" width="480" height="270"></iframe></div>';
			$replace_arr[$replace_num]=$coub_text;
			$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
			unset($coub_code);
			unset($coub_text);
		}
		elseif(false!==strpos($matches[0][$k],'youtube.com/embed/')){
			preg_match('~/embed/([a-zA-Z0-9_\-]*)~is',$matches[0][$k],$link_arr);
			$youtube_code=$link_arr[1];
			$youtube_image='https://i.ytimg.com/vi/'.$youtube_code.'/sddefault.jpg';
			$youtube_text='<div class="youtube_wrapper" data-youtube-code="'.$youtube_code.'"><iframe src="https://www.youtube.com/embed/'.$youtube_code.'" width="768" height="576" frameborder="0" allowfullscreen="allowfullscreen"></iframe></div>';
			$replace_arr[$replace_num]=$youtube_text;
			$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
			unset($youtube_code);
			unset($youtube_image);
			unset($youtube_text);
		}
		elseif(false!==strpos($matches[0][$k],'youtube.com')){
			preg_match('~v=([a-zA-Z0-9_\-]*)~is',$matches[0][$k],$link_arr);
			$youtube_code=$link_arr[1];
			if(''!=$youtube_code){
				$youtube_image='https://i.ytimg.com/vi/'.$youtube_code.'/sddefault.jpg';
				$youtube_text='<div class="youtube_wrapper" data-youtube-code="'.$youtube_code.'"><iframe src="https://www.youtube.com/embed/'.$youtube_code.'" width="768" height="576" frameborder="0" allowfullscreen="allowfullscreen"></iframe></div>';
				$replace_arr[$replace_num]=$youtube_text;
				$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
				$replace_num++;
				unset($youtube_image);
				unset($youtube_text);
				unset($youtube_code);
			}
			else{
				$link_text='<a href="'.$matches[0][$k].'" class="convert-link youtube">'.$matches[0][$k].'</a>';
				$replace_arr[$replace_num]=$link_text;
				$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
				$replace_num++;
			}
		}
		elseif(false!==strpos($matches[0][$k],'youtu.be')){//https://youtu.be/NIAjU3Pr7Cg
			preg_match('~\.be\/([a-zA-Z0-9_\-]*)~is',$matches[0][$k],$link_arr);
			$youtube_code=$link_arr[1];
			if(''!=$youtube_code){
				$youtube_image='https://i.ytimg.com/vi/'.$youtube_code.'/sddefault.jpg';
				$youtube_text='<div class="youtube_wrapper" data-youtube-code="'.$youtube_code.'"><iframe src="https://www.youtube.com/embed/'.$youtube_code.'" width="768px" height="576px" frameborder="0" allowfullscreen="allowfullscreen"></iframe></div>';
				$replace_arr[$replace_num]=$youtube_text;
				$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
				$replace_num++;
				unset($youtube_code);
				unset($youtube_image);
				unset($youtube_text);
			}
		}
		else{
			if(preg_match('~\.(jpg|jpeg|gif|png|psd|tiff|webp)$~is',$matches[0][$k],$link_arr)){
				$image_text='<img src="https://i.goldvoice.club/0x0/'.$matches[0][$k].'" class="convert-link-image" alt="">';
				if(preg_match('~^https://goldvoice\.club/~iUs',$matches[0][$k])){
					$image_text='<img src="'.$matches[0][$k].'" class="convert-link-image" alt="">';
				}
				if(preg_match('~^https://i.goldvoice\.club/~iUs',$img_arr[2])){
					$image_text='<img src="'.$matches[0][$k].'" class="convert-link-image" alt="">';
				}
				if(preg_match('~^https://imgp\.golos\.io/~iUs',$matches[0][$k])){
					$image_text='<img src="'.$matches[0][$k].'" class="convert-link-image" alt="">';
				}
				if(preg_match('~^https://images\.golos\.io/~iUs',$matches[0][$k])){
					$image_text='<img src="'.$matches[0][$k].'" class="convert-link-image" alt="">';
				}
				$replace_arr[$replace_num]=$image_text;
				$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
				$replace_num++;
			}
			elseif(preg_match('~\.(jpg|jpeg|gif|png|psd|tiff|webp)\?(.[^\n ]*)$~is',$matches[0][$k],$link_arr)){
				$image_text='<img src="https://i.goldvoice.club/0x0/'.$matches[0][$k].'" class="convert-link-image" alt="">';
				if(preg_match('~^https://goldvoice\.club/~iUs',$matches[0][$k])){
					$image_text='<img src="'.$matches[0][$k].'" class="convert-link-image" alt="">';
				}
				if(preg_match('~^https://i.goldvoice\.club/~iUs',$matches[0][$k])){
					$image_text='<img src="'.$matches[0][$k].'" class="convert-link-image" alt="">';
				}
				if(preg_match('~^https://imgp\.golos\.io/~iUs',$matches[0][$k])){
					$image_text='<img src="'.$matches[0][$k].'" class="convert-link-image" alt="">';
				}
				if(preg_match('~^https://images\.golos\.io/~iUs',$matches[0][$k])){
					$image_text='<img src="'.$matches[0][$k].'" class="convert-link-image" alt="">';
				}
				$replace_arr[$replace_num]=$image_text;
				$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
				$replace_num++;
			}
			else{
				$link_text='<a href="'.$matches[0][$k].'" class="convert-link">'.$matches[0][$k].'</a>';
				$replace_arr[$replace_num]=$link_text;
				$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
				$replace_num++;
			}
		}
	}

	/* convert #tag to links */
	preg_match_all('~\#([а-яА-ЯёЁa-zA-Z0-9+\.\-\_]*)~ius',$text,$matches);
	usort($matches[0],'sort_by_value_length');
	usort($matches[1],'sort_by_value_length');
	foreach($matches[0] as $k=>$match){
		$match=trim($match,'.');
		$matches[1][$k]=trim($matches[1][$k],'.');
		if($matches[1][$k]){
			$tag_ru=tags_translate(mb_strtolower($matches[1][$k]));
			if($tag_ru!=$matches[1][$k]){
				$tag_ru='ru--'.$tag_ru;
			}
			$replace_arr[$replace_num]='<a href="/tags/'.htmlspecialchars($tag_ru).'/">#'.$matches[1][$k].'</a>';
			$text=str_replace($match,'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
	}

	if($markdown){
		/* strange markdown golos */
		preg_match_all('~\n###### (.*)\n~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$replace_arr[$replace_num]=PHP_EOL.'<h6>'.$matches[1][$k].'</h6>';
			$text=str_replace($matches[0][$k],PHP_EOL.'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
		preg_match_all('~\n##### (.*)\n~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$replace_arr[$replace_num]=PHP_EOL.'<h5>'.$matches[1][$k].'</h5>';
			$text=str_replace($matches[0][$k],PHP_EOL.'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
		preg_match_all('~\n#### (.*)\n~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$replace_arr[$replace_num]=PHP_EOL.'<h4>'.$matches[1][$k].'</h4>';
			$text=str_replace($matches[0][$k],PHP_EOL.'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
		preg_match_all('~\n### (.*)\n~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$replace_arr[$replace_num]=PHP_EOL.'<h3>'.$matches[1][$k].'</h3>';
			$text=str_replace($matches[0][$k],PHP_EOL.'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
		preg_match_all('~\n## (.*)\n~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$replace_arr[$replace_num]=PHP_EOL.'<h2>'.$matches[1][$k].'</h2>';
			$text=str_replace($matches[0][$k],PHP_EOL.'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
		preg_match_all('~\n# (.*)\n~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$replace_arr[$replace_num]=PHP_EOL.'<h1>'.$matches[1][$k].'</h1>';
			$text=str_replace($matches[0][$k],PHP_EOL.'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
		preg_match_all('~___(.[^\r\n]*)___~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'<em><strong>'.$matches[1][$k].'</strong></em>',$text);
		}
		preg_match_all('~\*\*\*(.[^\r\n]*)\*\*\*~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'<em><strong>'.$matches[1][$k].'</strong></em>',$text);
		}
		preg_match_all('~__(.[^\r\n]*)__~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'<strong>'.$matches[1][$k].'</strong>',$text);
		}
		preg_match_all('~\*\*(.[^\r\n]*)\*\*~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'<strong>'.$matches[1][$k].'</strong>',$text);
		}
		preg_match_all('!~~(.[^\r\n]*)~~!iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'<del>'.$matches[1][$k].'</del>',$text);
		}
		preg_match_all('~\*(.[^\r\n]*)\*~iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'<em>'.$matches[1][$k].'</em>',$text);
		}
		preg_match_all('!```(.*)```!iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'<pre><code>'.$matches[1][$k].'</code></pre>',$text);
		}
		preg_match_all('!``(.*)``!iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'<pre><code>'.$matches[1][$k].'</code></pre>',$text);
		}
		preg_match_all('!`(.[^\r\n]*)`!iUs',$text,$matches);
		foreach($matches[0] as $k=>$match){
			$text=str_replace($matches[0][$k],'<code>'.$matches[1][$k].'</code>',$text);
		}

		$text=str_replace(">\n<",'>!NEW_LINE_BR!<',$text);
		$text=str_replace("\n",'<br>',$text);
		$text=str_replace('>!NEW_LINE_BR!<',">\n<",$text);
		$text=str_replace(' <br>','<br>',$text);
		$text=str_replace('<br> ','<br>',$text);
	}
	/* convert mail@domain to links */
	preg_match_all('~([a-z0-9\.\-\_]*)\@([a-z0-9\.\-\_]*)~is',$text,$matches);
	usort($matches[0],'sort_by_value_length');
	usort($matches[1],'sort_by_value_length');
	foreach($matches[0] as $k=>$match){
		if($matches[1][$k]){
			$replace_arr[$replace_num]='<a href="mailto:'.htmlspecialchars($matches[0][$k]).'">'.$matches[0][$k].'</a>';
			$text=str_replace($match,'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
	}
	/* convert @login to links */
	preg_match_all('~\@([a-z0-9\.\-\_]*)~is',$text,$matches);
	usort($matches[0],'sort_by_value_length');
	usort($matches[1],'sort_by_value_length');
	foreach($matches[0] as $k=>$match){
		if($matches[1][$k]){
			$user_login=trim($matches[1][$k]," \r\n\t.");
			$replace_arr[$replace_num]='<a href="/@'.htmlspecialchars($user_login).'/">@'.$user_login.'</a>';
			$text=str_replace('@'.$user_login,'{replacerQarrQ'.$replace_num.'}',$text);
			$replace_num++;
		}
	}

	preg_match_all('~<code>(.*)</code>~iUs',$text,$matches);
	foreach($matches[1] as $k=>$match){
		$match=str_replace('<','&lt;',$match);
		$match=str_replace('>','&gt;',$match);
		$replace_arr[$replace_num]='<code>'.$match.'</code>';
		$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
		$replace_num++;
	}
	preg_match_all('~<pre(.*)>(.*)</pre>~iUs',$text,$matches);
	foreach($matches[2] as $k=>$match){
		$replace_arr[$replace_num]='<pre'.$matches[1][$k].'>'.$match.'</pre>';
		$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
		$replace_num++;
	}
	preg_match_all('~<table(.*)>(.*)</table>~iUs',$text,$matches);
	foreach($matches[2] as $k=>$match){
		$match=repair_html_tags($match);
		$replace_arr[$replace_num]='<table'.$matches[1][$k].'>'.$match.'</table>';
		$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
		$replace_num++;
	}

	preg_match_all('~<p(.*)>(.*)</p>~iUs',$text,$matches);
	foreach($matches[2] as $k=>$match){
		$match=clear_html_tag($match,'p');
		$match=repair_html_tags($match);
		$text=str_replace($matches[0][$k],'<p'.$matches[1][$k].'>'.$match.'</p>',$text);
	}
	preg_match_all('~<div(.*)>(.*)</div>~iUs',$text,$matches);
	foreach($matches[2] as $k=>$match){
		$match=clear_html_tag($match,'div');
		$match=repair_html_tags($match);
		$replace_arr[$replace_num]='<section'.$matches[1][$k].'>'.$match.'</section>';
		$text=str_replace($matches[0][$k],'{replacerQarrQ'.$replace_num.'}',$text);
		$replace_num++;
	}
	$text=str_replace('</div>','</section>',$text);
	$text=str_replace('<div>','<section>',$text);

	foreach($replace_arr as $k=>$v){
		$text=str_replace('{replacerQarrQ'.$k.'}',$v,$text);
	}

	//need next replacement for recursive expand <a><img></a>
	preg_match_all('~\{replacerQarrQ([0-9]*)\}~iUs',$text,$matches);
	foreach($matches[0] as $k=>$match){
		$text=str_replace('{replacerQarrQ'.$matches[1][$k].'}',$replace_arr[$matches[1][$k]],$text);
	}
	preg_match_all('~\{replacerQarrQ([0-9]*)\}~iUs',$text,$matches);
	foreach($matches[0] as $k=>$match){
		$text=str_replace('{replacerQarrQ'.$matches[1][$k].'}',$replace_arr[$matches[1][$k]],$text);
	}
	preg_match_all('~\{replacerQarrQ([0-9]*)\}~iUs',$text,$matches);
	foreach($matches[0] as $k=>$match){
		$text=str_replace('{replacerQarrQ'.$matches[1][$k].'}',$replace_arr[$matches[1][$k]],$text);
	}
	if(false!==strpos($text,'<br><br>')){
		$text_arr=explode('<br><br>',$text);
		$text='<p>'.implode("</p>\n<p>",$text_arr).'</p>';
	}
	return $text;
}