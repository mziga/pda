<?php
/*
Uploadify
Copyright (c) 2012 Reactive Apps, Ronnie Garcia
Released under the MIT License <http://www.opensource.org/licenses/mit-license.php> 

	Main changes in file for Plagiarism Detection Assistant:
				*Added:
					*Retreiving next arguments:
						*secret code
						*save_folder
				*Changed:
					*Save file destination
*/

// Define a destination
	/* Get save folder destination (projects/assignments) */
	$targetFolder = "/".$_POST['save_folder'];
	
	/* Check if we are uploading file by verifying token; we use unique code and timestamp which are sent */
    $verify = md5('dvR\'fYg#7S9A!j8(5q"dx7.0vBV'.$_POST['timestamp']);

    if (!empty($_FILES) && $_POST['token'] == $verify) {
        $tempFile = $_FILES['Filedata']['tmp_name'];
        $targetPath = dirname(dirname(__FILE__)) . $targetFolder;
        $targetFile = rtrim($targetPath,'/') . '/' . $_FILES['Filedata']['name'];

        move_uploaded_file($tempFile,$targetFile);
    }
?>
