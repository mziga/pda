Plagiarism Detection Assistant
(Prototype)

The application is designed so that user can upload student submissions and then check them for plagiarism.
This application works for source code plagiarism detection. After submissions are checked (with external provider - Moss),
user can create visualisations which are based on retrieved data. Currently two types of visualisations are implemented
graph visualisation of plagiarism and co-occurence matrix visualisation.

User can then check each plagiate and confirm or reject it. With that, he can later create a list of persons which are potenital plagiates
and invite them on interview to verify suspiction of plagiarism. This application supports multiple assignments,
so user can track each person for plagiarism through all assignments.

Connection to social media websites such as Facebook and Twitter is also implemented. With that user can retrieve information
whether two persons are friends on those sites. Also number of Google results including their names is provided.

User can at the end of investigation create a report to sum all his findings.

Developed by Ziga Makuc (2013)

This is a prototype application created for Diploma. Application uses web template:
		Charisma v1.0.0

		Copyright 2012 Muhammad Usman
		Licensed under the Apache License v2.0
		http://www.apache.org/licenses/LICENSE-2.0

		http://usman.it
		http://twitter.com/halalit_usman

Everything else, which was created in prototype application is a work of Makuc Ziga.
		Plagiarism Detection Assistant
		(Prototype)
		
		Developed by Ziga Makuc (2013)
		Licensed under the Creative Commons Attribution ShareAlike 2.5 Slovenia
			http://creativecommons.org/licenses/by-sa/2.5/
		and
			http://creativecommons.si/
		(Refers to source code only and not web template)
		
		
		
1 Installation procedure:
	Download all files in this folder and place them on Linux based Apache server. Open index.php or install.php and follow the
	instructions in installation.

2 Requirements:
	*PHP 5
	*Apache Web Server
	*MySQL Database
	*wkhtmltopdf (for enabing visualisations in report)
	
2.1 Installing wkhtmltopdf:
	Wkhtmltopdf is software used to generate PDF Files from HTML. When
	generating report, pictures of visualisation are also shown. D3 is JavaScript
	based library, which means that the code to generate visualistion is run on
	client side. To save generated visualisation, some interface must be available,
	which can act as a client. Wkhtmltopdf is used to create snapshots of this
	visualisations so they can later be integrated in report.
	If application is run on Linux based system that support aptitude, next commands should be executed.
	
		sudo apt-get install xinit
		sudo apt-get install xvfb
		sudo apt-get install wkhtmltopdf

	Xinit is needed to install everything neccessary for X Windows System to work.
	Xvfb is X11 server, that can perform graphical operations, without showing any screen output.
	Wkhtmltopdf is previously described application.

3 Starting application
	When application is installed user can start using that application. 
	Example of usage:
	First go to page "Add New Project" and create a project with desired name and desired number of assignments.
	If left empty, one assignment will be created. After that go to "Overview" and click on "View" by desired project.
	Now you can upload assignments*1 and start Moss check on them. After that you can review assignment data by clicking "View".
	Now you can see persons in that assignment. You can view match (two users) or view Moss results. After checking Moss result you can
	either reject or confirm this match. By visiting match you can see where this two users had matches (in which assignments). If you visit
	person page you can see that as well. In those two pages you can manually check for Facebook and Twitter accounts (if keys were provided in installation).
	After that you can check if there exist any friendship relation between them. You can also go to "See visualisation" and generate visualisations based
	on retreived Moss results. You can either choose Graph or Co-occurence matrix visualisation. If you click on Project name, you can generate visualisations 
	based only on selected assignment. When there are some matches confirmed, user can generate PDF report. When viewing visualisation, only first N matches are 
	shown because the graph would be too big if a lot of matches are shown in visualisation. This can be changed in visualisation. Other info is provided in info
	section. In "Settings" page user can change default settings for visualisations and social media and Google search. When creating comparison data on Google
	for two persons, keywords can be given as well. By default "Name1 Surname1"+"Name2 Surname2" is searched on Google (all four combinations, where name can be on first
	place or second after surname). You can also add some keywords, to limit number of search results. Social media start and end position then limit which
	matches will be checked in project overview section, where user can start Facebook or Twitter check automatically.
	
	*1: When uploading files, following names of files are required:
		Name_Surname_(ident).java or
		Name_Surname1_Surname2_(ident).java or
		Name_Surname.java or
		Name_surname1_Surname2.java or
		ident.java.
		
		Number of surnames is unlimited. Extension of file can be everything (c, java, php, cpp,...).
		If ident is not provided, it is generated for each person.
	
4 For any questions you can contact me at zigamakuc@hotmail.com.
