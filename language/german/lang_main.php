<?php
/*	Project:	EQdkp-Plus
 *	Package:	MediaCenter Plugin
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2015 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('EQDKP_INC'))
{
    header('HTTP/1.0 404 Not Found');exit;
}

$lang = array(
	'cmsimport'                  	=> 'CMS-Import',
	
	// Description
	'cmsimport_short_desc'       	=> 'Importiere Daten von anderen CMS',
	'cmsimport_long_desc'        	=> 'Importiere Daten von anderen CMS wie Wordpress oder Joomla',
	  
	'ci_plugin_not_installed'		=> 'Das CMS-Import-Plugin ist nicht installiert.',
	'ci_import'        				=> 'Importieren',
	'ci_continue'					=> 'Weiter',
	'ci_db_types'	=> array(
			'Selbe Datenbank', 'Andere Datenbank', 'Bridge-Verbindung verwenden'
	),		
	'ci_fs_general' => 'Importer-Einstellungen',
	'ci_f_import_type' => 'Wähle das zu importierende System aus',
	'ci_f_url' => 'Link zu deinem CMS',
	'ci_f_help_url' => 'Trage hier den kompletten Link zu deinem CMS ein',
	'ci_fs_connection' => 'Verbindungs-Einstellungen',
	'ci_f_db_type' => 'Wähle aus, wie das andere CMS zu erreichen ist.',
	'ci_f_db_host'	=> 'Datenbank-Host',
	'ci_f_db_user'	=> 'Datenbank-Benutzer',
	'ci_f_db_password' => 'Datenbank-Passwort',
	'ci_f_db_database' => 'Datenbank-Name',
	'ci_f_db_prefix'	=> 'Installations-Prefix',
	'ci_f_help_db_prefix'	=> 'Das Prefix der Master EQdkp Plus Installation, z.B. "wp42_"',
	'ci_conn_error' => 'Es konnte keine Verbindung zum anderen CMS hergestellt werden. Bitte überprüfe deine Einstellungen.',
	'ci_select_steps' => 'Schritte auswählen',
	'ci_select_all_steps' => 'Wähle alle Schritte aus',
	'ci_step' => 'Schritt',
	'ci_import_error' => 'Ein Fehler ist während des importierens aufgetreten. Bitte versuche den Import erneut.',			
	'ci_step_user' => 'Benutzer',
	'ci_step_pages'=> 'Seiten',
	'ci_step_posts'=> 'Beiträge',
	'ci_user_import_hint' => 'Bitte beachte, dass nur die Benutzer importiert werden, und keine Zuordnungen zu Benutzergruppen. Die Benutzer werden in die Standardgruppe hinzugefügt.<br /><br />Beachte bitte auch, dass keine Passwörter importiert werden können. Importierte Benutzer sollen ein neues Passwort anfordern, sofern keine CMS-Bridge verwendet wird.',
	'ci_imported_users' => 'Importierte Benutzer',
	'ci_imported_pages' => 'Importierte Seiten',
	'ci_imported_posts' => 'Importierte Beiträge',
	'ci_end_message' => 'Der Import wurde beendet. Bitte überprüfe die Berechtigungen aller importierten Objekte.',
	'ci_default_user_pages' => 'Wähle einen Benutzer aus für Artikel ohne Benutzer',
	'ci_default_category_posts' => 'Wähle eine Kategorie für die zu importierenden Beiträge aus',
	'ci_default_category_pages' => 'Wähle eine Kategorie für die zu importierenden Seiten aus',
 );

?>
