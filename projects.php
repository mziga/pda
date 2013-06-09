<?php include('header.php'); ?>
<?
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

/* Function getResults to return user details when it is searched */
/* Example:
If $url is $url = "https://graph.facebook.com/search?access_token=".$fb_access_token."&q=".urlencode($search)."&type=user";,
where $fb_access_token is token you get on facebook and $search is for example "Name Surname" it returns array of users, which have corresponding
name.
	$result[data][0][id] - fb id of first user
	$result[data][0][name] - fb name of first user
	$result[data][1][id] - fb id of second user -> One search query can result in multiple users
	
*/
function getResults($url){
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


/* Function gets assignment ID-s and moss urls and parses moss output and then saves data in database*/
function parse_moss($assignments, $moss_ids){
	// Set time limit for this function, so script does not stop. It might take up to 10 minutes per assignment to completed.
	set_time_limit(0);
	for($i=1; $i<=end(array_keys($assignments)); $i++){  //Go through every assignment; only those checked are here
		if($assignments[$i]!=""){
			$assignment_id=mysql_real_escape_string($assignments[$i]);
			$result=mysql_query('SELECT * FROM assignment WHERE assignment_id="'.$assignment_id.'"');
			$row = mysql_fetch_assoc($result);
			$project_id=$row[project_id];
			/* First set all current matches to disabled, if they already exist in assignment;
				This can happen when user reuploads new file and then restart Moss check
				match_status=0 - just added
				match_status=1 - confirmed
				match_status=2 - rejected
				match_status=3 - disabled/deleted
			*/
			$sql='UPDATE matches M SET M.match_status=3 WHERE M.assignment_id='.$assignment_id.';';
			mysql_query($sql);
			
			/* Set assignment_status to 1; this means this assignment has been checked with moss */
			$sql='UPDATE assignment A SET A.assignment_status=1 WHERE A.assignment_id='.$assignment_id.';';
			mysql_query($sql);
			
			/* Get data from moss results */
			$path_to_moss_index='projects/'.$project_id.'/'.$assignment_id.'/moss/moss.stanford.edu/results/'.$moss_ids[$i].'/index.html';
			$doc = new DOMDocument();
			$doc->loadHTML(file_get_contents($path_to_moss_index));
			/* moss_results[][]
				moss_results[0][0]-match0.html
				moss_results[0][1]-user1_name
				moss_results[0][2]-user1_surname
				moss_results[0][3]-user1_id
				moss_results[0][4]-user1_percent
				moss_results[0][5]-user2_name
				moss_results[0][6]-user2_surname
				moss_results[0][7]-user2_id
				moss_results[0][8]-user2_percent
				moss_results[0][9]-users_lines
				moss_results[1][0]-match1.html
				...
			
			*/
			$counter=-1;
			$moss_results = array(array());
			$names_ids = array();
			foreach($doc->getElementsByTagName('tr') as $link) {
			 
				if($counter>-1){
				/*
				parts 0 path/to/namesurnameid1
				parts 1 percent1
				parts 2 !empty
				parts 3 !empty
				parts 4 !empty
				parts 5 path/to/namesurnameid2
				parts 6 percent2 lines
				
				*/
				// $link->nodeValue; <- example: "projects/49/141/submissions/name_surname_(63000001).sh (29%) projects/49/141/submissions/name2_surname2_(63000002).sh (21%) 15"
				//echo $link->nodeValue.'<br>';
					$parts = explode(" ", $link->nodeValue);
					
						$tokens = explode('/', $parts[0]);
						$name_surname_id=$tokens[sizeof($tokens)-1]; // get $name_surname in format: "name_surname_secondsurname_(id).fileextension"
							$tokens = explode('_', $name_surname_id);
							//echo count($tokens);
							if(count($tokens)>=3){
								/* Check if string contains brackets () ...if not, this means three names or more are used */
								if (strpos($name_surname_id, '(') !== true){
									/* It does not contain brackets */
									/* Now get name and surname are needed (or posible second name, and second surname */
										$tokens = explode('_', $name_surname_id);
										$moss_results[$counter][1] = $tokens[0]; //First one is always name
									//Others are surnames
									$surname="";
									for($intN=1; $intN<=count($tokens); $intN++){
										if($intN==1){
											if($intN==count($tokens)-1){
												$surname=explode('.', $tokens[$intN])[0];
											}else{
												$surname=$tokens[$intN];
											}
										}else if($intN==count($tokens)-1){ //At the end there is .extenstion added and must be removed
											$surname.=' '.explode('.', $tokens[$intN])[0];
										}else{
											$surname.=' '.$tokens[$intN];
										}
									}
									$moss_results[$counter][2]=$surname;
									/* In array names_ids are names of persons; id presents where in array this person is available;
										Check if person already exist in table, else put in table; use it's id to be saved in database */
									if(!in_array($tokens[0].' '.$surname, $names_ids, true)){
										array_push($names_ids, $tokens[0].' '.$surname);
										$id_of_user=max(array_keys($names_ids));
									}else{
										$id_of_user=array_search($tokens[0].' '.$surname, $names_ids);
									}
									$moss_results[$counter][3]=$project_id*10000000+$id_of_user;
									/* Now  get percents for this user */
										$percents = str_replace(array( '(', ')' ), '', $parts[1]); //Percents are stored in $parts[1] and they are in format (xx%), so we loose the brackets
										$moss_results[$counter][4] = explode("%", $percents)[0];
								}else{
									$tokens = explode('_', $name_surname_id);
									$user1_id_nf = $tokens[sizeof($tokens)-1]; //in format: "(id).fileextension"
									$tokens = explode('.', $user1_id_nf);
									$user1_id = $tokens[sizeof($tokens)-2]; //in format: "(id)"
									$moss_results[$counter][3] = str_replace(array( '(', ')' ), '', $user1_id); //Final format: "id"
									/* Now get name and surname (or posible second name, and second surname */
										$tokens = explode('_', $name_surname_id);
										$moss_results[$counter][1] = $tokens[0]; //First one is always name
									//Others are surnames
									$surname="";
									for($intN=1; $intN<count($tokens)-1; $intN++){
										if($intN==1){
											$surname=$tokens[$intN];
										}else{
											$surname.=' '.$tokens[$intN];
										}
									}
									$moss_results[$counter][2]=$surname;
									/* Now get percents for this user */
										$percents = str_replace(array( '(', ')' ), '', $parts[1]); //Percents are stored in $parts[1] and they are in format (xx%), so loose the brackets
										$moss_results[$counter][4] = explode("%", $percents)[0];
								}
							}else if(count($tokens)==2){
								/* Now get name and surname (or posible second name, and second surname */
									$tokens = explode('_', $name_surname_id);
									$moss_results[$counter][1] = $tokens[0]; //First one is always name
								//Others are surnames
								$surname=explode('.', $tokens[1])[0];
								$moss_results[$counter][2]=$surname;
								/* In array names_ids are names of persons; id presents where in array this person is available;
									Check if person already exist in table, else put in table; use it's id to be saved in database */
								if(!in_array($tokens[0].' '.$surname, $names_ids, true)){
									array_push($names_ids, $tokens[0].' '.$surname);
									$id_of_user=max(array_keys($names_ids));
								}else{
									$id_of_user=array_search($tokens[0].' '.$surname, $names_ids);
								}
								$moss_results[$counter][3]=$project_id*10000000+$id_of_user;
								/* Now get percents for this user */
									$percents = str_replace(array( '(', ')' ), '', $parts[1]); //Percents are stored in $parts[1] and they are in format (xx%), so loose the brackets
									$moss_results[$counter][4] = explode("%", $percents)[0];
							}else if(count($tokens)==1){
								$user1_id_nf = $tokens[sizeof($tokens)-1]; //in format: "id.fileextension"
								$tokens = explode('.', $user1_id_nf);
								print_r($tokens.'<br>');
								$user1_id = $tokens[sizeof($tokens)-2]; //in format: "id"
								$moss_results[$counter][3] = $user1_id; //Final format: "id"
							/* Now get name */
								$moss_results[$counter][1] = $user1_id; 
							/* Now get percents for this user */
								$percents = str_replace(array( '(', ')' ), '', $parts[1]); //Percents are stored in $parts[1] and they are in format (xx%), so loose the brackets
								$moss_results[$counter][4] = explode("%", $percents)[0];
							}
						/* Now repeat procedure for user 2 */
						$tokens = explode('/', $parts[5]); //user2 details are in $parts[5] and $parts[6](percent,lines)
						$name_surname_id=$tokens[sizeof($tokens)-1]; 
							$tokens = explode('_', $name_surname_id);
							//echo $name_surname_id;
							if(count($tokens)>=3){
								/* Check if string contains brackets () ...if not, this means three names or more are used */
								if (strpos($name_surname_id, '(') !== true){
									
									/* It does not contain brackets */
									$tokens = explode('_', $name_surname_id);
									$moss_results[$counter][5] = $tokens[0];
									$surname="";
									for($intN=1; $intN<=count($tokens); $intN++){
										if($intN==1){
											if($intN==count($tokens)-1){
												$surname=explode('.', $tokens[$intN])[0];
											}else{
												$surname=$tokens[$intN];
											}
										}else if($intN==count($tokens)-1){
											$surname.=' '.explode('.', $tokens[$intN])[0];
										}else{
											$surname.=' '.$tokens[$intN];
										}
									}
									$moss_results[$counter][6]=$surname;
									/* Now get percents for this user */
										$tokens = explode(')', $parts[6]);
										$percents = str_replace(array( '(' ), '', $tokens[0]);
										$moss_results[$counter][8] = explode("%", $percents)[0];
									/* Now store number of lines which is stored in the second part of tokens */
										$moss_results[$counter][9] = $tokens[1];
									
									if(!in_array($tokens[0].' '.$surname, $names_ids, true)){
										array_push($names_ids, $tokens[0].' '.$surname);
										$id_of_user=max(array_keys($names_ids));
									}else{
										$id_of_user=array_search($tokens[0].' '.$surname, $names_ids);
									}
									$moss_results[$counter][7]=$project_id*10000000+$id_of_user;
								}else{
									$tokens = explode('_', $name_surname_id);
									$user1_id_nf = $tokens[sizeof($tokens)-1]; 
									$tokens = explode('.', $user1_id_nf);
									$user1_id = $tokens[sizeof($tokens)-2]; 
									$moss_results[$counter][7] = str_replace(array( '(', ')' ), '', $user1_id); 
									$tokens = explode('_', $name_surname_id);
									$moss_results[$counter][5] = $tokens[0];
									$surname="";
									for($intN=1; $intN<sizeof($tokens)-1; $intN++){
										if($intN==1){
											$surname=$tokens[$intN];
										}else{
											$surname.=' '.$tokens[$intN];
										}
									}

									$moss_results[$counter][6]=$surname;
									/* Now get percents for this user */
										$tokens = explode(')', $parts[6]);
										$percents = str_replace(array( '(' ), '', $tokens[0]);
										$moss_results[$counter][8] = explode("%", $percents)[0];
									/* Now store number of lines which is stored in the second part of tokens */
										$moss_results[$counter][9] = $tokens[1];
								}
							}else if(count($tokens)==2){
								$tokens = explode('_', $name_surname_id);
								$moss_results[$counter][5] = $tokens[0];
								$surname=explode('.', $tokens[1])[0];
								$moss_results[$counter][6]=$surname;
								/* Now get percents for this user */
									$tokens = explode(')', $parts[6]);
									$percents = str_replace(array( '(' ), '', $tokens[0]);
									$moss_results[$counter][8] = explode("%", $percents)[0];
								/* Now store number of lines which is stored in the second part of tokens */
									$moss_results[$counter][9] = $tokens[1];
								
								if(!in_array($tokens[0].' '.$surname, $names_ids, true)){
									array_push($names_ids, $tokens[0].' '.$surname);
									$id_of_user=max(array_keys($names_ids));
								}else{
									$id_of_user=array_search($tokens[0].' '.$surname, $names_ids);
								}
								$moss_results[$counter][7]=$project_id*10000000+$id_of_user;
							}else if(count($tokens)==1){
								$user1_id_nf = $tokens[sizeof($tokens)-1]; //in format: "id.fileextension"
								$tokens = explode('.', $user1_id_nf);
								$user1_id = $tokens[sizeof($tokens)-2]; //in format: "id"
								$moss_results[$counter][7] = $user1_id; //Final format: "id"
								/* Now get name */
									$moss_results[$counter][5] = $user1_id; 
								/* Now get percents for this user */
									$tokens = explode(')', $parts[6]);
									$percents = str_replace(array( '(', ')' ), '', $parts[1]); //Percents are stored in $parts[1] and they are in format (xx%), so loose the brackets
									$moss_results[$counter][8] = explode("%", $percents)[0];
									$moss_results[$counter][9] = $tokens[1];
							}
				}
				$counter++;
			}
			$counter=-6;
			$inside_counter=0;
			foreach($doc->getElementsByTagName('a') as $link) {
				if($counter>-1){
					if($counter%2==0){
						$moss_results[$inside_counter][0]=$moss_ids[$i].'/'.$link->getAttribute('href');
						$inside_counter++;
					}
				}
				$counter++;
			}
			/* Display results */ 
			/*
			for($int=0; $int<count($moss_results); $int++){
				echo 'Filename : '.$moss_results[$int][0].' with '.$moss_results[$int][9].' similar lines.';
				echo '<br>     User: '.$moss_results[$int][1].' '.$moss_results[$int][2];
				echo ' ('.$moss_results[$int][3].') with '.$moss_results[$int][4].'%.';
				echo '<br>     User: '.$moss_results[$int][5].' '.$moss_results[$int][6];
				echo ' ('.$moss_results[$int][7].') with '.$moss_results[$int][8].'%.<br><br>';
			}
			*/
			
	
			/* Now table "moss_results" exist and results can be put in database */
			for ($int=0; $int<count($moss_results); $int++){
				/* First create two persons, if they do not exist already*/
					/* Check if person already exist */
					$sql='SELECT * FROM person WHERE person_ident="'.$moss_results[$int][3].'";';
					$result=mysql_query($sql);
					/* If there is not, create new */
					if(mysql_num_rows($result)==0){
						$sql='INSERT INTO person SET person_ident="'.$moss_results[$int][3].'", person_name="'.$moss_results[$int][1].'", person_surname="'.$moss_results[$int][2].'";';
						mysql_query($sql);
						/* Get id in database for new just added user */
						$first_person_db_id=mysql_insert_id();
					}
					/*If there is person, his id in database must be known */
					else{
						$row = mysql_fetch_assoc($result);
						$first_person_db_id=$row[person_id];
					}
					/* Check if person2 already exist */
					$sql='SELECT * FROM person WHERE person_ident="'.$moss_results[$int][7].'";';
					$result=mysql_query($sql);
					/* If there is not, create new */
					if(mysql_num_rows($result)==0){
						$sql='INSERT INTO person SET person_ident="'.$moss_results[$int][7].'", person_name="'.$moss_results[$int][5].'", person_surname="'.$moss_results[$int][6].'";';	
						mysql_query($sql);
						$second_person_db_id=mysql_insert_id();
					}
					/*If there is person, his id in database must be known */
					else{
						$row = mysql_fetch_assoc($result);
						$second_person_db_id=$row[person_id];
					}
				/* Then put in their match */
					$sql='INSERT INTO matches SET match_first_id="'.$first_person_db_id.'", match_second_id="'.$second_person_db_id.'", assignment_id="'.$assignment_id.'", match_first_sim="'.$moss_results[$int][4].'", match_second_sim="'.$moss_results[$int][8].'", match_lines="'.(int)$moss_results[$int][9].'", match_url="'.$moss_results[$int][0].'", match_status=0;';
					mysql_query($sql);
					
			}
			
		}
	}
}

	
function get_fb_data($fb_access_token, $project_id, $social_media_start_position, $social_media_end_position, $options, $person1_id, $person2_id){
	// Set time limit for this function, so script does not stop. It might take up to 10 minutes per assignment to completed.
	set_time_limit(0);
	
	/* Options settings */
	/*	$options = 0; - check for every user in database and find every relation
	/*  $options = 1; - check only for users
	/*  $options = 2; - check only for relations
	*/
	
	/* Now search if user exist on facebook */
	
	/* Go through every user in database - users which are used in selected project */
	/* Not every user because there is limit in config file */
	
	
	/* First check what similarity has match at position social_media_start_position */
	if($options==0){
		$sql='SELECT * FROM matches M, assignment A WHERE M.assignment_id = A.assignment_id AND A.project_id = '.$project_id.' AND NOT M.match_status=3 ORDER BY M.match_first_sim ASC , M.match_second_sim ASC LIMIT '.$social_media_start_position.' , 1;';
		$result=mysql_query($sql);
		$row = mysql_fetch_assoc($result);
		$min_similarity=$row[match_first_sim];
		/* Then check what similarity has match at position social_media_end_position */
		$sql='SELECT * FROM matches M, assignment A WHERE M.assignment_id = A.assignment_id AND A.project_id = '.$project_id.' AND NOT M.match_status=3 ORDER BY M.match_first_sim ASC , M.match_second_sim ASC LIMIT '.($social_media_end_position-1).' , 1;';
		$result=mysql_query($sql);
		$row = mysql_fetch_assoc($result);
		$max_similarity=$row[match_first_sim];	
	}
	
	/* Now include those values to go through persons who have that similarity */
	if($options==0 || $options==1){
		if($options==0){
			$sql='SELECT * FROM person P WHERE (P.person_id IN (SELECT match_second_id FROM assignment A, matches M WHERE A.project_id="'.$project_id.'" AND A.assignment_id=M.assignment_id AND M.match_first_sim>="'.$min_similarity.'" AND M.match_first_sim<="'.$max_similarity.'") OR P.person_id IN (SELECT match_first_id FROM assignment A, matches M WHERE A.project_id="'.$project_id.'" AND A.assignment_id=M.assignment_id AND M.match_first_sim>="'.$min_similarity.'" AND M.match_first_sim<="'.$max_similarity.'"));';
		}else if($options==1){
			$sql='SELECT * FROM person P WHERE P.person_id="'.$person1_id.'";';
		}
		$result=mysql_query($sql);
	
		while($row = mysql_fetch_assoc($result)){
			$search=$row[person_name].' '.$row[person_surname];

			/*search_inverted is used for comparison with fb results; both options are used */
			$search_inverted=strtolower($row[person_surname].' '.$row[person_name]);

			//echo $search.'<br>';
			$url = "https://graph.facebook.com/search?access_token=".$fb_access_token."&q=".urlencode($search)."&type=user";
			$ret_json = getResults($url);
			//echo $url.'<br>';
			/* In $users there will be stored every user who corresponds to search of "Name Surname" */
			$users = json_decode($ret_json, true);
			/* Insert every found facebook account */
			/* Comment: Not every found, because some are not even with correct name */
			//echo count($users[data]);

			for($i=0; $i<count($users[data]); $i++){
				/* Check if name and surname is similar to the search string using Levenstein distance */
				/* Convert search string to lower letters */
				$search=strtolower($search);
				/* Compare search string with result(low leter) */
				/* Name should differ with max 5 characters */
				//echo 'Comparing: "'.$search.'" with "'.$users[data][$i][name].'".';
				//echo "L1: ".levenshtein($search, strtolower($users[data][$i][name]));
				//echo "<br>L2: ".levenshtein($search_inverted, strtolower($users[data][$i][name]));
				if((levenshtein($search, strtolower($users[data][$i][name]))<5) || (levenshtein($search_inverted, strtolower($users[data][$i][name]))<5)){
					/* First check if this person does not already exist in database */
					$sql_q = 'SELECT COUNT(*) AS "exist" FROM fb_account WHERE fb_user_id="'.$users[data][$i][id].'";';
					$result_q=mysql_query($sql_q);
					$row_q = mysql_fetch_assoc($result_q);
					if($row_q[exist]<=0){
						$sql = 'INSERT INTO fb_account SET person_id="'.$row[person_id].'", fb_user_id="'.$users[data][$i][id].'", fb_name="'.$users[data][$i][name].'";';
						//echo $sql.'<br>||||<br>';
						mysql_query($sql);
					}else{ //If it already exist, it is probably deleted, so undelete it.
						$sql = 'UPDATE fb_account SET fb_status=0 WHERE person_id="'.$row[person_id].'" AND fb_user_id="'.$users[data][$i][id].'";';
						mysql_query($sql);
					}
				}
			} 
			/* If there was no user found, still save to database so it is known that FB has been checked for this user. */
			if($options==0){ // If options is to add users and relations, set status to 1 which means everything was checked
				$sql = 'UPDATE person P SET fb_checked="1" WHERE P.person_id="'.$row[person_id].'";';
			}else if($options==1){ // If option is to add only one user, set status to 2, which means relationship check must still be checked
				$sql = 'UPDATE person P SET fb_checked="2" WHERE P.person_id="'.$row[person_id].'";';
			}
			mysql_query($sql);
		}
	}
	// If there was any match found prior to checking manually, this records must be deleted
	if($options==2){
		$sql='UPDATE matches SET match_fb=null WHERE match_first_id="'.$person1_id.'" AND match_second_id="'.$person2_id.'";';
		mysql_query($sql);
	}
	if($options==0 || $options==2){
		/* Check if facebook match exists */
		/* Go through every user combination */ 
		if($options==0){
			$sql='select distinct M.match_first_id, M.match_second_id FROM matches M, assignment A WHERE NOT (M.match_status=3) AND A.assignment_id=M.assignment_id AND A.project_id="'.$project_id.'" AND M.match_first_sim>="'.$min_similarity.'" AND M.match_first_sim<="'.$max_similarity.'" ORDER BY M.match_first_id;';
		}else if ($options==2){
			$sql='select distinct M.match_first_id, M.match_second_id FROM matches M, assignment A WHERE NOT (M.match_status=3) AND M.match_first_id="'.$person1_id.'" AND M.match_second_id="'.$person2_id.'" ORDER BY M.match_first_id;';
		}
		$result=mysql_query($sql);
		$stop=false;
		while($row = mysql_fetch_assoc($result)){
			$id1=$row[match_first_id];
			$id2=$row[match_second_id];
			/* Go through all fb_accounts for first user */
			if($options==0){
				$sql2='SELECT * FROM fb_account FB WHERE FB.person_id="'.$row[match_first_id].'";';
			}else if ($options==2){ 
				//If this is manual check for relations, first check if there is any confirmed account; if it is ..use this one, or if not use others
				$sql2x='SELECT COUNT(*) as "if_exist" FROM fb_account FB WHERE FB.person_id="'.$row[match_first_id].'" AND fb_status=1 AND NOT fb_status=2;';
				$result2x=mysql_query($sql2x);
				$row2x = mysql_fetch_assoc($result2x);
				if($row2x[if_exist]>0){
					$sql2='SELECT * FROM fb_account FB WHERE FB.person_id="'.$row[match_first_id].'" AND fb_status=1 AND NOT fb_status=2;';
				}else{
					$sql2='SELECT * FROM fb_account FB WHERE FB.person_id="'.$row[match_first_id].'" AND NOT fb_status=2;';
				}
			}
			$result2=mysql_query($sql2);	
			while($row2 = mysql_fetch_assoc($result2)){
				/* Now check if match exist with every fb_account from second user */
				// Do the same for second user
				if($options==0){
					$sql3='SELECT * FROM fb_account FB WHERE FB.person_id="'.$row[match_second_id].'";';
				}else if ($options==2){ 
					//If we are checking manually for relations, first check if there is any confirmed account; if it is ..use this one, or if not use others
					$sql3x='SELECT COUNT(*) as "if_exist" FROM fb_account FB WHERE FB.person_id="'.$row[match_second_id].'" AND fb_status=1 AND NOT fb_status=2;';
					$result3x=mysql_query($sql3x);
					$row3x = mysql_fetch_assoc($result3x);
					if($row3x[if_exist]>0){
						$sql3='SELECT * FROM fb_account FB WHERE FB.person_id="'.$row[match_second_id].'" AND fb_status=1 AND NOT fb_status=2;';
					}else{
						$sql3='SELECT * FROM fb_account FB WHERE FB.person_id="'.$row[match_second_id].'" AND NOT fb_status=2;';
					}
				}
				$result3=mysql_query($sql3);
				while($row3 = mysql_fetch_assoc($result3)){
					$url = "https://graph.facebook.com/".$row3[fb_user_id]."?fields=friends.uid(".$row2[fb_user_id].")&access_token=".$fb_access_token;
					$ret_json = getResults($url);
					$users = json_decode($ret_json, true);
					/*If there is a result, save it to match */
					if($users[friends][data][0][name]!=""){
						$sql4='UPDATE matches SET match_fb="'.$row3[fb_user_id].'&'.$row2[fb_user_id].'" WHERE match_first_id="'.$row[match_first_id].'" AND match_second_id="'.$row[match_second_id].'" AND NOT match_status=3;';
						mysql_query($sql4);
						/* Do not check anymore if we have a match */
						$stop=true;
						break;
					}

				}
				if($stop==true) break;
			}
			if($stop==true) break;
		}
		if($options==2){ //If it is only check for relations, status for both users has to be changed to 1, which means it was already checked for relations 
			// In case of options==0, this is already done in person search
			$sql = 'UPDATE person P SET fb_checked="1" WHERE (P.person_id="'.$id1.'" OR P.person_id="'.$id2.'") ;';
			mysql_query($sql);
		}
	}
	if($options==0){
		echo '<div class="alert alert-success">
		<button type="button" class="close" data-dismiss="alert">x</button>
		Function has successfully checked for Facebook matches.
		</div>';
		echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a></p>';
	}
}

function get_tw_data($consumer_key, $consumer_secret, $access_token, $access_token_secret, $project_id, $social_media_start_position, $social_media_end_position, $options, $person1_id, $person2_id){
	// Set time limit for this function, so script does not stop. It might take up to 10 minutes per assignment to complete.
	set_time_limit(0);
	/* Options settings */
	/*	$options = 0; - check for every user in database and find every relation
	/*  $options = 1; - check only for users
	/*  $options = 2; - check only for relations
	*/
	
	/* Now search if user exist on twitter */
	
	require_once('include/twitteroauth.php'); //Path to twitteroauth library
	 
	$connection = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);

	/* Go through every user in database */
	/* Not every user because there is limit in config file */
	/* First check what similarity has match at position social_media_start_position */
	if($options==0){
		$sql='SELECT * FROM matches M, assignment A WHERE M.assignment_id = A.assignment_id AND A.project_id = '.$project_id.' AND NOT M.match_status=3 ORDER BY M.match_first_sim ASC , M.match_second_sim ASC LIMIT '.$social_media_start_position.' , 1';
		$result=mysql_query($sql);
		$row = mysql_fetch_assoc($result);
		$min_similarity=$row[match_first_sim];
		/* Then check what similarity has match at position social_media_end_position */
		$sql='SELECT * FROM matches M, assignment A WHERE M.assignment_id = A.assignment_id AND A.project_id = '.$project_id.' AND NOT M.match_status=3 ORDER BY M.match_first_sim ASC , M.match_second_sim ASC LIMIT '.($social_media_end_position-1).' , 1';
		$result=mysql_query($sql);
		$row = mysql_fetch_assoc($result);
		$max_similarity=$row[match_first_sim];	
	}
	// Check only one user or all in that project */
	if($options==0 || $options==1){
		if($options==0){
			$sql='SELECT * FROM person P WHERE (P.person_id IN (SELECT match_second_id FROM assignment A, matches M WHERE A.project_id="'.$project_id.'" AND A.assignment_id=M.assignment_id AND M.match_first_sim>="'.$min_similarity.'" AND M.match_first_sim<="'.$max_similarity.'") OR P.person_id IN (SELECT match_first_id FROM assignment A, matches M WHERE A.project_id="'.$project_id.'" AND A.assignment_id=M.assignment_id AND M.match_first_sim>="'.$min_similarity.'" AND M.match_first_sim<="'.$max_similarity.'"));';
		}else if($options==1){
			$sql='SELECT * FROM person P WHERE P.person_id="'.$person1_id.'";';
		}

		$result=mysql_query($sql);	
		while($row = mysql_fetch_assoc($result)){
			$query=$row[person_name].' '.$row[person_surname];
			/* sarch_inverted is used for comparison with fb results; both options are used */
			$query_inverted=strtolower($row[person_surname].' '.$row[person_name]);
			$ret_json = $connection->get('https://api.twitter.com/1/users/search.json?q='.urlencode($query));
				//$output=str_replace(",", ",<br>", json_encode($ret_json));
				//$output=str_replace("}", "}<br><br>", $output);
				//echo $output;
				
			$results = json_decode(json_encode($ret_json), true);
			//print_r($results);
			/* Go through every found match */
			for($i=0; $i<sizeof($results); $i++){
						$search=strtolower($query);
						/* Compare search string with result(low leter) */
						/* Name should differ with max 5 characters */
						//echo '<br>Comparing: "'.$search.'" with "'.$results[$i][name].'".';
						//echo "<br>L1: ".levenshtein($search, strtolower($results[$i][name]));
						//echo "<br>L2: ".levenshtein($search_inverted, strtolower($results[$i][name]))."<br>";
						if((levenshtein($search, strtolower($results[$i][name]))<5) || (levenshtein($search_inverted, strtolower($results[$i][name]))<5)){
							/* First check if this person does not already exist in database */
							$sql_q = 'SELECT COUNT(*) AS "exist" FROM tw_account WHERE tw_user_id="'.$results[$i][id].'";';
							$result_q=mysql_query($sql_q);
							$row_q = mysql_fetch_assoc($result_q);
							if($row_q[exist]<=0){
								$sql = 'INSERT INTO tw_account SET person_id="'.$row[person_id].'", tw_user_id="'.$results[$i][id].'", tw_name="'.$results[$i][name].'", tw_username="'.$results[$i][screen_name].'";';
								mysql_query($sql);
								//echo '<br>'.$results[$i][id].': '.$results[$i][screen_name].' ('.$results[$i][name].')<br>';
								//echo $sql.'<br>||||<br>';
							}else{ //If it already exist, it is probably deleted, so undelete it.
								$sql = 'UPDATE tw_account SET tw_status=0 WHERE person_id="'.$row[person_id].'" AND tw_user_id="'.$results[$i][id].'";';
								mysql_query($sql);
							}
						}
			}
						
			/* If there was no user found, still save to database so it is known that TW has been checked for this user. */
			if($options==0){ // If we are adding users and relations set status to 1 which means everything was checked
				$sql = 'UPDATE person P SET tw_checked="1" WHERE P.person_id="'.$row[person_id].'";';
			}else if($options==1){ // If we are only adding user, set status to 2, which means relationship check must be checked
				$sql = 'UPDATE person P SET tw_checked="2" WHERE P.person_id="'.$row[person_id].'";';
			}
			mysql_query($sql);
		}
	}
	/* Check if twitter match exists */
	// If there was any match found prior to checking manually, this records must be deleted
	if($options==2){
		$sql='UPDATE matches SET match_tw=null WHERE match_first_id="'.$person1_id.'" AND match_second_id="'.$person2_id.'";';
		mysql_query($sql);
	}

	/* Go through every user combination */
	if($options==0 || $options==2){
		if($options==0){
			$sql='select distinct M.match_first_id, M.match_second_id FROM matches M, assignment A WHERE NOT (M.match_status=3) AND A.assignment_id=M.assignment_id AND A.project_id="'.$project_id.'" AND M.match_first_sim>="'.$min_similarity.'" AND M.match_first_sim<="'.$max_similarity.'" ORDER BY M.match_first_id;';
		}else if ($options==2){
			$sql='select distinct M.match_first_id, M.match_second_id FROM matches M, assignment A WHERE NOT (M.match_status=3) AND M.match_first_id="'.$person1_id.'" AND M.match_second_id="'.$person2_id.'" ORDER BY M.match_first_id;';
		}
		$result=mysql_query($sql);
		$stop=false;
		while($row = mysql_fetch_assoc($result)){
			$id1=$row[match_first_id];
			$id2=$row[match_second_id];
			/* Go through all tw_accounts for first user */
			if($options==0){
				$sql2='SELECT * FROM tw_account TW WHERE TW.person_id="'.$row[match_first_id].'";';
			}else if ($options==2){ 
				//If we are checking manually for relations, first check if there is any confirmed account; if it is ..use this one, or if not use others
				$sql2x='SELECT COUNT(*) as "if_exist" FROM tw_account TW WHERE TW.person_id="'.$row[match_first_id].'" AND tw_status=1 AND NOT tw_status=2;';
				$result2x=mysql_query($sql2x);
				$row2x = mysql_fetch_assoc($result2x);
				if($row2x[if_exist]>0){
					$sql2='SELECT * FROM tw_account TW WHERE TW.person_id="'.$row[match_first_id].'" AND tw_status=1 AND NOT tw_status=2;';
				}else{
					$sql2='SELECT * FROM tw_account TW WHERE TW.person_id="'.$row[match_first_id].'" AND NOT tw_status=2;';
				}
			}
			$result2=mysql_query($sql2);	
			while($row2 = mysql_fetch_assoc($result2)){
				/* Now check if match exist with every tw_account from second user */
				if($options==0){
					$sql3='SELECT * FROM tw_account TW WHERE TW.person_id="'.$row[match_second_id].'";';
				}else if ($options==2){ 
					//If we are checking manually for relations, first check if there is any confirmed account; if it is ..use this one, or if not use others
					$sql3x='SELECT COUNT(*) as "if_exist" FROM tw_account TW WHERE TW.person_id="'.$row[match_second_id].'" AND tw_status=1 AND NOT tw_status=2;';
					$result3x=mysql_query($sql3x);
					$row3x = mysql_fetch_assoc($result3x);
					if($row3x[if_exist]>0){
						$sql3='SELECT * FROM tw_account TW WHERE TW.person_id="'.$row[match_second_id].'" AND tw_status=1 AND NOT tw_status=2;';
					}else{
						$sql3='SELECT * FROM tw_account TW WHERE TW.person_id="'.$row[match_second_id].'" AND NOT tw_status=2;';
					}
				}
				$result3=mysql_query($sql3);
				while($row3 = mysql_fetch_assoc($result3)){
					$source_id=$row2[tw_user_id];
					$target_id=$row3[tw_user_id];
					$ret_json2= $connection->get('https://api.twitter.com/1/friendships/show.json?source_id='.$source_id.'&target_id='.$target_id);
					$friendship_status = json_decode(json_encode($ret_json2), true);
						//$output=str_replace(",", ",<br>", json_encode($ret_json2));
						//$output=str_replace("}", "}<br><br>", $output);
						//$output=str_replace("{", "{<br><br>", $output);
						//print_r($friendship_status);
						//echo $output;
					/* If at least one user is following other */
					if($friendship_status[relationship][target][followed_by]==1 || $friendship_status[relationship][source][followed_by]==1){
						/* Save direction:
							$rel_status=1: User 1 follows User 2
							$rel_status=2: User 2 follows User 1
							$rel_status=3: User 1 follows User 2 and User 2 follows User 1 */
						if($friendship_status[relationship][target][followed_by]==1 && $friendship_status[relationship][source][followed_by]==""){
							$rel_status=1;
						}else if($friendship_status[relationship][target][followed_by]=="" && $friendship_status[relationship][source][followed_by]==1){
							$rel_status=2;
						}else if($friendship_status[relationship][target][followed_by]==1 && $friendship_status[relationship][source][followed_by]==1){
							$rel_status=3;
						}
						$sql4='UPDATE matches SET match_tw="'.$source_id.'&'.$target_id.'&rel='.$rel_status.'" WHERE match_first_id="'.$row[match_first_id].'" AND match_second_id="'.$row[match_second_id].'" AND NOT match_status=3;';
						//echo $sql4;
						mysql_query($sql4);
						$stop=true;
						break;
					}
				}
				if($stop==true) break;
			}
			if($stop==true) break;
		}
		if($options==2){ //If we are only checking for relations, status for both users has to be changed to 1, which means it was already checked for relations 
			// In case of options==0, this is already done in person search
			$sql = 'UPDATE person P SET tw_checked="1" WHERE (P.person_id="'.$id1.'" OR P.person_id="'.$id2.'") ;';
			mysql_query($sql);
		}
	}
	if($options==0){
		echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert">x</button>
			Function has successfully checked for Twitter matches.
			</div>';
		echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a></p>';
	}
}

// Function gets google data for matches and saves it to database
function get_google_data($project_id, $keywords, $social_media_start_position, $social_media_end_position, $options, $person1_id, $person2_id){
	// Set time limit for this function, so script does not stop.
	set_time_limit(0);
	
	/* Options settings */
	/*	$options = 0; - check for every user in database and find every relation
	/*  $options = 1; - check only for givne two users in $person1_id, and $person2_id
	*/
	
	
	/* Go through every user in database */
	/* Not every user because there is limit in config file */
	/* First check what similarity has match at position social_media_start_position */
	if($options==0){
		$sql='SELECT * FROM matches M, assignment A WHERE M.assignment_id = A.assignment_id AND A.project_id = '.$project_id.' AND NOT M.match_status=3 ORDER BY M.match_first_sim ASC , M.match_second_sim ASC LIMIT '.$social_media_start_position.' , 1';
		echo $sql;
		$result=mysql_query($sql);
		$row = mysql_fetch_assoc($result);
		$min_similarity=$row[match_first_sim];
		/* Then check what similarity has match at position social_media_end_position */
		$sql='SELECT * FROM matches M, assignment A WHERE M.assignment_id = A.assignment_id AND A.project_id = '.$project_id.' AND NOT M.match_status=3 ORDER BY M.match_first_sim ASC , M.match_second_sim ASC LIMIT '.($social_media_end_position-1).' , 1';
		$result=mysql_query($sql);
		$row = mysql_fetch_assoc($result);
		$max_similarity=$row[match_first_sim];
	}

	if($options==0){
		$sql='SELECT M.match_id, M.match_first_id, M.match_second_id, group_concat(`person_name` separator \',\') as \'person_name\', group_concat(`person_surname` separator \',\') as \'person_surname\' FROM matches M, person P, assignment A WHERE M.assignment_id=A.assignment_id AND A.project_id='.$project_id.' AND (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND NOT M.match_status=3 AND M.match_first_sim>="'.$min_similarity.'" AND M.match_first_sim<="'.$max_similarity.'" GROUP BY M.match_id;';
	}else if($options==1){
		$sql='SELECT M.match_id, M.match_first_id, M.match_second_id, group_concat(`person_name` separator \',\') as \'person_name\', group_concat(`person_surname` separator \',\') as \'person_surname\' FROM matches M, person P WHERE (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND NOT M.match_status=3 AND M.match_first_id="'.$person1_id.'" AND M.match_second_id="'.$person2_id.'" GROUP BY M.match_id;';
	}
	
	$result=mysql_query($sql);
	while($row = mysql_fetch_assoc($result)){
		/* Check all four combinations:
			Name1 Surname1 vs. Name2 Surname2
			Surname1 Name 1 vs. Surname2 Name2
			Name1 Surname1 vs. Surname2 Name2
			Surname2 Name2 vs. Name2 Surname2
			+add keywords at the end;
		*/
		$first_name=strtolower(explode(",", $row[person_name])[0].' '.explode(",", $row[person_surname])[0]);
		$first_name_inverted=strtolower(explode(",", $row[person_surname])[0].' '.explode(",", $row[person_name])[0]);
		$second_name=strtolower(explode(",", $row[person_name])[1].' '.explode(",", $row[person_surname])[1]);
		$second_name_inverted=strtolower(explode(",", $row[person_surname])[1].' '.explode(",", $row[person_name])[1]);
			
		$no_matches = find_google_match($first_name, $second_name, $keywords);
		$no_matches+= find_google_match($first_name_inverted, $second_name_inverted, $keywords)[0];
		$no_matches+= find_google_match($first_name, $second_name_inverted, $keywords)[0];
		$no_matches+= find_google_match($first_name_inverted, $second_name, $keywords)[0];
		
		$update='UPDATE matches M SET M.match_google="'.$no_matches.'" WHERE M.match_first_id="'.$row[match_first_id].'" AND M.match_second_id="'.$row[match_second_id].'" AND NOT M.match_status=3;';
		//echo $update;
		mysql_query($update);
		// wait for 0.1 seconds
		usleep(100000);
	}
	if($options==0){
		echo '<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert">x</button>
			Function has successfully checked for google matches.
			</div>';
			echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a></p>';
	}
}
			
								
// Function to retrieve google results based on input parameters
	/* input: String, String
	   output: number of results
	   
	   example input: $first_name="Jack Smith", $second_name="John Doe";
	   example output: $result[0]=449000, $result[1]='http://www.google.com/search?q="Jack+Smith"+"John+Doe"&btnG=Search&meta=';
	   
	*/
function find_google_match($first_name, $second_name, $keywords){
	$curl = curl_init();
	/* Spaces in search query msut be changed with symbol "+" */
	$first_name=str_replace(" ", "+", $first_name);
	$second_name=str_replace(" ", "+", $second_name);
	
	/* Search string is searching for litteral name and surname */
	$search_string='"'.$first_name.'"+"'.$second_name.'"';
	/* Add keywords to search string */
	for($i=0; $i<count($keywords); $i++){
		$search_string.='+"'.$keywords[$i].'"';
	}

	$referer = $_SERVER['SERVER_NAME'];
	
	/* Retreive html containing number of results */
	//$url = "http://www.google.com/search?q=".$search_string."&btnG=Search&meta=";
	
	$url = 'https://ajax.googleapis.com/ajax/services/search/web?v=1.0&q='.$search_string.'&userip='.$_SERVER['REMOTE_ADDR'].'';

	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_REFERER, $referer);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$result_html=curl_exec ($curl);

	$result_json = json_decode($result_html, true);
	
	return $result_json[responseData][cursor][estimatedResultCount];
}

?>

			<div>
				<ul class="breadcrumb">
					<li>
						<a class="ajax-link" href="index.php">Home</a> <span class="divider">/</span>
					</li>
					<li>
					<?if(!file_exists("include/config.php")) { echo '<a class="ajax-link" href="install.php">Installation</a>'; } else { echo '<a class="ajax-link" href="projects.php">Projects</a>'; } ?>
					</li>
				</ul>
			</div>
					

						<?
						if(file_exists("include/config.php")) { //Check if config file exists
							include("include/config.php");
							/* Connect to Database */
							$mysql_connect = mysql_connect($db_host, $db_user, $db_pass);
							$mysql_select_db = mysql_select_db($db_name); 
							/* Check if we are adding new project by POST */
							if(isset($_POST['project_name'])){
								echo '<div class="row-fluid">
									<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-eye-open"></i> Projects</h2>
										</div>
										<div class="box-content">';
								echo '<h1>Project <small>overview</small></h1><br>';
								/* Check for wrong input in post */
								$project_name=mysql_real_escape_string($_POST['project_name']);
								$no_assignments=mysql_real_escape_string($_POST['no_assignments']);	
								/* Check if project has name */
								if($project_name==""){
									echo'<div class="alert alert-error">
							<button type="button" class="close" data-dismiss="alert">x</button>
							Please use at least one character for project name.</div>';
									die('<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>');	
								}
								/* If there are zero assignments, choose default 1 */
								if($no_assignments==""){
									$no_assignments=1;
								/* Check if number of assignments is numeric */
								}else if(!is_numeric($no_assignments)){
									echo'<div class="alert alert-error">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Please use only numbers for number of assignments.</div>';
									die('<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a></p>');	
								/* Check if number of assignments is set to zero, then add at one assignment */
								}else if($no_assignments==0){
									$no_assignments=1;
								/* Check if number of assignments is lower than zero, then use positive number; also if it is more than 50, use 50*/
								}else if($no_assignments<0){
									$no_assignments=$no_assignments*(-1);
									if($no_assignments>50){
										$no_assignments=50;
									}
								}else if($no_assignments>50){
									$no_assignments=50;
								}
								/* Insert new data into database */
								if($no_assignments==1){
									echo '<p>Creating project with name "'.$project_name.'" and with '.$no_assignments.' assignment';
								}else{
									echo '<p>Creating project with name "'.$project_name.'" and with '.$no_assignments.' assignments';
								}
								/* project_status = 1 ; enabled
								   project_status = 2 ; deleted */
								$sql='INSERT INTO project SET project_name="'.$project_name.'", project_status=1;';
								if(mysql_query($sql)){ /* If insert was OK */
									$project_id=mysql_insert_id(); /* Get ID of the project, so we can later assign assignments to it */
									/* We create folder for this project */
									mkdir(dirname( __FILE__ ).'/projects/'.$project_id);
									$no_error=true;
									/* Now we need to put assignments in database for this project */
									for ($i = 1; $i <= $no_assignments; $i++) {
										/*
											assignment_status=0 - just added and ready to use
											assignment_status=1 - moss checked already
											assignment_status=2 - disabled (deleted/removed)
										*/
										$sql='INSERT INTO assignment SET project_id="'.$project_id.'", assignment_name="'.$i.'", assignment_status=0;';
										if(!mysql_query($sql)){ /* If there is any error, we should report it */
											$nor_error=false;
										}
										$assignment_id=mysql_insert_id();
										/* We also create folder for this assignment for uploaded assignments and MOSS results */
										mkdir(dirname( __FILE__ ).'/projects/'.$project_id.'/'.$assignment_id);
										mkdir(dirname( __FILE__ ).'/projects/'.$project_id.'/'.$assignment_id.'/submissions');
										mkdir(dirname( __FILE__ ).'/projects/'.$project_id.'/'.$assignment_id.'/moss');
									}
									if($no_error=true){
										echo ' <span class="label label-success">OK</span></p>';
										echo '<a href="projects.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i> Continue</a>';	
									}else{
										echo '<span class="label label-warning">FAILED at creating assignments</span>';
										echo '<div class="alert alert-error">
											<button type="button" class="close" data-dismiss="alert">x</button>
											'.mysql_error().'</div></p>';
									}
								}else{ /* If insert was not OK, warn user */
									echo '<span class="label label-warning">FAILED at creating project</span>';
									echo '<div class="alert alert-error">
										<button type="button" class="close" data-dismiss="alert">x</button>
										'.mysql_error().'</div></p>';
								}
							/* If we did not send POST for adding new project, check if we are about to add new project or just reviewing them */
							}else if(isset($_GET['add_project'])){
								echo '<div class="row-fluid">
									<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-eye-open"></i> Projects</h2>
										</div>
										<div class="box-content">';

								echo '<form id="add_project" class="form-horizontal" name="form" action="projects.php" method="POST">
								<fieldset>
									<legend>Create new Project:</legend>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Project name: </label>
										<div class="controls">
										  <input class="input-xlarge focused" id="focusedInput" type="text" name="project_name"> <i>(Name of class, eg. Programming 2012/2013)</i>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Number of assignments: </label>
										<div class="controls">
										  <input class="input-xlarge focused" id="focusedInput" type="text" name="no_assignments"> <i>(You can add new assingments later)</i>
										</div>
									</div>
									<div class="form-actions">
										<button type="submit" class="btn btn-primary">Save changes</button>
										<button class="btn">Cancel</button>
									</div>
								</fieldset>
								</form>';
							/* If we are viewing specific project */
							}else if(isset($_GET['view'])){
								echo '<div class="row-fluid">
									<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-eye-open"></i> Projects</h2>
										</div>
										<div class="box-content">';

								$project_id=mysql_real_escape_string($_GET['view']);
								$result=mysql_query('SELECT * FROM project WHERE project_id="'.$project_id.'" AND NOT project_status=2;');
								if(mysql_num_rows($result)!=0){
									$row = mysql_fetch_assoc($result);
									echo '<h3>Project '.$row[project_name].'</h3><br>';
									/* This is how we define table, where assignments will be shown */
									/* We do this for every assignment in database*/
									$result=mysql_query('SELECT * FROM assignment WHERE project_id="'.$project_id.'" AND NOT assignment_status=2');
									if(mysql_num_rows($result)!=0){
										echo '<div class="box-content">
												<table class="table table-striped table-bordered bootstrap-datatable datatable">
													<thead>
														<tr>
														  <th style="text-align: center;">Assignment name</th>
														  <!--<th style="text-align: center;">Assignment begin date</th>-->
														  <!--<th style="text-align: center;">Assignment end date</th>-->
														  <th style="text-align: center;">Status</th>
														  <th style="text-align: center;">Number of files</th>
														  <th style="text-align: center;">Upload files</th>
														  <th>Actions</th>
													  </tr>
													</thead>   
													<tbody>';
									}else{
										echo '<div class="alert alert-info">
												<button type="button" class="close" data-dismiss="alert">x</button>
												There are no assignments in this project.
											</div>
											<a href="projects.php?create_as='.$project_id.'" title="Creates new assignment" data-rel="tooltip" class="btn btn-warning">Create new assignment</a>';
										$no_assignments=true;
									}
									if($no_assignments!=true){
										/* $no_showing_assignments is used to know which assignment is; first, second,.. */
										$no_showing_assignments=0;
										while($row = mysql_fetch_assoc($result)){
											/* Every assignment we add one number to $no_showing_assignments */
											$no_showing_assignments++;
											/* We show every assignment in table */
											$assignment_id=$row[assignment_id];
											echo '<tr><td style="text-align: center;"><a class="ajax-link" href="projects.php?view_as='.$assignment_id.'">Assignment '.$row[assignment_name].'</a></td>';
											//echo '<td style="text-align: center;">'.$row[assignment_begin_date].'</td>';
											//echo '<td style="text-align: center;">'.$row[assignment_end_date].'</td>';
											if($row[assignment_status]==0){
												echo '<td style="text-align: center;"><span class="label label-warning">MOSS NOT checked</span> </td>';
											}else{
												echo '<td style="text-align: center;"><span class="label label-success">MOSS checked</span></td>';
											}
											
											/* Check how many asignments are in assignment folder */
											$dir="projects/".$project_id."/".$assignment_id."/submissions/";
											/* We assign save locatio	n to $save_files_location[$] - attribute where we save location for each assignment */
											$save_files_location[$no_showing_assignments]="projects/".$project_id."/".$assignment_id."/submissions/";
											if (glob($dir."*") != false){
												$filecount = count(glob($dir."*"));
												/* We use that $no_showing_assignments to add unique name for file upload, so we can define it in script at the end (footer.php) */
												echo '<td><h3 style="text-align: center;">'.$filecount.'</h3></td>';
											}else{
												echo '<td><h3 style="text-align: center;">0</h3></td>';
											}
											echo '<td><input data-no-uniform="true" type="file" name="file_upload'.$no_showing_assignments.'" id="file_upload'.$no_showing_assignments.'"	/></td>';
											echo '<td class="center">
													<a class="btn btn-success" href="projects.php?view_as='.$assignment_id.'">
														<i class="icon-zoom-in icon-white"></i>  
														View                                            
													</a>
													<a class="btn btn-info" href="projects.php?delete_asf='.$assignment_id.'">
														<i class="icon-trash icon-white"></i> 
														Delete All Files
													</a>
													<a class="btn btn-danger" href="projects.php?delete_as='.$assignment_id.'">
														<i class="icon-trash icon-white"></i> 
														Delete Assignment
													</a>
												</td></tr>';
										}
										/* End of table */
										echo '</tbody></table></div>';
										/* Button to create new assignment */
										echo '<a href="projects.php?create_as='.$project_id.'" title="Creates new assignment" data-rel="tooltip" class="btn btn-warning">Create new assignment</a>';
										
										/* Now we offer to start MOSS check over uploaded files */
										$result=mysql_query('SELECT * FROM assignment WHERE project_id="'.$project_id.'" AND assignment_status=0');
										if(mysql_num_rows($result)!=0){

													//mysql_data_seek($result, 0);
													$first=0;
													$no_showing_assignments_moss=0;
													while($row = mysql_fetch_assoc($result)){
														$no_showing_assignments_moss++;
														$dir="projects/".$row[project_id]."/".$row[assignment_id]."/submissions/";
														/* There should be at least one file present to start MOSS */
														if (glob($dir."*") != false){
															if($first==0){
																echo '
																<form class="form-horizontal" id="moss_check" name="form" action="projects.php" method="POST">
																<fieldset>
																	<legend>MOSS assignment check</legend>
																	<div class="control-group">
																		<label class="control-label">Select assignments:</label>
																		<div class="controls">
																		';
																$first++;
															}
															$filecount = count(glob($dir."*"));
															if($filecount>0){
																$moss_button=true;
																echo'
																<label class="checkbox inline">
																	<input type="checkbox" checked="" id="inlineCheckbox1" name="assignment_ids['.$no_showing_assignments_moss.']" value="'.$row[assignment_id].'"> '.$row[assignment_name].'
																</label>';
															}
														}
													}
													if($moss_button==true){
															echo '
																</div>
															</div>
															<div class="form-actions">
																<button type="submit" class="btn btn-primary">Start MOSS check</button> <i>*This might take a while.</i>
															</div>
														</fieldset>
														</form>';
													}
													
										/* If MOSS was already checked, offer FB and TW check buttons */
										}else{
											/* Button to start FB and TW Check */
											if($fb_access_token!=""){
												echo '<a href="projects.php?fb_check='.$project_id.'" title="Starts FB Check" data-rel="tooltip" class="ajax-link btn btn-warning">Start FB Check*</a>';
											}if($tw_access_token_secret!=""){
												echo '<a href="projects.php?tw_check='.$project_id.'" title="Starts TW Check" data-rel="tooltip" class="ajax-link btn btn-warning">Start TW Check*</a>';										
											}
											/* Button to start Google Check */
												echo '<a href="projects.php?google_check='.$project_id.'" title="Starts Google Search Check" data-rel="tooltip" class="ajax-link btn btn-warning">Start Google Search Check*</a>';										
											echo '<br><i>*This might take a while (~10minutes)</i>';
										}
									}
								}else{
									echo '<div class="alert alert-info ">
										<button type="button" class="close" data-dismiss="alert">x</button>
										<h4 class="alert-heading">Warning!</h4>
										Project does not exist.</div><a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a></p>';
								}
							/* If we are starting moss check procedure */
							}else if(isset($_POST['assignment_ids'])){
								echo '<div class="row-fluid">
									<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-eye-open"></i> Projects</h2>
										</div>
										<div class="box-content">';

								/* Start MOSS Procedure */
								echo '<h3>MOSS assignment check</h3><br>';
								echo '<h4>Starting MOSS Check</h4><br>';
								/* We are not using count in $_POST['assignment_id'], because we can choose less than all assignments */
								$moss_url = array();
								for($i=1; $i<=end(array_keys($_POST['assignment_ids'])); $i++){
									/*First we must check if current id is not empty - if we did not choose it */
									if($_POST['assignment_ids'][$i]!=""){
										$assignment_id=mysql_real_escape_string($_POST['assignment_ids'][$i]);
										$result=mysql_query('SELECT * FROM assignment WHERE assignment_id="'.$assignment_id.'"');
										$row = mysql_fetch_assoc($result);
										$project_id=$row[project_id];
										echo '<h5>Assignment '.$row[assignment_name].'</h5> ';
										/* Download MOSS Files */
											$return = exec('./mossnet projects/'.$project_id.'/'.$assignment_id.'/submissions/*');
										echo '<span class="label label-success">MOSS OK</span> ';
										/* Get moss id from url */
											$tokens = explode('/', $return);
											$moss_id = $tokens[sizeof($tokens)-1];
											$moss_ids[$i]=$moss_id;
											exec('cd projects/'.$project_id.'/'.$assignment_id.'/moss/ && wget --no-parent -r '.$return.'/');
											
										/* When we download MOSS Files we need to change index file so it won't include their original links inside but new ones */
										foreach (glob('projects/'.$project_id.'/'.$assignment_id.'/moss/moss.stanford.edu/results/'.$moss_id.'/index.html') as $filename){
											$file = file_get_contents($filename);
											file_put_contents($filename, str_replace('http://moss.stanford.edu/results/'.$moss_id.'/', '', $file));
										}
										echo '<span class="label label-success">DOWNLOAD OK</span><br><br><br>';
									}
								}
								/* Parse MOSS Files and save them to database */
								echo '<h4>Parsing MOSS files and saving to database</h4><br>';
								parse_moss($_POST['assignment_ids'],$moss_ids);

								echo ' <a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								
							/* If we are deleting files from assignment */
							}else if(isset($_GET['delete_asf'])){
								echo '<div class="row-fluid">
									<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-eye-open"></i> Projects</h2>
										</div>
										<div class="box-content">';

								$assignment_id=mysql_real_escape_string($_GET['delete_asf']);
								$result=mysql_query('SELECT * FROM assignment WHERE assignment_id="'.$assignment_id.'"');
								$row = mysql_fetch_assoc($result);
								$project_id=$row['project_id'];
								exec('rm -r projects/'.$project_id.'/'.$assignment_id.'/submissions/');
								mkdir(dirname( __FILE__ ).'/projects/'.$project_id.'/'.$assignment_id.'/submissions');

								echo '<div class="alert alert-success">
								<button type="button" class="close" data-dismiss="alert">x</button>
								Files were successfully deleted.
								</div>';
								echo '<script type="text/javascript" language="JavaScript">javascript:history.back() </script>';
							/* If we are deleting assignment - meaning we are actually disabling it*/
							}else if(isset($_GET['delete_as'])){
								echo '<div class="row-fluid">
									<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-eye-open"></i> Projects</h2>
										</div>
										<div class="box-content">';

								$assignment_id=mysql_real_escape_string($_GET['delete_as']);
								/* Disable all matches included in selected assignment to be deleted */
								$sql='UPDATE matches M SET M.match_status=3 WHERE M.assignment_id='.$assignment_id.';';
								mysql_query($sql);
								/* Disable selected assignment */
								$sql='UPDATE assignment A SET A.assignment_status=2 WHERE A.assignment_id='.$assignment_id.';';
								mysql_query($sql);
								echo '<div class="alert alert-success">
								<button type="button" class="close" data-dismiss="alert">x</button>
								Assignment was successfully deleted.
								</div>';
								echo '<script type="text/javascript" language="JavaScript">javascript:history.back() </script>';
							/* If we are deleting project - meaning we are actually disabling it and all corresponding assignments and matches*/
							}else if(isset($_GET['delete'])){
								echo '<div class="row-fluid">
									<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-eye-open"></i> Projects</h2>
										</div>
										<div class="box-content">';

								$project_id=mysql_real_escape_string($_GET['delete']);
								/* Disable selected project */
								$sql='UPDATE project PR SET PR.project_status=2 WHERE PR.project_id='.$project_id.';';
								mysql_query($sql);	
								/* Go through every assignment in that project */
								$sql='SELECT * FROM assignment A WHERE A.project_id="'.$project_id.'";';
								$result=mysql_query($sql);
								while($row = mysql_fetch_assoc($result)){
									$assignment_id=$row[assignment_id];
									/* Disable all matches included in selected assignment to be deleted */
									$sql='UPDATE matches M SET M.match_status=3 WHERE M.assignment_id='.$assignment_id.';';
									mysql_query($sql);
									/* Disable selected assignment */
									$sql='UPDATE assignment A SET A.assignment_status=2 WHERE A.assignment_id='.$assignment_id.';';
									mysql_query($sql);
									echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Project was successfully deleted.
									</div>';
									echo '<script type="text/javascript" language="JavaScript">javascript:history.back() </script>';
								}
							/* If we are viewing specific assignment */
							}else if(isset($_GET['view_as'])){
								$assignment_id=mysql_real_escape_string($_GET['view_as']);
								$result=mysql_query('SELECT * FROM assignment WHERE assignment_id="'.$assignment_id.'"');
								if(mysql_num_rows($result)!=0){
									$row = mysql_fetch_assoc($result);
									$assignment_name = $row[assignment_name];
								}
								/* Show assignment info and statistics */
								echo '<div class="row-fluid sortable"><div class="box span12">
								<div class="box-header well" data-original-title>
									<h2><i class="icon-info-sign"></i> Assignment '.$assignment_name.'</h2>
								</div>
								<div class="box-content">
									<div class="box-content">
										<ul class="dashboard-list">
											<li>';
								$sql='SELECT COUNT(*) AS "no_of_matches", A.assignment_status, AVG(M.match_first_sim) AS "AvgFirstSim", AVG(M.match_second_sim) AS "AvgSecondSim", MIN(M.match_first_sim) AS "MinFirstSim", MIN(M.match_second_sim) AS "MinSecondSim", MAX(M.match_first_sim) AS "MaxFirstSim", MAX(M.match_second_sim) AS "MaxSecondSim", AVG(M.match_first_sim) AS "FirstSim", AVG(M.match_second_sim) AS "SecondSim", MAX(M.match_lines) AS "MaxLines", AVG(M.match_lines) AS "AvgLines" FROM matches M, assignment A WHERE A.assignment_id=M.assignment_id AND M.assignment_id="'.$assignment_id.'" AND NOT M.match_status=3';
								$result=mysql_query($sql);
								$row = mysql_fetch_assoc($result);
								if($row[no_of_matches]>0){
									echo '<h2> Statistics of selected assignment</h2><br>';
									echo '<b>Number of matches: <i><font color="blue">'.$row[no_of_matches].'</font></i></b><br><br>';
									echo '<b>Average similar lines: <i><font color="blue">'.$row[AvgLines].'</font></i></b><br>';
									echo '<b>Max similar lines: <i><font color="blue">'.$row[MaxLines].'</font></i></b><br><br>';
									echo '<b>Average similarity: <i><font color="blue">'.(($row[AvgFirstSim]+$row[AvgSecondSim])/2).' %</font></i></b><br>';
									echo '<b>Max similarity: <i><font color="blue">'.(($row[MaxFirstSim]+$row[MaxSecondSim])/2).' %</font></i></b><br>';
									echo '<b>Min similarity: <i><font color="blue">'.(($row[MinFirstSim]+$row[MinSecondSim])/2).' %</font></i></b><br><br>';
									$sql='SELECT COUNT(*) AS "no_of_non_checked_matches" FROM matches M WHERE M.assignment_id="'.$assignment_id.'" AND M.match_status=0;';
									$result=mysql_query($sql);
									$row = mysql_fetch_assoc($result);
									if($row[no_of_non_checked_matches]>0){
										echo '<b>Number of non-checked matches: <i><font color="red">'.$row[no_of_non_checked_matches].'</font></i></b><br>';
										echo '<br><a class="ajax-link btn btn-danger" href="visualisation.php?reject_ncma='.$assignment_id.'">
													<i class="icon-trash icon-white"></i> 
													Reject All Non-Checked Matches
												</a>';
									}else{
										echo '<b>Number of non-checked matches: <i><font color="green">'.$row[no_of_non_checked_matches].'</font></i></b><br>';
									}
										echo '</li>
													
												</ul>
											</div>
										</div>
									</div></div><!--/span-->';
								}else{
									echo '<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
										Assignment data does not exist.
									</div>';
									echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a></p>';
								}
		
								/* Show matches list */
								$sql='SELECT M.match_id, M.match_first_id, M.match_second_id, M.match_url, M.match_lines, A.project_id, A.assignment_id, group_concat(`person_name` separator \',\') as \'person_name\', group_concat(`person_surname` separator \',\') as \'person_surname\', M.match_lines, M.match_status, M.match_first_sim, M.match_second_sim FROM matches M, person P, assignment A WHERE M.assignment_id=A.assignment_id AND M.assignment_id='.$assignment_id.' AND (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND NOT M.match_status=3 GROUP BY M.match_id ORDER BY (M.match_first_sim+M.match_second_sim)/2 DESC;';
								$result=mysql_query($sql);
								/* Show only if there are any results */
							if(mysql_num_rows($result)>0){		
								echo '<div class="row-fluid sortable"><div class="box span12">
								<div class="box-header well" data-original-title>
									<h2><i class="icon-user"></i> Matches</h2>
								</div>
								<div class="box-content">
									<div class="box-content">
										<ul class="dashboard-list">
											<li>';
										echo '<div class="box-content">
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
										/* End of table */
										echo '</tbody></table></div>';
								echo '</li>
										</ul></div></div></div>';
							



							/* Show person info */
								echo '<div class="row-fluid sortable"><div class="box span12">
								<div class="box-header well" data-original-title>
									<h2><i class="icon-user"></i> Person list</h2>
								</div>
								<div class="box-content">
									<div class="box-content">
										<ul class="dashboard-list">
											<li>';
										echo '<div class="box-content">
											<table class="table table-striped table-bordered bootstrap-datatable datatable">
												<thead>
													<tr>
													  <th>Person</th>
													  <th>ID</th>
													  <th>Actions</th>
												  </tr>
												</thead>   
												<tbody>';
										$sql='SELECT distinct P.person_id, P.person_name, P.person_surname, P.person_ident FROM person P, matches M WHERE (P.person_id=M.match_first_id OR P.person_id=M.match_second_id) AND NOT (M.match_status=3) AND M.assignment_id="'.$assignment_id.'" ORDER BY P.person_name;';
										$result=mysql_query($sql);
										while($row = mysql_fetch_assoc($result)){
											echo '<tr><td><a class="ajax-link" href="visualisation.php?view_p='.$row[person_id].'">'.$row[person_name].' '.$row[person_surname].'</a></td>';
											echo '<td>'.$row[person_ident].'</td>';
											echo '<td class="center">
											<a class="ajax-link btn btn-success" href="visualisation.php?view_p='.$row[person_id].'">
												<i class="icon-zoom-in icon-white"></i>  
												View                                            
											</a>
											</td></tr>';
										}
										/* End of table */
										echo '</tbody></table></div>';
								echo '				</li>
											
										</ul>
									</div>
								</div>
							</div></div><!--/span-->';
							}	
								//echo ' <a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
							/* Creating new assignment */
							}else if(isset($_GET['create_as'])){
								echo '<div class="row-fluid">
									<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-eye-open"></i> Projects</h2>
										</div>
										<div class="box-content">';

								$project_id=mysql_real_escape_string($_GET['create_as']);
								$result=mysql_query('SELECT * FROM assignment WHERE project_id="'.$project_id.'"');
								$i=mysql_num_rows($result)+1;
								$sql='INSERT INTO assignment SET project_id="'.$project_id.'", assignment_name="'.$i.'", assignment_status=0;';
								if(!mysql_query($sql)){ /* If there is any error, we should report it */
									echo 'Creating new assignment ... <span class="label label-error">FAILED</span></p>';
									echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i> Back</a>';	
								}else{
									$assignment_id=mysql_insert_id();
									/* We also create folder for this assignment for uploaded assignments and MOSS results */
									mkdir(dirname( __FILE__ ).'/projects/'.$project_id.'/'.$assignment_id);
									mkdir(dirname( __FILE__ ).'/projects/'.$project_id.'/'.$assignment_id.'/submissions');
									mkdir(dirname( __FILE__ ).'/projects/'.$project_id.'/'.$assignment_id.'/moss');
									
									echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Assignment was successfully added.
									</div>';
									echo '<script type="text/javascript" language="JavaScript">javascript:history.back() </script>';
									//echo 'Creating new assignment ... <span class="label label-success">OK</span></p>';
									//echo '<a href="projects.php?view='.$project_id.'" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i> Continue</a>';	
								}	
							/* When you upload new files, assignment status must be changed to 0, so you know you have to rerun MOSS*/
							}else if(isset($_POST['upload_update'])){
								$assignment_id=mysql_real_escape_string($_POST['upload_update']);			
								$sql='UPDATE assignment A SET A.assignment_status=0 WHERE A.assignment_id='.$assignment_id.';';
								mysql_query($sql);
							// Function to start Facebook check
							}else if(isset($_GET['fb_check'])){
								$project_id=mysql_real_escape_string($_GET['fb_check']);			
								get_fb_data($fb_access_token, $project_id, $social_media_start_position, $social_media_end_position, 0, 0, 0); // First 0 means check all users and relations in that project with condition of start and end position, second and third zeros are not needed in this case
							// Function to start Twitter check
							}else if(isset($_GET['tw_check'])){
								$project_id=mysql_real_escape_string($_GET['tw_check']);			
								get_tw_data($tw_consumer_key, $tw_consumer_secret, $tw_access_token, $tw_access_token_secret, $project_id, $social_media_start_position, $social_media_end_position, 0, 0, 0); // 0 means check all users and relations in that project with condition of start and end position, second and third zeros are not needed in this case
							// Function to start Google check
							}else if(isset($_GET['google_check'])){
								$project_id=mysql_real_escape_string($_GET['google_check']);			
								get_google_data($project_id, $google_search_keywords, $social_media_start_position, $social_media_end_position, 0, 0, 0);
								
							/* If selected site is to get accounts on Facebook based on person name */	
							}else if(isset($_GET[getFacebookAccounts])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Person</h2>
										</div>
									<div class="box-content">';
								$user_id=mysql_real_escape_string($_GET[getFacebookAccounts]);

								get_fb_data($fb_access_token, 0, 0, 0, 1, $user_id, 0);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Application has successfully searched for Facebook accounts for selected user.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								echo '</div></div>';
								
							/* If selected site is to get accounts on Twitter based on person name */	
							}else if(isset($_GET[getTwitterAccounts])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Person</h2>
										</div>
									<div class="box-content">';
								$user_id=mysql_real_escape_string($_GET[getTwitterAccounts]);
								get_tw_data($tw_consumer_key, $tw_consumer_secret, $tw_access_token, $tw_access_token_secret, 0, 0, 0, 1, $user_id, 0);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Application has successfully searched for Facebook accounts for selected user.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';									
								echo '</div></div>';
								
							/* If selected site is to check for matches on Facebook (only selected match) */	
							}else if(isset($_GET[getFacebookMatches])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Match</h2>
										</div>
									<div class="box-content">';
								// First check if there is any account; if there isn't, then start getFacebookAccounts()
								$user_id1=mysql_real_escape_string($_GET[user1]);
								$user_id2=mysql_real_escape_string($_GET[user2]);
								// First check for first user
									$sql = 'SELECT COUNT(*) as "fb_count" FROM fb_account WHERE person_id="'.$user_id1.'";';
									$result=mysql_query($sql);
									$row = mysql_fetch_assoc($result);
									if ($row[fb_count]==0){
										get_fb_data($fb_access_token, 0, 0, 0, 1, $user_id1, 0);
										echo '<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
										Application has successfully searched for Facebook accounts for first user.
										</div>';
									}
								// Then for second
									$sql = 'SELECT COUNT(*) as "fb_count" FROM fb_account WHERE person_id="'.$user_id2.'";';
									$result=mysql_query($sql);
									$row = mysql_fetch_assoc($result);
									if ($row[fb_count]==0){
										get_fb_data($fb_access_token, 0, 0, 0, 1, $user_id2, 0);
										echo '<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
										Application has successfully searched for Facebook accounts for second user.
										</div>';
									}
								// Then for matches
									get_fb_data($fb_access_token, 0, 0, 0, 2, $user_id1, $user_id2);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Application has successfully searched for Facebook relations for selected users.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								echo '</div></div>';
									
							/* If selected site is to check for matches on Twitter (only selected match) */	
							}else if(isset($_GET[getTwitterMatches])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Match</h2>
										</div>
									<div class="box-content">';
								// First check if there is any account; if there isn't, then start getTwitterAccounts()
								$user_id1=mysql_real_escape_string($_GET[user1]);
								$user_id2=mysql_real_escape_string($_GET[user2]);
								// First check for first user
									$sql = 'SELECT COUNT(*) as "tw_count" FROM tw_account WHERE person_id="'.$user_id1.'";';
									$result=mysql_query($sql);
									$row = mysql_fetch_assoc($result);
									if ($row[tw_count]==0){
										get_tw_data($tw_consumer_key, $tw_consumer_secret, $tw_access_token, $tw_access_token_secret, 0, 0, 0, 1, $user_id1, 0);
										echo '<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
										Application has successfully searched for Twitter accounts for first user.
										</div>';
									}
								// Then for second
									$sql = 'SELECT COUNT(*) as "tw_count" FROM tw_account WHERE person_id="'.$user_id2.'";';
									$result=mysql_query($sql);
									$row = mysql_fetch_assoc($result);
									if ($row[tw_count]==0){
										get_tw_data($tw_consumer_key, $tw_consumer_secret, $tw_access_token, $tw_access_token_secret, 0, 0, 0, 1, $user_id2, 0);
										echo '<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
										Application has successfully searched for Twitter accounts for second user.
										</div>';
									}
								// Then for matches
									get_tw_data($tw_consumer_key, $tw_consumer_secret, $tw_access_token, $tw_access_token_secret, 0, 0, 0, 2, $user_id1, $user_id2);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Application has successfully searched for Twitter relations for selected users.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								echo '</div></div>';
							
							/* If selected site is to check for matches on Google (only selected match) */	
							}else if(isset($_GET[getGoogleMatches])){
								echo '<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-user"></i>Match</h2>
										</div>
									<div class="box-content">';
								$user_id1=mysql_real_escape_string($_GET[user1]);
								$user_id2=mysql_real_escape_string($_GET[user2]);
								get_google_data(0, 0, 0, 0, 1, $user_id1, $user_id2);
								echo '<div class="alert alert-success">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Application has successfully searched for Google Search relations for selected users.
									</div>';
								echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i> Back</a>';
								echo '</div></div>';
								
							/* We are in review of projects mode */
							}else{
								echo '<div class="row-fluid">
									<div class="box span12">
										<div class="box-header well">
											<h2><i class="icon-eye-open"></i> Projects</h2>
										</div>
										<div class="box-content">';

								echo '<h1>Project <small>overview</small></h1><br>';
								/* But first we need to check if there is any project in database */
								$sql='SELECT * FROM project WHERE NOT project_status=2;';
								$list=mysql_query($sql);
								/* If there is none, offer to create one */
								if(mysql_num_rows($list) == 0){	
									echo'<div class="alert alert-info">
										<button type="button" class="close" data-dismiss="alert">x</button>
										There are no projects in database.</div>';
									echo '<a href="projects.php?add_project" class="ajax-link btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i> Add new project</a></p>';
								/* If there are, show them */
								}else{
									/* This is how we define table, where projects will be shown */
									echo '<div class="box-content">
											<table class="table table-striped table-bordered bootstrap-datatable datatable">
												<thead>
													<tr>
													  <th>Project name</th>
													  <th>Number of assignments</th>
													  <th>Actions</th>
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
										echo '<tr><td><a href="projects.php?view='.$project_id.'">'.$row[project_name].'</a></td>';
										echo '<td>'.$no_of_assignments.'</td>';
										echo '<td class="center">
												<a class="btn btn-success" href="projects.php?view='.$project_id.'">
													<i class="icon-zoom-in icon-white"></i>  
													View                                            
												</a>
												<a class="btn btn-danger" href="projects.php?delete='.$project_id.'">
													<i class="icon-trash icon-white"></i> 
													Delete
												</a>
											</td></tr>';

									}
									/* End of table */
									echo '</tbody></table></div>';
								}
							}
						}else{
							echo '<p>Plagiarism Detection Assistant has not yet been configured. Continue to installation procedure.</p>';
							echo '<a href="install.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
						}
						?>		
						<div class="clearfix"></div>
					</div>
				</div>
			</div>
			
       
<?php include('footer.php'); ?>
