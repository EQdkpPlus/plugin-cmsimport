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
	'cmsimport_short_desc'       	=> 'Import Data from other CMS',
	'cmsimport_long_desc'        	=> 'Import Data from other CMS, like Wordpress or Joomla.',
	  
	'ci_plugin_not_installed'		=> 'The SK Startorder-Plugin is not installed.',
	'ci_import'        			=> 'Import',
	'ci_continue'					=> 'Continue',
	'ci_db_types'	=> array(
			'Same database', 'Other database', 'Use bridge connection'
	),
	'ci_fs_general' => 'Importer Settings',
	'ci_f_import_type' => 'Select Importer',
	'ci_f_url' => 'URL to your CMS',
	'ci_f_help_url' => 'Complete URL to your CMS, including protocoll',
	'ci_fs_connection' => 'Connection Settings',
	'ci_f_db_type' => 'Select how to connect to the CMS',
	'ci_f_db_host'	=> 'Database host',
	'ci_f_db_user'	=> 'Database user',
	'ci_f_db_password' => 'Database password',
	'ci_f_db_database' => 'Database name',
	'ci_f_db_prefix'	=> 'Installation prefix',
	'ci_f_help_db_prefix'	=> 'The prefix of the Master EQdkp Plus Installation, e.g. "eqdkp20_"',
	'ci_conn_error' => 'No connection possible with CMS. Please check your settings.',
	'ci_select_steps' => 'Select Steps',
	'ci_select_all_steps' => 'Select all Steps',
 );

?>
