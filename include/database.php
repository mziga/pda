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
$query='

drop table if exists assignment;

drop table if exists fb_account;

drop table if exists matches;

drop table if exists person;

drop table if exists project;

drop table if exists tw_account;

/*==============================================================*/
/* Table: assignment                                            */
/*==============================================================*/
create table assignment
(
   assignment_id        int not null AUTO_INCREMENT,
   project_id           int not null,
   assignment_name      text,
   assignment_begin_date date,
   assignment_end_date  date,
   assignment_status	int,
   primary key (assignment_id)
);

/*==============================================================*/
/* Table: fb_account                                            */
/*==============================================================*/
create table fb_account
(
   fb_id                int not null AUTO_INCREMENT,
   person_id            int not null,
   fb_user_id           text,
   fb_name              text,
   fb_status            int,
   primary key (fb_id)
);

/*==============================================================*/
/* Table: matches                                               */
/*==============================================================*/
create table matches
(
   match_id             int not null AUTO_INCREMENT,
   assignment_id        int not null,
   match_first_sim      float,
   match_second_sim     float,
   match_lines          int,
   match_fb             text,
   match_tw             text,
   match_first_id       int not null,
   match_second_id      int not null,
   match_status			int,
   match_url			text,
   match_google			int DEFAULT \'-1\',
   primary key (match_id)
);

/*==============================================================*/
/* Table: person                                                */
/*==============================================================*/
create table person
(
   person_id            int not null AUTO_INCREMENT,
   person_ident         text,
   person_name          text,
   person_surname       text,
   fb_checked			int DEFAULT \'0\',
   tw_checked			int DEFAULT \'0\',
   primary key (person_id)
);

/*==============================================================*/
/* Table: project                                               */
/*==============================================================*/
create table project
(
   project_id           int not null AUTO_INCREMENT,
   project_name         text,
   project_status		int DEFAULT \'1\',
   primary key (project_id)
);

/*==============================================================*/
/* Table: tw_account                                            */
/*==============================================================*/
create table tw_account
(
   tw_id                int not null AUTO_INCREMENT,
   person_id            int not null,
   tw_user_id           text,
   tw_username			text,
   tw_name				text,
   tw_status            int,
   primary key (tw_id)
);

alter table assignment add constraint FK_has_assignment foreign key (project_id)
      references project (project_id) on delete restrict on update restrict;

alter table fb_account add constraint FK_has_fb_account foreign key (person_id)
      references person (person_id) on delete restrict on update restrict;

alter table matches add constraint FK_Reference_5 foreign key (match_first_id)
      references person (person_id) on delete restrict on update restrict;

alter table matches add constraint FK_Reference_6 foreign key (match_second_id)
      references person (person_id) on delete restrict on update restrict;

alter table matches add constraint FK_has_match foreign key (assignment_id)
      references assignment (assignment_id) on delete restrict on update restrict;

alter table tw_account add constraint FK_has_tw_account foreign key (person_id)
      references person (person_id) on delete restrict on update restrict;

';
?>