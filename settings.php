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
?>
			<div>
				<ul class="breadcrumb">
					<li>
						<a class="ajax-link" href="index.php">Home</a> <span class="divider">/</span>
					</li>
					<li>
					<?if(!file_exists("include/config.php")) { echo '<a class="ajax-link" href="install.php">Installation</a>'; } else { echo '<a class="ajax-link" href="settings.php">Settings</a>'; } ?>
					</li>
				</ul>
			</div>
					

						<?
						if(file_exists("include/config.php")) { //Check if config file exists
							include("include/config.php");
							/* Connect to Database */
							$mysql_connect = mysql_connect($db_host, $db_user, $db_pass);
							$mysql_select_db = mysql_select_db($db_name); 
							echo '<div class="row-fluid">
				<div class="box span12 tour">
					<div class="box-header well">
						<h2><i class="icon-info-sign"></i> Settings</h2>
					</div>
					<div class="box-content">
						<h1>Settings <small>for Plagiarism Detection Assistant</small></h1><br><br>';
							if(isset($_POST[submit]) && ($_POST[submit])=="basic"){
								$keywords=explode(",", mysql_real_escape_string($_POST['keywords']));
								$number_of_persons_similarity=mysql_real_escape_string($_POST['number_of_persons_similarity']);
								if($number_of_persons_similarity==0) $number_of_persons_similarity=999;
								$social_media_start_position=mysql_real_escape_string($_POST['social_media_start_position']);
								$social_media_end_position=mysql_real_escape_string($_POST['social_media_end_position']);
								/* Get config file */
								$configContent = file_get_contents('include/config.php');
								/* Store google keywords */
								$keywords_ouput="";
								for($i=0; $i<count($keywords); $i++){
									$keywords_ouput.='"'.$keywords[$i].'"';
									if($i<count($keywords)-1){
										$keywords_ouput.=",";
									}
								}
								/* Replace settings */
								$replace_string='$google_search_keywords=array('.$keywords_ouput.');'.PHP_EOL;
								$replace_string.=PHP_EOL.'// Show number of first matches at visualisation'.PHP_EOL;
								$replace_string.='$number_of_persons_similarity='.$number_of_persons_similarity.';'.PHP_EOL;
								$replace_string.=PHP_EOL.'// Social media start and end position'.PHP_EOL;
								$replace_string.='$social_media_start_position='.$social_media_start_position.';'.PHP_EOL;
								$replace_string.='$social_media_end_position='.$social_media_end_position.';'.PHP_EOL;
								$replace_string.=PHP_EOL.'// If wkhtmltopdf is enabled'.PHP_EOL;
								$replace_string.='$wkhtmltopdf='.$wkhtmltopdf.';'.PHP_EOL.'?>';

								$configContent = substr_replace($configContent, $replace_string, strpos($configContent, '$google_search_keywords'));
								$fh = fopen('include/config.php', "w");
								fwrite($fh, $configContent);
								fclose($fh);	
								/* End of settings */
								echo '<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
										New settings have been saved successfully.
									</div>';
								echo '<a href="index.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';													
							}else if(isset($_GET[submit]) && ($_GET[submit])=="pdf"){
								$configContent = file_get_contents('include/config.php');
								if($_GET[wkhtmltopdf]==true){
									if($wkhtmltopdf!=true){
										$configContent = substr_replace($configContent, '$wkhtmltopdf=1;'.PHP_EOL.'?>', strpos($configContent, '$wkhtmltopdf=0;'));
									}
								}else{
									if($wkhtmltopdf!=false){
										$configContent = substr_replace($configContent, '$wkhtmltopdf=0;'.PHP_EOL.'?>', strpos($configContent, '$wkhtmltopdf=1;'));
									}
								}
								$fh = fopen('include/config.php', "w");
								fwrite($fh, $configContent);
								fclose($fh);	
								/* End of settings */
								echo '<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
										New settings have been saved successfully.
									</div>';
								echo '<a href="index.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';													
							}else if(isset($_GET[pdf])){
								exec('wkhtmltopdf', $output);
								if(count($output)>0){
									echo '<form class="form-horizontal" name="form" action="settings.php" method="GET">
											<fieldset>
												<div class="control-group">
													Show visualisations in generated pdf: 
													';
													if($wkhtmltopdf==true){
														echo '<input type="checkbox" value="true" name="wkhtmltopdf" checked><br>';
													}else{
														echo '<input type="checkbox" value="true" name="wkhtmltopdf"><br>';
													}echo '
												</div>
												<div class="form-actions">
													<button type="submit" value="pdf" name="submit" class="btn btn-primary">Submit</button>
												</div>
											</fieldset>
											</form>';
								}else{
									echo '<form>
											Show visualisations in generated pdf: 
											<input type="checkbox" name="wkhtmltopdf" disabled> <i>(wkhtmltopdf is not installed)</i><br>
											</form>
											Please install <a href="http://code.google.com/p/wkhtmltopdf/" target=_blank>wkhtmltopdf</a>.<br><br>
											<p>On debian based unix systems type:<br>
											<div>sudo apt-get install wkhtmltopdf<br>
											sudo apt-get install xinit<br>
											sudo apt-get install xvfb<br>
											chmod a+x wkhtmltopdf.sh <i>(this file is in PlagVis base map)</i></div></p>
											
											';
								}
							}else{
								if($number_of_persons_similarity>=999) $number_of_persons_similarity=0;
								echo '<form class="form-horizontal" name="form" action="settings.php" method="POST">
								<fieldset>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Google search keywords[1]: </label>
										<div class="controls">';
										$keywords="";
										for($i=0; $i<count($google_search_keywords); $i++){
											$keywords.=$google_search_keywords[$i];
											if($i<count($google_search_keywords)-1){
												$keywords.=",";
											}
										}
										echo'  <input class="input-xlarge focused" id="focusedInput" type="text" name="keywords" value="'.$keywords.'"> <i>(eg. stanford) - keywords are seperated by comma</i>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Show first N matches by default[2]: </label>
										<div class="controls">
										  <input class="input-xlarge focused" id="focusedInput" type="text" name="number_of_persons_similarity" value="'.$number_of_persons_similarity.'"> <i>(0 means show all)</i>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Social media start position[3]: </label>
										<div class="controls">
										  <input class="input-xlarge focused" id="focusedInput" type="text" name="social_media_start_position" value="'.$social_media_start_position.'">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Social media end position[4]: </label>
										<div class="controls">
										  <input class="input-xlarge focused" id="focusedInput" type="text" name="social_media_end_position" value="'.$social_media_end_position.'">
										</div>
									</div>
									<p>[1]: When making a google search comparison, you can add keywords. This limits google results. For example you can use school abbrevation.</p>
									<p>[2]: When showing visualisation for the first time, a lot of results can be showed. You can limit this by adding default value for showing first N-matches. If two or more matches have same similarity, not always N matches will be shown, but more. Note that you can change that in every visualisation.</p>
									<p>[3]: Facebook and Twitter API engines have limits for how many queries one can make per hour/per day. Facebook limit is 600 queries in 600 seconds. Twitter limit is 150 queries in one hour. Here you can limit which matches you would like to check first. For example start position 0 and end position 20 will limit Facebook and Twitter search for only first 20 matches. If you wish to search others, wait the time limit and change 21-40 and restart check. 20 matches will have max. 40 persons. Each person can have multiple accounts by its query meaning cross checking for first 20 matches could take approx. 400 queries.</p>
									<p>[4]: Here you set end position limit. Note that end position must be lower than number of matches.</p>
									<div class="form-actions">
										<button type="submit" value="basic" name="submit" class="btn btn-primary">Submit</button>
									 </div>
								</fieldset>
									</form>
								';
							}
						echo '</div>
					</div>
				</div>';
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
