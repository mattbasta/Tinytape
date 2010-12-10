-- phpMyAdmin SQL Dump
-- version 3.3.1-rc1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 10, 2010 at 01:09 AM
-- Server version: 5.0.51
-- PHP Version: 5.2.6-1+lenny9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `tinytape`
--

-- --------------------------------------------------------

--
-- Table structure for table `songs`
--

DROP TABLE IF EXISTS `songs`;
CREATE TABLE IF NOT EXISTS `songs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(512) NOT NULL,
  `artist` varchar(512) NOT NULL,
  `album` varchar(512) NOT NULL,
  PRIMARY KEY  (`id`),
  FULLTEXT KEY `search` (`title`,`artist`,`album`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1356 ;

-- --------------------------------------------------------

--
-- Table structure for table `song_instance`
--

DROP TABLE IF EXISTS `song_instance`;
CREATE TABLE IF NOT EXISTS `song_instance` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `song_id` int(10) unsigned NOT NULL,
  `service` varchar(16) NOT NULL,
  `service_resource` varchar(256) NOT NULL,
  `version_name` text NOT NULL,
  `acoustic` tinyint(1) NOT NULL,
  `clean` tinyint(1) NOT NULL,
  `live` tinyint(1) NOT NULL,
  `live_event` text NOT NULL,
  `remix` tinyint(1) NOT NULL,
  `remix_name` text NOT NULL,
  `remix_artist` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `song_id` (`song_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=989 ;

-- --------------------------------------------------------

--
-- Table structure for table `song_reference`
--

DROP TABLE IF EXISTS `song_reference`;
CREATE TABLE IF NOT EXISTS `song_reference` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `song_id` int(10) unsigned NOT NULL,
  `tape_name` varchar(32) NOT NULL,
  `index` int(10) unsigned NOT NULL default '0',
  `song_instance` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `tape_id` (`tape_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1285 ;

-- --------------------------------------------------------

--
-- Table structure for table `tapes`
--

DROP TABLE IF EXISTS `tapes`;
CREATE TABLE IF NOT EXISTS `tapes` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `user` varchar(32) NOT NULL default '',
  `title` varchar(128) NOT NULL default '',
  `color` varchar(6) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `user` (`user`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=147 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `username` varchar(32) NOT NULL default '',
  `password` varchar(40) NOT NULL default '',
  `email` text NOT NULL,
  `activated` tinyint(1) NOT NULL default '0',
  `admin` tinyint(1) NOT NULL,
  `optout` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=95 ;
