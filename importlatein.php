<?php

function block_exalib_importlatein() {
	global $DB, $CFG;
	echo "der Import wurde angestossen";
	
	$DB->delete_records("exalib_category");
	$DB->delete_records("exalib_item");
	$DB->delete_records("exalib_item_category");
	
	//$categories = $DB->get_records("community_tree");
	$categories=$DB->get_records_sql("SELECT * FROM community_tree");
	foreach ($categories as $category) {
		$sql='INSERT INTO {exalib_category} (id,parent_id,name) VALUES ';
		$sql.='('.$category->kategorie_tree_id.','.$category->kategorie_tree_high.',"'.mysql_real_escape_string($category->name).'")';
		$DB->Execute($sql);
		//$DB->insert_record("exalib_category", array("id" =>  $category->kategorie_tree_id,"parent_id" =>  $category->kategorie_tree_high,"name" =>  $category->name));
	}
	$data= new stdClass();
	//latein plattform als root ohne parentid setzen
	$data->id=568;$data->parent_id=0;
	$DB->update_record('exalib_category', $data);
	
	
	$items=$DB->get_records_sql("Select a.*,k.kategorie_tree_id as treeid from community_artikel as a INNER JOIN community_kategorie as k ON a.artikel_id = k.is_id");
	foreach ($items as $item) {
		$sql="INSERT INTO mdl_exalib_item (id,resource_id,link,source,file,name,authors,content) VALUES ";
		$sql.="(".$item->artikel_id.",0,'','','','','','')";
		$DB->Execute($sql);
		
		
		$data= new stdClass();
		$data->id=$item->artikel_id;$data->content=block_exalib_ersnull($item->inhalt);
		$data->resource_id=block_exalib_ersnull($item->dokumentart_id);$data->link=block_exalib_ersnull($item->url_id);
		$data->source=block_exalib_ersnull($item->quelle);$data->file=block_exalib_ersnull($item->bildlink);
		$data->name=block_exalib_ersnull($item->titel);$data->authors=block_exalib_ersnull($item->autor);
		$DB->update_record('exalib_item', $data);
		
		$sql="INSERT INTO mdl_exalib_item (id,resource_id,link,source,file,name,authors) VALUES ";
		$sql.="(".$item->artikel_id.",
		".block_exalib_preperefields($item->dokumentart_id).",
		'".block_exalib_preperefields($item->url_id)."',
		'".block_exalib_preperefields($item->quelle)."',
		'".block_exalib_preperefields($item->bildlink)."',
		'".block_exalib_preperefields($item->titel)."',
		'".block_exalib_preperefields($item->autor)."',
		'".block_exalib_preperefields($item->inhalt)."')";
		//echo $sql;
		
		
		$sql='INSERT INTO {exalib_item_category} (item_id,category_id) VALUES ';
		$sql.='('.$item->artikel_id.','.$item->treeid.')';
		$DB->Execute($sql);
		//$DB->insert_record("exalib_category", array("id" =>  $category->kategorie_tree_id,"parent_id" =>  $category->kategorie_tree_high,"name" =>  $category->name));
	}
	
	$urls=$DB->get_records_sql("SELECT community_artikel.artikel_id,url.url_ID,url.url FROM url INNER JOIN community_artikel ON community_artikel.url_ID=url.url_ID");
	
	foreach ($urls as $url) {
		
		if(!empty($url->url)){
			$data= new stdClass();
			$data->id=$url->artikel_id;$data->link=$url->url;
			$DB->update_record('exalib_item', $data);
		}
	}
	$data= new stdClass();
	//latein plattform als root ohne parentid setzen
	$data->id=568;$data->parent_id=0;
	$DB->update_record('exalib_category', $data);
	
}
function block_exalib_ersnull($wert){
	if(empty($wert)) $wert="";
	return $wert;
}
function block_exalib_preperefields($wert){
	if(empty($wert)) return "0";
	else {
		$wert=str_replace("?","",$wert);
		$wert=str_replace(';','',$wert);
		return mysql_real_escape_string(stripslashes($wert));
	}
}
?>