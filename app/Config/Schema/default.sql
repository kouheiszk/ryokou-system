-- phpMyAdmin SQL Dump
-- version 2.11.2.2
-- http://www.phpmyadmin.net
--
-- ホスト: localhost
-- 生成時間: 2013 年 3 月 01 日 15:15
-- サーバのバージョン: 5.0.51
-- PHP のバージョン: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- データベース: `system`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `sys_acos`
--

CREATE TABLE IF NOT EXISTS `sys_acos` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `parent_id` int(11) unsigned default NULL,
  `model` varchar(255) default '',
  `foreign_key` int(11) unsigned default NULL,
  `alias` varchar(255) default '',
  `lft` int(11) unsigned default NULL,
  `rght` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- テーブルのデータをダンプしています `sys_acos`
--


-- --------------------------------------------------------

--
-- テーブルの構造 `sys_aros`
--

CREATE TABLE IF NOT EXISTS `sys_aros` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `parent_id` int(11) unsigned default NULL,
  `model` varchar(255) default '',
  `foreign_key` int(11) unsigned default NULL,
  `alias` varchar(255) default '',
  `lft` int(11) unsigned default NULL,
  `rght` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- テーブルのデータをダンプしています `sys_aros`
--


-- --------------------------------------------------------

--
-- テーブルの構造 `sys_aros_acos`
--

CREATE TABLE IF NOT EXISTS `sys_aros_acos` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `aro_id` int(11) unsigned NOT NULL,
  `aco_id` int(11) unsigned NOT NULL,
  `_create` char(2) NOT NULL default '0',
  `_read` char(2) NOT NULL default '0',
  `_update` char(2) NOT NULL default '0',
  `_delete` char(2) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- テーブルのデータをダンプしています `sys_aros_acos`
--


-- --------------------------------------------------------

--
-- テーブルの構造 `sys_blogs`
--

CREATE TABLE IF NOT EXISTS `sys_blogs` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user_id` int(11) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` varchar(255) NOT NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  `status` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- テーブルのデータをダンプしています `sys_blogs`
--


-- --------------------------------------------------------

--
-- テーブルの構造 `sys_groups`
--

CREATE TABLE IF NOT EXISTS `sys_groups` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  `status` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- テーブルのデータをダンプしています `sys_groups`
--


-- --------------------------------------------------------

--
-- テーブルの構造 `sys_users`
--

CREATE TABLE IF NOT EXISTS `sys_users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `group_id` int(11) unsigned NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  `status` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- テーブルのデータをダンプしています `sys_users`
--

