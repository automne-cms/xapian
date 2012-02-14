#----------------------------------------------------------------
# Messages content for module cms_i18n
# Language : de
#----------------------------------------------------------------

DELETE FROM messages WHERE module_mes = 'cms_i18n' and language_mes = 'de';

INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(27, 'cms_i18n', 'de', 'Suchen');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(28, 'cms_i18n', 'de', 'Ergebnis %s - %s von ca. %s Ergebnissen für Ihre Suche');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(29, 'cms_i18n', 'de', 'Meinten Sie:');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(30, 'cms_i18n', 'de', 'Ergebnis:');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(33, 'cms_i18n', 'de', 'Erweitern Sie Ihre Suche:');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(36, 'cms_i18n', 'de', 'Ihr Suche brachte keine Ergebnisse. <br />\nBitte versichern Sie sich, dass das Suchwort korrekt geschrieben ist, versuchen Sie andere Suchwörter oder allgemeinere Begriffe.\n');


