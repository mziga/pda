<?php
	/*
		Plagiarism Detection Assistant
		(Prototype)
		
		Developed by Makuc Ziga (2013)
		Licensed under the Creative Commons Attribution ShareAlike 2.5 Slovenia
			http://creativecommons.org/licenses/by-sa/2.5/
		and
			http://creativecommons.si/
		(Refers to source code only and not web template)
	*/
	
/* When pdf is being generated, only graph should be shown */
if(!isset($_GET[gen_pdf])){
	include('header.php');
}
 ?>
<?php

function callFb($url){
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function quicksort($input_array) {
    if(count($input_array)==0){
		return $input_array;
	}
	$low=array();
    $high=array();
	for($i=1; $i<count($input_array); $i++) {
		if($input_array[$i]<=$input_array[0]){
			$low[]=$input_array[$i];
		}else{
			$high[]=$input_array[$i];
		}
	}
    return array_merge(quicksort($low), array($input_array[0]), quicksort($high));
}

function connectedComponents ($aEdges) {
	// Developed by Csaba Gabor
	// given an edge array, $aEdges, this will return an array
	// of connected components. Each element of the returned
	// array, $aTrees, will correspond to one component and
	// have an array of the vertices in that component.
	$aTrees = array();
	$result = array();
	$aAdj = array();
	$ctr=-1;
	foreach ($aEdges as $br){ // Construct V/E adjacancy array
		foreach ($br as $i=>$v){
			if (!array_key_exists($v,$aAdj)){
				$aAdj[$v]=array($br[1-$i]);
			}else{
				array_push ($aAdj[$v], $br[1-$i]);
			}
		}
	}
	foreach ($aAdj as $v=>$aTrees[++$ctr]){ // Now build distinct
		for ($i=0;$i<sizeof($aTrees[$ctr]);++$i){ // components
			$aV = &$aTrees[$ctr];
			/* If $aAdj[$aV[$i]] is not array, warning is showed, but function works. If anything else is changed it does not work, so
				when this happens, "IGNORE" is added to array. When showing, ignore every row which contains "IGNORE" at the end */
				/* This is also changed in this algorithm in comparison to original */
			if(is_array($aAdj[$aV[$i]])){
				$merged = array_merge($aV, $aAdj[$aV[$i]]);
			}else{
				$merged = array_merge($aV, array("IGNORE"));
			}
			$aV = array_keys(array_flip($merged));
			unset ($aAdj[$aV[$i]]);
		}
	}
	
	//Return only valid clusters
	$internal_counter_i=0;
	for($i=0; $i<sizeof($aTrees); $i++){
		if($aTrees[$i][sizeof($aTrees[$i])-1] != "IGNORE"){
			for($j=0;$j<sizeof($aTrees[$i]); $j++){
				$result[$internal_counter_i][$j]=$aTrees[$i][$j];
			}
			$internal_counter_i++;
		}
	}
	return $result;
} 

?>
<style type="text/css">

.background {
  fill: #eee;
}

line {
  stroke: #fff;
}

text.active {
  fill: red;
}

text.hidden {
	visibility:hidden;
}

rect.hidden {
	visibility:hidden;
}

</style>

	<!-- d3 plugin -->
	<script src="js/d3.v3.min.js"></script>
<script type="text/javascript" src="include/rgbcolor.js"></script> 
<script type="text/javascript" src="include/canvg.js"></script> 
<script type='text/javascript' src='include/jquery.min.js'></script>
 <script src="js/jquery-1.9.1.js"></script>
<script src="js/f1.10.2/jquery-ui.js"></script>
<? if(!isset($_GET[gen_pdf])){

			echo '<div>
				<ul class="breadcrumb">
					<li>
						<a class="ajax-link" href="index.php">Home</a> <span class="divider">/</span>
					</li>
					<li>';

					if(!file_exists("include/config.php")) { echo '<a class="ajax-link" href="install.php">Installation</a>'; } else { echo '<a class="ajax-link" href="visualisation.php">Visualisation</a>'; }
					echo '</li>
				</ul>
			</div>
					
			<div class="row-fluid">';
	}
				if(!isset($_GET[view_match]) && !isset($_GET[view_p]) && !isset($_GET[gen_pdf]) &&!isset($_GET[confirmFB]) &&!isset($_GET[confirmTW]) &&!isset($_GET[deleteFB]) &&!isset($_GET[deleteTW]) &&!isset($_GET[addTWa]) &&!isset($_GET[addFBa]) &&!isset($_GET[confirmDTW]) &&!isset($_GET[confirmDFB])&&!isset($_POST[addFBa]) &&!isset($_POST[addTWa])){
					echo '
					<div class="box span12">
						<div class="box-header well">
							<h2><i class="icon-eye-open"></i>'; if(isset($_GET[gv_project_id]) || isset($_GET[gv_assignment_id]) || isset($_GET[cv_project_id]) || isset($_GET[cmv_assignment_id])) echo 'Visualisation'; else if(isset($_GET[project_id])) echo "Assignments"; else echo 'Projects'; echo'</h2>
						</div>
						<div class="box-content">';
				}
						
						if(file_exists("include/config.php")) { //Check if config file exists
							include("include/config.php");
							/* Connect to Database */
							$mysql_connect = mysql_connect($db_host, $db_user, $db_pass);
							$my_select_db = mysql_select_db($db_name); 
							/* Type of visualisation:
								"cmv_viz" - Co-Occurence Matrix Visualisation
								"gv_viz" - Graph Visualisation
							*/
							$type_of_viz="";
							/*Show Graph Visualisation */
							if(isset($_GET['gv_project_id']) || isset($_GET['gv_assignment_id'])){
								if(!isset($_GET[gen_pdf])){
									echo '<h1><i>Graph</i> <small>visualisation</small></h1><br>';
								}
								/* Check for wrong input in post */
								$project_id=mysql_real_escape_string($_GET['gv_project_id']);
								$assignment_id=mysql_real_escape_string($_GET['gv_assignment_id']);
								$min_similarity=mysql_real_escape_string($_GET['min_similarity']);
		
								if(mysql_real_escape_string($_GET['show'])=="0"){
									$show="M.match_status=0";
								}else if(mysql_real_escape_string($_GET['show'])=="1"){
									$show="M.match_status=1";
								}else{
									$show="NOT (M.match_status=2 OR M.match_status=3)";
								}
								/* Get recommended value to be set at beginning */
								if(!isset($_GET[min_similarity])){
									/* Check similarity by n-th (number_of_persons_similarity) match by match_first_id value and set it to be default min_similarity */
									if($assignment_id!=""){
										$sql ='SELECT * FROM matches M WHERE M.assignment_id="'.$assignment_id.'" AND '.$show.' ORDER BY M.match_first_sim DESC, M.match_second_sim DESC LIMIT '.$number_of_persons_similarity.',1';
									}else{ 
										$sql ='SELECT * FROM matches M, assignment A WHERE M.assignment_id=A.assignment_id AND A.project_id="'.$project_id.'" AND '.$show.' ORDER BY M.match_first_sim DESC, M.match_second_sim DESC LIMIT '.$number_of_persons_similarity.',1';
									}	
									$result=mysql_query($sql);
									$row=mysql_fetch_assoc($result);
									$min_similarity=$row[match_first_sim];
								}
								
								if($min_similarity=="") $min_similarity=0;
								
								/* Find max similarity to normalise values to 100 - if one is 10% and second 20%...First should be 50% and second 100% */
								/* First check if min similarity exist */
								if($assignment_id!=""){
									$max_sql ='SELECT MAX(M.match_first_sim) AS "MaxFirstSim", MAX(M.match_second_sim) AS "MaxSecondSim" FROM matches M, assignment A WHERE M.assignment_id=A.assignment_id AND A.assignment_id="'.$assignment_id.'" AND '.$show.';';
								/* Else show results by project */
								}else{ 
									$max_sql ='SELECT MAX(M.match_first_sim) AS "MaxFirstSim", MAX(M.match_second_sim) AS "MaxSecondSim" FROM matches M, assignment A, project PR WHERE M.assignment_id=A.assignment_id AND A.project_id=PR.project_id AND PR.project_id="'.$project_id.'" AND '.$show.';';
								}	
								$result=mysql_query($max_sql);
								$row=mysql_fetch_assoc($result);

								/* We have max similarity by first and second person. Average is used in presentation (opacity) and so is here average of two maxes */
								$max_similarity = ($row[MaxFirstSim] + $row[MaxSecondSim])/2;
								
								/* If selected min_similarity is greater than any similarity in matches, restore min_similarity to 0; otherwise nothing would be shown */
								if($min_similarity>$row[MaxFirstSim] && $min_similarity>$row[MaxSecondSim]){
									$min_similarity=0;
								}

								$output='var linkSet = [';

								if($assignment_id!=""){
									$sql='SELECT X.match_id, X.match_first_id, X.match_second_id, AVG(X.match_first_sim) AS "FirstSim", AVG(X.match_second_sim) AS "SecondSim",  X.match_fb, X.match_tw, X.match_status, group_concat(`assignment_name` separator \',\') as \'assignment_name\' FROM (select distinct M.match_id, M.match_first_id, M.match_second_id, M.match_first_sim, M.match_second_sim, M.match_fb, M.match_tw, A.assignment_name, M.match_status from matches M, person P, assignment A, project PR WHERE (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND '.$show.' AND A.assignment_id=M.assignment_id AND A.assignment_id="'.$assignment_id.'" AND '.$show.' AND (M.match_first_sim>='.$min_similarity.' OR M.match_second_sim>='.$min_similarity.') ORDER BY M.match_first_id, M.match_second_id) AS X GROUP BY X.match_first_id, X.match_second_id ORDER BY FirstSim DESC, SecondSim DESC';
								}else{ // min(X.match_status) because every assignment should be confirmed to be shown in visualisation as confirmed
									$sql='SELECT X.match_id, X.match_first_id, X.match_second_id, AVG(X.match_first_sim) AS "FirstSim", AVG(X.match_second_sim) AS "SecondSim", X.match_fb, X.match_tw, min(X.match_status) AS "match_status", group_concat(`assignment_name` separator \',\') as \'assignment_name\' FROM (select distinct M.match_id, M.match_first_id, M.match_second_id,M.match_first_sim, M.match_second_sim, M.match_fb, M.match_tw, A.assignment_name, M.match_status from matches M, person P, assignment A, project PR WHERE (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND '.$show.' AND A.assignment_id=M.assignment_id AND A.project_id=PR.project_id AND PR.project_id="'.$project_id.'" AND '.$show.'  AND (M.match_first_sim>='.$min_similarity.' OR M.match_second_sim>='.$min_similarity.') ORDER BY M.match_first_id, M.match_second_id) AS X GROUP BY X.match_first_id, X.match_second_id ORDER BY FirstSim DESC, SecondSim DESC;';
								}
								$result=mysql_query($sql);
								$first=0;
								while($row = mysql_fetch_assoc($result)){
									if($first!=0){
										$output.=','.PHP_EOL;
									}else{
										$first++;
									}
									/* If there is facebook or twitter match, show it in visualisation next to assignment name matches */
									if($row[match_fb]==null && $row[match_tw]==null){
										$output.='{sourceId: "'.$row[match_first_id].'", "value":'.(($row[FirstSim]+$row[SecondSim])/2).',  linkName: "'.$row[assignment_name].'", targetId: "'.$row[match_second_id].'", status: "'.$row[match_status].'", elink: "?view_match='.$row[match_first_id].'&match2='.$row[match_second_id].'"}';
									}else if($row[match_fb]!=null && $row[match_tw]==null){
										$output.='{sourceId: "'.$row[match_first_id].'", "value":'.(($row[FirstSim]+$row[SecondSim])/2).',  linkName: "'.$row[assignment_name].',FB", targetId: "'.$row[match_second_id].'", status: "'.$row[match_status].'", elink: "?view_match='.$row[match_first_id].'&match2='.$row[match_second_id].'"}';
									}else if($row[match_fb]==null && $row[match_tw]!=null){
										$output.='{sourceId: "'.$row[match_first_id].'", "value":'.(($row[FirstSim]+$row[SecondSim])/2).',  linkName: "'.$row[assignment_name].',TW", targetId: "'.$row[match_second_id].'", status: "'.$row[match_status].'", elink: "?view_match='.$row[match_first_id].'&match2='.$row[match_second_id].'"}';
									}else{
										$output.='{sourceId: "'.$row[match_first_id].'", "value":'.(($row[FirstSim]+$row[SecondSim])/2).',  linkName: "'.$row[assignment_name].',FB,TW", targetId: "'.$row[match_second_id].'", status: "'.$row[match_status].'", elink: "?view_match='.$row[match_first_id].'&match2='.$row[match_second_id].'"}';
									}
								}

								$output.=' ];';
								/* Algorithm to find cluster - connected components */
								$result=mysql_query($sql);
								$connected_components = [];
								$i_row=0;
								while($row = mysql_fetch_assoc($result)){
									$connected_components[$i_row]= array($row[match_first_id], $row[match_second_id]);
									$i_row++;
								}

								$aComponents = connectedComponents ($connected_components);
								$number_of_groups = sizeof($aComponents);
								/* Get number of people connected to distinct person; */
								if($assignment_id!=""){
									$sql = 'select match_first_id,  MAX(match_first_sim), COUNT(*) AS "countc" from matches M, assignment A where M.assignment_id=A.assignment_id AND A.assignment_id="'.$assignment_id.'" AND '.$show.' AND (M.match_first_sim>='.$min_similarity.' OR M.match_second_sim>='.$min_similarity.') GROUP BY M.match_first_id;';
								}else{
									$sql = 'select match_first_id ,MAX(match_first_sim), COUNT(*) AS "countc" from matches M, assignment A, project PR where M.assignment_id=A.assignment_id AND A.project_id=PR.project_id AND PR.project_id="'.$project_id.'" AND '.$show.' AND (M.match_first_sim>='.$min_similarity.' OR M.match_second_sim>='.$min_similarity.') GROUP BY M.match_first_id;';
								}
								$table_of_connections;
								$result=mysql_query($sql);
								$curr_row=0;
								while($row = mysql_fetch_assoc($result)){
									$table_of_connections[$curr_row][0] = $row[match_first_id];
									$table_of_connections[$curr_row][1] = $row[countc];
									$curr_row++;
								}
								/* And now in other direction */
								if($assignment_id!=""){
									$sql = 'select match_second_id, MAX(match_first_sim), COUNT(*) AS "countc" from matches M, assignment A where M.assignment_id=A.assignment_id AND A.assignment_id="'.$assignment_id.'" AND '.$show.' AND (M.match_first_sim>='.$min_similarity.' OR M.match_second_sim>='.$min_similarity.') GROUP BY M.match_second_id;';
								}else{
									$sql = 'select match_second_id, MAX(match_first_sim), COUNT(*) AS "countc" from matches M, assignment A, project PR where M.assignment_id=A.assignment_id AND A.project_id=PR.project_id AND PR.project_id="'.$project_id.'" AND '.$show.' AND (M.match_first_sim>='.$min_similarity.' OR M.match_second_sim>='.$min_similarity.') GROUP BY M.match_second_id;';
								}
								$table_of_connections;
								$result=mysql_query($sql);
								while($row = mysql_fetch_assoc($result)){
									$table_of_connections[$curr_row][0] = $row[match_second_id];
									$table_of_connections[$curr_row][1] = $row[countc];
									$curr_row++;
								}

								/* Show results by assignment */
								if($assignment_id!=""){
									$sql='select distinct(person_id), person_ident from matches M, person P, assignment A WHERE (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND A.assignment_id=M.assignment_id AND A.assignment_id="'.$assignment_id.'" AND '.$show.' AND (M.match_first_sim>='.$min_similarity.' OR M.match_second_sim>='.$min_similarity.');';
								}else{ /* Show results by project */
									$sql='select distinct(person_id), person_ident from matches M, person P, project PR, assignment A WHERE (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND A.assignment_id=M.assignment_id AND A.project_id=PR.project_id AND PR.project_id="'.$project_id.'" AND '.$show.' AND (M.match_first_sim>='.$min_similarity.' OR M.match_second_sim>='.$min_similarity.');';
								}
								$result=mysql_query($sql);
								$output.= '
									 '.PHP_EOL.' var nodeSet = [';
								$first=0;
								$max_connections=0;
								while($row = mysql_fetch_assoc($result)){
									if($first!=0){
										$output.=','.PHP_EOL;
									}else{
										$first++;

									}
									$group=-1;
									/* Check which cluster link corresponds to */
									for($i=0; $i<sizeof($aComponents); $i++){
										for($j=0;$j<sizeof($aComponents[$i]); $j++){
											if($aComponents[$i][$j]==$row[person_id]){
												$group=$i;
												break;
											};
										}
										if($group!=-1){
											break;
										}
									}
									$count=0;
									for($i=0; $i<sizeof($table_of_connections); $i++){
										if($table_of_connections[$i][0]==$row[person_id]){
											$count+=$table_of_connections[$i][1];
										}
									}
									/* Get number of max connections over all nodes */
									if($count>$max_connections){
										$max_connections=$count;
									}

									$output.='{id: "'.$row[person_id].'", name: "'.$row[person_ident].'", group: "'.$group.'", hlink: "visualisation.php?view_p='.$row[person_id].'", count: "'.$count.'"}';
									//$output.='{id: "'.$row[person_id].'", name: "", type: "Type 1", hlink: "#"}';
								}
								$output.=' ];';
										

								if(!isset($_GET[gen_pdf])){
									/* Show settings menu */
									echo '<p>Minimal Similarity:
											<input id="amount" size="2" style="border: 0; color: #f6931f; font-weight: bold;" />
										</p>
										<div style="width:500px" id="slider-range-max"></div>';
									echo '<p>Charge:
											<input id="amount2" size="2" style="border: 0; color: #f6931f; font-weight: bold;" />
										</p>
										<div style="width:500px" id="slider-range-min"></div>';

									echo '<aside style="margin-top:80px; text-align: left;">
										Show: <select id="show_type">';
									if(mysql_real_escape_string($_GET['show'])=="0"){
										echo '  <option value="all">All matches</option>
										  <option selected value="non_checked">Non-checked matches only</option>
										  <option value="checked">Checked matches only</option>';
									}else if(mysql_real_escape_string($_GET['show'])=="1"){
										echo '  <option value="all">All matches</option>
										  <option value="non_checked">Non-checked matches only</option>
										  <option selected value="checked">Checked matches only</option>';
									}else{
										echo '  <option selected value="all">All matches</option>
										  <option value="non_checked">Non-checked matches only</option>
										  <option value="checked">Checked matches only</option>';
									}
								}

									
									echo '</select>';
								/* Show visualisation */
								$type_of_viz="gv_viz";
								echo '<div id="'.$type_of_viz.'"></div>';
								if($assignment_id!=""){
									$redirect='gv_assignment_id='.$assignment_id.'&min_similarity=';
								}else{ 
									$redirect='gv_project_id='.$project_id.'&min_similarity=';
								}
							/* Show match information */
							}else if (isset($_GET[view_match]) && isset($_GET['match2'])){
								$match_id=mysql_real_escape_string($_GET['view_match']);
								$match_id2=mysql_real_escape_string($_GET['match2']);
								
								/* Get status if FB/TW were checked */
								$sql='SELECT P.fb_checked, P.tw_checked FROM person P WHERE P.person_id="'.$match_id.'" ';
								$result=mysql_query($sql);
								$row=mysql_fetch_assoc($result);
								$user1_fb_checked=$row[fb_checked];
								$user1_tw_checked=$row[tw_checked];
							
								$sql='SELECT P.fb_checked, P.tw_checked FROM person P WHERE P.person_id="'.$match_id2.'" ';
								$result=mysql_query($sql);
								$row=mysql_fetch_assoc($result);
								$user2_fb_checked=$row[fb_checked];
								$user2_tw_checked=$row[tw_checked];

								/*Show Person 1 INFO */
								$sql_fb_if_confirmed='SELECT FB.fb_user_id, COUNT(*) AS "NoOfFBConfirmed" FROM fb_account FB WHERE FB.person_id="'.$match_id.'" AND fb_status=1;';
								$sql_tw_if_confirmed='SELECT TW.tw_user_id, COUNT(*) AS "NoOfTWConfirmed" FROM tw_account TW WHERE TW.person_id="'.$match_id.'" AND tw_status=1;';
								// First check if there is confirmed account for Facebook or Twitter; if not use first of all of them
								$sql_fbx='SELECT COUNT(*) AS "NoOfFBAccounts" FROM fb_account FB WHERE FB.person_id="'.$match_id.'" AND fb_status=1;';
								$result_fbx=mysql_query($sql_fbx);
								$row_fbx = mysql_fetch_assoc($result_fbx);	
								if($row_fbx[NoOfFBAccounts]>0){
									$sql_fb='SELECT FB.fb_user_id, COUNT(*) AS "NoOfFBAccounts" FROM fb_account FB WHERE FB.person_id="'.$match_id.'" AND fb_status=1;';
									$fb_confirmed=true;
								}else{
									$sql_fb='SELECT FB.fb_user_id, COUNT(*) AS "NoOfFBAccounts" FROM fb_account FB WHERE FB.person_id="'.$match_id.'" AND NOT fb_status=2;';
								}
								
								$sql_twx='SELECT COUNT(*) AS "NoOfTWAccounts" FROM tw_account TW WHERE TW.person_id="'.$match_id.'" AND tw_status=1;';
								$result_twx=mysql_query($sql_twx);
								$row_twx = mysql_fetch_assoc($result_twx);	
								if($row_twx[NoOfTWAccounts]>0){
									$sql_tw='SELECT TW.tw_user_id, COUNT(*) AS "NoOfTWAccounts" FROM tw_account TW WHERE TW.person_id="'.$match_id.'" AND tw_status=1;';
									$tw_confirmed=true;
								}else{
									$sql_tw='SELECT TW.tw_user_id, COUNT(*) AS "NoOfTWAccounts" FROM tw_account TW WHERE TW.person_id="'.$match_id.'" AND NOT tw_status=2;';
								}
								$sql='SELECT * FROM person P WHERE P.person_id="'.$match_id.'";';
								$result_tw=mysql_query($sql_tw);
								$result_fb=mysql_query($sql_fb);
								$result=mysql_query($sql);
								$result_tw_if_confirmed=mysql_query($sql_tw_if_confirmed);
								$result_fb_if_confirmed=mysql_query($sql_fb_if_confirmed);
								$row_tw = mysql_fetch_assoc($result_tw);	
								$row_fb = mysql_fetch_assoc($result_fb);	
								$row = mysql_fetch_assoc($result);	
								$row_tw_if_confirmed = mysql_fetch_assoc($result_tw_if_confirmed);	
								$row_fb_if_confirmed = mysql_fetch_assoc($result_fb_if_confirmed);	
							
								$user1 = $row[person_name].' '.$row[person_surname];
								echo '<div class="row-fluid "><div class="box span6">
								<div class="box-header well" data-original-title>
									<h2><i class="icon-user"></i> '.$user1.'</h2>
								</div>
								<div class="box-content">
									<div class="box-content">
										<ul class="dashboard-list">
											<li>
												<a href="visualisation.php?view_p='.$match_id.'">
												';
												if($fb_confirmed){
													$img_url='https://graph.facebook.com/'.$row_fb[fb_user_id].'/picture?type=small';
												}else if($tw_confirmed){
													$img_url='https://api.twitter.com/1/users/profile_image/'.$row_tw[tw_user_id];
												}else if($row_fb[fb_user_id]!="" ){
													$img_url='https://graph.facebook.com/'.$row_fb[fb_user_id].'/picture?type=small';
												}else if ($row_tw[tw_user_id]!=""){
													$img_url='https://api.twitter.com/1/users/profile_image/'.$row_tw[tw_user_id];
												}else{
													$img_url="img/avatar.png";
												}
												echo '
													<img class="dashboard-avatar" alt="'.$row[person_ident].'" src="'.$img_url.'"></a>
													<strong>Name:</strong> 	<a href="visualisation.php?view_p='.$match_id.'">'.$row[person_name].' '.$row[person_surname].'</a><br>
												<strong>User ID:</strong> '.$row[person_ident].'<br><br><br>';
												/* Show facebook status only if it was checked */
												if($user1_fb_checked=="1" || $user1_fb_checked=="2"){
													echo '<strong>Facebook:</strong> ';
													if($row_fb[NoOfFBAccounts]>0){
														if($row_fb_if_confirmed[NoOfFBConfirmed]>0){ // If there is confirmed account, show that account is confirmed 
															echo '<span class="label label-warning">Account confirmed</span><br>';
														}else{ //Else show how many are found
															if($row_fb[NoOfFBAccounts]==1){
																echo '<span class="label label-warning">'.$row_fb[NoOfFBAccounts].' account matched</span><br>';
															}else{
																echo '<span class="label label-warning">'.$row_fb[NoOfFBAccounts].' accounts matched</span><br>';
															}
														}
													}else{
														$fb_account_not_found=true;
														echo '<span class="label label-success">NOT FOUND</span><br>';
													}
												}else{
													$fb_account_not_found=true;
												}
												if($user1_tw_checked=="1" || $user1_tw_checked=="2"){
													echo '<strong>Twitter:</strong> ';
													if($row_tw[NoOfTWAccounts]>0){
														if($row_tw_if_confirmed[NoOfTWConfirmed]>0){ // If there is confirmed account, show that account is confirmed 
															echo '<span class="label label-warning">Account confirmed</span><br>';
														}else{ //Else show how many are found
															if($row_tw[NoOfTWAccounts]==1){
																echo '<span class="label label-warning">'.$row_tw[NoOfTWAccounts].' account matched</span><br> ';
															}else{
																echo '<span class="label label-warning">'.$row_tw[NoOfTWAccounts].' accounts matched</span><br> ';
															}
														}
													}else{
														$tw_account_not_found=true;
														echo '<span class="label label-success">NOT FOUND</span><br>';
													}
												}else{
													$tw_account_not_found=true;
												}
												echo '
											</li>
											
										</ul>
									</div>
								</div>
							</div><!--/span-->';
							/*Show Person 2 INFO */
								// First check if there is confirmed account for Facebook or Twitter; if not use first of all of them
								$fb_confirmed=false;
								$tw_confirmed=false;
								$sql_fbx='SELECT COUNT(*) AS "NoOfFBAccounts" FROM fb_account FB WHERE FB.person_id="'.$match_id2.'" AND fb_status=1;';
								$result_fbx=mysql_query($sql_fbx);
								$row_fbx = mysql_fetch_assoc($result_fbx);	
								if($row_fbx[NoOfFBAccounts]>0){
									$sql_fb='SELECT FB.fb_user_id, COUNT(*) AS "NoOfFBAccounts" FROM fb_account FB WHERE FB.person_id="'.$match_id2.'" AND fb_status=1;';
									$fb_confirmed=true;
								}else{
									$sql_fb='SELECT FB.fb_user_id, COUNT(*) AS "NoOfFBAccounts" FROM fb_account FB WHERE FB.person_id="'.$match_id2.'" AND NOT fb_status=2;';
								}
								
								$sql_twx='SELECT COUNT(*) AS "NoOfTWAccounts" FROM tw_account TW WHERE TW.person_id="'.$match_id2.'" AND tw_status=1;';
								$result_twx=mysql_query($sql_twx);
								$row_twx = mysql_fetch_assoc($result_twx);	
								if($row_twx[NoOfTWAccounts]>0){
									$sql_tw='SELECT TW.tw_user_id, COUNT(*) AS "NoOfTWAccounts" FROM tw_account TW WHERE TW.person_id="'.$match_id2.'" AND tw_status=1;';
									$tw_confirmed=true;
								}else{
									$sql_tw='SELECT TW.tw_user_id, COUNT(*) AS "NoOfTWAccounts" FROM tw_account TW WHERE TW.person_id="'.$match_id2.'" AND NOT tw_status=2;';
								}
								$sql_fb_if_confirmed='SELECT FB.fb_user_id, COUNT(*) AS "NoOfFBConfirmed" FROM fb_account FB WHERE FB.person_id="'.$match_id2.'" AND fb_status=1;';
								$sql_tw_if_confirmed='SELECT TW.tw_user_id, COUNT(*) AS "NoOfTWConfirmed" FROM tw_account TW WHERE TW.person_id="'.$match_id2.'" AND tw_status=1;';
								$sql='SELECT * FROM person P WHERE P.person_id="'.$match_id2.'";';
								$result_tw=mysql_query($sql_tw);
								$result_fb=mysql_query($sql_fb);
								$result=mysql_query($sql);
								$result_tw_if_confirmed=mysql_query($sql_tw_if_confirmed);
								$result_fb_if_confirmed=mysql_query($sql_fb_if_confirmed);
								$row_tw = mysql_fetch_assoc($result_tw);
								$row_fb = mysql_fetch_assoc($result_fb);	
								$row = mysql_fetch_assoc($result);	
								$row_tw_if_confirmed = mysql_fetch_assoc($result_tw_if_confirmed);	
								$row_fb_if_confirmed = mysql_fetch_assoc($result_fb_if_confirmed);	
								$user2 = $row[person_name].' '.$row[person_surname];
								echo '<div class="box span6">
								<div class="box-header well" data-original-title>
									<h2><i class="icon-user"></i> '.$user2.'</h2>
								</div>
								<div class="box-content">
									<div class="box-content">
										<ul class="dashboard-list">
											<li>
												<a href="visualisation.php?view_p='.$match_id2.'">
												';

												if($fb_confirmed){
													$img_url='https://graph.facebook.com/'.$row_fb[fb_user_id].'/picture?type=small';
												}else if($tw_confirmed){
													$img_url='https://api.twitter.com/1/users/profile_image/'.$row_tw[tw_user_id];
												}else if($row_fb[fb_user_id]!="" ){
													$img_url='https://graph.facebook.com/'.$row_fb[fb_user_id].'/picture?type=small';
												}else if ($row_tw[tw_user_id]!=""){
													$img_url='https://api.twitter.com/1/users/profile_image/'.$row_tw[tw_user_id];
												}else{
													$img_url="img/avatar.png";
												}
												echo '
													<img class="dashboard-avatar" alt="'.$row[person_ident].'" src="'.$img_url.'"></a>
													<strong>Name:</strong> 	<a href="visualisation.php?view_p='.$match_id2.'">'.$row[person_name].' '.$row[person_surname].'</a><br>
												<strong>User ID:</strong> '.$row[person_ident].'<br><br><br>';
												/* Show facebook status only if it was checked */
												if($user2_fb_checked=="1" || $user2_fb_checked=="2"){
													echo '<strong>Facebook:</strong> ';
													if($row_fb[NoOfFBAccounts]>0){
														if($row_fb_if_confirmed[NoOfFBConfirmed]>0){ // If there is confirmed account, show that account is confirmed 
															echo '<span class="label label-warning">Account confirmed</span><br>';
														}else{ //Else show how many are found
															if($row_fb[NoOfFBAccounts]==1){
																echo '<span class="label label-warning">'.$row_fb[NoOfFBAccounts].' account matched</span><br>';
															}else{
																echo '<span class="label label-warning">'.$row_fb[NoOfFBAccounts].' accounts matched</span><br>';
															}
														}
													}else{
														$fb_account_not_found=true;
														echo '<span class="label label-success">NOT FOUND</span><br>';
													}
												}else{
													$fb_account_not_found=true;
												}
												if($user2_tw_checked=="1" || $user2_tw_checked=="2"){
													echo '<strong>Twitter:</strong> ';
													if($row_tw[NoOfTWAccounts]>0){
														if($row_tw_if_confirmed[NoOfTWConfirmed]>0){ // If there is confirmed account, show that account is confirmed 
															echo '<span class="label label-warning">Account confirmed</span><br>';
														}else{ //Else show how many are found
															if($row_tw[NoOfTWAccounts]==1){
																echo '<span class="label label-warning">'.$row_tw[NoOfTWAccounts].' account matched</span><br> ';
															}else{
																echo '<span class="label label-warning">'.$row_tw[NoOfTWAccounts].' accounts matched</span><br> ';
															}
														}
													}else{
														$tw_account_not_found=true;
														echo '<span class="label label-success">NOT FOUND</span><br>';
													}
												}else{
													$tw_account_not_found=true;
												}
												echo '
												
											</li>
											
										</ul>
									</div>
								</div>
							</div><div><!--/span-->';
							
							/* Show info about matches */
							echo '<div class="row-fluid sortable"><div class="box span12">
								<div class="box-header well" data-original-title>
									<h2><i class="icon-info-sign"></i> Matches</h2>
								</div>
								<div class="box-content">
									<div class="box-content">
										<ul class="dashboard-list">
											<li>';
							/* Show if Facebook match exist */
							/* First check if we have facebook results */
							if($user1_fb_checked=="1" && $user2_fb_checked=="1"){
								$sql='SELECT M.match_fb FROM matches M WHERE M.match_second_id="'.$match_id2.'" and M.match_first_id="'.$match_id.'" AND NOT M.match_fb="NULL" AND NOT(M.match_status=3) GROUP BY match_fb;';
								$result=mysql_query($sql);
								if(mysql_num_rows($result)>0){
									echo '
							<div class="alert alert-error ">
								<button type="button" class="close" data-dismiss="alert">x</button>
								<h4 class="alert-heading">Warning!</h4>
								<p>There was Facebook friendship match found between selected users!</p>
							</div>';
								}else{
									if(!$fb_account_not_found){
										echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									There was <strong>NO</strong> Facebook friendship match found.
								</div>';
									}else{
									echo '<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
										There were <strong>NO</strong> Facebook accounts found (for at least one user), therefore no relation can be found.
									</div>';
									}
								}
							}else if($user1_fb_checked=="2" || $user2_fb_checked=="2"){ //If account for Facebook was added after search was done..for at least one person, then enable recheck for Facebook relations
								if($fb_access_token!=""){
									echo '<a href="projects.php?getFacebookMatches&user1='.$match_id.'&user2='.$match_id2.'" title="Starts FB Check" data-rel="tooltip" class="ajax-link btn btn-warning">Recheck Facebook Relations</a> (<i>Facebook user was manually added, to get new results recheck relations.</i><br><br>';
								}
							}else{ //If user wishes to check for relations manually ..if they were not already checked
								if($fb_access_token!=""){
									echo '<a href="projects.php?getFacebookMatches&user1='.$match_id.'&user2='.$match_id2.'" title="Starts FB Check" data-rel="tooltip" class="ajax-link btn btn-warning">Start Facebook Relations Check</a> (<i>Checks for Facebook relations between this two users. If Facebook Account Search for both users was not yet started it will be checked prior to relations check.</i><br><br>';
								}
							}
							/* Show if Twitter exist */
							/* First check if we have twitter results */
							if($user1_tw_checked=="1" && $user2_tw_checked=="1"){
								$sql='SELECT M.match_tw FROM matches M WHERE M.match_second_id="'.$match_id2.'" and M.match_first_id="'.$match_id.'" AND NOT M.match_tw="NULL" AND NOT(M.match_status=3) GROUP BY match_tw;';
								$result=mysql_query($sql);
								if(mysql_num_rows($result)>0){
									echo '
									<div class="alert alert-error ">
									<button type="button" class="close" data-dismiss="alert">x</button>
									<h4 class="alert-heading">Warning!</h4>
									<p>There was Twitter friendship match found between selected users!<br>';
									/* Get relation direction from twitter */
									$row = mysql_fetch_assoc($result);	
									$rel_status=explode("=", explode("&", $row[match_tw])[2])[1];
									/* Direction:
										$rel_status=1: User 1 follows User 2
										$rel_status=2: User 2 follows User 1
										$rel_status=3: User 1 follows User 2 and User 2 follows User 1 */
									if($rel_status==1){
										echo '<i>User "'.$user1.'" follows user "'.$user2.'".</i>';
									}else if($rel_status==2){
										echo '<i>User "'.$user2.'" follows user "'.$user1.'".</i>';
									}else{
										echo '<i>User "'.$user1.'" follows user "'.$user2.'" and user "'.$user2.'" follows user "'.$user1.'".</i>';
									}
									echo '</p></div>';
								}else{
									if(!$tw_account_not_found){
										echo '<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
										There was <strong>NO</strong> Twitter friendship match found.
										</div>';
									}else{
										echo '<div class="alert alert-success">
											<button type="button" class="close" data-dismiss="alert">x</button>
											There were <strong>NO</strong> Twitter accounts found (for at least one user), therefore no relation can be found.
										</div>';
									}
								}
							}else if($user1_tw_checked=="2" || $user2_tw_checked=="2"){ //If account for Twitter was added after search was done..for at least one person, then enable recheck for Twitter relations
								if($fb_access_token!=""){
									echo '<a href="projects.php?getTwitterMatches&user1='.$match_id.'&user2='.$match_id2.'" title="Starts TW Check" data-rel="tooltip" class="ajax-link btn btn-warning">Recheck Twitter Relations</a> (<i>Twitter user was manually added, to get new results recheck relations.</i><br><br>';
								}
							}else{ //If user wishes to check for relations manually ..if they were not already checked
								if($fb_access_token!=""){
									echo '<a href="projects.php?getTwitterMatches&user1='.$match_id.'&user2='.$match_id2.'" title="Starts TW Check" data-rel="tooltip" class="ajax-link btn btn-warning">Start Twitter Relations Check</a> (<i>Checks for Twitter relations between this two users. If Twitter Account Search for both users was not yet started it will be checked prior to relations check.</i><br><br>';
								}
							}
							/* First check if we have google results */
							$sql_google='SELECT distinct M.match_google FROM matches M WHERE M.match_first_id="'.$match_id.'" AND M.match_second_id="'.$match_id2.'" AND NOT M.match_status=3;';
							/* Default value for match_google is -1; means it was not checked yet. If it is 0 or more, it means it was checked and this is result */
							/* Distincs and first_id, second_id must be included so if there are more matches (assignment 1,2,3,...) with same person...it should count them only once */
							$result_google=mysql_query($sql_google);
							$row_google = mysql_fetch_assoc($result_google);
							$no_of_matches = $row_google[match_google];
							if($no_of_matches!="-1"){
					
								/* Check for google match */
								$sql_google_avg_confirmed='SELECT DISTINCT M.match_first_id, M.match_second_id, AVG(M.match_google) AS "average_confirmed" FROM matches M WHERE M.match_status=1 AND NOT M.match_google=-1;';
								$sql_google_avg_rejected='SELECT DISTINCT M.match_first_id, M.match_second_id, AVG(M.match_google) AS "average_rejected" FROM matches M WHERE M.match_status=2 AND NOT M.match_google=-1;';
								$sql_google_avg_non_checked='SELECT DISTINCT M.match_first_id, M.match_second_id, AVG(M.match_google) AS "average_rejected" FROM matches M WHERE M.match_status=0 AND NOT M.match_google=-1;';
								$result_google_avg_confirmed=mysql_query($sql_google_avg_confirmed);
								$result_google_avg_rejected=mysql_query($sql_google_avg_rejected);
								$result_google_avg_non_checked=mysql_query($sql_google_avg_non_checked);
								$row_google_avg_confirmed = mysql_fetch_assoc($result_google_avg_confirmed);
								$row_google_avg_rejected = mysql_fetch_assoc($result_google_avg_rejected);
								$row_google_avg_non_checked = mysql_fetch_assoc($result_google_avg_non_checked);
								$avg_con=number_format($row_google_avg_confirmed[average_confirmed],1);
								$avg_rej=number_format($row_google_avg_rejected[average_rejected],1);
								$avg_nc=number_format($row_google_avg_non_checked[average_rejected],1);
								if($no_of_matches==0){
									echo '<div class="alert alert-success">
											<button type="button" class="close" data-dismiss="alert">x</button>
											There were <strong>NO</strong> Google search results found.<br>
											<i>Average result for confirmed match was "'.$avg_con.'".<br>
											Average result for rejected match was "'.$avg_rej.'".<br>
											Average result for non-checked match was "'.$avg_nc.'".</i>
										</div>';
								}else if($no_of_matches>0){
									echo '<div class="alert alert-error ">
									<button type="button" class="close" data-dismiss="alert">x</button>
									<h4 class="alert-heading">Warning!</h4>
									<p>There were '.$no_of_matches.' results found on Google search!<br>
										<i>Average result for confirmed match was "'.$avg_con.'".<br>
										Average result for rejected match was "'.$avg_rej.'".<br>
										Average result for non-checked match was "'.$avg_nc.'".</i>
									</div>';
								}
							}else{
								// $no_of_matches is in that case -1 which means that google search was not yet started on this match
								//If user wishes to check for relations manually 
								echo '<a href="projects.php?getGoogleMatches&user1='.$match_id.'&user2='.$match_id2.'" title="Starts Google Search Check" data-rel="tooltip" class="ajax-link btn btn-warning">Start Google Search Relations Check</a> (<i>Checks for Google Search relations between this two users.</i><br><br>';
							}
						    echo '			<br><br><table class="table table-striped table-bordered bootstrap-datatable datatable">
												<thead>
													<tr>
													  <th>Assignment</th>
													  <th>Number of same lines</th>
													  <th>Average Similarity</th>
													  <th>Status</th>
													  <th>Actions</th>
												  </tr>
												</thead>   
												<tbody>';
							$sql='SELECT * FROM matches M, assignment A, project PR WHERE ((M.match_first_id="'.$match_id.'" AND M.match_second_id="'.$match_id2.'") OR (M.match_first_id="'.$match_id2.'" AND M.match_second_id="'.$match_id.'")) AND M.assignment_id = A.assignment_id AND A.project_id=PR.project_id AND NOT M.match_status=3;';
							$result=mysql_query($sql);
							while($row = mysql_fetch_assoc($result)){
								echo '<tr><td><a class="ajax-link" href="projects.php?view_as='.$row[assignment_id].'">Assignment '.$row[assignment_name].'</a></td>';
								echo '<td>'.$row[match_lines].'</td>';
								echo '<td>'.(($row[match_first_sim]+$row[match_second_sim])/2).'%</td>';
								if($row[match_status]=="0"){
									echo '<td style="text-align: center;"><span class="label">NOT CHECKED</span></td>';
								}else if($row[match_status]=="1"){
									echo '<td style="text-align: center;"><span class="label label-success">CONFIRMED</span></td>';
								}else if($row[match_status]=="2"){
									echo '<td style="text-align: center;"><span class="label label-warning">REJECTED</span></td>';								
								}
								echo '<td class="center">
										<a class="btn btn-success" href="projects/'.$row[project_id].'/'.$row[assignment_id].'/moss/moss.stanford.edu/results/'.$row[match_url].'" target="_blank">
											<i class="icon-zoom-in icon-white"></i>  
											View                                            
										</a>
										<a class="ajax-link btn btn-info" href="visualisation.php?confirm_ma='.$row[match_id].'">
											<i class="icon-edit icon-white"></i>  
											Confirm Match                                     
										</a>
										<a class="ajax-link btn btn-danger" href="visualisation.php?reject_ma='.$row[match_id].'">
											<i class="icon-trash icon-white"></i> 
											Reject Match
										</a>
										</td></tr>';
							}
							echo '</tbody></table>
							</li>
											
										</ul>
									</div>
								</div>
							</div></div><!--/span-->';
							
							
							/* If we are confirming selected match */	
							}else if(isset($_GET[confirm_ma])){
								$confirm_ma=mysql_real_escape_string($_GET['confirm_ma']);
								$sql='UPDATE matches SET match_status="1" WHERE match_id="'.$confirm_ma.'";';
								mysql_query($sql);
								echo '<div class="alert alert-success">
								<button type="button" class="close" data-dismiss="alert">x</button>
								Match was successfully confirmed.
								</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
					
							
							/* If we are rejecting selected match */	
							}else if(isset($_GET[reject_ma])){
								$reject_ma=mysql_real_escape_string($_GET['reject_ma']);
								$sql='UPDATE matches SET match_status="2" WHERE match_id="'.$reject_ma.'";';
								mysql_query($sql);
								echo '<div class="alert alert-success">
								<button type="button" class="close" data-dismiss="alert">x</button>
								Match was successfully rejected.
								</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
							/* If we are rejecting all non-checked match in assignment*/	
							}else if(isset($_GET[reject_ncma])){
								$reject_ncma=mysql_real_escape_string($_GET['reject_ncma']);
								$sql='UPDATE matches M SET M.match_status="2" WHERE M.assignment_id="'.$reject_ncma.'" AND M.match_status=0;';
								mysql_query($sql);
								echo '<div class="alert alert-success">
								<button type="button" class="close" data-dismiss="alert">x</button>
								Matches were successfully rejected.
								</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
							/* Show assignments for project */
							}else if(isset($_GET[project_id])){
								$project_id=mysql_real_escape_string($_GET[project_id]);
								$result=mysql_query('SELECT * FROM project WHERE project_id="'.$project_id.'" AND NOT project_status=2');
								if(mysql_num_rows($result)!=0){
									$row = mysql_fetch_assoc($result);
									if($row[project_status]!=2){
											/* We do this for every assignment in database*/
											$sql='SELECT * FROM assignment WHERE project_id="'.$project_id.'" AND assignment_status=1;';
											$list=mysql_query($sql);
											if(mysql_num_rows($list)!=0){
												echo '<div class="box-content">
													<table class="table table-striped table-bordered bootstrap-datatable datatable">
														<thead>
															<tr>
															  <th>Assignment name</th>
															  <th>Number of matches</th>
															  <th>View Visualisation</th>
														  </tr>
														</thead>   
														<tbody>';
												while($row = mysql_fetch_assoc($list)){
													$assignment_id=$row[assignment_id];
													$sql2='SELECT COUNT(*) AS "no_of_matches", A.assignment_status, AVG(M.match_first_sim) AS "AvgFirstSim", AVG(M.match_second_sim) AS "AvgSecondSim", MIN(M.match_first_sim) AS "MinFirstSim", MIN(M.match_second_sim) AS "MinSecondSim", MAX(M.match_first_sim) AS "MaxFirstSim", MAX(M.match_second_sim) AS "MaxSecondSim", AVG(M.match_first_sim) AS "FirstSim", AVG(M.match_second_sim) AS "SecondSim", MAX(M.match_lines) AS "MaxLines", AVG(M.match_lines) AS "AvgLines" FROM matches M, assignment A WHERE A.assignment_id=M.assignment_id AND M.assignment_id="'.$assignment_id.'" AND NOT M.match_status=3';
													$result2=mysql_query($sql2);
													$row2 = mysql_fetch_assoc($result2);
													/* Then we show them in table */
													echo '<tr><td><a class="ajax-link" href=projects.php?view_as='.$assignment_id.'>Assignment '.$row[assignment_name].'</a></td>';
													echo '<td>'.$row2[no_of_matches].'</td>';
													echo '<td class="center">
															<a class="btn btn-success" href="visualisation.php?gv_assignment_id='.$assignment_id.'">
																<i class="icon-zoom-in icon-white"></i>  
																Graph                                          
															</a>
															<a class="btn btn-success" href="visualisation.php?cmv_assignment_id='.$assignment_id.'">
																<i class="icon-zoom-in icon-white"></i>  
																Co-Occurence Matrix                                            
															</a>
															<a class="btn btn-success" href="generate_report.php?assignment_id='.$assignment_id.'" target="_blank">
																<i class="icon-zoom-in icon-white"></i>  
																PDF Report                                          
															</a>
														</td></tr>';
											
												}
												echo '</tbody></table></div>';	
											}else{
												echo '<div class="alert alert-info ">
														<button type="button" class="close" data-dismiss="alert">x</button>
														<h4 class="alert-heading">Warning!</h4>
														Project does not contain assignment data.</div><a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a></p>';
											}
									}else{
										echo '<div class="alert alert-info ">
										<button type="button" class="close" data-dismiss="alert">x</button>
										<h4 class="alert-heading">Warning!</h4>
										Project does not exist.</div><a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a></p>';
									}
								}else{
									echo '<div class="alert alert-info ">
									<button type="button" class="close" data-dismiss="alert">x</button>
									<h4 class="alert-heading">Warning!</h4>
									Project does not exist.</div><a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a></p>';
								}
							/* Show Co-Occurence Matrix Visualisation */
							}else if(isset($_GET[cmv_assignment_id]) || isset($_GET[cmv_project_id])){
								$assignment_id=mysql_real_escape_string($_GET['cmv_assignment_id']);
								$project_id=mysql_real_escape_string($_GET['cmv_project_id']);
								/* Get minimal similarity that has to be between two users to show them */
								$min_similarity=mysql_real_escape_string($_GET['min_similarity']);
								
								
								if(mysql_real_escape_string($_GET['show'])=="0"){
									$show="M.match_status=0";
								}else if(mysql_real_escape_string($_GET['show'])=="1"){
									$show="M.match_status=1";
								}else{
									$show="NOT (M.match_status=2 OR M.match_status=3)";
								}
								
								/* Get recommended value to be set at beginning */
								if(!isset($_GET[min_similarity])){
									/* Check similarity by n-th (number_of_persons_similarity) match by match_first_id value and set it to be default min_similarity */
									if($assignment_id!=""){
										$sql ='SELECT * FROM matches M WHERE M.assignment_id="'.$assignment_id.'" AND '.$show.' ORDER BY M.match_first_sim DESC, M.match_second_sim DESC LIMIT '.$number_of_persons_similarity.',1';
									}else{ 
										$sql ='SELECT * FROM matches M, assignment A WHERE M.assignment_id=A.assignment_id AND A.project_id="'.$project_id.'" AND '.$show.' ORDER BY M.match_first_sim DESC, M.match_second_sim DESC LIMIT '.$number_of_persons_similarity.',1';
									}	
									$result=mysql_query($sql);
									$row=mysql_fetch_assoc($result);
									$min_similarity=$row[match_first_sim];
								}
								
								if($min_similarity=="") $min_similarity=0;
								
								/* Find max similarity to normalise values to 100 - if one is 10% and second 20%...First should be 50% and second 100% */
								/* First check if min similarity exist */
								if($assignment_id!=""){
									$max_sql ='SELECT MAX(M.match_first_sim) AS "MaxFirstSim", MAX(M.match_second_sim) AS "MaxSecondSim" FROM matches M, assignment A WHERE M.assignment_id=A.assignment_id AND A.assignment_id="'.$assignment_id.'" AND '.$show.';';
								/* Else show results by project */
								}else{ 
									$max_sql ='SELECT MAX(M.match_first_sim) AS "MaxFirstSim", MAX(M.match_second_sim) AS "MaxSecondSim" FROM matches M, assignment A, project PR WHERE M.assignment_id=A.assignment_id AND A.project_id=PR.project_id AND PR.project_id="'.$project_id.'" AND '.$show.';';
								}	
								$result=mysql_query($max_sql);
								$row=mysql_fetch_assoc($result);

								/* We have max similarity by first and second person. Average is used in presentation (opacity) and so is here average of two maxes */
								$max_similarity = ($row[MaxFirstSim] + $row[MaxSecondSim])/2;
								
								/* If selected min_similarity is greater than any similarity in matches, restore min_similarity to 0; otherwise nothing would be shown */
								if($min_similarity>$row[MaxFirstSim] && $min_similarity>$row[MaxSecondSim]){
									$min_similarity=0;
								}

								if($assignment_id!=""){
									$sql='SELECT X.match_id, X.match_first_id, X.match_second_id, AVG(X.match_first_sim) AS "FirstSim", AVG(X.match_second_sim) AS "SecondSim",  X.match_fb, X.match_tw, X.match_status FROM (select distinct M.match_id, M.match_first_id, M.match_second_id, P.person_ident, M.match_first_sim, M.match_second_sim, M.match_fb, M.match_tw, A.assignment_name, M.match_status from matches M, person P, assignment A, project PR WHERE (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND '.$show.' AND A.assignment_id=M.assignment_id AND A.assignment_id="'.$assignment_id.'" AND '.$show.' AND (M.match_first_sim>='.$min_similarity.' OR M.match_second_sim>='.$min_similarity.') ORDER BY M.match_first_id, M.match_second_id) AS X GROUP BY X.match_first_id, X.match_second_id ORDER BY FirstSim DESC, SecondSim DESC';
								}else{ // min(X.match_status) because every assignment should be confirmed to be shown in visualisation as confirmed
									$sql='SELECT X.match_id, X.match_first_id, X.match_second_id, AVG(X.match_first_sim) AS "FirstSim", AVG(X.match_second_sim) AS "SecondSim", X.match_fb, X.match_tw, min(X.match_status) AS "match_status" FROM (select distinct M.match_id, M.match_first_id, M.match_second_id, P.person_ident, M.match_first_sim, M.match_second_sim, M.match_fb, M.match_tw, A.assignment_name, M.match_status from matches M, person P, assignment A, project PR WHERE (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND '.$show.' AND A.assignment_id=M.assignment_id AND A.project_id=PR.project_id AND PR.project_id="'.$project_id.'" AND '.$show.' AND (M.match_first_sim>='.$min_similarity.' OR M.match_second_sim>='.$min_similarity.') ORDER BY M.match_first_id, M.match_second_id) AS X GROUP BY X.match_first_id, X.match_second_id ORDER BY FirstSim DESC, SecondSim DESC;';
								}
								$result=mysql_query($sql);
								$table_of_person_ids=array();
								$table_of_idents=array();
								$table_of_all=array();

								/* First get all person ids corresponding to selected matches */
								while($row = mysql_fetch_assoc($result)){					

									$sql2_id1='SELECT * FROM person P WHERE P.person_id="'.$row[match_first_id].'";';
									$sql2_id2='SELECT * FROM person P WHERE P.person_id="'.$row[match_second_id].'";';
									$result2_id1=mysql_query($sql2_id1);
									$result2_id2=mysql_query($sql2_id2);
									$row2_id1 = mysql_fetch_assoc($result2_id1);
									$row2_id2 = mysql_fetch_assoc($result2_id2);
									
									$person_ident1=$row2_id1[person_ident];
									$person_ident2=$row2_id2[person_ident];
									
									/* Save result to array */
									if(!in_array($person_ident1, $table_of_idents)){
										array_push($table_of_idents,$person_ident1);
									}
									if(!in_array($person_ident2, $table_of_idents)){
										array_push($table_of_idents,$person_ident2);
									}
	
									
									/* Save ids in array */
									if(!in_array($row[match_first_id], $table_of_person_ids)){
										array_push($table_of_person_ids,$row[match_first_id]);
									}
									if(!in_array($row[match_second_id], $table_of_person_ids)){
										array_push($table_of_person_ids,$row[match_second_id]);
									}
								}
								$i_row=0;
								if(mysql_num_rows($result) != 0){	
									mysql_data_seek($result, 0);
									/* Number of groups needed for types of colours to be presented in visualisations */
									while($row = mysql_fetch_assoc($result)){
										$connected_components[$i_row]= array($row[match_first_id], $row[match_second_id]);
										$i_row++;
									}
									$table_of_person_ids_original=$table_of_person_ids;
									$table_of_person_ids=quicksort($table_of_person_ids);
									$aComponents = connectedComponents ($connected_components);
									$number_of_groups = sizeof($aComponents);
									mysql_data_seek($result, 0);
								}
								$output='var links = [';


								$first=0;
								while($row = mysql_fetch_assoc($result)){
									if($first!=0){
										$output.=','.PHP_EOL;
									}else{
										$first++;									
									}
									$id1=0;
									$found1=false;
									while($id1<count($table_of_person_ids)){
										if($table_of_person_ids[$id1]==$row[match_first_id]){
											$found1=true;
											break;
										}
										$id1++;
									}
									$id2=0;
									$found2=false;
									while($id2<count($table_of_person_ids)){
										if($table_of_person_ids[$id2]==$row[match_second_id]){
											$found2=true;
											break;
										}
										$id2++;
									}
									$output.='{"source":'.$id1.', "target":'.$id2.', "value":'.(((($row[FirstSim]+$row[SecondSim])/2)/$max_similarity)*10).', "elink":"?view_match='.$row[match_first_id].'&match2='.$row[match_second_id].'"}';
								}
								$output.=' ];';
								$output.=PHP_EOL.'var nodes = [';
								/* Now build node information */
								$first=0;
								for($curr=0; $curr<count($table_of_person_ids); $curr++){
									$group=-1;
									/* Check which cluster link corresponds to */
									for($i=0; $i<sizeof($aComponents); $i++){
										for($j=0;$j<sizeof($aComponents[$i]); $j++){
											if($aComponents[$i][$j]==$table_of_person_ids[$curr]){
												$group=$i;
												break;
											}
										}
										if($group!=-1){
											break;
										}
									}
									$ident_id=0;
									/* $table_of_person_ids is sorted, so it can not be used for comparison - original is used instead */
									while($ident_id<count($table_of_person_ids)){
										/* If table_of_person_ids has
										i id
										0 10
										1 11
										2 12
										and
										table_of_person_ids_original has
										i id
										0 12
										1 10
										2 11
										Then select first: 11 and compare it to second table...result is 2.
										table_of_person_ids_original i-s are correspondat to table_of_idents names */
										if($table_of_person_ids[$curr]==$table_of_person_ids_original[$ident_id]){
											break;
										}
										$ident_id++;
									}
									if($first!=0){
										$output.=','.PHP_EOL;
									}else{
										$first++;
									}
									$output.='{id: "'.$curr.'", name: "'.$table_of_idents[$ident_id].'", group:'.$group.', hlink: "visualisation.php?view_p='.$table_of_person_ids[$curr].'"}';
									//echo $table_of_idents[$ident_id].'<br>';
								}
								//print_r($table_of_person_ids);
								//print_r($table_of_person_ids_original);
								//print_r($table_of_idents);
								$output.='      ];';
								if(!isset($_GET[gen_pdf])){
									echo '<h1><i>Co-occurrence matrix</i> <small>visualisation</small></h1>
										<aside style="margin-top:10px; margin-left:20px; text-align: left;">
										<p>Order values: <select id="order">
										  <option selected value="group">by Group</option>
										  <option value="name">by Name</option>
										  <option value="count">by Similarity</option>
										</select>';
										echo '<br>Show: <select id="show_type">';
									if(mysql_real_escape_string($_GET['show'])=="0"){
										echo '  <option value="all">All matches</option>
										  <option selected value="non_checked">Non-checked matches only</option>
										  <option value="checked">Checked matches only</option>';
									}else if(mysql_real_escape_string($_GET['show'])=="1"){
										echo '  <option value="all">All matches</option>
										  <option value="non_checked">Non-checked matches only</option>
										  <option selected value="checked">Checked matches only</option>';
									}else{
										echo '  <option selected value="all">All matches</option>
										  <option value="non_checked">Non-checked matches only</option>
										  <option value="checked">Checked matches only</option>';
									}
									echo '</select>
										<p>Minimal Similarity:
											<input id="amount" size="2" style="border: 0; color: #f6931f; font-weight: bold;" />
										</p>
										<div style="width:500px" id="slider-range-max"></div><br>
										Hide similarities between same users: <input type="checkbox" id="show_same"/><br />
										Normalize values: <input checked type="checkbox" id="normalize"/><br />';
										if($assignment_id!=""){
											$redirect='cmv_assignment_id='.$assignment_id.'&min_similarity=';
										}else{ 
											$redirect='cmv_project_id='.$project_id.'&min_similarity=';
										}
									/* Show visualisation */
									$type_of_viz="cmv_viz";
									echo '<div style="margin-left: -15px;text-align: center;" id="'.$type_of_viz.'"></div>';
								}else{
									if($assignment_id!=""){
										$redirect='cmv_assignment_id='.$assignment_id.'&min_similarity=';
									}else{ 
										$redirect='cmv_project_id='.$project_id.'&min_similarity=';
									}
									/* Show visualisation */
									$type_of_viz="cmv_viz";
									echo '<div style="margin-left: -15px;text-align: center;" id="'.$type_of_viz.'"></div>';
								}
							/* If selected site is to confirm Facebook account */
							}else if(isset($_GET[confirmFB]) && isset($_GET[id])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Person</h2>
										</div>
									<div class="box-content">';
								$fb_id=mysql_real_escape_string($_GET[confirmFB]);
								$user_id=mysql_real_escape_string($_GET[id]);
								// Set this facebook account status to 1. (status 0 means added, 1 means confirmed, 2 means deleted)
								$sql='UPDATE fb_account SET fb_status="1" WHERE fb_user_id="'.$fb_id.'" AND person_id="'.$user_id.'";';
								mysql_query($sql);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Facebook account was successfully confirmed.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								echo '</div></div>';
								
							/* If selected site is to confirm Twitter account */	
							}else if(isset($_GET[confirmTW]) && isset($_GET[id])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Person</h2>
										</div>
									<div class="box-content">';
								$tw_id=mysql_real_escape_string($_GET[confirmTW]);
								$user_id=mysql_real_escape_string($_GET[id]);
								$sql='UPDATE tw_account SET tw_status="1" WHERE tw_user_id="'.$tw_id.'" AND person_id="'.$user_id.'";';
								mysql_query($sql);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Twitter account was successfully confirmed.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								echo '</div></div>';
								
							/* If selected site is to un-confirm Facebook account */
							}else if(isset($_GET[confirmDFB]) && isset($_GET[id])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Person</h2>
										</div>
									<div class="box-content">';
								$fb_id=mysql_real_escape_string($_GET[confirmDFB]);
								$user_id=mysql_real_escape_string($_GET[id]);
								// Set this facebook account status to 0. (status 0 means added (or non-confirmed), 1 means confirmed, 2 means deleted)
								$sql='UPDATE fb_account SET fb_status="0" WHERE fb_user_id="'.$fb_id.'" AND person_id="'.$user_id.'";';
								mysql_query($sql);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Facebook account was successfully removed from being confirmed.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								echo '</div></div>';
								
							/* If selected site is to un-confirm Twitter account */	
							}else if(isset($_GET[confirmDTW]) && isset($_GET[id])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Person</h2>
										</div>
									<div class="box-content">';
								$tw_id=mysql_real_escape_string($_GET[confirmDTW]);
								$user_id=mysql_real_escape_string($_GET[id]);
								$sql='UPDATE tw_account SET tw_status="0" WHERE tw_user_id="'.$tw_id.'" AND person_id="'.$user_id.'";';
								mysql_query($sql);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Twitter account was successfully removed from being confirmed.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								echo '</div></div>';
								
							/* If selected site is to delete Facebook account */	
							}else if(isset($_GET[deleteFB]) && isset($_GET[id])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Person</h2>
										</div>
									<div class="box-content">';
								$fb_id=mysql_real_escape_string($_GET[deleteFB]);
								$user_id=mysql_real_escape_string($_GET[id]);
								// Set this facebook account status to 2. (status 0 means added (or non-confirmed), 1 means confirmed, 2 means deleted)
								$sql='UPDATE fb_account SET fb_status="2" WHERE fb_user_id="'.$fb_id.'" AND person_id="'.$user_id.'";';
								mysql_query($sql);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Facebook account was successfully removed.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								echo '</div></div>';
								
							/* If selected site is to delete Twitter account */	
							}else if(isset($_GET[deleteTW]) && isset($_GET[id])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Person</h2>
										</div>
									<div class="box-content">';
								$tw_id=mysql_real_escape_string($_GET[deleteTW]);
								$user_id=mysql_real_escape_string($_GET[id]);
								$sql='UPDATE tw_account SET tw_status="2" WHERE tw_user_id="'.$tw_id.'" AND person_id="'.$user_id.'";';
								mysql_query($sql);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Twitter account was successfully removed.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								echo '</div></div>';
								
							/* If selected site is to add Facebook account */	
							}else if(isset($_GET[addFBa]) || isset($_POST[addFBa])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Person</h2>
										</div>
									<div class="box-content">';
								if(!isset($_POST[addFBa])){
									$user_id=mysql_real_escape_string($_GET[addFBa]);
									echo 'Please enter user\'s Facebook ID or username!<br><br>';
									echo '<form class="form-horizontal" name="form" action="visualisation.php" method="POST">
											<fieldset>
												<div class="control-group">
													<label class="control-label" for="focusedInput">Facebook ID: </label>
													<div class="controls">
														<input class="input-xlarge focused" id="focusedInput" type="text" name="fb_id" value=""> <i>(eg. 100000000000001)</i>
													</div>
												</div>
												<div class="control-group">
													<label class="control-label" for="focusedInput">Facebook username: </label>
													<div class="controls">
													  <input class="input-xlarge focused" id="focusedInput" type="text" name="fb_name" value=""> <i>(eg. johndoe1 )</i>
													</div>
												</div>
												<input type="hidden" name="addFBa" value="'.$user_id.'">
												<div class="form-actions">
													<button type="submit" value="basic" name="submit" class="btn btn-primary">Submit</button>
												</div>
											</fieldset>
										</form>
									';
								}else{
									//Add facebook id
									$fb_id=mysql_real_escape_string($_POST[fb_id]);
									$fb_name=mysql_real_escape_string($_POST[fb_name]);
									$user_id=$_POST[addFBa];
										if(strlen($fb_id)>0){
											$url = "https://graph.facebook.com/".$fb_id;
										}else if(strlen($fb_name)>0){
											$url = "https://graph.facebook.com/".$fb_name;
										}else{
											$error=true;
										}
									if(!$error){
											$ret_json = callFb($url);
											$users=json_decode($ret_json,true);
											if(strlen($users[error][message])>0 || sizeof($users)==0){ //If there is error, print error
												echo '<div class="alert alert-error">
												<button type="button" class="close" data-dismiss="alert">x</button>
												Facebook account was not found.
												</div>';
												echo '<a href="visualisation.php?view_p='.$_POST[addFBa].'" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
											}else{ // Else write in database
												// Enter only if it does not already exist
												$sql = 'SELECT COUNT(*) as "if_exist" FROM fb_account WHERE person_id="'.$user_id.'" AND fb_user_id="'.$users[id].'";';
												$result=mysql_query($sql);
												$row = mysql_fetch_assoc($result);
												if($row[if_exist]>0){
													//Already exists
													echo '<div class="alert alert-warning">
													<button type="button" class="close" data-dismiss="alert">x</button>
													Facebook account already exist in database. If it was deleted it is now undeleted and set as confirmed. If it was not deleted it is just confirmed.
													</div>';
													$sql = 'UPDATE fb_account SET fb_status=1 WHERE person_id="'.$user_id.'" AND fb_user_id="'.$users[id].'";';
													mysql_query($sql);
													echo '<a href="visualisation.php?view_p='.$_POST[addFBa].'" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
												}else{
													// Insert new facebook account 
													$sql = 'INSERT INTO fb_account SET person_id="'.$user_id.'", fb_user_id="'.$users[id].'", fb_name="'.$users[name].'", fb_status=1;';
													mysql_query($sql);
													// Update user status to 2 which means that it was checked with facebook but has to be rechecked for friendships. With that option, user is able to recheck for Facebook relationship
													$sql='UPDATE person SET fb_checked="2" WHERE person_id="'.$user_id.'";';
													mysql_query($sql);
													echo '<div class="alert alert-success">
													<button type="button" class="close" data-dismiss="alert">x</button>
													Facebook account was successfully added.
													</div>';
													echo '<a href="visualisation.php?view_p='.$_POST[addFBa].'" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
												}
											}
									}else{
										echo '<div class="alert alert-error">
											<button type="button" class="close" data-dismiss="alert">x</button>
											Please enter at least one value.
											</div>';
										echo '<a href="visualisation.php?view_p='.$_POST[addFBa].'" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
									}	
								}

								echo '</div></div>';
							/* If selected site is to add Twitter account */	
							}else if(isset($_GET[addTWa]) || isset($_POST[addTWa])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Person</h2>
										</div>
									<div class="box-content">';
								if(!isset($_POST[addTWa])){
									$user_id=mysql_real_escape_string($_GET[addTWa]);
									echo 'Please enter user\'s Twitter ID or username!<br><br>';
									echo '<form class="form-horizontal" name="form" action="visualisation.php" method="POST">
											<fieldset>
												<div class="control-group">
													<label class="control-label" for="focusedInput">Twitter ID: </label>
													<div class="controls">
														<input class="input-xlarge focused" id="focusedInput" type="text" name="tw_id" value=""> <i>(eg. 100000000000001)</i>
													</div>
												</div>
												<div class="control-group">
													<label class="control-label" for="focusedInput">Twitter username: </label>
													<div class="controls">
													  <input class="input-xlarge focused" id="focusedInput" type="text" name="tw_name" value=""> <i>(eg. johndoe1 )</i>
													</div>
												</div>
												<input type="hidden" name="addTWa" value="'.$user_id.'">
												<div class="form-actions">
													<button type="submit" value="basic" name="submit" class="btn btn-primary">Submit</button>
												</div>
											</fieldset>
										</form>
									';
								}else{
									$tw_id=mysql_real_escape_string($_POST[tw_id]);
									$tw_name=mysql_real_escape_string($_POST[tw_name]);
									$user_id=$_POST[addTWa];
										if(strlen($tw_id)>0){
											$url = "https://api.twitter.com/1/users/show.json?id=".$tw_id;
										}else if(strlen($tw_name)>0){
											$url = "https://api.twitter.com/1/users/show.json?screen_name=".$tw_name;
										}else{
											$error=true;
										}
									if(!$error){
											$ret_json = callFb($url);
											$users=json_decode($ret_json,true);
											if(sizeof($users[errors])>0 || sizeof($users)==0){ //If there is error, print error
												echo '<div class="alert alert-error">
												<button type="button" class="close" data-dismiss="alert">x</button>
												Twitter account was not found.
												</div>';
												echo '<a href="visualisation.php?view_p='.$_POST[addTWa].'" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
											}else{ // Else write in database
												// Enter only if it does not already exist
												$sql = 'SELECT COUNT(*) as "if_exist" FROM tw_account WHERE person_id="'.$user_id.'" AND tw_user_id="'.$users[id].'";';
												$result=mysql_query($sql);
												$row = mysql_fetch_assoc($result);
												if($row[if_exist]>0){
													//Already exists
													echo '<div class="alert alert-warning">
													<button type="button" class="close" data-dismiss="alert">x</button>
													Twitter account already exist in database. If it was deleted it is now undeleted and set as confirmed. If it was not deleted it is just confirmed.
													</div>';
													$sql = 'UPDATE tw_account SET tw_status=1 WHERE person_id="'.$user_id.'" AND tw_user_id="'.$users[id].'";';
													mysql_query($sql);
													echo '<a href="visualisation.php?view_p='.$_POST[addTWa].'" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
												}else{
													$sql = 'INSERT INTO tw_account SET person_id="'.$user_id.'", tw_user_id="'.$users[id].'", tw_name="'.$users[name].'", tw_username="'.$users[screen_name].'", tw_status=1;';
													mysql_query($sql);
													// Update user status to 2 which means that it was checked with Twitter but has to be rechecked for friendships. With that option, user is able to recheck for Twitter relationship
													$sql='UPDATE person SET tw_checked="2" WHERE person_id="'.$user_id.'";';
													mysql_query($sql);
													echo '<div class="alert alert-success">
													<button type="button" class="close" data-dismiss="alert">x</button>
													Twitter account was successfully added.
													</div>';
													echo '<a href="visualisation.php?view_p='.$_POST[addTWa].'" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
												}
											}
									}else{
										echo '<div class="alert alert-error">
											<button type="button" class="close" data-dismiss="alert">x</button>
											Please enter at least one value.
											</div>';
										echo '<a href="visualisation.php?view_p='.$_POST[addFBa].'" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
									}	
								}
							/* Show user details */
							}else if(isset($_GET[view_p])){
								/* Show user details */
								$user_id=mysql_real_escape_string($_GET['view_p']);

								/*Show Person INFO */
								$sql='SELECT * FROM person P WHERE P.person_id="'.$user_id.'";';
								$sql_fbx='SELECT COUNT(*) AS "NoOfFBAccounts" FROM fb_account FB WHERE FB.person_id="'.$user_id.'" AND fb_status=1;';
								$result_fbx=mysql_query($sql_fbx);
								$row_fbx = mysql_fetch_assoc($result_fbx);	
								if($row_fbx[NoOfFBAccounts]>0){
									$sql_fb='SELECT FB.fb_user_id, COUNT(*) AS "NoOfFBAccounts" FROM fb_account FB WHERE FB.person_id="'.$user_id.'" AND fb_status=1;';
									$fb_confirmed=true;
								}else{
									$sql_fb='SELECT FB.fb_user_id, COUNT(*) AS "NoOfFBAccounts" FROM fb_account FB WHERE FB.person_id="'.$user_id.'" AND NOT fb_status=2;';
								}
								
								$sql_twx='SELECT COUNT(*) AS "NoOfTWAccounts" FROM tw_account TW WHERE TW.person_id="'.$user_id.'" AND tw_status=1;';
								$result_twx=mysql_query($sql_twx);
								$row_twx = mysql_fetch_assoc($result_twx);	
								if($row_twx[NoOfTWAccounts]>0){
									$sql_tw='SELECT TW.tw_user_id, COUNT(*) AS "NoOfTWAccounts" FROM tw_account TW WHERE TW.person_id="'.$user_id.'" AND tw_status=1;';
									$tw_confirmed=true;
								}else{
									$sql_tw='SELECT TW.tw_user_id, COUNT(*) AS "NoOfTWAccounts" FROM tw_account TW WHERE TW.person_id="'.$user_id.'" AND NOT tw_status=2;';
								}
								
								$sql_fb_if_confirmed='SELECT FB.fb_user_id, COUNT(*) AS "NoOfFBConfirmed" FROM fb_account FB WHERE FB.person_id="'.$user_id.'" AND fb_status=1;';
								$sql_tw_if_confirmed='SELECT TW.tw_user_id, COUNT(*) AS "NoOfTWConfirmed" FROM tw_account TW WHERE TW.person_id="'.$user_id.'" AND tw_status=1;';
								$sql_fb_all='SELECT FB.fb_user_id, FB.fb_status FROM fb_account FB WHERE FB.person_id="'.$user_id.'" AND NOT fb_status=2;';
								$sql_tw_all='SELECT TW.tw_user_id, TW.tw_username, TW.tw_status FROM tw_account TW WHERE TW.person_id="'.$user_id.'" AND NOT tw_status=2;';
								$result_tw=mysql_query($sql_tw);
								$result_tw_if_confirmed=mysql_query($sql_tw_if_confirmed);
								$result_fb=mysql_query($sql_fb);
								$result_fb_if_confirmed=mysql_query($sql_fb_if_confirmed);
								$result_tw_all=mysql_query($sql_tw_all);
								$result_fb_all=mysql_query($sql_fb_all);
								$result=mysql_query($sql);
								$row_tw = mysql_fetch_assoc($result_tw);	
								$row_fb = mysql_fetch_assoc($result_fb);	
								$row_tw_if_confirmed = mysql_fetch_assoc($result_tw_if_confirmed);	
								$row_fb_if_confirmed = mysql_fetch_assoc($result_fb_if_confirmed);	
								$row = mysql_fetch_assoc($result);	
								$user1 = $row[person_name].' '.$row[person_surname];

							echo '<div class="row-fluid ">';
								echo '<div class="box span6">';
							
								echo '<div class="box-header well" data-original-title>
									<h2><i class="icon-user"></i> '.$user1.'</h2>
								</div>
								<div class="box-content">
									<div class="box-content">
										<ul class="dashboard-list">
											<li>';
								if($fb_confirmed){ //If there is confirmed account, show this ...if both facebook and twitter accounts are confirmed, facebook profile will be shown..if nothing is confirmed, first available is shown
									$img_url='https://graph.facebook.com/'.$row_fb[fb_user_id].'/picture?type=small';
								}else if($tw_confirmed){
									$img_url='https://api.twitter.com/1/users/profile_image/'.$row_tw[tw_user_id];
								}else if($row_fb[fb_user_id]!="" ){
									$img_url='https://graph.facebook.com/'.$row_fb[fb_user_id].'/picture?type=small';
								}else if ($row_tw[tw_user_id]!=""){
									$img_url='https://api.twitter.com/1/users/profile_image/'.$row_tw[tw_user_id];
								}else{
									$img_url="img/avatar.png";
								}
								echo '
									<img class="dashboard-avatar" alt="'.$row[person_ident].'" src="'.$img_url.'"></a>
									<strong>Name:</strong> 	<a href="visualisation.php?view_p='.$row[person_id].'">'.$row[person_name].' '.$row[person_surname].'</a><br>
								<strong>User ID:</strong> '.$row[person_ident].'<br><br><br>';
								
							echo '</li>
										</ul>
									</div>
								</div>
							</div><!--/span-->';
							
							
								/* Show user social media info */
								echo '<div class="box span6">
									<div class="box-header well" data-original-title>
										<h2><i class="icon-user"></i> Social media</h2>
									</div>
									<div class="box-content">
										<div class="box-content">
											<ul class="dashboard-list">
												<li>';
										
									/* Show facebook status only if it was checked */
									if($row[fb_checked]=="1" || $row[fb_checked]=="2"){
										echo '<strong>Facebook:</strong> ';
										if($row_fb[NoOfFBAccounts]>0){
											if($row_fb_if_confirmed[NoOfFBConfirmed]>0){ // If there is confirmed account, show that account is confirmed 
												echo '<span class="label label-warning">Account confirmed</span><br>';
											}else{ //Else show how many are found
												if($row_fb[NoOfFBAccounts]==1){
													echo '<span class="label label-warning">'.$row_fb[NoOfFBAccounts].' account matched</span><br>';
												}else{
													echo '<span class="label label-warning">'.$row_fb[NoOfFBAccounts].' accounts matched</span><br>';
												}
											}
											/* Show all accounts */
											while($row2 = mysql_fetch_assoc($result_fb_all)){
												if($row_fb_if_confirmed[NoOfFBConfirmed]>0){ // If there is confirmed account, show only this one
													if($row2[fb_status]=="1"){
														echo '<blockquote>ID: <a href="https://www.facebook.com/'.$row2[fb_user_id].'" TARGET="_blank"> '.$row2[fb_user_id].'</a> (<a href="visualisation.php?confirmDFB='.$row2[fb_user_id].'&id='.$user_id.'">UNDO CONFIRM</a>,<a href="visualisation.php?deleteFB='.$row2[fb_user_id].'&id='.$user_id.'">DELETE</a>)</blockquote>';
													}
												}else{ //Else show others
													if($row2[fb_status]=="0"){
														echo '<blockquote>ID: <a href="https://www.facebook.com/'.$row2[fb_user_id].'" TARGET="_blank"> '.$row2[fb_user_id].'</a> (<a href="visualisation.php?confirmFB='.$row2[fb_user_id].'&id='.$user_id.'">CONFIRM</a>,<a href="visualisation.php?deleteFB='.$row2[fb_user_id].'&id='.$user_id.'">DELETE</a>)</blockquote>';
													}
												}
											}
											if($row_fb_if_confirmed[NoOfFBConfirmed]==0){
												echo '<blockquote><a href="visualisation.php?addFBa='.$user_id.'">Add Facebook account manually</a></blockquote>';
											}
										}else{
											echo '<span class="label label-success">NOT FOUND</span><br>';
											echo '<blockquote><a href="visualisation.php?addFBa='.$user_id.'">Add Facebook account manually</a></blockquote>';
										}
									}else{ //Show option to check manually for Facebook search
										echo '<a href="visualisation.php?addFBa='.$user_id.'" title="Add Facebook account manually" data-rel="tooltip" class="ajax-link btn btn-warning">Add Facebook account manually</a><br><br>';
										echo '<a href="projects.php?getFacebookAccounts='.$user_id.'" title="Starts Facebook Account Search" data-rel="tooltip" class="ajax-link btn btn-warning">Start Facebook Account Search</a> (<i>Checks for Facebook accounts based on user name and surname.)</i><br><br>';
									}
									if($row[tw_checked]=="1" || $row[tw_checked]=="2"){
										echo '<p><strong>Twitter:</strong> ';
										if($row_tw[NoOfTWAccounts]>0){
											if($row_tw_if_confirmed[NoOfTWConfirmed]>0){ // If there is confirmed account, show that account is confirmed 
												echo '<span class="label label-warning">Account confirmed</span><br>';
											}else{ //Else show how many are found
												if($row_tw[NoOfTWAccounts]==1){
													echo '<span class="label label-warning">'.$row_tw[NoOfTWAccounts].' account matched</span><br> ';
												}else{
													echo '<span class="label label-warning">'.$row_tw[NoOfTWAccounts].' accounts matched</span><br> ';
												}
											}
											/* Show all accounts */
											while($row2 = mysql_fetch_assoc($result_tw_all)){
												if($row_tw_if_confirmed[NoOfTWConfirmed]>0){ // If there is confirmed account, show only this one
													if($row2[tw_status]=="1"){
														echo '<blockquote>ID: <a href="https://www.twitter.com/'.$row2[tw_username].'" TARGET="_blank"> '.$row2[tw_user_id].'</a> (<a href="visualisation.php?confirmDTW='.$row2[tw_user_id].'&id='.$user_id.'">UNDO CONFIRM</a>,<a href="visualisation.php?deleteTW='.$row2[tw_user_id].'&id='.$user_id.'">DELETE</a>)</blockquote>';
													}
												}else{ //Else show others
													if($row2[tw_status]=="0"){
														echo '<blockquote>ID: <a href="https://www.twitter.com/'.$row2[tw_username].'" TARGET="_blank"> '.$row2[tw_user_id].'</a> (<a href="visualisation.php?confirmTW='.$row2[tw_user_id].'&id='.$user_id.'">CONFIRM</a>,<a href="visualisation.php?deleteTW='.$row2[tw_user_id].'&id='.$user_id.'">DELETE</a>)</blockquote>';
													}
												}
											}
											if($row_tw_if_confirmed[NoOfTWConfirmed]==0){
												echo '<blockquote><a href="visualisation.php?addTWa='.$user_id.'">Add Twitter account manually</a></blockquote>';
											}
											
										}else{
											echo '<span class="label label-success">NOT FOUND</span><br>';
											echo '<blockquote><a href="visualisation.php?addTWa='.$user_id.'">Add Twitter account manually</a></blockquote>';
										}
										echo '</p>';
									}else{ //Show option to check manually for Twitter search
										echo '<a href="visualisation.php?addTWa='.$user_id.'" title="Add Twitter account manually" data-rel="tooltip" class="ajax-link btn btn-warning">Add Twitter account manually</a><br><br>';
										echo '<a href="projects.php?getTwitterAccounts='.$user_id.'" title="Starts Twitter Account Search" data-rel="tooltip" class="ajax-link btn btn-warning">Start Twitter Account Search</a> (<i>Checks for Twitter accounts based on user name and surname.)</i><br><br>';
									}
									echo '</li>

											</ul>
										</div>
									</div>
								</div><div><!--/span-->';
							

								/* Show all matches that contain this user */
							echo '<div class="row-fluid sortable"><div class="box span12">
								<div class="box-header well" data-original-title>
									<h2><i class="icon-info-sign"></i> Matches</h2>
								</div>
								<div class="box-content">
									<div class="box-content">
										<ul class="dashboard-list">
											<li>';

							/* Show matches list */
							/* Go through every assignment in project */
							$sql_a='SELECT distinct A.assignment_id, A.assignment_name FROM matches M, assignment A, person P WHERE M.assignment_id=A.assignment_id AND (M.match_first_id="'.$user_id.'" OR M.match_second_id="'.$user_id.'") AND (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND NOT M.match_status=3';
							$result_a=mysql_query($sql_a);
							while($row_a = mysql_fetch_assoc($result_a)){
								echo '<h3> Assignment '.$row_a[assignment_name].'</h3>';
								$sql='SELECT M.match_id, M.match_first_id, M.match_second_id, M.match_url, M.match_lines, A.project_id, A.assignment_name, A.assignment_id, group_concat(`person_name` separator \',\') as \'person_name\', group_concat(`person_surname` separator \',\') as \'person_surname\', M.match_lines, M.match_status, M.match_first_sim, M.match_second_sim FROM matches M, person P, assignment A WHERE M.assignment_id=A.assignment_id AND (M.match_first_id="'.$user_id.'" OR M.match_second_id="'.$user_id.'") AND (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND NOT M.match_status=3 AND A.assignment_id="'.$row_a[assignment_id].'" GROUP BY M.match_id ORDER BY (M.match_first_sim+M.match_second_sim)/2 DESC;';
								$result=mysql_query($sql);
								/* Show only if there are any results */
								if(mysql_num_rows($result)>0){		
										echo '
											<table class="table table-striped table-bordered bootstrap-datatable datatable">
												<thead>
													<tr>
													  <th>Person 1</th>
													  <th>Person 2</th>
													  <th>Similarity</th>
													  <th>Number of same lines</th>
													  <th>Status</th>
													  <th>Actions</th>
												  </tr>
												</thead>   
												<tbody>';
										while($row = mysql_fetch_assoc($result)){
											echo '<tr><td><a class="ajax-link" href="visualisation.php?view_p='.$row[match_first_id].'">'.explode(",", $row[person_name])[0].' '.explode(",", $row[person_surname])[0].'</a></td>';
											echo '<td><a class="ajax-link" href="visualisation.php?view_p='.$row[match_second_id].'">'.explode(",", $row[person_name])[1].' '.explode(",", $row[person_surname])[1].'</a></td>';
											echo '<td class="center">'.(($row[match_first_sim]+$row[match_second_sim])/2).' %</td>';
											echo '<td class="center">'.$row[match_lines].'</td>';
											if($row[match_status]=="0"){
												echo '<td style="text-align: center;"><span class="label">NOT CHECKED</span></td>';
											}else if($row[match_status]=="1"){
												echo '<td style="text-align: center;"><span class="label label-success">CONFIRMED</span></td>';
											}else if($row[match_status]=="2"){
												echo '<td style="text-align: center;"><span class="label label-warning">REJECTED</span></td>';								
											}
											echo '<td class="center">
											<a class="ajax-link btn btn-success" href="visualisation.php?view_match='.$row[match_first_id].'&amp;match2='.$row[match_second_id].'">
												<i class="ajax-link icon-zoom-in icon-white"></i>  
												View Match                                          
											</a>
											<a class="btn btn-success" href="projects/'.$row[project_id].'/'.$row[assignment_id].'/moss/moss.stanford.edu/results/'.$row[match_url].'" target="_blank">
												<i class="icon-zoom-in icon-white"></i>  
												View Result                                          
											</a>
											<a class="btn btn-info" href="visualisation.php?confirm_ma='.$row[match_id].'">
												<i class="icon-edit icon-white"></i>  
												Confirm Match                                     
											</a>
											<a class="ajax-link btn btn-danger" href="visualisation.php?reject_ma='.$row[match_id].'">
												<i class="icon-trash icon-white"></i> 
												Reject Match
											</a>
											</td></tr>';
										}
										echo '</tbody></table>';
										/* End of table */
								}
							}
							
							echo '</li>
											
										</ul>
									</div>
								</div>
							</div></div><!--/span-->';
							
							
							/* Show projects */
							}else{
								$sql='SELECT * FROM project WHERE NOT project_status=2;';
								$list=mysql_query($sql);
								/* If there is none, offer to create one */
								if(mysql_num_rows($list) == 0){		
									echo'<div class="alert alert-info">
										<button type="button" class="close" data-dismiss="alert">x</button>
										There are no projects in database.</div>';
									echo '<a href="projects.php?add_project" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i> Add new project</a></p>';
								/* If there are, show them */
								}else{
									echo '<div class="box-content">
												<table class="table table-striped table-bordered bootstrap-datatable datatable">
													<thead>
														<tr>
														  <th>Project name</th>
														  <th>Number of assignments</th>
														  <th>View Visualisation</th>
													  </tr>
													</thead>   
													<tbody>';
										/* We do this for every project in database*/
										while($row = mysql_fetch_assoc($list)){
											$project_id=$row[project_id];
											/* We have to know how many assignment different project has */
											$assignment = mysql_query('SELECT * FROM assignment WHERE project_id="'.$project_id.'" AND NOT assignment_status=2;');
											$no_of_assignments=0;
											while($assignment_row = mysql_fetch_assoc($assignment)){
												$no_of_assignments++;
											}
											/* Then we show them in table */
											echo '<tr><td><a class="ajax-link" href=visualisation.php?project_id='.$project_id.'>'.$row[project_name].'</a></td>';
											echo '<td>'.$no_of_assignments.'</td>';
											echo '<td class="center">
													<a class="btn btn-success" href="visualisation.php?gv_project_id='.$project_id.'">
														<i class="icon-zoom-in icon-white"></i>  
														Graph                                            
													</a>
													<a class="btn btn-success" href="visualisation.php?cmv_project_id='.$project_id.'">
														<i class="icon-zoom-in icon-white"></i>  
														Co-Occurence Matrix                                            
													</a>
													<a class="btn btn-success" href="generate_report.php?project_id='.$project_id.'" target="_blank">
														<i class="icon-zoom-in icon-white"></i>  
														PDF Report                                          
													</a>
												</td></tr>';

										}
										echo '</tbody></table></div>';

										/* End of table */
									}
							}
							
						}else{
							echo '<p>Plagiarism Detection Assistant has not yet been configured. Continue to installation procedure.</p>';
							echo '<a href="install.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
						}
						?>		
						<div class="clearfix"></div>
					<?php if(!isset($_GET[view_match])){
				echo '
					</div></div>';
					}
					?>
				
			</div></div>
<script>
  <? echo $output; ?>
 
</script>
<? 
if($output!=""){
	if($type_of_viz=="gv_viz"){
echo '
<script type="text/javascript">
	// Template from GitHub was used and modified for appropriate needs: http://bl.ocks.org/mbostock/4062045/
    var width = 800;
    var height = 600;
	centerNodeSize = 50;
	';
	/* If number of groups is less than 10, use color scale for 10 colours; else use colour scale of 20 */
	if($number_of_groups <= 10){
		echo 'colorScale = d3.scale.category10().domain(d3.range(10)); //Define number of colors - number of groups-clusters-connected components';
	}else{
		echo 'colorScale = d3.scale.category20().domain(d3.range(20)); //Define number of colors - number of groups-clusters-connected components';
	}
	echo '
		
	d3.select("#show_type").on("change", function() {
		show_type(this.value);
	});
	
	function show_type(value) {
		if(value=="all"){
			window.location="visualisation.php?'.$redirect.'"+$(\'#slider-range-max\').slider("option", "value");
		}else if(value=="checked"){
			window.location="visualisation.php?'.$redirect.'"+$(\'#slider-range-max\').slider("option", "value")+"&show=1";
		}else if(value=="non_checked"){
			window.location="visualisation.php?'.$redirect.'"+$(\'#slider-range-max\').slider("option", "value")+"&show=0";
		}
	}
	var svg = d3.select("#gv_viz").append("svg:svg")
		.attr("width", width)
		.attr("height", height);
		  
		  
	/* Legend (help menu) */';
	if(!isset($_GET[gen_pdf])){
		echo '
	var legend = d3.select("#gv_viz").append("svg:svg")
		.attr("width", 200 )
		.attr("height", 600)
		.style("margin-left", 30 + "px")
		.style("margin-bottom", height-590 + "px")
		.on("mousedown", legend_mouse_out);';
	}else{
		echo 'var legend = d3.select("#none");';
	}
	echo '

	legend.append("rect")
		.attr("class", "background")
		.attr("width", 200)
		.attr("height", 20)
		.on("mouseover", legend_mouse_click);
	  
    legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 15)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "bold 14px Arial")
		.attr("fill", "Black")
		.text("Mouse over for Info");

	var node_hash = [];
	var type_hash = [];

	// Create a hash that allows access to each node by its id
	nodeSet.forEach(function(d, i) {
		node_hash[d.id] = d;
		type_hash[d.type] = d.type;
	});

	// Append the source object node and the target object node to each link
	linkSet.forEach(function(d, i) {
		d.source = node_hash[d.sourceId];
		d.target = node_hash[d.targetId];
	});
	
	// Create a force layout and bind Nodes and Links
	';
	if(!isset($_GET[gen_pdf])){
		echo 'var force = d3.layout.force()
				.charge(-800)
				.nodes(nodeSet)
				.links(linkSet)
				.size([width, height])
				.linkDistance( function(d) { if (width < height) { return width*1/4; } else { return height*1/4; } } ) // Controls edge length
				.on("tick", tick)
				.start();';
	}else{
	    echo 'var force = d3.layout.force()
				.charge(-600)
				.theta(2)
				.nodes(nodeSet)
				.links(linkSet)
				.size([width, height])
				.linkDistance( function(d) { if (width < height) { return width*1/4; } else { return height*1/4; } } ) // Controls edge length
				.on("tick", tick);';
		echo 'setTimeout(function() {
				force.start();
				for (var i = 0; i < 200; i++){
					force.tick();
				}
				force.stop();
			}, 0);';
	 }
	echo '
	
	// Draw lines for Links between Nodes
	var link = svg.selectAll(".gLink")
				.data(force.links())
				.enter().append("g")
				.append("a")
				.attr("xlink:href", function(d) { return d.elink; })
				.attr("class", "gLink")
				.append("line")
				.attr("class", "link")
				.style("stroke", function(d) {
		  ';
	if(!isset($_GET[gen_pdf])){
		echo '				if(d.status=="0") return "#AAA"; //Status 0 represents added match --grey color
						if(d.status=="1") return "#F00"; //Status 1 represents confirmed match -red color; rejected or deleted are not shown in visualisation
			';
		}else{
			echo '					return "#AAA";';
		}echo'
				})
				.style("stroke-width", function(d) {
					if(d.status=="0") return "2"; //line is less thicker when not checked
					if(d.status=="1") return "3";
				})
				.attr("x1", function(d) { return d.source.x; })
				.attr("y1", function(d) { return d.source.y; })
				.attr("x2", function(d) { return d.target.x; })
				.attr("y2", function(d) { return d.target.y; });

	// Create Nodes
	var node = svg.selectAll(".node")
				.data(force.nodes())
				.enter().append("g")
				.attr("class", "node")
				.on("mouseover", nodeMouseover)
				.on("mouseout", nodeMouseout)
				.call(force.drag);

	// Append circles to Nodes
	node.append("circle")
		.attr("x", function(d) { return d.x; })
		.attr("y", function(d) { return d.y; })
		.attr("r", function(d) { return ((d.count/'.$max_connections.')*10); }   ) // Size of node is defined by number of connections to this node - normalized with max connections; If max connections to one node is 6..size will be 10. If other has 3 connections size will be 5.
		.style("fill", "White") // Make the nodes hollow looking
		.style("stroke-width", 7) // Give the node strokes some thickness
		.style("stroke", function(d, i) { colorVal = colorScale(d.group); return colorVal; } ) // Node stroke colors; colour of stroke is defined by which group node is in
	    .call(force.drag);
 
	// Append text to Nodes
	node.append("a")
		.attr("xlink:href", function(d) { return d.hlink; })
		.append("text")
		.attr("x", function(d) { return 10; } )
		.attr("y", function(d) { return -15; } )
		.attr("text-anchor", function(d) { return "start"; })
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 18px Arial")
		.attr("fill", "Blue")
		.attr("dy", ".35em")
		.text(function(d) { return d.name; });
				
	// Append text to Link edges
	linkText = svg.selectAll(".gLink")
		.data(force.links())
		.on("mouseover", linkMouseover)
		.on("mouseout", linkMouseout)	
		.append("text")
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.attr("x", function(d) { // The point of this system is to always put text on right side of the link..If link is rising in y, text should be lowered, if links is falling in y text should be increased
			if (d.target.x > d.source.x) {
				if (d.target.y == d.source.y){ 
						return (d.source.x + 0 + (d.target.x - d.source.x)/2); // x2>x1&&y2==y1
				}else if(d.target.y > d.source.y){
					return (d.source.x + 7 + (d.target.x - d.source.x)/2); // x2>x1&&y2>y1
				}else{
					return (d.source.x + 7 + (d.target.x - d.source.x)/2); // x2>x1&&y2<y1
				}
			}else if(d.target.x == d.source.x){
				if(d.target.y > d.source.y){
					return (d.source.x + 7 + (d.target.x - d.source.x)/2); // x2==x1&&y2>y1
				}else{
					return (d.source.x + 7 + (d.target.x - d.source.x)/2); // x2==x1&&y2<y1
				}
			}else{
				if (d.target.y == d.source.y){
					return (d.target.x + 0 + (d.source.x - d.target.x)/2); // x2<x1&&y2==y1
				}else if (d.target.y > d.source.y){
					return (d.target.x + 7 + (d.source.x - d.target.x)/2); //x2<x1&&y2>y1
				}else{
					return (d.target.x + 7 + (d.source.x - d.target.x)/2); //x2<x1&&y2>y1
				}
			}
		})
		.attr("y", function(d) {
			if (d.target.x > d.source.x) {
				if (d.target.y == d.source.y){ 
					return (d.source.y + 7 + (d.target.y - d.source.y)/2); // x2>x1&&y2==y1
				}else if(d.target.y > d.source.y){
					return (d.source.y - 7 + (d.target.y - d.source.y)/2); // x2>x1&&y2>y1
				}else{
					return (d.source.y + 7 + (d.target.y - d.source.y)/2); // x2>x1&&y2<y1
				}
			}else if(d.target.x == d.source.x){
				if(d.target.y > d.source.y){
					return (d.source.y + 0 + (d.target.y - d.source.y)/2); // x2==x1&&y2>y1
				}else{
					return (d.source.y + 0 + (d.target.y - d.source.y)/2); // x2==x1&&y2<y1
				}
			}else{
				if (d.target.y == d.source.y){
					return (d.target.y + 7 + (d.source.y - d.target.y)/2); // x2<x1&&y2==y1
				}else if (d.target.y > d.source.y){
					return (d.target.y - 7 + (d.source.y - d.target.y)/2); //x2<x1&&y2>y1
				}else{
					return (d.target.y + 7 + (d.source.y - d.target.y)/2); //x2<x1&&y2>y1
				}
			}
		})
		.attr("fill", "Maroon")
			.style("font", "normal 18px Arial")
			.attr("dy", ".35em")
			.text(function(d) { return d.linkName; });

	function tick() {
		link
			.attr("x1", function(d) { return d.source.x; })
			.attr("y1", function(d) { return d.source.y; })
			.attr("x2", function(d) { return d.target.x; })
			.attr("y2", function(d) { return d.target.y; });
			
		node
			.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; });
			
		linkText
			.attr("x", function(d) { // The point of this system is to always put text on right side of the link..If link is rising in y, text should be lowered, if links is falling in y text should be increased
				if (d.target.x > d.source.x) {
					if (d.target.y == d.source.y){ 
						return (d.source.x + 0 + (d.target.x - d.source.x)/2); // x2>x1&&y2==y1
					}else if(d.target.y > d.source.y){
						return (d.source.x + 7 + (d.target.x - d.source.x)/2); // x2>x1&&y2>y1
					}else{
						return (d.source.x + 7 + (d.target.x - d.source.x)/2); // x2>x1&&y2<y1
					}
				}else if(d.target.x == d.source.x){
					if(d.target.y > d.source.y){
						return (d.source.x + 7 + (d.target.x - d.source.x)/2); // x2==x1&&y2>y1
					}else{
						return (d.source.x + 7 + (d.target.x - d.source.x)/2); // x2==x1&&y2<y1
					}
				}else{
					if (d.target.y == d.source.y){
						return (d.target.x + 0 + (d.source.x - d.target.x)/2); // x2<x1&&y2==y1
					}else if (d.target.y > d.source.y){
						return (d.target.x + 7 + (d.source.x - d.target.x)/2); //x2<x1&&y2>y1
					}else{
						return (d.target.x + 7 + (d.source.x - d.target.x)/2); //x2<x1&&y2>y1
					}
				}
			})
			.attr("y", function(d) {
				if (d.target.x > d.source.x) {
					if (d.target.y == d.source.y){ 
						return (d.source.y + 7 + (d.target.y - d.source.y)/2); // x2>x1&&y2==y1
					}else if(d.target.y > d.source.y){
						return (d.source.y - 7 + (d.target.y - d.source.y)/2); // x2>x1&&y2>y1
					}else{
						return (d.source.y + 7 + (d.target.y - d.source.y)/2); // x2>x1&&y2<y1
					}
				}else if(d.target.x == d.source.x){
					if(d.target.y > d.source.y){
						return (d.source.y + 0 + (d.target.y - d.source.y)/2); // x2==x1&&y2>y1
					}else{
						return (d.source.y + 0 + (d.target.y - d.source.y)/2); // x2==x1&&y2<y1
					}
				}else{
					if (d.target.y == d.source.y){
						return (d.target.y + 7 + (d.source.y - d.target.y)/2); // x2<x1&&y2==y1
					}else if (d.target.y > d.source.y){
						return (d.target.y - 7 + (d.source.y - d.target.y)/2); //x2<x1&&y2>y1
					}else{
						return (d.target.y + 7 + (d.source.y - d.target.y)/2); //x2<x1&&y2>y1
					}
				}
			});
	}

	function nodeMouseover() {
		d3.select(this).select("circle").transition()
			.duration(150)
			.attr("r", function(d,i) { return 10; } );

		d3.select(this).select("text").transition()
			.duration(150)
			.style("font", "bold 18px Arial")
			.attr("fill", "Blue");
	}
	
	function nodeMouseout() {
		d3.select(this).select("circle").transition()
			.duration(150)
			.attr("r", function(d,i) {return ((d.count/'.$max_connections.')*10); }   ) // Size of node is defined by number of connections to this node - normalized with max connections; If max connections to one node is 6..size will be 10. If other has 3 connections size will be 5.
		
		d3.select(this).select("text").transition()
			.duration(150)
			.style("font", "normal 18px Arial")
			.attr("fill", "Blue");
	}
	  
	function linkMouseover() {
		d3.select(this).select("text").transition()
			.duration(150)
			.attr("fill", "Maroon")
			.style("font", "bold 18px Arial")
			.attr("dy", ".35em")
	}

	function linkMouseout() {
		d3.select(this).select("text").transition()
			.duration(150)
			.attr("fill", "Maroon")
			.style("font", "normal 18px Arial")
			.attr("dy", ".35em")
	}

	/* Function for similarity slider */
	$(function() {
		$( "#slider-range-max" ).slider({
			range: "max",
			min: 0,
			max: '.$max_similarity.',
			value: '.$min_similarity.',
			slide: function( event, ui ) {
				$( "#amount" ).val( ui.value+"%");
				if($(\'#show_type\').val()=="all"){
					window.location="visualisation.php?'.$redirect.'"+ui.value+"";
				}else if($(\'#show_type\').val()=="checked"){
					window.location="visualisation.php?'.$redirect.'"+ui.value+"&show=1";
				}else if($(\'#show_type\').val()=="non_checked"){
					window.location="visualisation.php?'.$redirect.'"+ui.value+"&show=0";
				}
			}
		});
		$( "#amount" ).val( $( "#slider-range-max" ).slider( "value" )+"%" );
	});
	/* Function for force slider */

	$(function() {
		$( "#slider-range-min" ).slider({
			range: "min",
			min: 0,
			max: 100,
			value: 20,
			slide: function( event, ui ) {
				$( "#amount2" ).val( ui.value);
				force.charge(-(100-ui.value)*10);
				force.start();
			}
		});
		$( "#amount2" ).val( $( "#slider-range-min" ).slider("value" ));
	});

	function legend_mouse_click(){
		legend.selectAll("text.title").remove();
		
		legend.append("rect")
			.attr("class", "info")
			.style("fill", "gray")
			.style("fill-opacity", 0.1)
			.attr("width", 200)
			.attr("height", 600);
	  
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 15)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "bold 14px Arial")
		.attr("fill", "Black")
		.text("Mouse click to hide");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 30)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Each colour of node represent group");
		
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 45)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("of connected students.");
		
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 75)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Node size presents number of");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 90)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text(" connected persons to this person.");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 105)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text(" Bigger node -> More students");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 120)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text(" Smaler node -> Less students");
	
	
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 150)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Click on node redirects you to");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 165)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text(" this person\'s page.");
	
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 180)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Click on link redirects you to");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 195)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text(" this two person\'s page.");
	
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 225)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Label above node is person\'s ID.");
	
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 240)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Label above link shows assignment");
		
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 255)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text(" names and if there exist Facebook");
		
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 270)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text(" or Twitter friendship relationship.");
		
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 300)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Above visualisation you can change");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 315)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text(" minimal similarity that has to be");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 330)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text(" between users to be shown in graph.");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 360)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("With charge you can change");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 375)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text(" attraction between nodes.");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 405)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("With show you can change whether");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 420)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("you would like to see all links,");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 435)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("only links that were confirmed and");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 450)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("links that were not yet checked.");
		
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 480)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Links that are grey are not");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 495)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("yet checked.");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 510)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Links that are red are confirmed.");
	legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 525)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "normal 12px Arial")
		.attr("fill", "Black")
		.text("Rejected links are not shown.");
}

	function legend_mouse_out() {
		legend.selectAll("rect.info").remove();
		legend.selectAll("text.info").remove();
		legend.selectAll("text.title").remove();
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 15)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "bold 14px Arial")
			.attr("fill", "Black")
			.text("Mouse over for Info");
	}
</script>';
	}else if($type_of_viz=="cmv_viz"){
	echo '
<script type="text/javascript">
	// Template from GitHub was used and modified for appropriate needs: http://bost.ocks.org/mike/miserables/
	var margin = {top: 80, right: 0, bottom: 10, left: 80};
	var width = 600;
	var height = 600;
		
	var x = d3.scale.ordinal().rangeBands([0, width]);
    var z = d3.scale.linear().domain([0, 10]).clamp(true); //Define color opacity - 10 shades of color;'.PHP_EOL;;

	/* If number of groups is less than 10, use color scale for 10 colours; else use colour scale of 20 */
	if($number_of_groups <= 10){
		echo '	var c = d3.scale.category10().domain(d3.range(10)); //Define number of colors - number of groups-clusters-connected components';
	}else{
		echo '	var c = d3.scale.category20().domain(d3.range(20)); //Define number of colors - number of groups-clusters-connected components';
	}
	
    echo '	
	d3.select("#show_type").on("change", function() {
		show_type(this.value);
	});

	function show_type(value) {
		if(value=="all"){
			window.location="visualisation.php?'.$redirect.'"+$(\'#slider-range-max\').slider("option", "value");
		}else if(value=="checked"){
			window.location="visualisation.php?'.$redirect.'"+$(\'#slider-range-max\').slider("option", "value")+"&show=1";
		}else if(value=="non_checked"){
			window.location="visualisation.php?'.$redirect.'"+$(\'#slider-range-max\').slider("option", "value")+"&show=0";
		}
	}

	var svg = d3.select("#cmv_viz").append("svg:svg")
		.attr("width", width + margin.left + margin.right)
		.attr("height", height + margin.top + margin.bottom)
		.style("margin-left", -margin.left + "px")
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")")
		.on("mousemove", svg_mouse_move);
	
	/* Legend (help menu) */
	';
	if(!isset($_GET[gen_pdf])){
	echo '
	var legend = d3.select("#cmv_viz").append("svg:svg")
				.attr("width", 200 )
				.attr("height", 600)
				.style("margin-left", 30 + "px")
				.style("margin-bottom", height-590 + "px")
				.on("mousedown", legend_mouse_out);';
	}else{
		echo '	var legend = d3.select("#none");';
	}


	echo '	legend.append("rect")
		.attr("class", "background")
		.attr("width", 200)
		.attr("height", 20)
		.on("mouseover", legend_mouse_click);
	  
    legend.append("text")
		.attr("class", "title")
		.attr("x", 0)
		.attr("y", 15)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "bold 14px Arial")
		.attr("fill", "Black")
		.text("Mouse over for Info");



	// Create a table where link id is stored by source and target id
	var links_table = new Array(nodes.length);
	for (var i = 0; i < nodes.length; i++) {
		links_table[i] = new Array(nodes.length);
	}
	links.forEach(function(link) {
		links_table[link.source][link.target]= link.elink;
		links_table[link.target][link.source]= link.elink;
	});

	var matrix = [],
		//nodes = miserables.nodes,
		n = nodes.length;

	// Compute index per node.
	nodes.forEach(function(node, i) {
		node.index = i;
		node.count = 0;
		matrix[i] = d3.range(n).map(function(j) { return {x: j, y: i, z: 0}; });
	});

	// Convert links to matrix; count character occurrences.
	links.forEach(function(link) {
		matrix[link.source][link.target].z += link.value;
		matrix[link.target][link.source].z += link.value;
		matrix[link.source][link.source].z = 10; //Similarity between same document is always 100%
		matrix[link.target][link.target].z = 10;
		nodes[link.source].count += link.value;
		nodes[link.target].count += link.value;
	});

	// Precompute the orders.
	var orders = {
		name: d3.range(n).sort(function(a, b) { return d3.ascending(nodes[a].name, nodes[b].name); }),
		count: d3.range(n).sort(function(a, b) { return nodes[b].count - nodes[a].count; }),
		group: d3.range(n).sort(function(a, b) { return nodes[b].group - nodes[a].group; })
	};

	// The default sort order.
	x.domain(orders.group);

	svg.append("rect")
		.attr("class", "background")
		.attr("width", width)
		.attr("height", height);

	var row = svg.selectAll(".row")
		.data(matrix)
		.enter().append("g")
		.attr("class", "row")
		.attr("transform", function(d, i) { return "translate(0," + x(i) + ")"; })
		.each(row);
 
	row.append("line")
		.attr("x2", width);

	row.append("text")
		.attr("x", -6)
		.attr("y", x.rangeBand() / 2 )
		.attr("dy", ".32em")
		.attr("text-anchor", "end")
		.append("a")
		.attr("xlink:href", function(d,i) { return nodes[i].hlink; })
		.text(function(d, i) { return nodes[i].name; });';
	
	if(isset($_GET[gen_pdf])){
		echo '	row.style("font", "11px Arial");';
	}
	echo '
	var column = svg.selectAll(".column")
					.data(matrix)
					.enter().append("g")
					.attr("class", "column")
					.attr("transform", function(d, i) { return "translate(" + x(i) + ")rotate(-90)"; });

	column.append("line")
		.attr("x1", -width);

	column.append("text")
		.attr("x", 6)
		.attr("y", x.rangeBand() / 2)
		.attr("dy", ".32em")
		.attr("text-anchor", "start")
		.append("a")
		.attr("xlink:href", function(d,i) { return nodes[i].hlink; })
		.text(function(d, i) { return nodes[i].name; });
';
	if(isset($_GET[gen_pdf])){
		echo '	column.style("font", "11px Arial");';
	}
echo '
	function row(row) {
		var cell = d3.select(this).selectAll(".cell")
					.data(row.filter(function(d) { return d.z; }))
					.enter()
					.append("a")
					.attr(
						"xlink:href", function(d) {
							return links_table[nodes[d.x].id][nodes[d.y].id];
						}
					)
					.append("rect")
					.attr("class", "cell")
					.attr("x", function(d) { return x(d.x); })
					.attr("width", x.rangeBand())
					.attr("height", x.rangeBand())
					.style("fill-opacity", function(d) { return z(d.z); })
					.style("fill", function(d) { return nodes[d.x].group == nodes[d.y].group ? c(nodes[d.x].group) : null; })
					.on("mouseover", mouseover)
					.on("mouseout", mouseout);
	}

	/* Tooltip text for showing cell data */
	svg.append("text")
		.attr("class", "info")
		.classed("hidden", true)
		.attr("font-family", "Arial, Helvetica, sans-serif")
		.style("font", "bold 14px Arial")
		.attr("fill", "Black");
	
	svg.append("rect")
		.attr("class", "info")
		.classed("hidden", true)
		.attr("width", 45)
		.attr("height", 30)
		.attr("border-radius", 10)
		.attr("fill", "steelblue" )
		.style("fill-opacity",0.5);

	function legend_mouse_click(){
		legend.selectAll("text.title").remove();
		legend.append("rect")
		  .attr("class", "info")
		  .style("fill", "gray")
		  .style("fill-opacity", 0.1)
		  .attr("width", 200)
		  .attr("height", 600);
		  
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 15)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "bold 14px Arial")
			.attr("fill", "Black")
			.text("Mouse click to hide");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 30)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("Each colour of cells represent group");
			
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 45)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("of connected students.");
			
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 75)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("Cell opacity presents percent of");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 90)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("similarity.");
		
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 120)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("Click on the cell forwards you to");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 135)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("page with selected user similarities.");
		
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 165)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("Click on the ID above and left");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 180)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("forwards you to page with ");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 195)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("selected user similarities.");
		
		
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 225)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("Mouse over cell shows average");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 240)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("similarity between selected users.");
		
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 270)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("Similarity between same user is");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 285)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text(" always 100%.");

		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 315)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("Above visualisation you can change");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 330)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("order of data presentation and");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 345)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("other settings.");
			
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 360)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("If person has match with other");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 375)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("person in more than one");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 390)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("assignment, average similarity is");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 405)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("shown in graph. Match is shown if");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 420)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("any of these matches has greater");
		legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 435)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "normal 12px Arial")
			.attr("fill", "Black")
			.text("or same similarity as selected.");
	}
		
	function legend_mouse_out() {
		legend.selectAll("rect.info").remove();
		legend.selectAll("text.info").remove();
		legend.selectAll("text.title").remove();
	    legend.append("text")
			.attr("class", "title")
			.attr("x", 0)
			.attr("y", 15)
			.attr("font-family", "Arial, Helvetica, sans-serif")
			.style("font", "bold 14px Arial")
			.attr("fill", "Black")
			.text("Mouse over for Info");
	}

	function mouseout() {
		svg.selectAll("text").classed("active", false);
		svg.selectAll("text.info").classed("hidden", true);
	}

	function mouseover(p) {
		svg.selectAll(".row text").classed("active", function(d, i) { return i == p.y; });
		svg.selectAll(".column text").classed("active", function(d, i) { return i == p.x; });

		svg.selectAll("text.info").classed("hidden", false);
		svg.selectAll("text.info").text(function(d,i) {
			if(p.x == p.y){
				return 100+"%";
			}else{
				return d3.round(('.$max_similarity.'*(z(p.z))),1)+"%";
			}
		});
	}
	function svg_mouse_move(){
		var mouse_x=d3.mouse(this)[0];
		var mouse_y=d3.mouse(this)[1];
		svg.selectAll("text.info")
			.attr("x", mouse_x+15)
			.attr("y", mouse_y+15);
	}

	d3.select("#order").on("change", function() {
		clearTimeout(timeout);
		order(this.value);
	});
  
	var show_same_value = false;
  
	d3.selectAll("input[id=show_same]").on("change", function() {
		clearTimeout(timeout);
		show_same_value=this.checked;
		show_same(this.checked);
	});
	
	var normalize_value = true;
	
	d3.selectAll("input[id=normalize]").on("change", function() {
		clearTimeout(timeout);
		normalize_value = this.checked;
		normalize(this.checked);
	});';
  
	/* Show normalized values when generating pdf */
	if(isset($_GET[gen_pdf])){
		echo '	normalize(0);';
	}
	echo '
	function normalize(value){
		svg.selectAll(".row")
			.selectAll(".cell")
			.style("fill-opacity", function(d){
				if(d.x==d.y){
					if(show_same_value==true){
						return 0;
					}else{
						return 10;
					}
				}else{
					if(value==true){
						return z(d.z);
					}else{
						//d3.select("body").append("p").text('.$max_similarity.'*z(d.z)/100);
						return ('.$max_similarity.'*z(d.z)/100);
					}
				}
			});
	}

	function show_same(value){
		svg.selectAll(".row")
			.selectAll(".cell")
			.style("fill-opacity", function(d){
				if(d.x==d.y){
					if(value==true){
						return 0;
					}else{
						return z(d.z);
					}
				}else{
					if(normalize_value==true){
						return z(d.z);
					}else{
						return ('.$max_similarity.'*z(d.z)/100);
					}
				}
			});
	}
	function order(value) {
		x.domain(orders[value]);
		
		var t = svg.transition().duration(2500);
		
		t.selectAll(".row")
			.delay(function(d, i) { return x(i) * 4; })
			.attr("transform", function(d, i) { return "translate(0," + x(i) + ")"; })
			.selectAll(".cell")
			.delay(function(d) { return x(d.x) * 4; })
			.attr("x", function(d) { return x(d.x); });
		
		t.selectAll(".column")
			.delay(function(d, i) { return x(i) * 4; })
			.attr("transform", function(d, i) { return "translate(" + x(i) + ")rotate(-90)"; });
	}

	var timeout = setTimeout(function() {
		order("group");
		d3.select("#order").property("selectedIndex", 0).node().focus();
	}, 0);

	
	/* Function for similarity slider */
	$(function() {
		$( "#slider-range-max" ).slider({
			range: "max",
			min: 0,
			max: '.$max_similarity.',
			value: '.$min_similarity.',
			slide: function( event, ui ) {
				$( "#amount" ).val( ui.value+"%");
				if($(\'#show_type\').val()=="all"){
					window.location="visualisation.php?'.$redirect.'"+ui.value+"";
				}else if($(\'#show_type\').val()=="checked"){
					window.location="visualisation.php?'.$redirect.'"+ui.value+"&show=1";
				}else if($(\'#show_type\').val()=="non_checked"){
					window.location="visualisation.php?'.$redirect.'"+ui.value+"&show=0";
				}
			}
		});
		$( "#amount" ).val( $( "#slider-range-max" ).slider( "value" )+"%" );

	});

</script>';
	}
}
?>
<?php

/* When pdf is being generated, only graph should be shown */
if(!isset($_GET[gen_pdf])){
	include('footer.php');
}

?>
