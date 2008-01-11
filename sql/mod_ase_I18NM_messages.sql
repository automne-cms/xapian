##
## Contains declaration for module installation : 
## All messages (mandatory) : inject 2/2
##
## @version $Id: mod_ase_I18NM_messages.sql,v 1.8 2008/01/11 08:42:13 sebastien Exp $

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
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (33, 'ase', NOW(), 'Affiner votre recherche :', 'Expand your query:');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (34, 'ase', NOW(), 'Aide', 'Help');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (35, 'ase', NOW(), 'Pages:', 'Pages :');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (36, 'ase', NOW(), 'Votre recherche ne correspond à aucun document ...<br />Suggestions : Vérifiez l\'orthographe des termes de recherche, essayez d\'autres mots, utilisez des mots plus généraux.', 'Your search did not match any documents...<br />Suggestions: Make sure all words are spelled correctly, try different keywords, try more general keywords.');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (37, 'ase', NOW(), 'Votre recherche a entraîné une erreur, merci de la modifier ...', 'Your search involved an error, thank you to modify it...');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (38, 'ase', NOW(), 'Pourcentage de pertinence :', 'Relevance percentage:');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (39, 'ase', NOW(), 'Affiner la recherche à partir de ce document', 'Refine search using this document');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (40, 'ase', NOW(), 'Affiner la recherche à partir de ce document', 'Refine search using this document');
INSERT INTO `I18NM_messages` (`id`, `module`, `timestamp`, `fr`, `en`) VALUES (41, 'ase', NOW(), '<p>Les accents, majuscules ainsi que les termes vides de sens (le, les, de, du, etc.) ne sont pas pris en compte. Les recherches sont <a href="http://fr.wikipedia.org/wiki/Lemmatisation" target="_blank" alt="Voir la définition de Wikipedia" title="Voir la définition de Wikipedia">lemmatisées</a> (cheval équivaut à chevaux, documentation équivaut à documenter et inversement). Les mots commençant par une majuscule sont considérés comme des noms propres.</p>
	<h3>Affiner votre recherche :</h3>
	<p>Les termes proposés pour affiner votre recherche sont des termes importants dans les premiers documents renvoyés par votre recherche.</p>
	<p>Le lien "Affiner la recherche à partir de ce document" vous permet d\'identifier les documents qui vous semblent correspondre le plus à ce que vous recherchez pour relancer une recherche qui en tiendra compte.</p>
	<p>Si vos termes de recherche contiennent des mots dans une langue étrangère (anglais), sélectionner cette langue pour la recherche permettra une meilleur analyse lexicale de votre recherche et donc de meilleurs résultats.</p>
	<h3>Opérateurs :</h3>
	<table>
		<tr>
			<th>AND : </th>
			<td>Les documents résultant répondront aux deux termes.</td>
		</tr>
		<tr>
			<th>OR : </th>
			<td>Les documents résultant répondront à l\'un des deux termes.</td>
		</tr>
		<tr>
			<th>NOT : </th>
			<td>Les documents résultant répondront uniquement au terme de gauche.</td>
		</tr>
		<tr>
			<th>XOR : </th>
			<td>Les documents résultant répondront à l\'un des deux termes mais pas au deux.</td>
		</tr>
		<tr>
			<th>( et ) : </th>
			<td>Vous permet de grouper les expressions.</td>
		</tr>
		<tr>
			<th>+ et - : </th>
			<td>Opérateurs unaires. Les documents résultant répondront à tous les termes préfixés d\'un signe plus et à aucun des termes préfixés d\'un signe moins. <br />Exemple : +cheval -voiture</td>
		</tr>
		<tr>
			<th>NEAR : </th>
			<td>Les documents résultant contiendront les deux termes à 10 mots d\'intervalle maximum.<br />Exemple : cheval NEAR voiture</td>
		</tr>
		<tr>
			<th>" " : </th>
			<td>Permet une recherche de phrase exacte.</td>
		</tr>
		<tr>
			<th>* : </th>
			<td>Signe joker. Attention l\'emploi de cet opérateur peut ralentir votre recherche.</td>
		</tr>
	</table>
	<h3>Préfixes :</h3>
	<p>Les préfixes suivants vous permettent de restreindre vos recherches sur certaines caractéristiques de documents. Le terme doit suivre le préfixe directement (sans espaces). Vous pouvez combiner ces préfixes avec tout type de recherche par mots clés classique.</p>
	<table>
		<tr>
			<th>"title:" : </th>
			<td>Le terme suivant ce préfixe sera dans le titre du document.<br />Exemple : title:cheval</td>
		</tr>
		<tr>
			<th>"filetype:" : </th>
			<td>Les documents résultant seront des fichiers du format donné <br />Les formats disponibles sont : %s<br />Exemple : filetype:pdf</td>
		</tr>
		<tr>
			<th>"language:" : </th>
			<td>Les documents résultant seront dans la langue donnée <br />Les langues disponibles sont : fr, en <br />Exemple : language:fr</td>
		</tr>
		<!--<tr>
			<th>"page:" : </th>
			<td>Les documents résultant seront dans la page donnée<br />Example : page:12</td>
		</tr>
		<tr>
			<th>"root:" : </th>
			<td>Les documents résultant seront sous la page donnée<br />Example : root:12</td>
		</tr>-->
	</table>', '<p>Accents, capital letters as well as words with no proper meaning (the, that, of, a, etc.) will not be taken into account. The engine uses <a href="http://en.wikipedia.org/wiki/Stemming" target="_blank" alt="See Wikipedia definition" title="See Wikipedia definition">stemming</a> search (horse equals horses, documentation equals documents and vice versa). Words beginning with a capital letter will be considered as proper nouns.</p>
	<h3>Refine your search:</h3>
	<p>The terms suggested to refine your search are important words from the first documents resulting from your search.</p>
	<p>The link "Refine your search from this document" enables you to identify the most relevant documents and launch a new search taking them into account.</p>
	<p>If the terms you use for your search include words in a foreign language, select this language to enable a better lexical analysis of your demand, hence better results.</p>
	<h3>Operators:</h3>
	<table>
		<tr>
			<th>AND : </th>
			<td>The resulting documents will respond to both terms.</td>
		</tr>
		<tr>
			<th>OR : </th>
			<td>The resulting documents will respond to one of the terms.</td>
		</tr>
		<tr>
			<th>NOT : </th>
			<td>The resulting documents will only respond to the word on the left.</td>
		</tr>
		<tr>
			<th>XOR : </th>
			<td>The resulting documents will respond to one of the words but not to both.</td>
		</tr>
		<tr>
			<th>( and ) : </th>
			<td>Allows you to use a group of words.</td>
		</tr>
		<tr>
			<th>+ et - : </th>
			<td>The resulting documents will respond to all the terms preceeded by + and to no term preceeded by a -. Example: +horse -car</td>
		</tr>
		<tr>
			<th>NEAR : </th>
			<td>The resulting documents will include both terms separated by no more than 10 words.<br />Exemple: horse NEAR car</td>
		</tr>
		<tr>
			<th>" " : </th>
			<td>Allows to search for an exact sentence.</td>
		</tr>
		<tr>
			<th>* : </th>
			<td>Wildcard: Attention, the use of this sign may slow down your search.</td>
		</tr>
	</table>
	<h3>Prefixes :</h3>
	<p>The following prefixes enable you to limit your search to some characteristics of documents. The word has to follow the prefix immediately, with no space between them. You can combine these prefixes with any kind of classic keyword search.</p>
	<table>
		<tr>
			<th>"title:" : </th>
			<td>The word following this prefix will be part of the document's title.<br />Example: title:horse</td>
		</tr>
		<tr>
			<th>"filetype:" : </th>
			<td>This defines the type of document that will respond your search.<br />The available types of documents are: %s<br />Example: filetype:pdf</td>
		</tr>
		<tr>
			<th>"language:" : </th>
			<td>This defines the language used in the documents that will respond your search. The available languages are: fr, en<br />Example: language:fr</td>
		</tr>
		<!--<tr>
			<th>"page:" : </th>
			<td>The resulting documents will be located within the specified page.<br />Example: page:12</td>
		</tr>
		<tr>
			<th>"root:" : </th>
			<td>The resulting documents will be located within sub-pages of the specified root page.<br />Example : root:12</td>
		</tr>-->
	</table>');
INSERT INTO I18NM_messages (id, module, timestamp, fr, en) VALUES (42, 'ase', NOW(), '<strong>Moteur de recherche :</strong><br /><strong>&lt;block module=&quot;ase&quot; type=&quot;search&quot; language=&quot;</strong>code<strong>&quot;&gt;&lt;/block&gt;<br /></strong><ul><li><strong>code : </strong>Identifiant de la langue &agrave; utiliser : fr ou en</li></ul>', 'TODO');