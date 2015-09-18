<?php
/*	Project:	EQdkp-Plus
 *	Package:	CMS-Import Plugin
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

// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);
define('PLUGIN', 'cmsimport');

$eqdkp_root_path = './../../../';
include_once($eqdkp_root_path.'common.php');


/*+----------------------------------------------------------------------------
  | CMSAdminImport
  +--------------------------------------------------------------------------*/
class CMSAdminImport extends page_generic
{
  /**
   * __dependencies
   * Get module dependencies
   */
  public static function __shortcuts()
  {
    $shortcuts = array('pm', 'user', 'config', 'core', 'in', 'jquery', 'html', 'tpl');
    return array_merge(parent::$shortcuts, $shortcuts);
  }
  
  private $objCIFunctions;
  private $arrFieldData = array();

  /**
   * Constructor
   */
  public function __construct()
  {
    // plugin installed?
    if (!$this->pm->check('cmsimport', PLUGIN_INSTALLED))
      message_die($this->user->lang('ci_plugin_not_installed'));

    $handler = array(
    	'step'  => array('process' => 'handle_steps', 'csrf' => true),
    	'step0' => array('process' => 'handle_step0', 'csrf' => true),
    );
	
	$this->user->check_auth('a_cmsimport_manage');
	
	include_once $this->root_path.'plugins/cmsimport/includes/cmsimport_functions.class.php';
	$this->objCIFunctions = register('CMSImportFunctions');
	
    parent::__construct(null, $handler);

    $this->process();
  }
  
  public function handle_step0(){
  	$arrFields = $this->fields();
  	$form = register('form', array('ci_import'));
  	$form->reset_fields();
  	$form->use_fieldsets = true;
  	$form->use_dependency = true;
  	$form->lang_prefix = 'ci_';
  	$form->add_fieldsets($arrFields);
  	$arrValues = $form->return_values();
  	$this->arrFieldData = $arrValues;
  	
  	//Make Connection Test
  	$blnResult = $this->objCIFunctions->checkConnection($arrValues['import_type'], $arrValues['db_type'], $arrValues['db_host'], $arrValues['db_user'], $arrValues['db_password'], $arrValues['db_database'], $arrValues['db_prefix']);
  	if($blnResult){
  		//Save Settings
  		$this->config->set('general_data', $arrValues, 'cmsimport');
  		
  		//Display the different import types
  		$objImporter = $this->objCIFunctions->getImporterClass($arrValues['import_type']);
  		if($objImporter){
  			$this->display_step0($objImporter);
  		}
  	} else {
  		//Error Message and display again
  		$this->core->message($this->user->lang('ci_conn_error'), $this->user->lang('error'), 'red');
  		$this->display();
  		return;
  	}
  }
  
  public function handle_steps(){
  	$arrValues = $this->config->get('general_data', 'cmsimport');
  	$step_id = $this->in->get('step_id');
  	$step_type = $this->in->get('step_type');
  	$next_step = $this->in->get('next_step');
  	$next_step_type = $this->in->get('next_step_type');
  	
  	if($step_id == "0"){
  		$arrSteps = $this->in->getArray('steps', 'string');
  		$arrKeys = array_keys($arrSteps);
  		if(count($arrSteps) > 0){
  			$this->config->set('steps', $arrKeys, 'cmsimport');
  		} else {
  			$this->config->set('steps', array(), 'cmsimport');
  		  	$objImporter = $this->objCIFunctions->getImporterClass($arrValues['import_type']);
	  		if($objImporter){
	  			$this->display_step0($objImporter);
	  			return;
	  		}
  		}
  		
  		$arrNextStep = $this->objCIFunctions->calculateNextStep($step_id, $step_type);
  		
  		$step_id = $arrNextStep[0];
  		$step_type = $arrNextStep[1];
  		$next_step = $step_id;
  		$next_step_type = $step_type;
  	}
  	
  	$objImporter = $this->objCIFunctions->getImporterClass($arrValues['import_type']);
  	
  	if($next_step == "end"){
  		//Execute End
  		$strResult = $objImporter->end();
  		
  		$this->config->del(false, 'cmsimport');
  		
  		$this->tpl->assign_vars(array(
  			'OUTPUT' 			=> $strResult,
  			'S_END'				=> true,
  		));
  		 
  		$this->core->set_vars(array(
  				'page_title'    => $this->user->lang('ci_select_steps'),
  				'template_path' => $this->pm->get_data('cmsimport', 'template_path'),
  				'template_file' => 'admin/import_step.html',
  				'display'       => true
  		));
  	}
  	
  	//Execute Step
  	$strFunctionName = "step_".$next_step.(($next_step_type == "output") ? '_output' : '');
  	$strResult = $objImporter->$strFunctionName();
  	
  	//End whole import if a method does not return a string or true or something like that.
  	if($strResult === false || $strResult === NULL){
  		$this->tpl->assign_vars(array(
  				'OUTPUT' 			=> "An error occured. Please import again.",
  				'S_END'				=> true,
  		));
  		
  		$this->config->del(false, 'cmsimport');
  			
  		$this->core->set_vars(array(
  				'page_title'    => $this->user->lang('ci_select_steps'),
  				'template_path' => $this->pm->get_data('cmsimport', 'template_path'),
  				'template_file' => 'admin/import_step.html',
  				'display'       => true
  		));
  	}
  	
  	$step_id = $next_step;
  	$step_type = $next_step_type;

  	$arrNextStep = $this->objCIFunctions->calculateNextStep($step_id, $step_type);
  	$next_step = $arrNextStep[0];
  	$next_step_type = $arrNextStep[1];
  	
  	$this->tpl->assign_vars(array(
  		'STEP_ID'			=> $step_id,
  		'STEP_TYPE' 		=> $step_type,
  		'NEXT_STEP' 		=> $next_step,
  		'NEXT_STEP_TYPE' 	=> $next_step_type,
  		'OUTPUT' 			=> $strResult,
  	));
  	
  	$this->core->set_vars(array(
  		'page_title'    => $this->user->lang('ci_select_steps'),
  		'template_path' => $this->pm->get_data('cmsimport', 'template_path'),
  		'template_file' => 'admin/import_step.html',
  		'display'       => true
  	));
  }
  
  public function display_step0($objImporter){
  	$arrSteps = $objImporter->availableSteps();
  		
  	foreach($arrSteps as $key => $val){
  		$this->tpl->assign_block_vars('fields', array(
  				'KEY'	=> $val,
  				'VALUE' => ucfirst($val),
  		));
  	}
  		
  	$this->tpl->add_js(
  			"$('#rli_select_all').click(function() {
  					if($(this).prop('checked')) {
  					$('.steps_cb').attr('checked', 'checked');
  			} else {
  					$('.steps_cb').removeAttr('checked');
  			}
  			});", 'docready');
  		
  	$this->core->set_vars(array(
  			'page_title'    => $this->user->lang('ci_select_steps'),
  			'template_path' => $this->pm->get_data('cmsimport', 'template_path'),
  			'template_file' => 'admin/import_select_steps.html',
  			'display'       => true
  	));
  }
  
  private function fields(){
  	$arrAvailableImporters = $this->objCIFunctions->getAvailableImporters();
  	$arrImporters = array();
  	foreach($arrAvailableImporters as $val){
  		$arrImporters[$val] = ucfirst($val);
  	}
  	
  	$arrFields = array(
  			'general' => array(
  					'import_type' => array(
  							'type' => 'dropdown',
  							'options' => $arrImporters,
  					),
  					'url' => array(
  							'type' => 'text',
  							'size' => 30,
  							'required' => true,
  					)
  			),
  			'connection' => array(
  					'db_type' => array(
  							'type'		=> 'radio',
  							'options'	=> $this->user->lang('ci_db_types'),
  					),
  					'db_host' => array(
  							'type' => 'text',
  							'size' => 30,
  					),
  					'db_user' => array(
  							'type' => 'text',
  							'size' => 30,
  					),
  					'db_password' => array(
  							'type' => 'password',
  							'size' => 30,
  							'set_value' => true,
  					),
  					'db_database' => array(
  							'type' => 'text',
  							'size' => 30,
  					),
  					'db_prefix' => array(
  							'type' => 'text',
  							'size' => 30,
  							//'required' => true,
  					),
  			),
  	);
  
  	unset($arrFields['connection']['db_type']['options'][2]);
  
  	return $arrFields;
  }
  
  /**
   * display
   * Display the page
   *
   * @param    array  $messages   Array of Messages to output
   */
  public function display()
  {
  	
  	registry::load("form");
  	
  	$arrFields = $this->fields();
  	$form = register('form', array('ci_import'));
  	$form->reset_fields();
  	$form->use_fieldsets = true;
  	$form->use_dependency = true;
  	$form->lang_prefix = 'ci_';
  	$form->add_fieldsets($arrFields);
  	
  	$arrValues = array();
  	$form->output($arrValues);
  	
    
    // -- EQDKP ---------------------------------------------------------------
    $this->core->set_vars(array(
      'page_title'    => $this->user->lang('ci_import'),
      'template_path' => $this->pm->get_data('cmsimport', 'template_path'),
      'template_file' => 'admin/import_init.html',
      'display'       => true
    ));
  }
  
}

registry::register('CMSAdminImport');

?>