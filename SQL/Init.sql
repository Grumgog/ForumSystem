drop database if exists forumsystems;
create database if not exists forumsystems;
use forumsystems;

create table Forum_metainfo(
	keyv varchar(50) primary key,
    valuev varchar(50)
);

create table forums (
	forum_name varchar(50)  primary key,
    description varchar(250)
);

create table users (
	email varchar(75) primary key,
    login varchar(50),
    password varchar(255),
    registration_date datetime,
    session_id varchar(125)
);

create table forum_themes (
	id int auto_increment primary key,
    forum_name varchar(50),
    title varchar(100),
    content varchar(250),
    attachments blob,
    user_email varchar(75),
	FOREIGN KEY (user_email) REFERENCES users(email),
    FOREIGN KEY (forum_name) REFERENCES forums(forum_name)
);

create table forum_post (
	id int auto_increment primary key,
    forum_theme int,
    title varchar(50),
    content varchar(250),
    attachments blob,
    user_email varchar(75),
	FOREIGN KEY (user_email) REFERENCES users(email),
    FOREIGN KEY (forum_theme) REFERENCES forum_themes(id)
);
