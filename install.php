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
						<a href="index.php">Home</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="install.php">Installation</a>
					</li>
				</ul>
			</div>
					
			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well">
						<h2><i class="icon-info-sign"></i> Installation</h2>
					</div>
					<div class="box-content">
						<?
			if(isset($_GET['site'])){ //If installation site is selected
				if($_GET['site']=="install"){
					if(file_exists("include/config.php")) { //Check if config file already exists
						echo'<div class="alert alert-info">
							<button type="button" class="close" data-dismiss="alert">x</button>
							Plagiarism Detection Assistant has already been configured. Delete file "config.php" if you wish to repeat installation procedure.</div>';
						echo '<a href="index.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Start using software</a></p>';													
					}else{  //If not, continue with installation
						echo '<div class="page-header"><h1>Welcome <small>to Plagiarism Detection Assistant Installer</small></h1></div>';
						echo '<div class="row-fluid ">            
							  <div class="span4">';
					    if($_SESSION['moss']==true && !isset($_SESSION['mysql'])){ // This means that moss was already set, start with second part
							echo '<h3>Step 2/3 &ndash; MySQL Configuration</h3></div></div><br>';
							if(!isset($_POST['server_host'])){ //MySQL data was not yet entered in part 2
								echo '<p>Please enter details about your MySQL server:</p>';
								echo '<form class="form-horizontal" name="form" action="install.php?site=install" method="POST">
								<fieldset>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Server Host: </label>
										<div class="controls">
										  <input class="input-xlarge focused" id="focusedInput" type="text" name="server_host"> <i>(eg. localhost)</i>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Username: </label>
										<div class="controls">
										  <input class="input-xlarge focused" id="focusedInput" type="text" name="server_user"> <i>(eg. root)</i>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Password: </label>
										<div class="controls">
										  <input class="input-xlarge focused" id="focusedInput" type="password" name="server_pass"> <i>(eg. secret33pass)</i>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Database Name: </label>
										<div class="controls">
										  <input class="input-xlarge focused" id="focusedInput" type="text" name="server_dbname"> <i>(eg. plagvis)<br>   If database does not exist, new will be created.</i></td>
										</div>
									</div>
									<div class="form-actions">
										<button type="submit" class="btn btn-primary">Submit</button>
									 </div>
								</fieldset>
									</form>';
							}else{ // MySQL data was already entered
								echo '<p>Using details about your MySQL server:</p>';
								if($_POST['server_host']==""){
									$_POST['server_host']="localhost";
								}
								if($_POST['server_dbname']==""){ //Use default if empty
									$_POST['server_dbname']="plagvis";
								}
								$server_host=$_SESSION['dbase']['host']=$_POST['server_host'];
								$server_user=$_SESSION['dbase']['user']=$_POST['server_user'];
								$server_pass=$_SESSION['dbase']['pass']=$_POST['server_pass'];
								$server_dbname=$_SESSION['dbase']['dbname']=$_POST['server_dbname'];
								echo 'MySQL Server Host: '.$server_host.'<br>';
								echo 'MySQL Username: '.$server_user.'<br>';
								echo 'MySQL Password: *hidden*<br>';
								echo 'MySQL Database: '.$server_dbname.'<br>';
								echo '<br><p>Checking if connection works';
								//check if connection works
								$link = mysql_connect($server_host, $server_user, $server_pass);
								if (!$link) {
									echo ' <span class="label label-warning">FAILED</span></p>';
									echo '<div class="alert alert-error">
											<button type="button" class="close" data-dismiss="alert">x</button>
											'.mysql_error().'</div>';
									echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>';													
								}else{
									echo ' <span class="label label-success">OK</span></p>';
								
									// Check if db exist
									echo '<p>Creating new database "'.$server_dbname.'" ';
									$db_selected = mysql_select_db($server_dbname, $link);
									if (!$db_selected) {
										// Create new
										$sql='CREATE DATABASE '.$server_dbname.' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;';
										if (mysql_query($sql)){
											echo ' <span class="label label-success">OK</span></p>';
											$db_selected = mysql_select_db($server_dbname, $link);
											if($db_selected){
												// Create tables in database
												require_once('include/database.php');
												echo '<p>Filling up database with tables, constraints and keys ';
												$sql=$query;
												$mysqli = new mysqli($server_host, $server_user, $server_pass, $server_dbname);
												if ($mysqli->multi_query($sql)){
													echo ' <span class="label label-success">OK</span></p>';
													echo '<div class="alert alert-success">
															<button type="button" class="close" data-dismiss="alert">×</button>
															MySQL has successfully been configured. Please, continue with the installation.</div>';
													// Close connection 
													$mysqli->close();
													$_SESSION['mysql']=true;
													echo '<a href="install.php?site=install" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';													
												}else{
													echo ' <span class="label label-warning">FAILED</span></p>';
													echo '<div class="alert alert-error">
															<button type="button" class="close" data-dismiss="alert">x</button>
															'.$mysqli->error().'</div>';
													echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>';													
												}
												/* close connection */
												//$mysqli->close();
											}else{
												echo '<span class="label label-warning">ERROR</span>: Database could not be accessed.</p>';
												echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>';													
											}
										}else{
											echo ' <span class="label label-warning">FAILED</span></p>';
											echo '<div class="alert alert-error">
												<button type="button" class="close" data-dismiss="alert">x</button>
												'.mysql_error().'</div>';
											echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>';													
										}
									}else{ // Database already exist; assume it was previously created
										echo ' <span class="label label-info">SKIP</span> : Database already exists.</p>';
										echo '<div class="alert alert-info">
										<button type="button" class="close" data-dismiss="alert">x</button>
										Assuming selected database is this software\'s database, MySQL has successfully been configured. Please, continue with the installation.

									</div>';
										$_SESSION['mysqlexisted']=true;
										$_SESSION['mysql']=true;
										echo '<a href="install.php?site=install" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';													
									}
								}
							mysql_close($link);
							}
						}else if($_SESSION['moss']==true && $_SESSION['mysql']==true && !isset($_SESSION['finished'])){ // Moss and mysql data entered, start part 3
							echo '<h3>Step 3/3 &ndash; Connect with Social Media and Additional Settings</h3></div></div><br>';
							// If data was already entered
							if(isset($_POST['fb_access_token']) || (isset($_POST['tw_consumer_key']) && isset($_POST['tw_consumer_secret']) && isset($_POST['tw_access_token']) && isset($_POST['tw_access_token_secret'])) || isset($_SESSION['additional']['isadded'])){
								if(!isset($_POST['login']) && !isset($_POST['done'])){ //Show entered data
									echo '<p>Using current data for Social Media:</p>';
									$_SESSION['additional']['fb_access_token']=$_POST['fb_access_token'];
									$fb_access_token=$_POST['fb_access_token'];
									
									$_SESSION['additional']['tw_consumer_key']=$_POST['tw_consumer_key'];
									$tw_consumer_key=$_POST['tw_consumer_key'];
									
									$_SESSION['additional']['tw_consumer_secret']=$_POST['tw_consumer_secret'];
									$tw_consumer_secret=$_POST['tw_consumer_secret'];
									
									$_SESSION['additional']['tw_access_token']=$_POST['tw_access_token'];
									$tw_access_token=$_POST['tw_access_token'];
									
									$_SESSION['additional']['tw_access_token_secret']=$_POST['tw_access_token_secret'];
									$tw_access_token_secret=$_POST['tw_access_token_secret'];
									
									if($fb_access_token!=""){
										echo 'Facebook User Access Token: '.$fb_access_token.'<br>';
									}else{
										echo 'Not using Facebook. You can change this setting after the installation in config.php by entering required data.<br>';
									}
									if($tw_app_id!=""){
										echo 'Twitter Consumer Key: '.$tw_consumer_key.'<br>';
										echo 'Twitter Consumer Secret: '.$tw_consumer_secret.'<br>';
										echo 'Twitter Access Token: '.$tw_access_token.'<br>';
										echo 'Twitter Access Token Secret: '.$tw_access_token_secret.'<br>';
									}else{
										echo 'Not using Twitter. You can change this setting after the installation in config.php by entering required data..<br>';
									}
									echo '<br><br><form class="form-horizontal" name="form" action="install.php?site=install" method="POST">
									<fieldset>
										<legend>Additional Settings</legend>
										<div class="control-group">
											<label class="control-label">Login: </label>
											<div class="controls">
											  <label class="checkbox inline">
												<input type="checkbox" id="inlineCheckbox1" value="option1" name="login" disabled=""> I would like to use login for this site.
											  </label>
											</div>
										</div>
										<div class="control-group">
											<label class="control-label">Something else: </label>
											<div class="controls">
											  <label class="checkbox inline">
												<input type="checkbox" id="inlineCheckbox1" value="option1" name="else" disabled=""> I would love that!
											  </label>
											</div>
										</div>
										<input type="hidden" name="done"/>
										<div class="form-actions">
											<button type="submit" class="btn btn-primary">Submit</button>
										</div>
									</fieldset>
									</form>';
									$_SESSION['additional']['isadded']=true;
								}else if(isset($_POST['login'])){ //If user wishes to create login page
									echo 'Enter Username and password to access this site:<br><br>';
									echo'<form class="form-horizontal" name="form" action="install.php?site=install" method="POST">
									<fieldset>
										<div class="control-group">
											<label class="control-label" for="focusedInput">Username: </label>
											<div class="controls">
											  <input class="input-xlarge focused" id="focusedInput" type="text" name="username">
											</div>
										</div>										
										<div class="control-group">
											<label class="control-label" for="focusedInput">Password: </label>
											<div class="controls">
											  <input class="input-xlarge focused" id="focusedInput" type="text" name="password">
											</div>
										</div>
										<div class="form-actions">
											<button type="submit" class="btn btn-primary">Submit</button>
										</div>
									</fieldset>
										</form>';
								}else{//configure script
									if(isset($_POST['username'])){  
										 //create .htacces and .htpasswd if requested
										echo '<p>Creating .htaccess and .htpasswd files.';

										$username=$_POST['username'];
										$password=$_POST['password'];
										$htaccess ='AuthUserFile '.dirname(__FILE__).'/.htpasswd
AuthType Basic
AuthName "Please enter username and password to enter this site."
Require valid-user';
										$htpasswd=$username.':'.crypt($password, base64_encode($password));
										//.htaccess
										$fh = fopen('.htaccess', "w") or die('<p><br><strong style=\"color: red;\">Please add priviledges for use of this folder for web server (chown -R www-data path/to/root/folder/*).</strong><a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>');
										fwrite($fh, $htaccess);
										fclose($fh);	
										
										//.htpasswd
										$fh = fopen('.htpasswd', "w") or die('<p><br><strong style=\"color: red;\">Please add priviledges for use of this folder for web server (chown -R www-data path/to/root/folder/*)</strong><a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>');
										fwrite($fh, $htpasswd);
										fclose($fh);	
										echo ' <span class="label label-success">OK</span></p><br>';
									}
									// Configure script
									// Write everything neccesary

									
									$fb_access_token=$_SESSION['additional']['fb_access_token'];
									$tw_consumer_key=$_SESSION['additional']['tw_consumer_key'];
									$tw_consumer_secret=$_SESSION['additional']['tw_consumer_secret'];
									$tw_access_token=$_SESSION['additional']['tw_access_token'];
									$tw_access_token_secret=$_SESSION['additional']['tw_access_token_secret'];
									$db_host=$_SESSION['dbase']['host'];
									$db_user=$_SESSION['dbase']['user'];
									$db_pass=$_SESSION['dbase']['pass'];
									$db_name=$_SESSION['dbase']['dbname'];	
									
									//Also add priviledges to start script wkhtmltopdf.sh
									$wkhtmltopdf_chmod = shell_exec('chmod +x wkhtmltopdf.sh');
									
									/* Check if wkhtmltopdf exist */
									if (strpos(exec('./wkhtmltopdf.sh'),'wkhtmltopdf: not found') !== false) {
										/* Does not exist */
										$wkhtmltopdfexist=0;
									}else{
										/* Does exist, hence enable wkhtmltopdf function */
										$wkhtmltopdfexist=1;
									}			
									// Generated output file
									$config='<?php
///////////////////////////////////////////////////////////////////////////
// //
// PDA Config File
// User settings
// //
///////////////////////////////////////////////////////////////////////////

// Database configuration (MySQL)
$db_host="'.$db_host.'";
$db_user="'.$db_user.'";
$db_pass="'.$db_pass.'";
$db_name="'.$db_name.'";

// Facebook configuration
$fb_access_token="'.$fb_access_token.'";

// Twitter configuraiton
$tw_consumer_key="'.$tw_consumer_key.'";
$tw_consumer_secret="'.$tw_consumer_secret.'";
$tw_access_token="'.$tw_access_token.'";
$tw_access_token_secret="'.$tw_access_token_secret.'";

// Google search keywords
$google_search_keywords=array("");

// Show number of first matches
$number_of_persons_similarity=10;

// Social media start and end position
$social_media_start_position=0;
$social_media_end_position=10;

// If wkhtmltopdf is enabled
$wkhtmltopdf='.$wkhtmltopdfexist.';
?>';
									// Save config file
									$fh = fopen('include/config.php', "w") or die('<p><br><strong style=\"color: red;\">Please add priviledges for use of this folder for web server (chown -R www-data path/to/root/folder/*).</strong><a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>');
									fwrite($fh, $config);
									fclose($fh);	
									/* Create backup for config if something went wrong later to have backup available*/
									$fh = fopen('include/config_backup.php', "w") or die('<p><br><strong style=\"color: red;\">Please add priviledges for use of this folder for web server (chown -R www-data path/to/root/folder/*).</strong><a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>');
									fwrite($fh, $config);
									fclose($fh);
									/* If database already existed, do not delete data about projects */
									if(!$_SESSION['mysqlexisted']){
										$delete_all_projects = shell_exec('rm -rf projects/*');
										$copy_back_ht_access_file = shell_exec('cp include/.htaccess projects');
									}
									echo '<div class="alert alert-success">
										<button type="button" class="close" data-dismiss="alert">x</button>
										Congratulations! Installation and configuration is completed. You can now delete this file (install.php) and start using application.</div>';
									echo '<a href="index.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Start</a></p>';													
									$_SESSION['finished']=true;
								}
							}else{
								echo 'If you wish to use Facebook and Twitter for additional data such as:
								<ul>
									<li> Searching for friendship relations in Facebook </li>
									<li> Searching for follower relations in Twitter </li>
									<li> Adding additional information to users </li>
									<li> ... </li>
								</ul>
								then fill out next forms [<a href="install.php?help=fbtwitterinfo">How to get this data?</a>]:<br><br>';
								echo '<form class="form-horizontal" name="form" action="install.php?site=install" method="POST">
								<fieldset>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Facebook Access Token*: </label>
										<div class="controls">
											<input class="input-xlarge focused" name="fb_access_token" id="focusedInput" type="text" value=""> <i>(eg. 12345678987654)</i>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Twitter Consumer Key*: </label>
										<div class="controls">
											<input class="input-xlarge focused" name="tw_consumer_key" id="focusedInput" type="text" value=""> <i>(eg. xxx)</i>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Twitter Consumer Secret*: </label>
										<div class="controls">
											<input class="input-xlarge focused" name="tw_consumer_secret" id="focusedInput" type="text" value=""> <i>(eg. xxx)</i>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Twitter Access Token*: </label>
										<div class="controls">
											<input class="input-xlarge focused" name="tw_access_token" id="focusedInput" type="text" value=""> <i>(eg. xxx)</i>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="focusedInput">Twitter Acess Token Secret*: </label>
										<div class="controls">
											<input class="input-xlarge focused" name="tw_access_token_secret" id="focusedInput" type="text" value=""> <i>(eg. xxx)</i>
										</div>
									</div>
									<p class="help-block"><i>*If you do not wish to use one or more of this modules, leave them blank.</i></p>
									<div class="form-actions">
										<button type="submit" class="btn btn-primary">Submit</button>
									</div>
								</fieldset>
								</form>';
							}
						}else if(!isset($_SESSION['finished'])){
							if(!isset($_POST['moss_id'])){//First step; download moss script
								if(file_exists("mossnet")){  //If exist, do not download
									echo '<h3>Step 1/3 &ndash; MOSS Initialization</h3></div></div><br>';
									echo '<p>Downloading MOSS script. Please wait ';
									echo ' <span class="label label-info">SKIP</span> </p>';
									echo '<div class="alert alert-info">
										<button type="button" class="close" data-dismiss="alert">x</button>
										Assuming that MOSS has already been installed and configured. Please, continue with the installation.
									</div>';
									$_SESSION['moss']=true;
									echo '<a href="install.php?site=install" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';													
								}else{ // First enter id and then download and configure script
									echo '<h3>Step 1/3 &ndash; MOSS Initialization</h3></div></div><br>';
									echo '<form class="form-horizontal" name="form" action="install.php?site=install" method="POST">
									<fieldset>
										<div class="control-group">
											<p>Please enter MOSS user identification number: [<a href="install.php?help=nomossnumber">Don\'t have one?</a>]</p>
											<label class="control-label" for="focusedInput">MOSS ID:</label>
											<div class="controls">
												<input class="input-xlarge focused" id="focusedInput" type="number" maxlength="9" name="moss_id">
											</div>
										</div>
										<div class="form-actions">
											<button type="submit" class="btn btn-primary">Submit</button>
										</div>
									</fieldset>
									</form> ';
								}
							}else{
								$moss_user_id=$_POST['moss_id'];
								echo '<h3>Step 1/3 &ndash; MOSS Initialization</h3></div></div><br>';
								echo '<p>Downloading MOSS script. Please wait.';
								/* Download moss */
								$moss_script_dl = file_get_contents('http://moss.stanford.edu/general/scripts/mossnet');
								if(strlen($moss_script_dl)==0){
									echo ' <span class="label label-warning">FAILED</span></p>';
								    echo '<div class="alert alert-error">
											<button type="button" class="close" data-dismiss="alert">x</button>
											Please download script manually from <a href="http://theory.stanford.edu/~aiken/moss/" target=_blank>here</a>. Additionaly, create your own account and change user id in script. Save it to same folder as this<br> website is and add executable rights. Then retry visiting this page.
										</div>';
									echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>';													
								}else{
									/* Enter new moss id */
									echo ' <strong style="color: green;">[OK]</strong></p>';
									$moss_id=$_POST['moss_id'];
									echo '<p>Using MOSS user identification number: <b>'.$moss_id.'</b></p>';
									$moss_new_id='$userid='.$moss_user_id.';';
									// moss_old_id is id stored in downloaded version which does not work and must be changed
									$moss_old_id='$userid=987654321;';
									$moss_script=str_replace($moss_old_id, $moss_new_id, $moss_script_dl);
									$moss_savefile = "mossnet";
									$fh = fopen($moss_savefile, "w") or die('<div class="alert alert-error">
										<button type="button" class="close" data-dismiss="alert">x</button>
										Please add priviledges for use of this folder for web server (chown -R www-data path/to/root/folder/*).
									</div><a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a>');
									fwrite($fh, $moss_script);
									fclose($fh);		
									echo '<p>Saving file and configuring executable priviledges.</p>';
									$moss_chmod = shell_exec('chmod +x mossnet');
									echo '<div class="alert alert-success">
											<button type="button" class="close" data-dismiss="alert">x</button>
											MOSS has successfully been installed and configured. Please, continue with the installation.
										</div>';
									$_SESSION['moss']=true;
									echo '<a href="install.php?site=install" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';													
								}
							}
						}else{
							if(file_exists("include/config.php")) { //Check if config file exists
								echo '<p>Configuration is completed. Delete this file and you can start using this application.</p>';
								echo '<a href="index.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Start</a></p>';	
							}else{ //There was probably a problem, so unset all session data and restart
								echo'<div class="alert alert-info">
									<button type="button" class="close" data-dismiss="alert">x</button>
									Please refresh this site.
									</div>';
								unset($_SESSION['moss']);
								unset($_SESSION['mysql']);
								unset($_SESSION['dbase']);
								unset($_SESSION['additional']);
								unset($_SESSION['finished']);
								echo '<a href="install.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Refresh</a></p>';													
							}
						}
					}
				}
				// Help options
			}else if(isset($_GET['help'])){
				if($_GET['help']=="nomossnumber"){
					echo '<h1>Help<br>';
					echo '<small>No MOSS user identification number</small></h1><br>';
					echo '<p>To obtain a Moss account, send a mail message to moss@moss.stanford.edu. The body of the message should appear exactly as follows:<br>
						&nbsp;&nbsp;registeruser<br>
						&nbsp;&nbsp;mail <i>username@domain</i><br>
						where the last bit in italics is your email address.</p>';
					echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>';													
				}
				if($_GET['help']=="fbtwitterinfo"){
					echo '<h1>Help<br>';
					echo '<small>How to get Facebook and Twitter data for use in this software.</small></h1><br>';
					echo '<h2>Facebook</h2>';
					echo '<h3>Step I</h3>First step is to create Facebook account. If investigator, who uses this application, does not have account yet, one should be created. Note that multiple and fake Facebook accounts are <b>prohibited</b>.
 <h3>Step II</h3>Next step is to create Facebook application. This is done at <a href="https://developers.facebook.com/apps/">following page</a>. To create a new application, click button "Create New App".
 <h3>Step III</h3>Enter name of application, then enter address where this application is hosted. Address should be entered in sections "Website with Facebook Login" and "App on Facebook".
 <h3>Step IV</h3>Then save changes and go to "Use Graph API Explorer". User access token is generated, and this access token is then used when making queries on Facebook.
 <h3>Information</h3>

	Now that user access token is available, some specific characteristics of token must be changed. This is due to fact, that user access token can become expired. This can happen because of <i>four different reasons</i>:
<ol>
<li>The token expires after <i>expiration time</i>, which is set to 2 hours by default.</li>
<li>The token expires if user <i>changes his password</i>.</li>
<li>The token expires if user <i>de-authorizes application</i>.</li>
<li>The token expires if user l<i>ogs out of Facebook</i>. </li>
</ol> 
The second and third reasons do not present a problem, but first and last do. So next thing to do is to make token valid, even if user logs out. This means that it is available in <i>off-line mode</i>. Also expiry time must be extended. Maximal expiration time is currently 60 days, so this option is used. This can be achieved by visiting next URL:<br>

<a href="https://graph.facebook.com/oauth/access_token?             
    client_id=APP_ID
    &client_secret=APP_SECRET
    &grant_type=fb_exchange_token
    &fb_exchange_token=EXISTING_USER_ACCESS_TOKEN">https://graph.facebook.com/oauth/access_token?     <br>        
    client_id="APP_ID"<br>
    &client_secret="APP_SECRET"<br>
    &grant_type=fb_exchange_token<br>
    &fb_exchange_token="EXISTING_USER_ACCESS_TOKEN"</a>
<br>	
	New access token is generated and it is shown in output. Now that user access token is available in off-line mode and will not expire for next 60 days, it can be used to get required data.';
				echo '<br><br><h2>Twitter</h2>';
					echo '<h3>Step I</h3>First step is to create Twitter account. If investigator, who uses this application, does not have account yet, one should be created.';
echo '<h3>Step II</h3>Next step is to create Twitter application. This is done at <a href="https://dev.twitter.com/apps">following page</a>. To create a new application, click button "Create a new application".';
echo '<h3>Step II</h3>Enter name of the application, description of application and then enter address where this application is hosted.';
echo '<h3>Step I</h3>Then save changes and go to "Create my access token" in application settings. Access token is generated, and this access token is then used when making queries on Twitter.';
echo '<br>Now that access token (along with access token secret, consumer key and consumer secret) is available, queries are ready to be executed.';
					echo '<a href="javascript:history.back()" class="btn btn-large btn-primary"><i class="icon-chevron-left icon-white"></i>  Back</a></p>';													
				}
			}else{  //If there is no site detected, check first if config file is set
				if (!file_exists('include/config.php')) { //if not, this is welcome site
					echo '<h1>Welcome <small>to Plagiarism Detection Assistant Installer</small></h1>';
					echo '<p>This installation will guide you through the process of software configuration and installation.</p>';
					echo '<div class="alert alert-info">
							<button type="button" class="close" data-dismiss="alert">x</button>
							Please make sure that all folders and files have access to web server ("chown -R www-data path/to/root/folder/of/this/page*").
						</div>';
					echo '<a href="install.php?site=install" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';													
				}else{ // If so, continue with first site
					echo '<h1>Welcome <small>to Plagiarism Detection Assistant Installer</small></h1>';
					echo '<p>Configuration is completed. You can start using this application now.</p>';
					echo '<a href="index.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Start</a></p>';	
				}
			}
			?>
						<div class="clearfix"></div>
					</div>
				</div>
			</div>
<?php include('footer.php'); ?>
