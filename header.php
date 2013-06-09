<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<!--
		Charisma v1.0.0

		Copyright 2012 Muhammad Usman
		Licensed under the Apache License v2.0
		http://www.apache.org/licenses/LICENSE-2.0

		http://usman.it
		http://twitter.com/halalit_usman
		(Refers to web template only)
	-->
	<!--
		Plagiarism Detection Assistant
		(Prototype)
		
		Developed by Makuc Ziga (2013)
		Licensed under the Creative Commons Attribution ShareAlike 2.5 Slovenia
			http://creativecommons.org/licenses/by-sa/2.5/
		and
			http://creativecommons.si/
		(Refers to source code only and not web template)
	-->
	<meta charset="utf-8">
	<title>Plagiarism Detection Assistant</title>

	<!-- The styles -->
	<link id="bs-css" href="css/bootstrap-cerulean.css" rel="stylesheet">
	<style type="text/css">
	  body {
		padding-bottom: 40px;
	  }
	  .sidebar-nav {
		padding: 9px 0;
	  }
	</style>
	<link href="css/bootstrap-responsive.css" rel="stylesheet">
	<link href="css/charisma-app.css" rel="stylesheet">
	<link href="css/jquery-ui-1.8.21.custom.css" rel="stylesheet">
	<link href='css/fullcalendar.css' rel='stylesheet'>
	<link href='css/fullcalendar.print.css' rel='stylesheet'  media='print'>
	<link href='css/chosen.css' rel='stylesheet'>
	<link href='css/uniform.default.css' rel='stylesheet'>
	<link href='css/colorbox.css' rel='stylesheet'>
	<link href='css/jquery.cleditor.css' rel='stylesheet'>
	<link href='css/jquery.noty.css' rel='stylesheet'>
	<link href='css/noty_theme_default.css' rel='stylesheet'>
	<link href='css/elfinder.min.css' rel='stylesheet'>
	<link href='css/elfinder.theme.css' rel='stylesheet'>
	<link href='css/jquery.iphone.toggle.css' rel='stylesheet'>
	<link href='css/opa-icons.css' rel='stylesheet'>
	<link href='css/uploadify.css' rel='stylesheet'>
	
	

	<!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
	  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

	<!-- The fav icon -->
	<link rel="shortcut icon" href="img/favicon.ico">
		
</head>

<body>
	<?php if(!isset($no_visible_elements) || !$no_visible_elements)	{ ?>
	<!-- topbar starts -->
	<div class="navbar">
		<div class="navbar-inner">
			<div class="container-fluid">
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".top-nav.nav-collapse,.sidebar-nav.nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</a>
				<a class="brand" href="index.php"> <img alt="Charisma Logo" src="img/logo20.png" /> <span>PDA</span></a>
				
	
			<!--/.nav-collapse -->
			</div>
		</div>
	</div>
	<!-- topbar ends -->
	<?php } ?>
	<div class="container-fluid">
		<div class="row-fluid">
		<?php if(!isset($no_visible_elements) || !$no_visible_elements) { ?>
			<?
			if(file_exists("include/config.php")) { //Check if config file exists
				echo'<!-- left menu starts -->
				<div class="span2 main-menu-span">
					<div class="well nav-collapse sidebar-nav">
						<ul class="nav nav-tabs nav-stacked main-menu">
							<li class="nav-header hidden-tablet">Projects</li>
							<li><a class="ajax-link" href="projects.php?add_project"><i class="icon-edit"></i><span class="hidden-tablet"> Add New Project</span></a></li>
							<li><a class="ajax-link" href="projects.php"><i class="icon-eye-open"></i><span class="hidden-tablet"> Overview</span></a></li>
							<li class="nav-header hidden-tablet">Visualization</li>
							<li><a class="ajax-link" href="visualisation.php"><i class="icon-globe"></i><span class="hidden-tablet"> See Visualization</span></a></li>
							<li class="nav-header hidden-tablet">Settings</li>
							<li><a class="ajax-link" href="settings.php"><i class="icon-list-alt"></i><span class="hidden-tablet"> Settings</span></a></li>
							<li><a class="ajax-link" href="settings.php?pdf"><i class="icon-list-alt"></i><span class="hidden-tablet"> Report Settings</span></a></li>

						</ul>
						<label id="is-ajax" class="hidden-tablet" for="is-ajax"></label>
					</div><!--/.well -->
				</div><!--/span-->
				<!-- left menu ends -->';
			}else{
				echo'<!-- left menu starts -->
				<div class="span2 main-menu-span">
					<div class="well nav-collapse sidebar-nav">
						<ul class="nav nav-tabs nav-stacked main-menu">
							<li class="nav-header hidden-tablet">Installation</li>
							<li><a class="ajax-link" href="install.php"><i class="icon-star"></i><span class="hidden-tablet"> Installer</span></a></li>
						</ul>
					</div><!--/.well -->
				</div><!--/span-->
				<!-- left menu ends -->';
			}
			?>
			<noscript>
				<div class="alert alert-block span10">
					<h4 class="alert-heading">Warning!</h4>
					<p>You need to have <a href="http://en.wikipedia.org/wiki/JavaScript" target="_blank">JavaScript</a> enabled to use this site.</p>
				</div>
			</noscript>
			
			<div id="content" class="span10">
			<!-- content starts -->
			<?php } ?>
