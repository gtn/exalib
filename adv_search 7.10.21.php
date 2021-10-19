<?php
// This file is part of ExabIs Library
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Library is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

if (!defined('BLOCK_EXALIB_IS_ADMIN_MODE')) {
	define('BLOCK_EXALIB_IS_ADMIN_MODE', 0);
}

require __DIR__.'/inc.php';
// sonderzeichen löschen
/*
$items = $DB->get_records('block_exalib_item');
foreach ($items as $item) {
	if ($item->methods != html_entity_decode($item->methods, ENT_NOQUOTES, 'UTF-8')) {
		var_dump($item->methods);
		var_dump(html_entity_decode($item->methods, ENT_NOQUOTES, 'UTF-8'));

		$DB->update_record('block_exalib_item', (object)[
			'id' => $item->id,
			'methods' => html_entity_decode($item->methods, ENT_NOQUOTES, 'UTF-8')
		]);
	}
}
exit;
*/

require_login();

block_exalib_init_page();
/*
if (BLOCK_EXALIB_IS_ADMIN_MODE) {
	block_exalib_require_cap(BLOCK_EXALIB_CAP_MANAGE_CONTENT);
} else {
	block_exalib_require_cap(BLOCK_EXALIB_CAP_USE);
}
*/
$urloverview = new moodle_url('/blocks/exalib');
$urlpage = block_exalib_new_moodle_url();
$urlsearch = new block_exalib\url($urlpage, array('page' => null, 'q' => null));
$urladd = new moodle_url($urlpage, array('show' => 'add'));
$urlcategory = new moodle_url($urlpage, array('page' => null, 'q' => null, 'category_id' => null));
$resulttrue=false;
$filtercontent = new stdClass;
$PAGE->set_url($urlpage);
			$sm2="";
			$sm3="";
			$dispNone="";
			$sm9="";
			$sm12="";
$topGroups = array(11=>'Abstracts', 12=>'Documents', 13=>'Images', 14=>'Podcasts', 15=>'Webcasts');
$ibd_first=false;
$q = optional_param('q', '', PARAM_TEXT);
$ibd = optional_param('view2', '', PARAM_TEXT);
if ($ibd==10) {$ibd=1;$ibd_first=true;}//erstmaliger aufruf vom ibd curriculum, dann idb=10, wenn wegen Filter reload dann 1
$category_ids = optional_param_array('category_ids', array(), PARAM_INT);
$sub_filter_id = optional_param('sub_filter_id', '', PARAM_INT);
$search_by = optional_param('search_by', 'name', PARAM_TEXT);
$filter_year = optional_param('filter_year', 0, PARAM_INT);

$filter_category = optional_param('filter_category', '', PARAM_INT); //Dropdown Category or parent category if it comes from IBD
$filter_sub_type = optional_param('filter_sub_type', '', PARAM_INT);
$guidelines = optional_param('guidelines', "", PARAM_TEXT);
$latestC = optional_param('latestC', "", PARAM_TEXT);
$archiveC = optional_param('archiveC', "", PARAM_TEXT);
$ibdget = "";
if ($ibd==1) $ibdget = "?view2=1";
if ($guidelines!="") {
			$filter_sub_type = '51206';
			$filter_category = '51303';
}
		

			
$perpage = 20;
$page    = optional_param('page', 0, PARAM_INT);

$items = null;
$pagingbar = null;
$result_filter_summary = new stdClass;
$categoryManager = new block_exalib_category_manager(BLOCK_EXALIB_IS_ADMIN_MODE, block_exalib_course_settings::root_category_id());

if (BLOCK_EXALIB_IS_ADMIN_MODE) {
	$sqlItemWhere = "";
} else {
	$sqlItemWhere = "AND item.online > 0
		AND (item.online_from=0 OR item.online_from IS NULL OR item.online_from <= ".time().")
		AND (item.online_to=0 OR item.online_to IS NULL OR item.online_to >= ".time().")";
}
if ($ibd==1) $sqlItemWhere .= " AND ibd=1";
$sql = "SELECT DISTINCT year, year as tmp FROM {block_exalib_item} AS item 
WHERE 1=1 $sqlItemWhere
AND year>2015
ORDER BY year DESC
";
$years = $DB->get_records_sql_menu($sql);


if ($category_ids) {
	$q = trim($q);
	$qparams = preg_split('!\s+!', $q);

	$sqlWhere = $sqlItemWhere;
	$sqlJoin = "";
	$sqlJoinSubfilter = "";
	$sqlParams = array();


	if ($category_ids) {
		$filter_ids = [];
		foreach ($category_ids as $category_id) {
			$cat = $categoryManager->getcategory($category_id);

			if ($cat) {
				$filter_ids = array_merge($filter_ids, $cat->self_inc_all_sub_ids);
			}
		}
	}

	if ($filter_ids) {
		$sqlJoin .= " JOIN {block_exalib_item_category} AS ic_filter ON (ic_filter.item_id = item.id AND (ic_filter.category_id IN (".join(',',$filter_ids).")))";
	}

/*
$filter_category nicht als condition nehmen wenn der aufruf von ibd curriculum kommt
wenn zb in IBD auf 1.1 geklickt wird, ist die filter_category der wert von 1
damit beim dropdown category ein besserer wert angezeigt wird, 1.1 ist ja nicht drinnen, als 1 im dropdown selecten
*/
	if ($filter_category && $ibd!=1) {
		$sqlJoin .= " JOIN {block_exalib_item_category} filter_category ON item.id=filter_category.item_id AND filter_category.category_id=?";
		$sqlParams[] = $filter_category;
	}
	if ($filter_sub_type) {
		$sqlJoin .= " JOIN {block_exalib_item_category} filter_sub_type ON item.id=filter_sub_type.item_id AND filter_sub_type.category_id=?";
		$sqlParams[] = $filter_sub_type;
	}

	if ($q) {
		$result_filter_summary->content.=", searchstring: ".$q;
		if ($search_by == 'title') {
				$result_filter_summary->content.=" in title";
		} elseif ($search_by == 'author') {
				$result_filter_summary->content.=" in author";
		} elseif ($search_by == 'source') {
				$result_filter_summary->content.=" in source";
		}
		if ($search_by == 'title') {
					$search_fields = ['item.name'];
		} elseif ($search_by == 'author') {
					$search_fields = ['item.authors'];
		} elseif ($search_by == 'source') {
					$search_fields = ['item.source'];
		}elseif ($search_by == 'all') {
						$search_fields = [
						'item.link', 'item.source', 'item.file', 'item.name', 'item.authors',
						'item.abstract', 'item.content', 'item.link_titel', 'item.search_abstract', 'item.background', 'item.methods', 'item.results', 'item.conclusion', 'item.affiliations',
					];
		} else {
					$search_fields = ['item.name'];
		}
		if (count($qparams)>3){
			$sqlWhere .= " AND ".$DB->sql_concat_join("' '", $search_fields)." LIKE ?";
			$sqlParams[] = "%".$DB->sql_like_escape($q)."%";
		}else{ 
			foreach ($qparams as $i => $qparam) {
				
	
				/*Angerer 01.06.2021 $sqlJoin .= " LEFT JOIN {block_exalib_item_category} ic$i ON item.id=ic$i.item_id";
				$sqlJoin .= " LEFT JOIN {block_exalib_category} c$i ON ic$i.category_id=c$i.id";*/
				$sqlWhere .= " AND ".$DB->sql_concat_join("' '", $search_fields)." LIKE ?";
				$sqlParams[] = "%".$DB->sql_like_escape($qparam)."%";
			}
		}
	}
	if ($latestC!="") {
		$sqlWhere .= ' AND item.time_modified > ?';
		$bevor= time();
		$bevor = $bevor - (60*60*24*92);
		$sqlParams[] =  $bevor; 
	}else	if ($filter_year) {
		$sqlWhere .= ' AND item.year=?';
		$sqlParams[] = $filter_year;
		$result_filter_summary->content.=", year: ".$filter_year;
	}
	if ($archiveC!="") {
		$sqlWhere .= ' AND item.year<2016';
		$result_filter_summary->content.=", year < 2016 ";
	}

	$sql = "SELECT COUNT(*) FROM (SELECT item.id
	FROM {block_exalib_item} AS item 
	$sqlJoin
	$sqlJoinSubfilter
	WHERE 1=1 $sqlWhere
	GROUP BY item.id
	) AS x";
	$count = $DB->get_field_sql($sql, $sqlParams);

	$pagingbar = new paging_bar($count, $page, $perpage, new moodle_url($_SERVER['REQUEST_URI']));

	$sql = "SELECT item.*";
	if ($filter_ids) 	$sql .= ", ic_filter.category_id AS icfcat";

	$sql.="	FROM {block_exalib_item} AS item 
	$sqlJoin
	$sqlJoinSubfilter
	WHERE 1=1 $sqlWhere
	GROUP BY item.id
	ORDER BY maincategory,year DESC,name
	LIMIT ".$page*$perpage.', '.$perpage;
	
	
	
	//echo $sql; 
	//print_r ($sqlParams);die;
	
	$items = $DB->get_records_sql($sql, $sqlParams);

	if ($items !== null) $resulttrue=true;


	// SUBFILTER
	$sub_filter_categories = [];
	/*
	$sql = "SELECT c.category_id AS id, COUNT(item.id) AS cnt
	FROM {block_exalib_item} AS item 
	$sqlJoin
	JOIN mdl_library_item_category AS c ON (c.item_id = item.id)
	WHERE 1=1 $sqlWhere
	GROUP BY c.category_id
	ORDER BY c.category_id";
	$sub_filter_categories = $DB->get_records_sql($sql, $sqlParams);
	$all_categories = $DB->get_records_sql("SELECT id, name, parent_id
	FROM mdl_library_categories AS category
	WHERE parent_id!=1 -- no types in select
	ORDER BY name");

	foreach ($sub_filter_categories as $sub_filter_category) {
		if (!$all_categories[$sub_filter_category->id]) {
			unset($sub_filter_categories[$sub_filter_category->id]);
			continue;
		}

		$sub_filter_category->name = $all_categories[$sub_filter_category->id]->name;
		$sub_filter_category->parent_id = $all_categories[$sub_filter_category->id]->parent_id;

		if ($sub_filter_category->parent_id && $all_categories[$sub_filter_category->parent_id]->parent_id) {
			$sub_filter_category->name = $all_categories[$sub_filter_category->parent_id]->name.' >> '.$sub_filter_category->name;
		}
	}
	uasort($sub_filter_categories, create_function('$a, $b', 'return strcmp($a->name, $b->name);'));

	/*
	echo "<pre>";
	print_r($sub_filter_categories);
	*/
}

$output = block_exalib_get_renderer();

echo $output->header();

?>
<script>
	$(function(){
		// un/check all
		var $all_cat = $('#search-all-categories');
		var $category_ids = $('input[name="category_ids[]"]:visible'); // :visible, because other form has hidden category_ids[] fields!

		$all_cat.click(function(){
			var checked = $(this).prop('checked');
			$category_ids.prop('checked', checked);
		});

		$category_ids.click(function(){
			if ($category_ids.not(':checked').length == 0) {
				$all_cat.prop('checked', true);
			}
			else if ($category_ids.not(':checked').length > 0) {
				$all_cat.prop('checked', false);
			}
		});

		if ($all_cat.prop('checked')) {
			$all_cat.triggerHandler('click');
		} else {
			$($category_ids[0]).triggerHandler('click');
		}
	});
</script>


<div class="container Checkbox">
	<?php echo '<form method="get" action="adv_search.php'.$ibdget.'#searchresult" class="form-horizontal">'; ?>
	<div class="row">
		<div class="col-sm-6">
			<div class="ecco_lib form-group">

				
					<div class="col-sm-9"><?php
						if ($resulttrue==false)
						{ 
						if ($ibd==1) echo '<h1 class="sectionname">Welcome to the IBD Curriculum!</h1>';
						else echo '<h1 class="sectionname ">Welcome to the e-CCO Library!</h1>';?>
						
							<?php $dispNone="hideInput";
							
							if ($ibd==1) echo '<input type="hidden" name="view2" value="1" />'; 
						} 	//end result true ?>
		
							<label style="margin:0;padding:0;list-style:none;" for="searchtext" class=" control-label">Search:</label>
							<input id="searchtext" name="q" type="text" class="libary_search_input form-control" value="<?php echo $q; ?>">		
							
							<label style="margin:0;padding:0;list-style:none;" for="searchtext" class=" control-label">Search by:</label>
						
							<div class="cselect">
								<select name="search_by" class="form-control">
									<?php
									echo '<option value="title" ';
									if ($search_by == 'title') echo 'selected="selected"'; 
									echo '>Title</option><option value="author" ';
									if ($search_by == 'author') echo 'selected="selected"';
									echo '>Author</option><option value="source" ';
									if ($search_by == 'source') echo 'selected="selected"'; 
									echo '>Source</option><option value="all" ';
									if ($search_by == 'all') echo 'selected="selected"'; 
									echo '>All</option>';
									?>
								</select>
							</div>
						
							<h1 class="nextFilter_area">Refine your search with filters</h1>
							<label style="margin:0;padding:0;list-style:none;" for="searchyear" class="col-sm-2 control-label">Year:</label>	<div class="cselect">
							<?php echo html_writer::select($years, 'filter_year', $filter_year, array('0'=>"..."), array('id'=>'menufilter_year','class'=>'form-control')); ?>
						</div>
				<?php $values = array_map(function($cat) { return $cat->name; }, $categoryManager->getChildren(51001));
						if (count($category_ids)==1 && $category_ids[0]==51301){
							
						}else{
								echo '<label style="margin:0;padding:0;list-style:none;" for="searchtext" class="col-sm-2 control-label">Category:</label>';
								echo html_writer::select($values, 'filter_category', $filter_category,  array(''=>"..."), array('class'=>'form-control'));
								
			/*
								$subtypes = [];
								foreach ($categoryManager->getChildren(51002) as $category) {
									if (in_array($category->id, $category_ids)) {
										$subtypes += $categoryManager->getChildren($category->id) ?: [];
									}
								}
								$values = array_map(function($cat) { return $cat->name; }, $subtypes);

								if ($values) {
									echo '<br> <br>
									<label style="margin:0;padding:0;list-style:none;" for="subtype" class="col-sm-2 control-label">Sub Type:</label>';
									//[51205] => Congress Presentations: Plenary [51206] => Articles: Guidelines [51207] => Articles: Literature Review (ECCO News) [51210] => Articles: Position Papers [51209] => Articles: Topical Reviews [51208] => Articles: Viewpoints [51211] => Webcasts: Educational Programme [51212] => Webcasts: Plenary
									echo html_writer::select($values, 'filter_sub_type', $filter_sub_type, array(''=>"..."), array('class'=>'form-control'));
									if ($filter_sub_type) {
										$result_filter_summary->content.=', Sub Type: '.$values[$filter_sub_type];
									}
								}*/
						}?>
		
				</div> <!--sm9-->
			</div> <!--formgroup-->
			<div class="container Checkbox12">
				<div class="row">
					<div class="col-sm-6">
					
			      <div class="Checkbox-1">
							<div class="Box-wfmb7k-0 hFmYJS"> 
								<input 
								<?php if(in_array(51302, $category_ids) || $ibd_first==true) { echo ' checked="checked" ';}  
											else if (!$category_ids) { echo ' checked="checked" ';} 
								?>
								 type="checkbox" id="search-all-categories" value="Abstracts" data-testid="navigator-Zustand-checkbox-modal-Neu" aria-labelledby="Zustand-checkbox-modal-Neu-label" class="Checkbox__CheckboxInput-sc-7kkiwa-4 dVAswu">
								<img src="pix/all1.png" alt="All-Materials">
								All
							</div>
						</div>
				
						<div class="Checkbox-2">
								<div class="Box-wfmb7k-0 hFmYJS">
								<input 
								<?php if(in_array(51301, $category_ids) || $ibd_first==true) { echo ' checked="checked" ';}  
											else if (!$category_ids) { echo ' checked="checked" ';} 
								?>
								name="category_ids[]" value="51301" type="checkbox" id="" data-testid="" aria-labelledby="" class="">
								<img  src="pix/congress-abstracts1.png" alt="Congress-Abstracts">Congress Abstracts	</div>
						</div>
				
						<div class="Checkbox-3">
								<div class="Box-wfmb7k-0 hFmYJS">
								<input 
								<?php if(in_array(51304, $category_ids) || $ibd_first==true) { echo ' checked="checked" ';}  
											else if (!$category_ids) { echo ' checked="checked" ';} 
								?>
								name="category_ids[]" value="51304" type="checkbox" id="Zustand-checkbox3-modal-Neu" data-testid="navigator-Zustand-checkbox-modal-Neu" aria-labelledby="Zustand-checkbox-modal-Neu-label" class="Checkbox__CheckboxInput-sc-7kkiwa-4 dVAswu">
								<img src="pix/tools-skills1.png" alt="Tools-Skills">Tools & Skills
								</div>
						</div>
			    </div><!--sm6 2a-->
					<div class="col-sm-6">
					  <div class="Checkbox-4">
								<div class="Box-wfmb7k-0 hFmYJS">
								<input 
								<?php if(in_array(51303, $category_ids) || $ibd_first==true) { echo ' checked="checked" ';}  
											else if (!$category_ids) { echo ' checked="checked" ';} 
								?>
								 name="category_ids[]" value="51303" type="checkbox" id="Zustand-checkbox4-modal-Neu" data-testid="navigator-Zustand-checkbox-modal-Neu" aria-labelledby="Zustand-checkbox-modal-Neu-label" class="Checkbox__CheckboxInput-sc-7kkiwa-4 dVAswu">
								<img src="pix/publications1.png" alt="Publications">Publications
								</div>
						</div>
						
						<div class="Checkbox-5">
								<div class="Box-wfmb7k-0 hFmYJS">
								<input 
								<?php if(in_array(51302, $category_ids) || $ibd_first==true) { echo ' checked="checked" ';}  
											else if (!$category_ids) { echo ' checked="checked" ';} 
								?>
								 name="category_ids[]" value="51302" type="checkbox" id="Zustand-checkbox5-modal-Neu" data-testid="navigator-Zustand-checkbox-modal-Neu" aria-labelledby="Zustand-checkbox-modal-Neu-label" class="Checkbox__CheckboxInput-sc-7kkiwa-4 dVAswu">
								<img src="pix/congress-slides1.png" alt="Congress-Slides">Congress Slides
								</div>
						</div>
						
						<div class="Checkbox-6">
								<div class="Box-wfmb7k-0 hFmYJS">
								<input 
								<?php if(in_array(51305, $category_ids) || $ibd_first==true) { echo ' checked="checked" ';}  
											else if (!$category_ids) { echo ' checked="checked" ';} 
								?>
								  name="category_ids[]" value="51305" type="checkbox" id="Zustand-checkbox6-modal-Neu" data-testid="navigator-Zustand-checkbox-modal-Neu" aria-labelledby="Zustand-checkbox-modal-Neu-label" class="Checkbox__CheckboxInput-sc-7kkiwa-4 dVAswu">
								<img src="pix/videos-podcasts1.png" alt="Videos-Podcasts">Videos & Podcasts
								</div>
								<?php
									if ($ibd==1) {
										echo '<input type="hidden" name="" value="">';
									}
								?>
						</div>
					</div> <!--sm6 2b-->		
				</div> <!--row-->
			</div> <!--container Checkbox 2-->
			<div class="row bottomSearchBar">
			<div class="col-sm-12 nextFilter_area">
											<input value="Search" type="submit" class="search">
											<input value="Clear Filter" type="button" class="btn-seFo" >
			</div>
			</div>
		</div> <!--sm6-->
		<div class="col-sm-6">
			<div class="col-lg-4 eccoSearchRight">
							<div class="form-group">
		
								<div class="col-sm-12">
									<h1> Quicksearch</h1>
		
									<input value="Latest Content" name="latestC" type="submit" class="form-control">
								</div>
							</div>
							<div class="form-group">
								<div class="col-sm-12">
									<input value="Guidelines" name="guidelines" type="submit" class="form-control">
								</div>
							</div>
							<div class="form-group">
								<div class="col-sm-12">
							    	<input value="Keywords" name="keywords" type="button" onclick="window.location.href='https://e-learning.ecco-ibd.eu/blocks/exalib/pdfjs/PlainViewer/web/viewer.html?file=../../pdfs/2019_ECCO_e-Library_categories_and_keywords.pdf'" class="form-control">
								</div>
							</div>
							<?php if (is_dir(__DIR__.'/../../mod/library')) {
											echo '<div class="form-group">
												<div class="col-sm-12">
													<!--<input value="Archive 2011-2015" type="submit" name="archiveC" class="form-control" onclick="document.location.href=\''.$CFG->wwwroot.'/mod/library/adv_search.php\';">-->
											    <input value="Archive 2011-2015" type="button" name="archiveC" class="form-control" onclick="window.location.href=\'https://e-learning2017.ecco-ibd.eu/mod/library/adv_search.php\'">
	
												</div>
											</div>';
										}?>
			</div><!--eccoSearchRight-->
		</div><!--sm6-->
	</div> <!--row-->
	</form>
</div> <!--container Checkbox-->	


<div class="container Checkbox">
<div class="row">
			
			
			<?php 
			
			/*$i==1;$j=2;
				foreach ($categoryManager->getChildren(51002) as $category) {
						if ($ibd==1 AND $category->id=="51301") continue;
						$filtercontent->contenttype.='<div class="col-sm-6"><div class="checkbox'.$j.'"><label><input type="checkbox" name="category_ids[]" value="'.$category->id.'" ';
						if(in_array($category->id, $category_ids)) { $filtercontent->contenttype.='checked="checked"';$result_filter_summary->ctype.=' ,'.$category->name;$catidtemp=$category->id;}
							$filtercontent->contenttype.='/><img src="pix/contenttypicon'.$category->id.'.png" class="searchLibCheckIcon" /><span class="searchLibCheckTxt">'.$category->name.'</span></label></div></div>';
							if ($i==1) {$filtercontent->contenttype.= '</div><div class="row">';$i==0;}
							$i++;$j++;
				}
					if ($result_filter_summary->ctype) $result_filter_summary->content.=", ContentType: ".$result_filter_summary->ctype;
					$filtercontent->contenttype.='</div>';
				echo $filtercontent->contenttype;


                    echo '<div class="row eccosearchvideocenter">';
                       block_exalib_print_jwplayer(array(
                            'file'    => $CFG->wwwroot . "/blocks/exalib/images/Video/2019_02_21_MASTER_eLibrary_VIDEO.mp4",
                            'width'    => "100%",
                            'height' => "100%",
                            'usePreviewImage' => true
                        ));
                    echo '</div></div>';
		
		if ($resulttrue==false){			
				echo '</div><!-- / col-sm-4 -->
						</div><!-- /row -->	
					    <div class="row">
					    
					        <p><h3>Our new e-Library facilitates user-friendly and high-quality search functions</h3></p>
<p>We have introduced a transparent indexing system based on the IBD Curriculum for all search material as of ECCO’16 onwards.<br><br>Thematic categories used for the e-Library are aligned with the broad ECCO IBD Curriculum categories and constitute the back-bone of filtering the search function.<br><br> The predefined keywords ensure that the same system of terminology is used for roughly inventorying the whole content of the e-Library to facilitate free text search, which also applies to the abstracts.<br>
You can access the current category and keyword overview used for indexing the e-Library search function  <a href="'. $CFG->wwwroot . '/blocks/exalib/pdfjs/pdfs/2019_ECCO_e-Library_categories_and_keywords.pdf">HERE</a>.<p>

                        </div>
						<div class="row bottomSearchBar">
							<div class="col-sm-12">
								<input value="Clear Filter" type="button" class="clear-filter btn-seFo" onclick="document.location.href=\''.$_SERVER['PHP_SELF'].'\';">
								<input value="Search" type="submit" class="btn-seFo">
							</div>
						</div>
					</form>

				</div>';
		}	 */
		?>
		
		<?php if (false && count($category_ids) >= 1 && $sub_filter_categories): ?>
		<div class="libary_top_filter">
		<form method="get" action="adv_search.php<?php echo $ibdget;?>">
			<input name="q" type="hidden" value="<?php p($q) ?>" />
			<input name="search_by" type="hidden" value="<?php p($search_by) ?>" />
			<input name="view2" type="hidden"  value="<?php echo $ibd; ?>" />
			<?php
				foreach ($category_ids as $id) {
					echo '<input name="category_ids[]" type="hidden" value="'.$id.'" />';
				}
			?>
			<table style="width: 100%;">
				<tbody><!-- tr>
					<td colspan="4"><h4>e-CCO Library: <?php echo $topGroups[reset($category_ids)]; ?></h4></td>
				</tr -->
				<tr>
					<td style="width:40px;">Filter: </td>
					<td>


							<select name="sub_filter_id">
								<?php
									foreach ($sub_filter_categories as $sub_filter_category) {
										echo '<option value="'.$sub_filter_category->id.'"';
										if ($sub_filter_id == $sub_filter_category->id) {
											echo ' selected="selected"';
										}
										echo '>';
										echo $sub_filter_category->name.' ('.$sub_filter_category->cnt.')</option>';
									}
								?>
						  	</select>
					</td>
					<td style="text-align:right;vertical-align: bottom;">
						<input value="Apply Filter" type="submit">
					</td>
				</tr>
			</tbody></table>
		</form>
		</div>
		<?php endif; ?>


<div style="margin-top: 30px;">
	<div class="row">
		
		<?php 
		if ($ibd!=1) {
			echo '<div class="col-sm-4 col-md-3 col-sm-push-8 col-md-push-9 exalibSearchBarleft">';
		
		
			/*if ($resulttrue==true){	
				echo '<div class="exalibSearchBarleftCnt"><h2>Filtering</h2>';
				echo '<form method="get" action="adv_search.php'.$ibdget.'">';
				echo $filtercontent->searchbar;
				echo $filtercontent->searchby;
				echo $filtercontent->contenttype;
				echo $filtercontent->year;
				echo $filtercontent->cat;
			
			echo '<div class="form-group">
							<div class="">
				
				<input value="Search" class="form-control" type="submit" class="btn-seFo">
				</div>
						</div>';
				echo $filtercontent->archivebutton;
				if ($ibd==1) echo '<input type="text" name="view2" value="1" />';
					echo '<div class="form-group">
							<div class="">
								<input  disabled="disabled" value="Help" name="keywords" type="submit" class="form-control">
							</div>
						</div>';
				
				echo "</form></div>";
			}*/
			echo '</div>';
		}
		
		//<!--  Searchbar rechts hier einfügen --->
	
		
		echo '<div class="col-sm-12 col-md-12 col-sm-pull-12 col-md-pull-12" id="searchresult">';
			
			if ($ibd==1) {echo '<h2>ECCO IBD Curriculum</h2>';
				echo '<h5 class="sectionname" style="font-size:1.4rem">'.$cat->name.'</h5>';
			}
			
			

			if ($items !== null) {

				if ($result_filter_summary->content!="") {};
				$result_filter_summary->content="Search results for:  ".$result_filter_summary->content;
				//echo $result_filter_summary->content;
			
				if (!$items) {
					echo block_exalib_get_string('noitemsfound');
				} else {
					if ($pagingbar) {
						echo $output->render($pagingbar);
					}
					$output->item_list('public', $items);
					if ($pagingbar) {
						echo $output->render($pagingbar);
					}
				}
			}
			/*  else {
				?>
				<div style="text-align: center; padding: 200px 60px 0 0;" class="libary_nores">
					Not found
				</div>
				<?php
			}
			*/
			if ($ibd==1) {
				//echo '<a href="../../course/view.php?id=61#ibdindex">back to the IBD Curriculum</a>';  
			  echo '<div style="text-align:center"><input style="background-color: #003772;color:#fff" value="back to IBD Curriculum" type="button" class="clear-filter btn-seFo" onclick="document.location.href=\'../../course/view.php?id=61#ibdindex\';"></div>';
			}
		echo '</div>'; //col-sm-9 
	echo '</div>'; //row 
	
	?>
	
</div>
</div>
<?php
echo $output->footer();
