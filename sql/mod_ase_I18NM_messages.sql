##
## Contains declaration for module installation : 
## All messages (mandatory) : inject 2/2
##
## @version $Id: mod_ase_I18NM_messages.sql,v 1.1 2007/09/04 15:23:47 sebastien Exp $

DELETE FROM I18NM_messages WHERE module='ase';


INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (1, 'ase', '20070307185941', 'Moteur de Recherche', 'Search Engine');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (2, 'ase', '20070307185941', 'ASE : Automne Search Engine', 'ASE : Automne Search Engine');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (3, 'ase', '20070307185941', 'La librairie Xapian n''existe pas sur votre système, le module ne fonctionne pas ... Merci de contacter votre <a href="mailto:%s" class="admin">administrateur système</a>.', 'Xapian librairie does not exists on your system, module is not running ... Please contact your <a href="mailto:%s" class="admin">system administrator</a>.');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (4, 'ase', '20070307185941', 'Version de Xapian : %s', 'Xapian version: %s');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (5, 'ase', '20070307185941', 'Filtres de contenu actifs :', 'Active content filters:');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (6, 'ase', '20070307185941', 'Microsoft Word', 'Microsoft Word');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (7, 'ase', '20070307185941', 'Microsoft Excel', 'Microsoft Excel');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (8, 'ase', '20070307185941', 'PDF', 'PDF');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (9, 'ase', '20070307185941', 'Open Office', 'Open Office');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (10, 'ase', '20070307185941', 'Microsoft PowerPoint', 'Microsoft PowerPoint');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (11, 'ase', '20070307185941', 'HTML', 'HTML');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (12, 'ase', '20070307185941', 'Texte Brut', 'Plain text');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (13, 'ase', '20070307185941', 'Filtre inconnu', 'Unknown filter');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (14, 'ase', '20070307185941', 'Filtre', 'Filter');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (15, 'ase', '20070307185941', 'Extensions supportées', 'Supported extensions');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (16, 'ase', '20070307185941', 'Inactif (binaires requis non trouvés)', 'Inactive (required binaries not found)');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (17, 'ase', '20070307185941', 'Actif', 'Active');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (18, 'ase', '20070307185941', 'Inactif', 'Inactive');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (19, 'ase', '20070307185941', 'Taille de l''index', 'Index size');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (20, 'ase', '20070307185941', 'Nombre de documents indexés', 'Number of indexed documents');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (21, 'ase', '20070307185941', 'Réindexer', 'Reindex');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (22, 'ase', '20070307185941', 'Confirmez-vous la réindexation complète du contenu du module ''%s'' ?', 'Do you confirm content reindexation for module ''%s''?');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (23, 'ase', '20070307185941', 'Moteur de recherche : Interrogation de l''element ''%s''', 'Search engine : Query element ''%s''');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (24, 'ase', '20070307185941', 'Moteur de recherche : Indexation de l''element ''%s''', 'Search engine : Indexing element ''%s''');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (25, 'ase', '20070307185941', 'Moteur de recherche : Suppression de l''element ''%s''', 'Search engine : Delete element ''%s''');
