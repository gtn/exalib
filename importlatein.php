<?php

function block_exalib_importlatein_urls() {
	global $DB;
	
	$redownload = false;
	
	$fs = get_file_storage();
	
	$urls = $DB->get_records_sql("SELECT community_artikel.artikel_id,url.url_ID,url.url,url.url_titel FROM url INNER JOIN community_artikel ON community_artikel.url_ID=url.url_ID");
	
	foreach ($urls as $url) {
		if(empty($url->url)) continue;
		
		$data= new stdClass();
		$data->id=$url->artikel_id;
		$data->link=$url->url;
		$data->link_titel=block_exalib_ersnull($url->url_titel);
		
		if (preg_match('!schule.at!', $url->url)) {
			// only import schule.at stuff
			if (preg_match('!\.(jpg|docx?|xlsx?|pdf|pptx?|csv|odt|gif|mp3|zip|pps|rtf|png|bmp|m4a)$!i', $url->url)) {
				// only those filetypes
				if ($redownload) {
					$fs->delete_area_files(context_system::instance()->id, 'block_exalib', 'item_file', $data->id);
					$file = null;
				} else {
					$areafiles = $fs->get_area_files(context_system::instance()->id, 'block_exalib', 'item_file', $data->id, 'itemid', '', false);
					$file = reset($areafiles);
				}

				if (!$file) {
					// only download if not yet downloaded
					
					// testing:
					// $url->url = 'http://localhost/phpmyaasdfdsaf.jpg';
					
					echo "downloading ".$url->url."<br />\n";
					
					try {
						$fs->create_file_from_url(array(
							'component' => 'block_exalib',
							'contextid' => context_system::instance()->id,
							'filearea' => 'item_file',
							'filepath' => '/',
							'filename' => basename($url->url),
							'itemid' => $data->id
						), $url->url);
						
						echo "  ok<br />\n";
					} catch (file_exception $e) {
						// couldn't download
						echo "  ERROR: ".$e->getMessage()."<br />\n";
					}
				}
				$data->link = '';
			} else {
				echo "ignored download: ".$url->url."<br />\n";
			}
		}
		
		$DB->update_record('exalib_item', $data);
	}
	
	echo "downloading all files finished";
}
function block_exalib_importlatein3() {
	global $ret;global $erg;global $DB;
	$erg=array();
	$bundesl=array("400"=>"Oberösterreich",
	"443"=>"Burgenland",
	"459"=>"Wien",
	"449"=>"Niederösterreich",
	"3352"=>"Südtirol",
	"483"=>"Kärnten");
	
	foreach($bundesl as $k=>$v){
		block_exalib_bundeslaender($k,$v);
	}
	/*block_exalib_bundeslaender("443","BGLD");//bgld
	block_exalib_bundeslaender("459","WIEN");//wien
	
  block_exalib_bundeslaender("449","NÖ");//NÖ
	block_exalib_bundeslaender("3352","SüdT");//Südtirol
	block_exalib_bundeslaender("483","KTN");//ktn
	
	block_exalib_bundeslaender("449");//NÖ
	block_exalib_bundeslaender("3352");//Südtirol
	block_exalib_bundeslaender("483");//ktn*/
	echo "<pre>";
	foreach($erg as $k=>$v){
		echo "<br>Userid:".$k." bl:";
		$anztemp=0;$blid="";
		foreach($v as $k1=>$v1){
			if($v1>$anztemp) {$blid=$k1;$anztemp=$v1;}
		}
		if ($info = $DB->get_record('user_info_data', array('userid' => $k,"fieldid"=>1))) {
			
			if(empty($info->data)){
				$data=array("id"=>$info->id,"data"=>$bundesl[$blid]);
				$DB->update_record('user_info_data', $data);
			}
		}else{
				$data=array("data"=>$bundesl[$blid],"fieldid"=>1,"userid"=>$k);
				$DB->insert_record('user_info_data', $data);
			
		}
		
	}
	
}
function block_exalib_bundeslaender($retl,$bltitle){
	global $erg;global $ret;
	$ret=$retl;
	block_exalib_getsubcats($ret);
	$erg=block_exalib_adduser($ret,$erg,$retl,$bltitle);
}
function block_exalib_adduser($ret,$erg,$retl,$bltitle){
	global $DB;
	if($ret!=""){
		$items=$DB->get_records_sql("SELECT i.id,i.created_by,i.modified_by FROM {exalib_item_category} mm INNER JOIN {exalib_item} i ON i.id=mm.item_id WHERE category_id IN (".$ret.")");
		foreach ($items as $item) {
			if(array_key_exists($item->created_by,$erg) && array_key_exists($retl,$erg[$item->created_by])) $erg[$item->created_by][$retl]=($erg[$item->created_by][$retl]+1);
			else {
				$erg[$item->created_by][$retl]=1;
			}
			/*if($item->created_by!=$item->modified_by){
				if(array_key_exists($item->modified_by,$erg)) $erg[$item->modified_by]=($erg[$item->modified_by]+1);
				else $erg[$item->modified_by]=1;
			}*/
		}
	}
	return $erg;
}
function block_exalib_getsubcats($catid){
	global $ret,$DB;
	$items=$DB->get_records("exalib_category",array("parent_id"=>$catid));
	foreach ($items as $item) {
		block_exalib_getsubcats($item->id);
		if($item->hidden!=1){
			$ret.=",".$item->id;
		}
	}
}

function block_exalib_importlatein2() {
	global $DB, $CFG;
	echo "Import Update<br />\n";
	
	/* wenn kein bis datum angegeben, bis datum in weiter zukunft setzen */
	$items=$DB->get_records_sql("Select a.*,k.kategorie_tree_id as treeid from community_artikel as a INNER JOIN community_kategorie as k ON a.artikel_id = k.is_id");
	foreach ($items as $item) {
		//if ($item->_delete) continue;
		$data= new stdClass();
		$data->id=$item->artikel_id;
		
		if(empty($item->verfallsdatum)){
			$data->online_to=5555555555;
		}else{
			$datum = new DateTime($item->verfallsdatum);
			$data->online_to=$datum->getTimestamp();
		}
		$DB->update_record('exalib_item', $data);
	}
	

	/*leere kategorien löschen, aber nur kategorien die keine unterkategorien haben */
	$sql='SELECT parent_id FROM {exalib_category} GROUP BY parent_id';
	$parents=$DB->get_records_sql($sql);
	$parlist="-1";
	foreach ($parents as $par) {
		$parlist.=",".$par->parent_id;
	}
	
	$sql='select cat.id from {exalib_category} cat LEFT JOIN {exalib_item_category} mm ON mm.category_id=cat.id WHERE cat.id NOT IN ('.$parlist.') AND mm.id IS NULL';

	$emptycats=$DB->get_records_sql($sql);
	foreach ($emptycats as $cat) {
		$DB->update_record('exalib_category', array ("id"=>$cat->id,"hidden"=>1));
	}
	
	
}
function block_exalib_importlatein() {
	global $DB, $CFG;
	echo "der Import wurde angestossen<br />\n";
	
	$DB->delete_records("exalib_category");
	$DB->delete_records("exalib_item");
	$DB->delete_records("exalib_item_category");
	
	$transaction = $DB->start_delegated_transaction();

	$categories=$DB->get_records_sql("SELECT * FROM community_tree");
	foreach ($categories as $category) {
		if(!empty($category->level_mother)){
			$parentid=$category->level_mother;
		}else{
			$parentid=$category->kategorie_tree_high;
		}
		$DB->import_record('exalib_category', array(
			'id' => $category->kategorie_tree_id,
			'parent_id' => $parentid,
			'name' => $category->name
		));
	}

	//latein plattform als root ohne parentid setzen
	$data= new stdClass();
	$data->id=568;$data->parent_id=0;
	$DB->update_record('exalib_category', $data);
	
	$last_recid=$DB->insert_record("exalib_category", array("parent_id"=>0,"name"=>"Archiv"));
	$newsletter_catid=$DB->insert_record("exalib_category", array("parent_id"=>$last_recid,"name"=>"Newsletter"));
	
	$items=$DB->get_records_sql("Select a.*,k.kategorie_tree_id as treeid from community_artikel as a INNER JOIN community_kategorie as k ON a.artikel_id = k.is_id");
	foreach ($items as $item) {
		if ($item->_delete) continue;

		$sql="INSERT INTO {exalib_item} (id,resource_id,link,source,file,name,authors,content) VALUES ";
		$sql.="(".$item->artikel_id.",0,'','','','','','')";
		$DB->Execute($sql);
		
		
		$data= new stdClass();
		$data->id=$item->artikel_id;$data->content=block_exalib_ersnull($item->inhalt);
		//?? = block_exalib_ersnull($item->dokumentart_id);
		$data->link=block_exalib_ersnull($item->url_id);
		$data->modified_by=$item->lastmodby_id;
		$data->created_by=$item->owner_id;
		$data->source=block_exalib_ersnull($item->quelle);$data->file=block_exalib_ersnull($item->bildlink);
		$data->name=block_exalib_ersnull($item->titel);$data->authors=block_exalib_ersnull($item->autor);
		$datum = new DateTime($item->erscheindatum);
		$data->online_from=$datum->getTimestamp();
		$data->time_created=$data->online_from;
		if(empty($item->verfallsdatum)){
			$data->online_to=0;
		}else{
			$datum = new DateTime($item->verfallsdatum);
			$data->online_to=$datum->getTimestamp();
		}
		
		$data->hidden=block_exalib_inverthidden($item->genehmigt);
		$datum = new DateTime($item->aenderungsdatum);
		$data->time_modified=$datum->getTimestamp();
		$data->modifiedby=$item->lastmodby;
		
		$DB->update_record('exalib_item', $data);
		
		
		$sql='INSERT INTO {exalib_item_category} (item_id,category_id) VALUES ';
		$sql.='('.$item->artikel_id.','.$item->treeid.')';
		$DB->Execute($sql);
		//$DB->insert_record("exalib_category", array("id" =>  $category->kategorie_tree_id,"parent_id" =>  $category->kategorie_tree_high,"name" =>  $category->name));
	}
	
	//community_html
	
	$htmls=$DB->get_records_sql("SELECT * FROM community_html");

	foreach ($htmls as $html) {	
		
		$last_recid=$DB->insert_record("exalib_item", array("resource_id"=>0,"link" =>block_exalib_ersnull($html->imageurl),"source" =>  "","file" =>  "","name" =>  "html_comunity_rs","authors" =>  block_exalib_ersnull($html->autor),"content" =>block_exalib_ersnull($html->beschreibung),"modified_by"=>$html->lastmodby_id,"created_by"=>$html->owner_id));
		if (intval($last_recid)>0){
			$DB->insert_record("exalib_item_category", array("item_id"=>$last_recid,"category_id" =>  $html->bereich));
			//$sql='INSERT INTO {exalib_item_category} (item_id,category_id) VALUES ';
			//$sql.='('.$last_recid.','.$html->bereich.')';
			//$DB->Execute($sql);
		}
	}
	//community_news
	
	$news=$DB->get_records_sql("SELECT * FROM community_news WHERE del=0");

	foreach ($news as $new) {
		
		$last_recid=$DB->insert_record("exalib_item", array("resource_id"=>0,"link" =>block_exalib_ersnull($new->url),"source" =>  "","file" =>  block_exalib_ersnull($new->imageurl),"name" =>$new->titel,"authors" =>  block_exalib_ersnull($new->autor),"content" =>"<div class='news_short'>".$new->short."</div>".$new->beschreibung,"modified_by"=>$new->lastmodby_id,"created_by"=>$new->owner_id));
		if (intval($last_recid)>0){
			$DB->insert_record("exalib_item_category", array("item_id"=>$last_recid,"category_id" =>  $new->bereich));
			//$sql='INSERT INTO {exalib_item_category} (item_id,category_id) VALUES ';
			//$sql.='('.$last_recid.','.$html->bereich.')';
			//$DB->Execute($sql);
		}
	}
	//community_newsletter
	
	$newsltrs=$DB->get_records_sql("SELECT * FROM community_newsletter");

	foreach ($newsltrs as $newsltr) {
		$data=array("resource_id"=>0,
		"link" =>"",
		"source" => "",
		"file" =>block_exalib_ersnull($newsltr->attach_link),
		"name" =>block_exalib_ersnull($newsltr->betreff),
		"authors" =>  block_exalib_ersnull($newsltr->u_owner),
		"created_by"=>$newsltr->owner_id,
		"content" =>"<div class='newsletter_inhalt'>".block_exalib_ersnull($newsltr->inhalt)."</div>Die Newsletterempfänger:<br><br>".block_exalib_ersnull($newsltr->recipients));
		
		$last_recid=$DB->insert_record("exalib_item", $data);
		if (intval($last_recid)>0){
			$DB->insert_record("exalib_item_category", array("item_id"=>$last_recid,"category_id" =>  $newsletter_catid));
			//$sql='INSERT INTO {exalib_item_category} (item_id,category_id) VALUES ';
			//$sql.='('.$last_recid.','.$html->bereich.')';
			//$DB->Execute($sql);
		}
	}
	
	echo "<a href='".$_SERVER['PHP_SELF']."?importlatein=urls'>urls/files downloaden</a><br />\n";
	
	$transaction->allow_commit();
}

function block_exalib_inverthidden($wert){
	/*if genehmigt ($wert=1) return hidden=0*/
	if ($wert==1) return 0;
	else return 1;
}
function block_exalib_ersnull($wert){
	if(empty($wert)) $wert="";
	//if(is_null($wert)) $wert="";
	return $wert;
}
