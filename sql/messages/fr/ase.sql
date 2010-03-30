#----------------------------------------------------------------
#
# Messages content for module ase
# French Messages
#
#----------------------------------------------------------------
# $Id: ase.sql,v 1.1 2010/01/25 16:31:57 sebastien Exp $

DELETE FROM messages WHERE module_mes = 'ase' and language_mes = 'fr';

INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(1, 'ase', 'fr', 'Moteur de Recherche');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(2, 'ase', 'fr', 'ASE : Automne Search Engine');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(3, 'ase', 'fr', 'La librairie Xapian n''existe pas sur votre système, le module ne fonctionne pas ... Merci de contacter votre <a href="mailto:%s" class="admin">administrateur système</a>.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(4, 'ase', 'fr', 'Version de Xapian : %s');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(5, 'ase', 'fr', 'Filtres de contenu actifs :');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(6, 'ase', 'fr', 'Microsoft Word');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(7, 'ase', 'fr', 'Microsoft Excel');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(8, 'ase', 'fr', 'PDF');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(9, 'ase', 'fr', 'Open Office');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(10, 'ase', 'fr', 'Microsoft PowerPoint');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(11, 'ase', 'fr', 'HTML');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(12, 'ase', 'fr', 'Texte Brut');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(13, 'ase', 'fr', 'Filtre inconnu');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(14, 'ase', 'fr', 'Filtre');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(15, 'ase', 'fr', 'Extensions supportées');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(16, 'ase', 'fr', 'Inactif');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(17, 'ase', 'fr', 'Indexation active');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(18, 'ase', 'fr', 'Inactif');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(19, 'ase', 'fr', 'Taille de l''index');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(20, 'ase', 'fr', 'Nombre de documents indexés');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(21, 'ase', 'fr', 'Réindexer');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(22, 'ase', 'fr', 'Confirmez-vous la réindexation complète du contenu du module ?<br /><br />Attention, durant la réindexation le moteur ne fournira plus de résultats pertinents pour le module.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(23, 'ase', 'fr', 'Moteur de recherche : Interrogation de l''element ''%s''');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(24, 'ase', 'fr', 'Moteur de recherche : Indexation de l''element ''%s''');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(25, 'ase', 'fr', 'Moteur de recherche : Suppression de l''element ''%s''');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(26, 'ase', 'fr', 'Version minimum nécessaire : %s');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(27, 'ase', 'fr', 'Rechercher');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(28, 'ase', 'fr', 'Résultats %s - %s sur un total de %s pour votre recherche');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(29, 'ase', 'fr', 'Essayez avec cette orthographe : ');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(30, 'ase', 'fr', 'Résultats : ');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(31, 'ase', 'fr', 'Indexé le');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(32, 'ase', 'fr', 'Publié le');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(33, 'ase', 'fr', 'Affiner votre recherche :');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(34, 'ase', 'fr', 'Aide');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(35, 'ase', 'fr', 'Pages:');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(36, 'ase', 'fr', 'Votre recherche ne correspond à aucun document ...<br />Suggestions : Vérifiez l''orthographe des termes de recherche, essayez d''autres mots, utilisez des mots plus généraux.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(37, 'ase', 'fr', 'Votre recherche a entraîné une erreur, merci de la modifier ...');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(38, 'ase', 'fr', 'Pourcentage de pertinence :');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(39, 'ase', 'fr', 'Affiner la recherche à partir de ce document');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(40, 'ase', 'fr', 'Affiner la recherche à partir de ce document');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(41, 'ase', 'fr', '<p>Les accents, majuscules ainsi que les termes vides de sens (le, les, de, du, etc.) ne sont pas pris en compte. Les recherches sont <a href="http://fr.wikipedia.org/wiki/Lemmatisation" target="_blank" alt="Voir la définition de Wikipedia" title="Voir la définition de Wikipedia">lemmatisées</a> (cheval équivaut à chevaux, documentation équivaut à documenter et inversement). Les mots commençant par une majuscule sont considérés comme des noms propres.</p>\n	<h3>Affiner votre recherche :</h3>\n	<p>Les termes proposés pour affiner votre recherche sont des termes importants dans les premiers documents renvoyés par votre recherche.</p>\n	<p>Le lien "Affiner la recherche à partir de ce document" vous permet d''identifier les documents qui vous semblent correspondre le plus à ce que vous recherchez pour relancer une recherche qui en tiendra compte.</p>\n	<p>Si vos termes de recherche contiennent des mots dans une langue étrangère (anglais), sélectionner cette langue pour la recherche permettra une meilleur analyse lexicale de votre recherche et donc de meilleurs résultats.</p>\n	<h3>Opérateurs :</h3>\n	<table>\n		<tr>\n			<th>AND : </th>\n			<td>Les documents résultant répondront aux deux termes.</td>\n		</tr>\n		<tr>\n			<th>OR : </th>\n			<td>Les documents résultant répondront à l''un des deux termes.</td>\n		</tr>\n		<tr>\n			<th>NOT : </th>\n			<td>Les documents résultant répondront uniquement au terme de gauche.</td>\n		</tr>\n		<tr>\n			<th>XOR : </th>\n			<td>Les documents résultant répondront à l''un des deux termes mais pas au deux.</td>\n		</tr>\n		<tr>\n			<th>( et ) : </th>\n			<td>Vous permet de grouper les expressions.</td>\n		</tr>\n		<tr>\n			<th>+ et - : </th>\n			<td>Opérateurs unaires. Les documents résultant répondront à tous les termes préfixés d''un signe plus et à aucun des termes préfixés d''un signe moins. <br />Exemple : +cheval -voiture</td>\n		</tr>\n		<tr>\n			<th>NEAR : </th>\n			<td>Les documents résultant contiendront les deux termes à 10 mots d''intervalle maximum.<br />Exemple : cheval NEAR voiture</td>\n		</tr>\n		<tr>\n			<th>" " : </th>\n			<td>Permet une recherche de phrase exacte.</td>\n		</tr>\n		<tr>\n			<th>* : </th>\n			<td>Signe joker. Attention l''emploi de cet opérateur peut ralentir votre recherche.</td>\n		</tr>\n	</table>\n	<h3>Préfixes :</h3>\n	<p>Les préfixes suivants vous permettent de restreindre vos recherches sur certaines caractéristiques de documents. Le terme doit suivre le préfixe directement (sans espaces). Vous pouvez combiner ces préfixes avec tout type de recherche par mots clés classique.</p>\n	<table>\n		<tr>\n			<th>"title:" : </th>\n			<td>Le terme suivant ce préfixe sera dans le titre du document.<br />Exemple : title:cheval</td>\n		</tr>\n		<tr>\n			<th>"filetype:" : </th>\n			<td>Les documents résultant seront des fichiers du format donné <br />Les formats disponibles sont : %s<br />Exemple : filetype:pdf</td>\n		</tr>\n		<tr>\n			<th>"language:" : </th>\n			<td>Les documents résultant seront dans la langue donnée <br />Les langues disponibles sont : fr, en <br />Exemple : language:fr</td>\n		</tr>\n		<!--<tr>\n			<th>"page:" : </th>\n			<td>Les documents résultant seront dans la page donnée<br />Example : page:12</td>\n		</tr>\n		<tr>\n			<th>"root:" : </th>\n			<td>Les documents résultant seront sous la page donnée<br />Example : root:12</td>\n		</tr>-->\n	</table>');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(42, 'ase', 'fr', '<div class="rowComment">\r\n<h1>Afficher les r&eacute;sultats d''une recherche :</h1>\r\n<span class="code"> &lt;block module=&quot;ase&quot; type=&quot;search&quot; language=&quot;<span class="keyword">code</span>&quot;&gt;&lt;/block&gt;</span>\r\n<ul>\r\n    <li><span class="keyword">code </span>: Identifiant de la langue &agrave; utiliser : <span class="vertclair">fr </span>ou <span class="vertclair">en</span>. Cet attribut <span class="keyword">language </span>est optionnel. Si il n''est pas pr&eacute;sent, la langue de la page ou est ins&eacute;r&eacute; la rang&eacute;e sera employ&eacute;e.</li>\r\n</ul>\r\n<p>Ce tag vous permet d''afficher la page par d&eacute;faut du moteur de recherche qui comporte un champ de recherche et les r&eacute;sultats des recherches effectu&eacute;es.</p>\r\n<p>Si la page est appel&eacute;e avec un param&egrave;tre &quot;q&quot;, une recherche sera automatiquement lanc&eacute;e avec la valeur du param&egrave;tre. </p>\r\n<p>Cela permet de cr&eacute;er des petits moteurs de recherche facilement sur votre site &agrave; l''aide d''un code similaire &agrave; celui ci-dessous :</p>\r\n<div class="code">&lt;atm-linx type=&quot;direct&quot;&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &lt;selection&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &lt;start&gt;&lt;nodespec type=&quot;node&quot; value=&quot;<span class="keyword">pageID</span>&quot; /&gt;&lt;/start&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &lt;/selection&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &lt;display&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &lt;htmltemplate&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &lt;form action=&quot;{{href}}&quot; method=&quot;get&quot;&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &lt;input class=&quot;input&quot; type=&quot;text&quot; name=&quot;q&quot; value=&quot;&quot; /&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &lt;input class=&quot;send&quot; type=&quot;submit&quot; value=&quot;Rechercher&quot; /&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &lt;/form&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &lt;/htmltemplate&gt;<br />\r\n&nbsp;&nbsp;&nbsp; &lt;/display&gt;<br />\r\n&lt;/atm-linx&gt;&nbsp; &nbsp; </div>\r\n</div>\r\n<ul>\r\n    <li><span class="keyword">pageID </span>: Identifiant de la page ou se trouve la rang&eacute;e du moteur de recherche.</li>\r\n</ul>');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(43, 'ase', 'fr', 'Microsoft Word 2007');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(44, 'ase', 'fr', 'Microsoft Excel 2007');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(45, 'ase', 'fr', 'Support des textes Japonais');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(46, 'ase', 'fr', 'Binaire manquant');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(47, 'ase', 'fr', 'Modules indexés');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(48, 'ase', 'fr', 'Consulter les modules indexés et réindexer un module');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(49, 'ase', 'fr', 'Configuration');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(50, 'ase', 'fr', 'Configuration du moteur');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(51, 'ase', 'fr', 'Consulter l''état de fonctionnement du moteur et les filtres de documents actifs');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(52, 'ase', 'fr', 'Rafraichir les informations sur l''index');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(53, 'ase', 'fr', 'Réindexer le module : En cas de problème sur les résultats de recherche (incomplets ou erronés), il peut être nécessaire de réindexer le contenu d''un module pour mettre à jour toutes les informations du moteur de recherche.');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(54, 'ase', 'fr', 'Pages de recherches employées pour Open Search :');
INSERT INTO `messages` (`id_mes`, `module_mes`, `language_mes`, `message_mes`) VALUES(55, 'ase', 'fr', 'Pages racines des arborescences exclues de l''indexation :');
