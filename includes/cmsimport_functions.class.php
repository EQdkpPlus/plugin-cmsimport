<?php
/*	Project:	EQdkp-Plus
 *	Package:	Shoutbox Plugin
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

if (!defined('EQDKP_INC')){
	header('HTTP/1.0 404 Not Found');exit;
}


/*+----------------------------------------------------------------------------
 | ShoutboxClass
 +--------------------------------------------------------------------------*/
if (!class_exists("CMSImportFunctions")){
	class CMSImportFunctions extends gen_class{
		
		public function getAvailableImporters(){
			$arrImporters = sdir($this->root_path.'plugins/cmsimport/importer', '*_importer.class.php', '_importer.class.php');
			return $arrImporters;
		}
		
		public function getImporterClass($strImportername){
			include_once($this->root_path.'plugins/cmsimport/includes/importer_generic.class.php');
			
			if(is_file($this->root_path.'plugins/cmsimport/importer/'.$strImportername.'_importer.class.php')){
				include_once($this->root_path.'plugins/cmsimport/importer/'.$strImportername.'_importer.class.php');
				$objImporter = register($strImportername.'_importer');
				if($objImporter) return $objImporter;
			}
			
			return false;
		}
		
		public function calculateNextStep($currentStepID, $currentStepType){
			$arrValues = $this->config->get('general_data', 'cmsimport');
			$arrSelectedSteps = $this->config->get('steps', 'cmsimport');
			$objImporter = $this->getImporterClass($arrValues['import_type']);
			
			$arrSteps = $objImporter->availableSteps();
			if($currentStepID == "0") {
				$intKey = -1;
				$currentStepID = $arrSteps[0];
			} else {
				$intKey = array_search($currentStepID, $arrSteps);
			}
			
			if($currentStepType == "output"){
				//Next is normal method
				return array($currentStepID, "method");
				
			} elseif(!isset($arrSteps[$intKey+1])){
				//End if next is not available
				return array("end", "method"); 
			} else {
				//Next is normal method or output method if available
				$nextKey = $intKey+1;
				$nextStepname = $arrSteps[$nextKey];
				
				if(!in_array($nextStepname, $arrSelectedSteps) && $nextStepname != "end"){
					
					return $this->calculateNextStep($nextStepname, "method");
				}
				
				if(method_exists($objImporter, 'step_'.$nextStepname.'_output')){
					return array($nextStepname, "output");
				} else {
					return array($nextStepname, "method");
				}
			}
		}
		
		public function checkConnection($strImporter, $db_type, $db_host, $db_user, $db_password, $db_database, $db_prefix){
			if((int)$db_type == 0){
				//Same DB
				try {
					$mydb = dbal::factory(array('dbtype' => registry::get_const('dbtype'), 'open'=>true, 'debug_prefix' => 'cmsimport_', 'table_prefix' => trim($db_prefix)));
				} catch(DBALException $e){
					return $e->getMessage();
					$mydb = false;
				}
			} elseif((int)$db_type == 1){
				//Other DB
				try {
					$mydb = dbal::factory(array('dbtype' => registry::get_const('dbtype'), 'debug_prefix' => 'cmsimport_', 'table_prefix' => trim($db_prefix)));
					$mydb->connect($db_host, $db_database, $db_user, $db_password);
				} catch(DBALException $e){
					return $e->getMessage();
					$mydb = false;
				}
			} elseif((int)$db_type == 2){
				//Bridge
				$mydb = $this->bridge->bridgedb;
			}
		
			if ($mydb){
				$objImporter = $this->getImporterClass($strImporter);
				if($objImporter){
					$blnResult = $objImporter->checkConnection($mydb);
				}

				return $blnResult;
			}
			return false;
		}
		
		public function createConnection(){
			$arrOptions = $this->config->get('general_data', 'cmsimport');
			$db_type = $arrOptions['db_type'];
			$db_host = $arrOptions['db_host']; 
			$db_user = $arrOptions['db_user'];
			$db_password = $arrOptions['db_password'];
			$db_database = $arrOptions['db_database'];
			$db_prefix = $arrOptions['db_prefix'];
			
			if((int)$db_type == 0){
				//Same DB
				try {
					$mydb = dbal::factory(array('dbtype' => registry::get_const('dbtype'), 'open'=>true, 'debug_prefix' => 'sso_connector_', 'table_prefix' => trim($db_prefix)));
				} catch(DBALException $e){
					$mydb = false;
				}
			} elseif((int)$db_type == 1){
				//Other DB
				try {
					$mydb = dbal::factory(array('dbtype' => registry::get_const('dbtype'), 'debug_prefix' => 'sso_connector_', 'table_prefix' => trim($db_prefix)));
					$mydb->connect($db_host, $db_database, $db_user, $db_password);
				} catch(DBALException $e){
					$mydb = false;
				}
			} elseif((int)$db_type == 2){
				//Bridge
				$mydb = $this->bridge->bridgedb;
			}
		
			return $mydb;
		}
		
	}
}