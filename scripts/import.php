<?php
error_reporting(E_ALL & ~E_NOTICE);
use block_exalib\globals as g;

require '../inc.php';

//congress abstracts löschen
//delete_items(2016,"51301,51201,51308,51202,51203");
//delete_items(2017,"51301,51201,51308,51202,51203");
//delete_items(2018,"51301,51201,51308,51202,51203");
//delete_items(2019,"51301,51201,51308,51202,51203");

//import_csv("2019_02_20_import_Congress_Abstracts_2016.csv","2016",7000);
//image_replacetif(2016,"51301,51201,51308,51202,51203");

//import_csv("2019_02_19_import_Congress_abstracts_2017.csv","2017",9000);
//image_to_uppercase(2017,"51301,51201,51308,51202,51203");
//delete_graficspath(2017,"51301,51201,51308,51202,51203");

//import_csv("2019_02_19_import_Congress_abstracts_2018.csv","2018",11500);

//import_csv("2019_02_20_import_Congress_abstracts_2019.csv","2019",14000);
//import_csv("19_02_27_Late_breaker_abstracts_2019_korr.csv","2019",15006);

//import_csv("2019_04_10_GTN_DRAFT_Presentation_Webcasts_upload_korr.csv","2019",20000);
//import_files_fromfolder(20001,25002);

/*-------------------------------------28.5.2019---------------------------*/
//delete_items(2019,"",20000,20252);

//import_csv("GTN_Master_ECCO'19_Congress Presentations_V3.csv","2019",20000);
//import_file_frompath(19999,25002);
/*-------------------------------------04.06.2019---------------------------*/
//import_csv("2019_04_12_GTN_DRAFT_Webcasts_upload - KopieV3.csv","2019",20500);
//import_csv("5ECCO_20_Abstracts_ for_GTN.csv","2020",21000);

/*  --------------- Presentations 2020  ------------------ */
//delete_items(2020,"",22500,22500);
//import_csv("2020_06_04_Master_GTN_ECCO20_Congress_Presentations_V2a.csv","2020",22500,false,true);

//import_file_frompath(22501,22785);

/*import_file_frompath(22505,22507);
import_file_frompath(22617,22619);
import_file_frompath(22551,22553);
import_file_frompath(22661,22663);
import_file_frompath(22670,22672);
import_file_frompath(22678,22680);
import_file_frompath(22588,22590);*/

/*  --------------- Webcasts 2020  ------------------ */
//import_csv("2020_06_04_Master_GTN_ECCO20_Congress_Webcasts_V2a.csv","2020",22800,true,true);

/*  --------------- Abstracts 2021  ------------------ */
//import_csv("MASTER_2021_05_20_import template_Abstracts_2021_V2.csv","2021",23000,false,true);

//import_csv("MASTER_2021_05_20_import template_Abstracts_2021_juni.csv","2021",23000,false,true);
//delete_items(2021,"",23000,24600);

/*  --------------- Webcasts 2021  ------------------ */
//nach webcasts import in detail.php eventuell dieses jahr dazugeben:   if (preg_match('!rtmp://!', $item->link) || preg_match('!rtmps://!', $item->link) || preg_match('!https://e-learning.ecco-ibd.eu/ECCO2019/Webcasts!', $item->link) || preg_match('!https://video.ecco-ibd.eu/ECCO2020!', $item->link) || preg_match('!https://video.ecco-ibd.eu/ECCO2021!', $item->link)) {
//import_csv("2021_06_23_GTN_DRAFT_ECCO'21_Congress_DOP_Webcasts_v2.csv","2021",25000,true,true);

/*  --------------- Videos/Webcasts 2021  ------------------ */
import_csv("Webcasts2021.csv","2021",25100,true,false);
//delete_items(2021,"",25100,25400);
echo "done";  



//set_maincategory();
//ALTER TABLE mdl_block_exalib_item ADD deleted int(1)
//ALTER TABLE mdl_block_exalib_item_category ADD deleted int(1)
//UPDATE mdl_block_exalib_item SET deleted=0
//DELETE FROM mdl_block_exalib_item WHERE deleted=1;
//DELETE FROM mdl_block_exalib_item_category WHERE deleted=1
//delete from mdl_block_exalib_item where id >11499 and id< 13003
//delete from mdl_block_exalib_item_category where item_id >11499 and item_id< 13003

function cp($param){
	return preg_replace('/[^0-9,]/i', '', $param);
}

function import_files_fromfolder($idfrom,$idto){
	$fs = get_file_storage();
	$items = g::$DB->get_records_sql('select id,filepathtemp from mdl_block_exalib_item where id>'.$idfrom.' AND id<'.$idto);

	foreach ($items as $item) {
		
		if ($item->filepathtemp != "") {
			$alledateien = scandir($item->filepathtemp); //Ordner "files" auslesen

			/*foreach ($alledateien as $datei) { // Ausgabeschleife
	
				$pos = strpos($datei, ".pdf");
				if ($pos === false) {
				    //not found
				} else {
				    $dateiname=$datei; //Ausgabe Einzeldatei
				}
			};*/
		
			foreach ($alledateien as $k=>$datei){
				if ($datei!=""){
					$dateiges = __DIR__.'/'.$item->filepathtemp.'/'.$datei;
					//echo "<br>dateiges:".$dateiges;
					$pos = strpos($datei, ".pdf");
					if ($pos === false) {
						
					}
					elseif (file_exists($dateiges)) {
						echo "datei ".$dateiges."existiert<br>";
						/*$fs->delete_area_files(context_system::instance()->id,
							'block_exalib',
							'item_file',
							$item['id']);*/
				
						$filerecord = new stdClass();
						$filerecord->contextid = context_system::instance()->id;
						$filerecord->component = 'block_exalib';
						$filerecord->filearea = 'item_file';
						$filerecord->filepath = '/';
						$filerecord->filename = str_replace('_', '', basename($dateiges));
						$filerecord->itemid = $item->id;
				
						$fs->create_file_from_pathname($filerecord, $dateiges);
					}
				}
			}
			
		}
	}
}
function import_file_frompath($idfrom,$idto){
	$dateitypOK=false;
	$fs = get_file_storage();
	$items = g::$DB->get_records_sql('select id,filepathtemp,filestemp from mdl_block_exalib_item where id>'.$idfrom.' AND id<'.$idto);

	foreach ($items as $item) {
		//$datei=str_replace("/moodleneu/blocks/exalib/scripts/","",$item->filepathtemp)."/".$item->filestemp;
		$datei=str_replace("/moodleneu/blocks/exalib/scripts/","",$item->filepathtemp)."/".$item->filestemp;
		

		
		if ($datei != "") {
			

					$dateiges = __DIR__.'/'.$datei;

					echo "<br>dateiges:".$dateiges;
					
					$pos = strpos($datei, ".pdf");
					if ($pos === false) {
						
					}else{$dateitypOK=true;}
						
					$pos = strpos($datei, ".mp4");
					if ($pos === false) {
						
					}else{$dateitypOK=true;}	
					
					if ($dateitypOK==true){
						if (file_exists($dateiges)) {
							echo "datei ".$dateiges."existiert<br>";
							$fs->delete_area_files(context_system::instance()->id,
								'block_exalib',
								'item_file',
								$item->id);
					
							$filerecord = new stdClass();
							$filerecord->contextid = context_system::instance()->id;
							$filerecord->component = 'block_exalib';
							$filerecord->filearea = 'item_file';
							$filerecord->filepath = '/';
							$filerecord->filename = str_replace('_', '', basename($dateiges));
							$filerecord->itemid = $item->id;
					
							$fs->create_file_from_pathname($filerecord, $dateiges);
						}
					}
			
			
		}

	}
}

function set_maincategory(){
	$items = g::$DB->get_records_sql('SELECT i.id,i.maincategory FROM mdl_block_exalib_item i JOIN mdl_block_exalib_item_category ic ON ic.item_id=i.id');

	foreach ($items as $item){
		
		if(empty($item->maincategory)){
			setMainCat($item->id);
			echo $item->id."<hr>";
	  }
	
	}
	
}
function setMainCat($itemid){
	if($mcategories = g::$DB->get_records_sql("
        	SELECT category_id
        	FROM {block_exalib_item_category}
        	WHERE item_id=".$itemid." AND category_id IN (51301,51302,51303,51304,51305)
        	ORDER BY category_id DESC;
		")){
			foreach ($mcategories as $mcategorie) {
				g::$DB->update_record('block_exalib_item', ['id' => $itemid,'maincategory' => $mcategorie->category_id]);
				return $mcategorie->category_id;
				break;
			} 
		}
		elseif($mcategories = g::$DB->get_records_sql("
        	SELECT c.parent_id
        	FROM {block_exalib_item_category} ic JOIN {block_exalib_category} c ON c.id=ic.category_id
        	WHERE ic.item_id=".$itemid." AND c.parent_id IN (51301,51302,51303,51304,51305)
        	ORDER BY c.parent_id DESC;
		")){
			foreach ($mcategories as $mcategorie) {
				g::$DB->update_record('block_exalib_item', ['id' => $itemid,'maincategory' => $mcategorie->parent_id]);
				return $mcategorie->parent_id;
				break;
			} 
		}
		else {return 0;}
}
function image_to_uppercase($year,$category_id){
	$items = g::$DB->get_records_sql('SELECT i.id FROM mdl_block_exalib_item i JOIN mdl_block_exalib_item_category ic ON ic.item_id=i.id WHERE i.year='.$year.' AND ic.category_id IN ('.$category_id.')');

	foreach ($items as $item){
			//echo $item->id;echo "<br>";
	  g::$DB->execute('UPDATE {block_exalib_item} SET results=REPLACE(results,"images/2017/graphics/a","images/2017/graphics/A") WHERE id='.$item->id);
	  g::$DB->execute('UPDATE {block_exalib_item} SET background=REPLACE(background,"images/2017/graphics/a","images/2017/graphics/A") WHERE id='.$item->id);
	  g::$DB->execute('UPDATE {block_exalib_item} SET methods=REPLACE(methods,"images/2017/graphics/a","images/2017/graphics/A") WHERE id='.$item->id);
	  g::$DB->execute('UPDATE {block_exalib_item} SET conclusion=REPLACE(conclusion,"images/2017/graphics/a","images/2017/graphics/A") WHERE id='.$item->id);
	    
	
	}
	
}
function image_replacetif($year,$category_id){
	$items = g::$DB->get_records_sql('SELECT i.id FROM mdl_block_exalib_item i JOIN mdl_block_exalib_item_category ic ON ic.item_id=i.id WHERE i.year='.$year.' AND ic.category_id IN ('.$category_id.')');

	foreach ($items as $item){
			//echo $item->id;echo "<br>";
	  g::$DB->execute('UPDATE {block_exalib_item} SET results=REPLACE(results,".tif",".jpg") WHERE id='.$item->id);
	  g::$DB->execute('UPDATE {block_exalib_item} SET background=REPLACE(background,".tif",".jpg") WHERE id='.$item->id);
	  g::$DB->execute('UPDATE {block_exalib_item} SET methods=REPLACE(methods,".tif",".jpg") WHERE id='.$item->id);
	  g::$DB->execute('UPDATE {block_exalib_item} SET conclusion=REPLACE(conclusion,".tif",".jpg") WHERE id='.$item->id);
	    
	
	}
	
}

function delete_graficspath($year,$category_id){
	$items = g::$DB->get_records_sql('SELECT i.id FROM mdl_block_exalib_item i JOIN mdl_block_exalib_item_category ic ON ic.item_id=i.id WHERE i.year='.$year.' AND ic.category_id IN ('.$category_id.')');

	foreach ($items as $item){
			//echo $item->id;echo "<br>";
	  g::$DB->execute('UPDATE {block_exalib_item} SET results=REPLACE(results,"images/2017/graphics/","images/2017/") WHERE id='.$item->id);
	  g::$DB->execute('UPDATE {block_exalib_item} SET background=REPLACE(background,"images/2017/graphics/","images/2017/") WHERE id='.$item->id);
	  g::$DB->execute('UPDATE {block_exalib_item} SET methods=REPLACE(methods,"images/2017/graphics/","images/2017/") WHERE id='.$item->id);
	  g::$DB->execute('UPDATE {block_exalib_item} SET conclusion=REPLACE(conclusion,"images/2017/graphics/","images/2017/") WHERE id='.$item->id);
	    
	
	}
	
}
function delete_items($year,$category_id,$idvon=0,$idbis=0){
	if ($idvon>0){
		$items = g::$DB->get_records_sql('SELECT i.id FROM mdl_block_exalib_item i WHERE i.id>='.$idvon.' AND i.id<='.$idbis);
	}else{
		$items = g::$DB->get_records_sql('SELECT i.id FROM mdl_block_exalib_item i JOIN mdl_block_exalib_item_category ic ON ic.item_id=i.id WHERE i.year='.$year.' AND ic.category_id IN ('.$category_id.')');
	}
	$data["deleted"]=1;
	$fs = get_file_storage();
	foreach ($items as $item){
			echo $item->id;echo "<br>";
			
			// file delete,  not tested yet
			if ($files = $fs->get_area_files(context_system::instance()->id,'block_exalib','item_file',$item->id,'itemid','',false)){

					$fs->delete_area_files(context_system::instance()->id,
								'block_exalib',
								'item_file',
								$item->id);
			}
						
	  g::$DB->execute('UPDATE {block_exalib_item} SET deleted=1 WHERE id='.$item->id);
		g::$DB->execute('UPDATE {block_exalib_item_category} SET deleted=1 WHERE item_id='.$item->id);
		g::$DB->execute('DELETE FROM mdl_block_exalib_item WHERE deleted=1');
		g::$DB->execute('DELETE FROM mdl_block_exalib_item_category WHERE deleted=1');

		//g::$DB->update_record('mdl_block_exalib_item', $data, ['id' => $item->id]);
		//g::$DB->update_record('mdl_block_exalib_item_category', $data, ['item_id' => $item->id]);
	}
	
}
function addIfNotNull($wert,$add){
	if ($wert!="") $wert.=$add;
	return $wert;
}
function removeTag($wert,$tagname){
	$wert=str_replace("<".$tagname.">","",$wert);
	$wert=str_replace("</".$tagname.">","",$wert);
	return $wert;
}
function graphic_path($wert,$year){
	//2016
	$wert=str_replace('<img xlink:href="','<img src="images/'.$year.'/',$wert);
	$wert=str_replace("<img xlink:href='","<img src='images/".$year."/",$wert);
	$wert=str_replace('<uri xmlns:xlink=""http://www.w3.org/1999/xlink"" xlink:href=','<a href=',$wert);
	$wert=str_replace('<uri xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href=','<a href=',$wert);
	$wert=str_replace('</uri>','</a>',$wert);
	$wert=str_replace("<graphic xlink:href='file:./../graphics/","<img src='images/".$year."/",$wert);
	$wert=str_replace('<graphic xlink:href="file:./../graphics/','<img src="images/'.$year.'/',$wert);
	$wert=str_replace('<graphic xlink:href=""file:./../graphics/','<img src="images/'.$year.'/',$wert);
	$wert=str_replace('<graphic xlink:href="file:./../','<img src="images/'.$year.'/',$wert);                  
  $wert=str_replace('<graphic xlink:href=""file:./../','<img src="images/'.$year.'/',$wert);
	return $wert;
}

function import_csv($filename,$year,$k,$uselink=false,$typetotitle=false){

	$csv = file_get_contents($filename);
	$csv = stringToCsv($csv, ';', true);
	
	foreach ($csv as $i => $item) {
		$data=array();
		$filen='';
		$data['id'] = $k;
		$filen.=get_graphics($item['Results']).get_graphics($item['Methods']).get_graphics($item['Background']).get_graphics($item['Conclusions']);
		$filen.=$item['FileName'];
		$data['source'] = $item['Source'];
		if($typetotitle){
			$data['name'] = addIfNotNull(strip_tags($item['PresentationType']),': ').removeTag($item['Title'],'p');
			
		}else{
			$data['name'] = removeTag($item['Title'],'p');
		}
		$data['authors'] = $item['Author'];
		$data['background'] = str_replace('<bold>Background:</bold> ', '', graphic_path($item['Background'],$year));
		$data['methods'] = str_replace('<bold>Methods:</bold> ', '', graphic_path($item['Methods'],$year));
		$data['results'] = str_replace('<bold>Results:</bold> ', '', graphic_path($item['Results'],$year));
		$data['conclusion'] = str_replace('<bold>Conclusions:</bold> ', '', graphic_path($item['Conclusion'],$year));
		$data['affiliations'] = $item['Affiliations'];
		$data['year'] = $year;
		$data['online'] = 1;
		$data['time_created'] = time();
		$data['time_modified'] = time();
		$data['modified_by'] = '0';
		$data['online'] = 1;
		$data['created_by'] = '0';		
		$data['reviewer_id'] = 0;
		$data['abstract'] = $item['Abstract'];
		$data['search_abstract'] = $item['SearchAbstract'];
		$data['filestemp'] = $filen;
		//abstracts 2020, nicht notwendig
		//$data['filepathtemp'] = "video.ecco-ibd.eu".$item['FilePath'];
		$data['filepathtemp'] = $item['FilePath'];
		if ($uselink){
			$data['link']="https://video.ecco-ibd.eu".$item['FilePath']."/".$item['FileName'];
		}else{
			$data['link']=""; //bei webcasts ausblenden
		}

		$data['SearchAbstract']=$item['SearchAbstract'];

		
		//??$data['SubType']=$item['SubType'];
		//??$data['_Specific_keywords'] = $item['Specific_keywords'];
		$data['Cat1']=cp($item['Cat1']);$data['Cat2']=cp($item['Cat2']);$data['Cat3']=cp($item['Cat3']);$data['Cat4']=cp($item['Cat4']);
		$data['Cat5']=cp($item['Cat5']);$data['Cat6']=cp($item['Cat6']);$data['Cat7']=cp($item['Cat7']);$data['Cat8']=cp($item['Cat8']);$data['Cat9']=cp($item['Cat9']);$data['Cat10']=cp($item['Cat10']);
		$maincategory_arr=Array();$maincategory_arr[51301]=1;$maincategory_arr[51302]=1;$maincategory_arr[51303]=1;
		$maincategory_arr[51304]=1;$maincategory_arr[51305]=1;
		$catt="";
		if ($data['name']!=" "){
			//print_r($data);die;
			 g::$DB->execute('INSERT INTO {block_exalib_item} (id) VALUES ('.$data['id'].')');
			 
		
			/* unterkategorie is in daten, prüfen ob das wirklich so ist                     */
			//$category_id = 51301;
			//g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			if (intval($data['Cat1'])>0){
				//$category_id=51100+$data['Cat1']; //nicht 51100 dazuzählen, weil ecco im ersten feld die richtigen langen nummern reinschreibt
				$category_id=$data['Cat1'];
				if ($data['Cat1']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
				if ($maincategory_arr[$category_id]==1) $catt=$category_id;
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
			if (intval($data['Cat2'])>0){
				$category_id=51100+$data['Cat2']; //51100 dazuzählen, weil es dann übereinstimmt, zb 1 bei ecco ist "1. Aetiology of disease", hat bei exalib 51101
				if ($data['Cat2']==18) $category_id=51306;			// weil "18. Clinical trial design and outcomes" die id 51306 hat und nicht nach der anderen systematik 51118
				if ($data['Cat2']==19) $category_id=51307;if ($data['Cat2']==20) $category_id=51309;
				if ($maincategory_arr[$category_id]==1) $catt=$category_id;
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
			if (intval($data['Cat3'])>0){
				$category_id=51100+$data['Cat3'];
				if ($data['Cat3']==18) $category_id=51306;if ($data['Cat3']==19) $category_id=51307;if ($data['Cat3']==20) $category_id=51309;
				if ($maincategory_arr[$category_id]==1) $catt=$category_id;
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
			if (intval($data['Cat4'])>0){
				$category_id=51100+$data['Cat4'];
				if ($data['Cat4']==18) $category_id=51306;if ($data['Cat4']==19) $category_id=51307;if ($data['Cat4']==20) $category_id=51309;
				if ($maincategory_arr[$category_id]==1) $catt=$category_id;
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
			if (intval($data['Cat5'])>0){
				$category_id=51100+$data['Cat5'];
				if ($data['Cat5']==18) $category_id=51306;if ($data['Cat5']==19) $category_id=51307;if ($data['Cat5']==20) $category_id=51309;
				if ($maincategory_arr[$category_id]==1) $catt=$category_id;
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
			if (intval($data['Cat6'])>0){
				$category_id=51100+$data['Cat6'];
				if ($data['Cat6']==18) $category_id=51306;if ($data['Cat6']==19) $category_id=51307;if ($data['Cat6']==20) $category_id=51309;
				if ($maincategory_arr[$category_id]==1) $catt=$category_id;
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
			if (intval($data['Cat7'])>0){
				$category_id=51100+$data['Cat7'];
				if ($data['Cat7']==18) $category_id=51306;if ($data['Cat7']==19) $category_id=51307;if ($data['Cat7']==20) $category_id=51309;
				if ($maincategory_arr[$category_id]==1) $catt=$category_id;
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
			
			if (intval($data['Cat8'])>0){
				$category_id=51100+$data['Cat8'];
				if ($data['Cat8']==18) $category_id=51306;if ($data['Cat8']==19) $category_id=51307;if ($data['Cat8']==20) $category_id=51309;
				if ($maincategory_arr[$category_id]==1) $catt=$category_id;
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
			if (intval($data['Cat9'])>0){
				$category_id=51100+$data['Cat9'];
				if ($data['Cat9']==18) $category_id=51306;if ($data['Cat9']==19) $category_id=51307;if ($data['Cat9']==20) $category_id=51309;
				if ($maincategory_arr[$category_id]==1) $catt=$category_id;
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
			if (intval($data['Cat10'])>0){
				$category_id=51100+$data['Cat10'];
				if ($data['Cat10']==18) $category_id=51306;if ($data['Cat10']==19) $category_id=51307;if ($data['Cat10']==20) $category_id=51309;
				if ($maincategory_arr[$category_id]==1) $catt=$category_id;
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
			
			$data["maincategory"]=$catt;
			echo "<br><br>id: ".$data['id']."<br>";
			print_r($item);
			g::$DB->update_record('block_exalib_item', $data, ['id' => $data['id']]);
			
			$category_id=0;
			if ($data['SubType']=="Oral Presentation") $category_id=51202;
			elseif ($data['SubType']=="Digital Oral Presentation") $category_id=51201;
			elseif ($data['SubType']=="Poster Presentation") $category_id=51203;
			elseif ($data['SubType']=="Nurse Presentation") $category_id=51308;
			if ($category_id>0){
				g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
			}
		}
		$k++;
		//if ($k>0) exit;
	}

}

function stringToCsv($string, $delimiter, $has_header) {
	$string = trim($string, "\r\n");
	$string = rtrim($string);
	$csv = preg_split("!\r?\n!", $string);

	foreach ($csv as &$item) {
		$item = str_getcsv($item, $delimiter);
	}
	unset($item);

	if ($has_header) {
		$header = array_shift($csv);

		foreach ($csv as &$item) {
			$newItem = [];
			foreach ($item as $i => $part) {
				$newItem[$header[$i]] = $part;
			}
			$item = $newItem;
		}
		unset($item);
	}

	return $csv;
}
function get_graphics($val){


	$pos = strpos($val, "graphic xlink:href='file:./../");
	if ($pos === false) {
	    return '';
	} else {
			$pos=$pos+30;
	    $val=substr($val,$pos);
	    $posend=strpos($val, "'");
	    if ($posend === false) {
			} else {
				$val=substr($val,0,$posend);
			}
	    $val.=",";
	}
	return $val;
}

function old_code(){
//presentation Angerer 2018 add source
$csv = file_get_contents('Presentation_2018.csv');
$csv = stringToCsv($csv, ',', true);

$k=0;
foreach ($csv as $i => $item) {
	$data=array();

	$data['source'] = $item['Source'];
	$data['id'] = 100100 + $k;

	if ($data['source']!=""){
		 g::$DB->update_record('block_exalib_item', $data, ['id' => $data['id']]);
	}
	$k++;

	//if ($k>0) exit;
}
exit;

//webcasts 2018 subtyp zuordnen Angerer

$items = g::$DB->get_records_sql('SELECT * FROM mdl_block_exalib_item WHERE id>100160 AND id<100200 AND	`_affiliations`<>"Slides only!"');
foreach ($items as $item) {
	echo $item->id."<br>";
	//g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item->id, 'category_id' => 51305]);
	g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item->id, 'category_id' => 51211]);
	
}
exit;
//files for abstracts 2018 angerer

$fs = get_file_storage();

//$items = g::$DB->get_records_sql('select id,_affiliations from mdl_block_exalib_item where id>14999 AND id<17000');
$items = g::$DB->get_records_sql('select id,_affiliations from mdl_block_exalib_item where id>14999 AND id<17000');

//print_r($items);
foreach ($items as $item) {
	if ($item->_affiliations != "") {
		//echo "dateiname:".$item->_affiliations;
		$dateien=explode( ',' , $item->_affiliations);
		foreach ($dateien as $k=>$datei){
			if ($datei!=""){
				$dateiges = __DIR__.'/'.$datei;
				echo "<br>dateiges:".$dateiges;
				if (file_exists($dateiges)) {
					/*$fs->delete_area_files(context_system::instance()->id,
						'block_exalib',
						'item_file',
						$item['id']);*/
			
					$filerecord = new stdClass();
					$filerecord->contextid = context_system::instance()->id;
					$filerecord->component = 'block_exalib';
					$filerecord->filearea = 'item_file';
					$filerecord->filepath = '/';
					$filerecord->filename = str_replace('_', '', basename($dateiges));
					$filerecord->itemid = $item->id;
			
					$fs->create_file_from_pathname($filerecord, $dateiges);
				}
			}
		}
		
	}
}
exit;

// abstracts 2018 angerer
/* */
$csv = file_get_contents('2018_Abstracts.csv');
$csv = stringToCsv($csv, ',', true);

$k=0;
foreach ($csv as $i => $item) {
	$data=array();
	$filen='';
	$filen.=get_graphics($item['Results']).get_graphics($item['Methods']).get_graphics($item['Background']).get_graphics($item['Conclusions']);
	$data['year'] = 2018;
	$data['name'] = $item['Number'].': '.$item['Title'];
	$data['results'] = str_replace('<bold>Results:</bold> ', '', $item['Results']);
	$data['methods'] = str_replace('<bold>Methods:</bold> ', '', $item['Methods']);
	$data['background'] = str_replace('<bold>Background:</bold> ', '', $item['Background']);
	$data['conclusion'] = str_replace('<bold>Conclusions:</bold> ', '', $item['Conclusions']);
	$data['abstract'] = $item['Affiliations'];
	$data['_affiliations'] = $filen;
	$data['authors'] = $item['Authors'];
	$data['online'] = 1;
	$data['reviewer_id'] = 0;
	$data['time_created'] = time();
	$data['time_modified'] = time();
	$data['modified_by'] = '0';
	$data['created_by'] = '0';
	$data['SubType']=$item['SubType'];
	$data['_Specific_keywords'] = $item['Specific_keywords'];
	$data['Category1']=$item['Category1'];$data['Category2']=$item['Category2'];$data['Category3']=$item['Category3'];$data['Category4']=$item['Category4'];
	$data['Category5']=$item['Category5'];$data['Category6']=$item['Category6'];$data['Category7']=$item['Category7'];
	$data['id'] = 15000 + $k;
	
	if ($data['name']!=" "){
		 g::$DB->execute('INSERT INTO {block_exalib_item} (id) VALUES ('.$data['id'].')');
		 g::$DB->update_record('block_exalib_item', $data, ['id' => $data['id']]);
	
		$category_id = 51301;
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		if (intval($data['Category1'])>0){
			$category_id=51100+$data['Category1'];
			if ($data['Category1']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category2'])>0){
			$category_id=51100+$data['Category2'];
			if ($data['Category2']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category3'])>0){
			$category_id=51100+$data['Category3'];
			if ($data['Category3']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category4'])>0){
			$category_id=51100+$data['Category4'];
			if ($data['Category4']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category5'])>0){
			$category_id=51100+$data['Category5'];
			if ($data['Category5']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category6'])>0){
			$category_id=51100+$data['Category6'];
			if ($data['Category6']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category7'])>0){
			$category_id=51100+$data['Category7'];
			if ($data['Category7']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		
		$category_id=0;
		if ($data['SubType']=="Oral presentations") $category_id=51202;
		elseif ($data['SubType']=="Digital oral presentations") $category_id=51201;
		elseif ($data['SubType']=="Poster presentations") $category_id=51203;
		elseif ($data['SubType']=="Nurses presentations") $category_id=51308;
		if ($category_id>0){
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
	}
	$k++;
	//if ($k>0) exit;
}
exit;

//exit;
//presentation files angerer 2018
$fs = get_file_storage();

//$items = g::$DB->get_records_sql('select id,_affiliations from mdl_block_exalib_item where id>14999 AND id<17000');
$items = g::$DB->get_records_sql('select * from mdl_block_exalib_item where id>100199');

//print_r($items);
foreach ($items as $item) {
	//echo "drinnen";
	echo $item->id."<br>";
	
						

	//echo $item->_specific_keywords;
	$alledateien = scandir('presentation/'.$item->_specific_keywords); //Ordner "files" auslesen
		
		$dateiname="";
		foreach ($alledateien as $datei) { // Ausgabeschleife

			$pos = strpos($datei, ".pdf");
			if ($pos === false) {
			    //not found
			} else {
			    $dateiname=$datei; //Ausgabe Einzeldatei
			}
		};


	if ($dateiname != "") {
				$dateiges = 'presentation/'.$item->_specific_keywords.'/'.$dateiname;
				
				if (file_exists($dateiges)) {
					echo "<br>dateiges:".$dateiges;
					/*$fs->delete_area_files(context_system::instance()->id,
						'block_exalib',
						'item_file',
						$item['id']);*/
								
								
		
					
					/*$filerecord = new stdClass();
					$filerecord->contextid = context_system::instance()->id;
					$filerecord->component = 'block_exalib';
					$filerecord->filearea = 'item_file';
					$filerecord->filepath = '/';
					$filerecord->filename = str_replace('_', '', basename($dateiges));
					$filerecord->itemid = $item->id;
			
					$fs->create_file_from_pathname($filerecord, $dateiges);*/
					
				}
		
	}

}
exit;

//presentation Angerer 2018
$csv = file_get_contents('Presentation_2018_onlySheet.csv');
$csv = stringToCsv($csv, ',', true);

$k=0;
foreach ($csv as $i => $item) {
	$data=array();

	$data['year'] = 2018;
	$data['name'] = $item['Title'];
	$data['_affiliations'] = $item['_affiliations'];
	$data['authors'] = $item['Speaker_Last_name'].' '.$item['Speaker_First_name'];
	$data['online'] = 0;
	$data['reviewer_id'] = 0;
	$data['time_created'] = time();
	$data['time_modified'] = time();
	$data['modified_by'] = '0';
	$data['created_by'] = '0';
	$data['SubType']=$item['SubType'];
	$data['Category1']=$item['Category1'];$data['Category2']=$item['Category2'];$data['Category3']=$item['Category3'];$data['Category4']=$item['Category4'];
	$data['Category5']=$item['Category5'];$data['Category6']=$item['Category6'];
	$data['id'] = 100200 + $k;
	$data['_Specific_keywords'] = $item['Presentation Number'];
	//echo $data['id']."<br>";
	if ($data['name']!=" "){
		 g::$DB->execute('INSERT INTO {block_exalib_item} (id) VALUES ('.$data['id'].')');
		 g::$DB->update_record('block_exalib_item', $data, ['id' => $data['id']]);
	
		$category_id = 51302;
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);

		//new
		if (intval($data['Category1'])>0){
			$category_id=51100+$data['Category1'];
			if ($data['Category1']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category2'])>0){
			$category_id=51100+$data['Category2'];
			if ($data['Category2']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category3'])>0){
			$category_id=51100+$data['Category3'];
			if ($data['Category3']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category4'])>0){
			$category_id=51100+$data['Category4'];
			if ($data['Category4']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category5'])>0){
			$category_id=51100+$data['Category5'];
			if ($data['Category5']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		if (intval($data['Category6'])>0){
			$category_id=51100+$data['Category6'];
			if ($data['Category6']==18) $category_id=51306;			// var_dump(['itemid'=>$item['id']]);
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
		
		//new
		$category_id=0;
		if ($data['SubType']=="Congress Presentations: Plenary") $category_id=51205;
		elseif ($data['SubType']=="Congress Presentations: Educational programme") $category_id=51204;

		if ($category_id>0){
			g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$data['id'], 'category_id' => $category_id]);
		}
	}
	$k++;

	//if ($k>0) exit;
}
exit;




exit;



/*foreach ($csv as $i => $item) {
	if (!$item['number']) {
		echo 'n';
		continue;
	}

	$item['id'] = 12000 + $item['number'];

	$file = __DIR__.'/files2016/_'.$item['number'].'.pdf';
	if (file_exists($file)) {
		$fs->delete_area_files(context_system::instance()->id,
			'block_exalib',
			'item_file',
			$item['id']);

		$filerecord = new stdClass();
		$filerecord->contextid = context_system::instance()->id;
		$filerecord->component = 'block_exalib';
		$filerecord->filearea = 'item_file';
		$filerecord->filepath = '/';
		$filerecord->filename = str_replace('_', '', basename($file));
		$filerecord->itemid = $item['id'];

		$fs->create_file_from_pathname($filerecord, $file);
	}
}*/


exit;



$file = __DIR__.'/files2016/';
echo $file;
exit;

//files for abstracts 2018 angerer

$fs = get_file_storage();

foreach ($csv as $i => $item) {
	if (!$item['number']) {
		echo 'n';
		continue;
	}

	$item['id'] = 12000 + $item['number'];

	$file = __DIR__.'/files2016/_'.$item['number'].'.pdf';
	if (file_exists($file)) {
		$fs->delete_area_files(context_system::instance()->id,
			'block_exalib',
			'item_file',
			$item['id']);

		$filerecord = new stdClass();
		$filerecord->contextid = context_system::instance()->id;
		$filerecord->component = 'block_exalib';
		$filerecord->filearea = 'item_file';
		$filerecord->filepath = '/';
		$filerecord->filename = str_replace('_', '', basename($file));
		$filerecord->itemid = $item['id'];

		$fs->create_file_from_pathname($filerecord, $file);
	}
}


exit;
// die('disabled');


// abstracts
/* */
$csv = file_get_contents('ECCOJC_11-S1_Abstract_UPDATED.CSV');
$csv = stringToCsv($csv, ',', true);

foreach ($csv as $i => $item) {
	$item['year'] = 2017;
	$item['name'] = $item['name1'].$item['name2'];

	$item['results'] = str_replace('<bold>Results:</bold> ', '', $item['results']);
	$item['methods'] = str_replace('<bold>Methods:</bold> ', '', $item['methods']);
	$item['background'] = str_replace('<bold>Background:</bold> ', '', $item['background']);
	$item['conclusion'] = str_replace('<bold>Conclusions:</bold> ', '', $item['conclusion']);

	// var_dump($item);
	$item['id'] = 7000 + 1 + $i;
	// $DB->execute('INSERT INTO {block_exalib_item} (id) VALUES ('.$item['id'].')');
	// g::$DB->update_record('block_exalib_item', $item, ['id' => $item['id']]);

	$category_id = 51301;
	g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id]);

	$category_id = 51100 + 6;
	// var_dump(['itemid'=>$item['id']]);
	// g::$DB->delete_records('block_exalib_item_category', ['item_id'=>$item['id']]);
	// exit;
	// g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id]);
}
exit;


exit;
$csv = file_get_contents('2017_03_13_MASTER_2016Presentation_upload.csv');
$csv = stringToCsv($csv, ',', true);
$fs = get_file_storage();

foreach ($csv as $i => $item) {
	if (!$item['number']) {
		echo 'n';
		continue;
	}

	$item['id'] = 12000 + $item['number'];

	$file = __DIR__.'/files2016/_'.$item['number'].'.pdf';
	if (file_exists($file)) {
		$fs->delete_area_files(context_system::instance()->id,
			'block_exalib',
			'item_file',
			$item['id']);

		$filerecord = new stdClass();
		$filerecord->contextid = context_system::instance()->id;
		$filerecord->component = 'block_exalib';
		$filerecord->filearea = 'item_file';
		$filerecord->filepath = '/';
		$filerecord->filename = str_replace('_', '', basename($file));
		$filerecord->itemid = $item['id'];

		$fs->create_file_from_pathname($filerecord, $file);
	}
}

exit;


/* */

exit;

$csv = file_get_contents('2017_03_13_MASTER_2016Presentation_upload.csv');
$csv = stringToCsv($csv, ',', true);
$fs = get_file_storage();

foreach ($csv as $i => $item) {
	if (!$item['number']) {
		echo 'n';
		continue;
	}

	$item['id'] = 12000 + $item['number'];
	if ($item['type'] === 'Congress Presentations') {
		$category_id = 51302;
	} elseif ($item['type'] === 'Congress Presentations: Educational programme') {
		$category_id = 51204;
	} elseif ($item['type'] === 'Congress Presentations: Plenary') {
		$category_id = 51205;
	} else {// abstracts
/* */
$csv = file_get_contents('ECCOJC_11-S1_Abstract_UPDATED.CSV');
$csv = stringToCsv($csv, ',', true);

foreach ($csv as $i => $item) {
	$item['year'] = 2017;
	$item['name'] = $item['name1'].$item['name2'];

	$item['results'] = str_replace('<bold>Results:</bold> ', '', $item['results']);
	$item['methods'] = str_replace('<bold>Methods:</bold> ', '', $item['methods']);
	$item['background'] = str_replace('<bold>Background:</bold> ', '', $item['background']);
	$item['conclusion'] = str_replace('<bold>Conclusions:</bold> ', '', $item['conclusion']);

	// var_dump($item);
	$item['id'] = 7000 + 1 + $i;
	// $DB->execute('INSERT INTO {block_exalib_item} (id) VALUES ('.$item['id'].')');
	// g::$DB->update_record('block_exalib_item', $item, ['id' => $item['id']]);

	$category_id = 51301;
	g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id]);

	$category_id = 51100 + 6;
	// var_dump(['itemid'=>$item['id']]);
	// g::$DB->delete_records('block_exalib_item_category', ['item_id'=>$item['id']]);
	// exit;
	// g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id]);
}
exit;
		var_dump($item);
		die('x');
	}

	// $DB->execute('INSERT INTO {block_exalib_item} (id) VALUES ('.$item['id'].')');
	// g::$DB->update_record('block_exalib_item', $item, ['id' => $item['id']]);

	// g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id]);

	// var_dump($category_id);

	/* *
	$category_id = 51100;
	if ($item['category1'] > 0) {
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id+$item['category1']]);
	}
	if ($item['category2'] > 0) {
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id+$item['category2']]);
	}
	if ($item['category3'] > 0) {
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id+$item['category3']]);
	}
	if ($item['category4'] > 0) {
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id+$item['category4']]);
	}
	if ($item['category5'] > 0) {
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id+$item['category5']]);
	}
	/* */

	/* *
	$file = __DIR__.'/files2016/_'.$item['number'].'.pdf';
	if (file_exists($file)) {
		$fs->delete_area_files(context_system::instance()->id,
			'block_exalib',
			'item_file',
			$item['id']);

		$filerecord = new stdClass();
		$filerecord->contextid = context_system::instance()->id;
		$filerecord->component = 'block_exalib';
		$filerecord->filearea = 'item_file';
		$filerecord->filepath = '/';
		$filerecord->filename = str_replace('_', '', basename($file));
		$filerecord->itemid = $item['id'];

		$fs->create_file_from_pathname($filerecord, $file);
	}
	/* */

	// var_dump(['itemid'=>$item['id']]);
	// g::$DB->delete_records('block_exalib_item_category', ['item_id'=>$item['id']]);
	// exit;
	// g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id]);
}

exit;

// presentations
/*
delete from mdl_block_exalib_item where id>=10000;
delete from mdl_block_exalib_item_category where item_id>=10000;
*/
exit;
$csv = file_get_contents('2017_03_13_MASTER_Presentation_upload.csv');
$csv = stringToCsv($csv, ',', true);
$fs = get_file_storage();

foreach ($csv as $i => $item) {
	if (!$item['number']) {
		continue;
	}

	$item['id'] = 10000 + $item['number'];
	if ($item['type'] === 'Congress Presentations') {
		$category_id = 51302;
	} elseif ($item['type'] === 'Congress Presentations: Educational programme') {
		$category_id = 51204;
	} elseif ($item['type'] === 'Congress Presentations: Plenary') {
		$category_id = 51205;
	} else {
		die('x');
	}

	// g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id]);

	var_dump($category_id);
	// $DB->execute('INSERT INTO {block_exalib_item} (id) VALUES ('.$item['id'].')');
	// g::$DB->update_record('block_exalib_item', $item, ['id' => $item['id']]);

	/* *
	$category_id = 51100;
	if ($item['category1'] > 0) {
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id+$item['category1']]);
	}
	if ($item['category2'] > 0) {
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id+$item['category2']]);
	}
	if ($item['category3'] > 0) {
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id+$item['category3']]);
	}
	if ($item['category4'] > 0) {
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id+$item['category4']]);
	}
	if ($item['category5'] > 0) {
		g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id+$item['category5']]);
	}
	/* *

	$file = __DIR__.'/files2017/_'.$item['number'].'.pdf';
	if (file_exists($file)) {
		$fs->delete_area_files(context_system::instance()->id,
			'block_exalib',
			'item_file',
			$item['id']);

		$filerecord = new stdClass();
		$filerecord->contextid = context_system::instance()->id;
		$filerecord->component = 'block_exalib';
		$filerecord->filearea = 'item_file';
		$filerecord->filepath = '/';
		$filerecord->filename = str_replace('_', '', basename($file));
		$filerecord->itemid = $item['id'];

		$fs->create_file_from_pathname($filerecord, $file);
	}

	// var_dump(['itemid'=>$item['id']]);
	// g::$DB->delete_records('block_exalib_item_category', ['item_id'=>$item['id']]);
	// exit;
	// g::$DB->insert_record('block_exalib_item_category', ['item_id'=>$item['id'], 'category_id' => $category_id]);
	*/
}
/* */

// var_dump($csv);

}