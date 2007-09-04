##
## Contains declaration for module installation : 
## All table creation (mandatory) : inject 1/2
##
## @version $Id: mod_ase.sql,v 1.1 2007/09/04 15:23:47 sebastien Exp $

# --------------------------------------------------------

# 
# Structure de la table `mod_ase_document`
# 

DROP TABLE IF EXISTS `mod_ase_document`;
CREATE TABLE `mod_ase_document` (
  `id_mased` int(11) unsigned NOT NULL auto_increment,
  `xid_mased` varchar(255) NOT NULL default '',
  `uid_mased` varchar(255) NOT NULL default '',
  `module_mased` varchar(20) NOT NULL default '',
  `language_mased` char(2) NOT NULL default '',
  `type_mased` varchar(10) NOT NULL default '',
  PRIMARY KEY  (`id_mased`),
  UNIQUE KEY `mod-uid` (`uid_mased`,`module_mased`),
  KEY `xid_mased` (`xid_mased`),
  KEY `uid_mased` (`uid_mased`),
  KEY `module_mased` (`module_mased`)
) TYPE=MyISAM;

#
# Contenu de la table `modules`
#

INSERT INTO `modules` (`id_mod`, `label_mod`, `codename_mod`, `administrationFrontend_mod`, `hasParameters_mod`, `isPolymod_mod`) VALUES 
('', 1, 'ase', 'index.php', 1, 0);
