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
					<?if(!file_exists("include/config.php")) { echo '<a href="install.php">Installation</a>'; } else { echo '<a href="index.php">Welcome</a>'; } ?>
					</li>
				</ul>
			</div>
					
			<div class="row-fluid">
				<div class="box span12 tour">
					<div class="box-header well">
						<h2><i class="icon-info-sign"></i> Welcome</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-setting btn-round"><i class="icon-cog"></i></a>
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
							<a href="#" class="btn btn-close btn-round"><i class="icon-remove"></i></a>
						</div>
					</div>
					<div class="box-content">
						<h1>Welcome <small>to Plagiarism Detection Assistant</small></h1>
						<?
						if(file_exists("include/config.php")) { //Check if config file exists
							echo '<h2>Site overview</h2>';
							echo 'Continue using this application on next sites.<br><br><br>';
							echo '<p><a href="projects.php" class=""><i class="icon-cog"></i>Projects Overview</a></p><br>';
							echo '<p><a href="visualisation.php" class=""><i class="icon-cog"></i>Visualisation</a></p><br>';
							echo '<p><a href="settings.php" class=""><i class="icon-cog"></i>Settings</a></p><br>';
							echo '<p><a href="settings.php?pdf" class=""><i class="icon-cog"></i>Report Settings</a></p><br>';
						}else{ // If it does not, offer option to install
							echo '<p>Plagiarism Detection Assistant has not yet been configured. Continue to installation procedure.</p>';
							echo '<a href="install.php" class="btn btn-large btn-primary"><i class="icon-chevron-right icon-white"></i>  Continue</a></p>';
						}
							?>		
						<div class="clearfix"></div>
					</div>
				</div>
			</div>
		
				  

		  
       
<?php include('footer.php'); ?>
