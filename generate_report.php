<?php
	/*
		Plagiarism Detection Assistant
		(Prototype)
		
		Developed by Makuc Ziga (2013)
		Licensed under the Creative Commons Attribution ShareAlike 2.5 Slovenia
			http://creativecommons.org/licenses/by-sa/2.5/
		and
			http://creativecommons.si/
	*/
	
require_once('include/fpdf/fpdf.php');
require_once('include/fpdf/fpdi.php'); 
require_once('include/config.php'); 

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


class PDF extends FPDI{	// Example from site http://www.fpdf.org/
	// Page header
	function Header(){
		// Include logo
		$this->Image('include/fpdf/logo.png',10,10,35);
		// Set font to Arial bold 15
		$this->SetFont('Arial','B',15);
		// Move to the right
		$this->Cell(80);
		// Print Title
		$this->Cell(30,10,'Plagiarism Report',0,0,'C');
		// Line break
		$this->Ln(20);
	}

	// Page footer
	function Footer(){
		// Position at 1.5 cm from bottom
		$this->SetY(-15);
		// Set font toArial italic 8
		$this->SetFont('Arial','I',8);
		// Page number
		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
	}
}

if(file_exists("include/config.php")) { //Check if config file exists
	include("include/config.php");
	/* Connect to Database */
	$mysql_connect = mysql_connect($db_host, $db_user, $db_pass);
	$mysql_select_db = mysql_select_db($db_name); 

	// Instanciation of inherited class
	$pdf = new PDF();
	$pdf->AliasNbPages();
	$pdf->AddPage();	
	/* Print project and/or assignment details */
	$project_id=mysql_real_escape_string($_GET['project_id']);
	$assignment_id=mysql_real_escape_string($_GET['assignment_id']);
	/* If user wishes to print project */
	if($project_id!=""){
		$sql='SELECT * FROM project PR, assignment A WHERE A.project_id=PR.project_id AND PR.project_id="'.$project_id.'" AND A.assignment_status=1;';
		$result=mysql_query($sql);
		$row = mysql_fetch_assoc($result);
		$pdf->SetFont('Times','B',20);
		$pdf->Cell(0,10,$row[project_name],0,1);
	
	/* If user wishes to print assignment data */
	 }else if($assignment_id!=""){
		$sql='SELECT * FROM assignment A, project PR WHERE A.assignment_status=1 AND A.project_id=PR.project_id AND A.assignment_id="'.$assignment_id.'"';
		$result=mysql_query($sql);
		$row = mysql_fetch_assoc($result);
		$pdf->SetFont('Times','B',20);
		$pdf->Cell(0,10,$row[project_name],0,1);
		$pdf->SetFont('Times','B',16);
		$pdf->Cell(0,10,"Assignment ".$row[assignment_name],0,1);
	 }

	/* Algorithm to find cluster - connected components */
	if($assignment_id!=""){
		$sql='select distinct M.match_id, M.match_first_id, M.match_second_id from matches M WHERE M.assignment_id="'.$assignment_id.'" AND M.match_status=1;';
	}else{ // min(X.match_status) because every assignment should be confirmed to be shown in visualisation as confirmed
		$sql='select distinct M.match_id, M.match_first_id, M.match_second_id from matches M, assignment A WHERE A.assignment_id=M.assignment_id AND A.project_id="'.$project_id.'" AND M.match_status=1;'; //dodej match_status=1
	}						
	$result=mysql_query($sql);
	$connected_components = [];
	$i_row=0;
	while($row = mysql_fetch_assoc($result)){
		$connected_components[$i_row]= array($row[match_first_id], $row[match_second_id]);
		$i_row++;
	}
	$pdf->SetFont('Times','B',14);
	$pdf->Cell(0,10,"Groups",0,1);
	$pdf->SetFont('Times','',12);
	$aComponents = connectedComponents ($connected_components);
	
	// Store number of different groups (clusters)
	$number_of_groups = sizeof($aComponents);
	
	/* Show results by assignment */
	if($assignment_id!=""){
		$sql='select distinct(person_id), person_ident, person_name, person_surname from matches M, person P, assignment A WHERE (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND A.assignment_id=M.assignment_id AND A.assignment_id="'.$assignment_id.'" AND M.match_status=1;';
	}else{ /* Show results by project */
		$sql='select distinct(person_id), person_ident, person_name, person_surname from matches M, person P, project PR, assignment A WHERE (M.match_first_id=P.person_id OR M.match_second_id=P.person_id) AND M.match_status=1 AND A.assignment_id=M.assignment_id AND A.project_id=PR.project_id AND PR.project_id="'.$project_id.'";';
	}
	$result=mysql_query($sql);

	while($row = mysql_fetch_assoc($result)){
		$group=-1;
		/* Check to which cluster each link corresponds to */
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
		$persons[$first][0]=$row[person_id];
		$persons[$first][1]=$row[person_ident];
		$persons[$first][2]=$group;
		$persons[$first][3]=$row[person_name];
		$persons[$first][4]=$row[person_surname];
		$first++;
	}

	$max_group=0;
	for($i=0; $i<sizeof($persons); $i++){
		if($persons[$i][2]>$max_group) $max_group=$persons[$i][2];
	}
	$row=1;
	for($group=$max_group; $group>=0; $group--){
		$pdf->Cell(0,10,($row).'. group:',0,1);
		for($i=0; $i<sizeof($persons); $i++){
			if($persons[$i][2]==$group){
				$pdf->Cell(0,10,'      '.$persons[$i][1].' - '.$persons[$i][3].' '.$persons[$i][4],0,1);
			}
		}
		$pdf->Cell(0,10,'',0,1);
		$row++;
	}

	$pdf->SetFont('Times','B',14);
	$pdf->Cell(0,10,"Matches",0,1);
	$pdf->SetFont('Times','',12);
	/* Show all matches */
	if($assignment_id!=""){
		$sql='SELECT * FROM assignment A WHERE A.assignment_id='.$assignment_id.' AND A.assignment_status=1';
	}else{
		$sql='SELECT * FROM assignment A WHERE A.project_id='.$project_id.' AND A.assignment_status=1';
	}
	$result=mysql_query($sql);
	while($row = mysql_fetch_assoc($result)){
		$pdf->SetFont('Times','B',12);
		$pdf->Cell(0,10,"Assignment ".$row[assignment_name],0,1);
		$assignment_id_internal=$row[assignment_id];
		$sql2='SELECT DISTINCT M.match_id, M.match_first_id, M.match_second_id, group_concat( `person_ident` SEPARATOR \',\' ) AS \'person_ident\', M.match_first_sim, M.match_second_sim, M.match_fb, M.match_tw, A.assignment_name, M.match_status FROM matches M, person P, assignment A, project PR WHERE ( M.match_first_id = P.person_id OR M.match_second_id = P.person_id ) AND M.match_status =1 AND A.assignment_id = M.assignment_id AND A.assignment_id = '.$assignment_id_internal.' AND M.match_status =1 GROUP BY M.match_first_id, M.match_second_id ORDER BY M.match_first_id, M.match_second_id;';
		$result2=mysql_query($sql2);
		while($row2 = mysql_fetch_assoc($result2)){
			$pdf->SetFont('Times','',12);
			$pdf->Cell(0,10,explode(",", $row2[person_ident])[0].' and '.explode(",", $row2[person_ident])[count(explode(",", $row2[person_ident]))/2],0,1);
			$pdf->Cell(0,10,'      with similarities: '.$row2[match_first_sim ].'% and '.$row2[match_second_sim ].'%',0,1);	
			$pdf->Cell(0,10,'',0,1);
		}
	}
	/* Check if adding visualisations to pdf is supported */
	if($wkhtmltopdf==true){
		/* Get current url, where site is being loaded */
		$host = $_SERVER['HTTP_HOST'];
		$self = $_SERVER['PHP_SELF'];
		$url = "http://$host$self";
		$tokens = explode('/', $url);
		for($i=0; $i<count($tokens)-1; $i++){
			$new_url.=$tokens[$i].'/';
		}
		/* Generate pdf for co-occurence matrix */
		if($assignment_id!=""){
			exec('./wkhtmltopdf.sh "'.$new_url.'/visualisation.php?cmv_assignment_id='.$assignment_id.'&min_similarity=0&show=1&gen_pdf" gen_pdf_cmv.pdf --orientation landscape');
		}else{
			exec('./wkhtmltopdf.sh "'.$new_url.'/visualisation.php?cmv_project_id='.$project_id.'&min_similarity=0&show=1&gen_pdf" gen_pdf_cmv.pdf --orientation landscape');
		}
		// Import generated pdf in report
		$pagecount = $pdf->setSourceFile('gen_pdf_cmv.pdf'); 
		$template = $pdf->importPage(1, '/MediaBox'); 
		$pdf->addPage(); 
		$pdf->SetFont('Times','B',14);
		$pdf->Cell(0,10,"Co-Occurrence Matrix Visualisation",0,1);
		$pdf->useTemplate($template, -50, 30, 0); 

		/* Generate pdf for graph visualisation */
		if($assignment_id!=""){
			exec('./wkhtmltopdf.sh "'.$new_url.'/visualisation.php?gv_assignment_id='.$assignment_id.'&min_similarity=0&show=1&gen_pdf" gen_pdf_gv.pdf --orientation landscape');
		}else{
			exec('./wkhtmltopdf.sh "'.$new_url.'/visualisation.php?gv_project_id='.$project_id.'&min_similarity=0&show=1&gen_pdf" gen_pdf_gv.pdf --orientation landscape');
		}
		// Import generated pdf in report
		$pagecount = $pdf->setSourceFile('gen_pdf_gv.pdf'); 
		$template = $pdf->importPage(1, '/MediaBox'); 
		$pdf->addPage(); 
		$pdf->Cell(0,10,"Graph Visualisation",0,1);
		$pdf->useTemplate($template, 0, 30, 0); 
		// Remove both generated files
		exec('rm gen_pdf_gv.pdf');	
		exec('rm gen_pdf_cmv.pdf');
	}
	// Generate output
	$pdf->Output();
}
?>