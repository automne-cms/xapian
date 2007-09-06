##
## Contains declaration for module installation : 
## All messages (mandatory) : inject 2/2
##
## @version $Id: mod_ase_I18NM_messages.sql,v 1.2 2007/09/06 16:36:03 sebastien Exp $

DELETE FROM I18NM_messages WHERE module='ase';

INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (1, 'ase', NOW(), 'Moteur de Recherche', 'Search Engine');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (2, 'ase', NOW(), 'ASE : Automne Search Engine', 'ASE : Automne Search Engine');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (3, 'ase', NOW(), 'La librairie Xapian n''existe pas sur votre système, le module ne fonctionne pas ... Merci de contacter votre <a href="mailto:%s" class="admin">administrateur système</a>.', 'Xapian librairie does not exists on your system, module is not running ... Please contact your <a href="mailto:%s" class="admin">system administrator</a>.');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (4, 'ase', NOW(), 'Version de Xapian : %s', 'Xapian version: %s');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (5, 'ase', NOW(), 'Filtres de contenu actifs :', 'Active content filters:');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (6, 'ase', NOW(), 'Microsoft Word', 'Microsoft Word');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (7, 'ase', NOW(), 'Microsoft Excel', 'Microsoft Excel');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (8, 'ase', NOW(), 'PDF', 'PDF');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (9, 'ase', NOW(), 'Open Office', 'Open Office');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (10, 'ase', NOW(), 'Microsoft PowerPoint', 'Microsoft PowerPoint');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (11, 'ase', NOW(), 'HTML', 'HTML');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (12, 'ase', NOW(), 'Texte Brut', 'Plain text');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (13, 'ase', NOW(), 'Filtre inconnu', 'Unknown filter');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (14, 'ase', NOW(), 'Filtre', 'Filter');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (15, 'ase', NOW(), 'Extensions supportées', 'Supported extensions');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (16, 'ase', NOW(), 'Inactif (binaires requis non trouvés)', 'Inactive (required binaries not found)');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (17, 'ase', NOW(), 'Actif', 'Active');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (18, 'ase', NOW(), 'Inactif', 'Inactive');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (19, 'ase', NOW(), 'Taille de l''index', 'Index size');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (20, 'ase', NOW(), 'Nombre de documents indexés', 'Number of indexed documents');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (21, 'ase', NOW(), 'Réindexer', 'Reindex');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (22, 'ase', NOW(), 'Confirmez-vous la réindexation complète du contenu du module ''%s'' ?', 'Do you confirm content reindexation for module ''%s''?');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (23, 'ase', NOW(), 'Moteur de recherche : Interrogation de l''element ''%s''', 'Search engine : Query element ''%s''');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (24, 'ase', NOW(), 'Moteur de recherche : Indexation de l''element ''%s''', 'Search engine : Indexing element ''%s''');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (25, 'ase', NOW(), 'Moteur de recherche : Suppression de l''element ''%s''', 'Search engine : Delete element ''%s''');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (26, 'ase', NOW(), 'Version minimum nécessaire : %s', 'Minimum version needed: %s');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (27, 'ase', NOW(), 'Rechercher', 'Search');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (28, 'ase', NOW(), 'Résultats %s - %s sur un total de %s pour votre recherche', 'Results %s - %s of about %s for your query');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (29, 'ase', NOW(), 'Essayez avec cette orthographe : ', 'Did you mean:');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (30, 'ase', NOW(), 'Résultats : ', 'Results:');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (31, 'ase', NOW(), 'Indexé le', 'Indexed on');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (32, 'ase', NOW(), 'Publié le', 'Published on');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (33, 'ase', NOW(), 'Etendre votre recherche :', 'Expand your query:');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (34, 'ase', NOW(), 'Aide', 'Help');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (35, 'ase', NOW(), 'Pages:', 'Pages :');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (36, 'ase', NOW(), 'Votre recherche ne correspond à aucun document ...', 'Your search did not match any documents...');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (37, 'ase', NOW(), 'Votre recherche a entraîné une erreur, merci de la modifier ...', 'Your search involved an error, thank you to modify it...');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (38, 'ase', NOW(), 'Pourcentage de pertinence :', 'Relevance percentage:');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (39, 'ase', NOW(), 'Ce document est plus pertinent', 'This document is more relevant');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (40, 'ase', NOW(), 'Relancer la recherche en utilisant cette information.', 'Reload search using this information.');
