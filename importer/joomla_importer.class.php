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
if (!class_exists("joomla_importer")){
	class joomla_importer extends importer_generic {
		
		public $arrSteps = array(
			'user',
			'categories',
			'pages',
		);
		
		public function checkConnection($objDatabase){
			if($objDatabase){
				$objResult = $objDatabase->query('SELECT count(*) as count FROM __users;');
				if($objResult){
					$arrResult = $objResult->fetchAssoc();
					if(intval($arrResult['count']) > 0) return true;
				}
			}
			
			return false;
		}
		
		public function step_categories(){
			$objDatabase = $this->objCIFunctions->createConnection();
			
			$arrCategoryMapping = array();
			//Get User
			$objResult = $objDatabase->query('SELECT * FROM __categories WHERE extension="com_content";');
			if($objResult){
				while($arrData = $objResult->fetchAssoc()){
					if ((int)$arrData['id'] == 0) continue;
					
					$intCategoryID = $this->pdh->put('article_categories', 'add', array(
						//$strName, $strDescription, $strAlias, $intPublished, $intPortalLayout, 
						$arrData['title'],
						$arrData['description'],
						$arrData['alias'],
						(((int)$arrData['published']) ? 1 : 0),
						1,
						//$intArticlePerPage, $intParentCategory, $intListType, $intShowChilds, 
						25,
						1,
						1,
						1,
						//$arrAggregation, $intFeaturedOnly, $intSocialButtons, $intArticlePublishedState,
						array(),
						0,
						0, 
						1,
						//$arrPermissions, $intNotifyUnpublishedArticles,$intHideHeader, $intSortationType,
						unserialize('a:5:{s:3:"rea";a:6:{i:2;s:2:"-1";i:3;s:2:"-1";i:4;s:2:"-1";i:5;s:2:"-1";i:6;s:2:"-1";i:1;s:2:"-1";}s:3:"cre";a:6:{i:2;s:2:"-1";i:3;s:2:"-1";i:4;s:2:"-1";i:5;s:2:"-1";i:6;s:2:"-1";i:1;s:2:"-1";}s:3:"upd";a:6:{i:2;s:2:"-1";i:3;s:2:"-1";i:4;s:2:"-1";i:5;s:2:"-1";i:6;s:2:"-1";i:1;s:2:"-1";}s:3:"del";a:6:{i:2;s:2:"-1";i:3;s:2:"-1";i:4;s:2:"-1";i:5;s:2:"-1";i:6;s:2:"-1";i:1;s:2:"-1";}s:3:"chs";a:6:{i:2;s:2:"-1";i:3;s:2:"-1";i:4;s:2:"-1";i:5;s:2:"-1";i:6;s:2:"-1";i:1;s:2:"-1";}}'),
						0,
						0,
						1,
						//$intFeaturedOntop, $intHideOnRSS
						0,
						0
					));

					if(!$intCategoryID) return false;
						
					$arrImported[] = $arrData['title'];
					$arrCategoryMapping[(int)$arrData['id']] = $intCategoryID;
				}
			}
			
			//Set the correct pending
			$this->config->set('cat_mapping', $arrCategoryMapping, 'cmsimport');
			$objResult = $objDatabase->query('SELECT * FROM __categories WHERE extension="com_content";');
			if($objResult){
				while($arrData = $objResult->fetchAssoc()){
					$id = (int)$arrData['id'];
					if($id === 0) continue;
					
					$newCategoryID = $arrCategoryMapping[$id];
					$newParentID = $arrCategoryMapping[$arrData['parent_id']];
					if($newCategoryID && $newParentID){
						$this->db->prepare("UPDATE __article_categories SET parent=? WHERE id=?")->execute($newParentID,$newCategoryID);
					}
				}
			}
				
			$this->pdh->process_hook_queue();
				
			//Display imported Users
			$out = '<h2>'.$this->user->lang('ci_imported_categories').'</h2>
					<table class="table">';
				
			foreach($arrImported as $val){
				$out .= '<tr><td>'.$val.'</td></tr>';
			}
				
			$out .= '</table>';
				
			return $out;
		}
		
		public function step_user_output(){
			return $this->user->lang('ci_user_import_hint').'<br />';
		}
		
		public function step_user(){
			$objDatabase = $this->objCIFunctions->createConnection();
			
			//Get User
			$objResult = $objDatabase->query('SELECT * FROM __users;');
			if($objResult){
				while($arrUserdata = $objResult->fetchAssoc()){
					if ($this->pdh->get('user', 'check_username', array(sanitize($arrUserdata['username']))) != 'false'){
						$strPassword = random_string(40);

						$new_password = $this->user->encrypt_password($strPassword);
						$arrData = array(
								'username'				=> $arrUserdata['username'],
								'user_password'			=> $new_password,
								'user_email'			=> register('encrypt')->encrypt($arrUserdata['email']),
								'user_active'			=> ($arrUserdata['block'] != '0') ? 0 : 1,
								'rules'					=> 1,
								'user_registered'		=> strtotime($arrUserdata['registerDate']),
						);
						
						$intUserID = $this->pdh->put('user', 'insert_user', array($arrData, false));
						if(!$intUserID) return false;
						
						$arrImported[] = $arrUserdata['username'];
					}
				}
			}
			
			$this->pdh->process_hook_queue();
			
			//Display imported Users
			$out = '<h2>'.$this->user->lang('ci_imported_users').'</h2>
					<table class="table">';
			
			foreach($arrImported as $val){
				$out .= '<tr><td>'.$val.'</td></tr>';
			}
			
			$out .= '</table>';
			
			return $out;
		}
		
		public function step_pages_output(){
			//Select User that will be used for articles that don't have a user
			$arrUser = $this->pdh->aget('user', 'name', 0, array($this->pdh->get('user', 'id_list')));
			
			$arrSteps = $this->config->get('steps', 'cmsimport');
			$blnHasCategories = in_array('categories', $arrSteps) ? true : false;
			
			$arrCategoryIDs = $this->pdh->sort($this->pdh->get('article_categories', 'id_list', array()), 'article_categories', 'sort_id', 'asc');
			$arrCategories = array();

			foreach($arrCategoryIDs as $caid){
				$arrCategories[$caid] = $this->pdh->get('article_categories', 'name_prefix', array($caid)).$this->pdh->get('article_categories', 'name', array($caid));
			}
			
			$out = '<fieldset class="settings" id="{fieldsets.ID}">
		<dl>
			<dt><label>'.$this->user->lang('ci_default_user_pages').'</label></dt>
			<dd>'.(new hdropdown('user', array('options' => $arrUser, 'value' => $this->user->id)))->output().'</dd>
		</dl>';
			if(!$blnHasCategories){
				$out .= '<dl>
			<dt><label>'.$this->user->lang('ci_default_category_pages').'</label></dt>
			<dd>'.(new hdropdown('category_pages', array('options' => $arrCategories, 'value' => 2)))->output().'</dd>
		</dl>';
			}
		
		
			$out .= '</fieldset>';
			
			return $out;
		}
		
		public function step_pages(){
			$defaultUser = $this->in->get('user', 0);
			$intDefaultCategoryPages = $this->in->get('category_pages', 0);
			
			$arrSteps = $this->config->get('steps', 'cmsimport');
			$blnHasCategories = in_array('categories', $arrSteps) ? true : false;
			$arrCategoryMapping = $this->config->get('cat_mapping', 'cmsimport');
			
			$arrUser = $this->pdh->aget('user', 'name', 0, array($this->pdh->get('user', 'id_list')));
			$arrUserMapping = array();
			foreach($arrUser as $userid => $strUsername){
				$arrUserMapping[clean_username($strUsername)] = $userid;
			}
			
			$objDatabase = $this->objCIFunctions->createConnection();
			$objResult = $objDatabase->query('SELECT p.*, u.username FROM __content p, __users u WHERE p.created_by = u.id;');
			if($objResult){
				while($arrRow = $objResult->fetchAssoc()){
					//add($strTitle, $strText, $arrTags, $strPreviewimage, $strAlias, $intPublished, 
					//$intFeatured, $intCategory, $intUserID, $intComments, $intVotes,$intDate, 
					//$strShowFrom,$strShowTo, $intHideHeader){
					
					$catID = (isset($arrCategoryMapping[(int)$arrRow['catid']])) ? $arrCategoryMapping[(int)$arrRow['catid']] : $intDefaultCategoryPages;

					$intArticleID = $this->pdh->put('articles', 'add', array(
						$arrRow['title'],
						$this->handle_text($arrRow['introtext'], $arrRow['fulltext']),
						array(),
						'',
						(($arrRow['alias'] != "") ? $arrRow['alias'] : $arrRow['title']),
						(((int)$arrRow['state'] == 1) ? 1 : 0),
						(((int)$arrRow['featured'] == 1) ? 1 : 0),
						$catID,
						((isset($arrUserMapping[clean_username($arrRow['username'])])) ? $arrUserMapping[clean_username($arrRow['username'])] : $defaultUser),
						0,
						0,
						(strtotime($arrRow['created'])) ? strtotime($arrRow['created']) : strtotime($arrRow['modified']),
						(strtotime($arrRow['publish_up'])) ? strtotime($arrRow['publish_up']) : '',
						(strtotime($arrRow['publish_down'])) ? strtotime($arrRow['publish_down']) : '',
						0,
					));
					
					$arrImported[] = $arrRow['title'];
					
				}
			}
			
			$this->pdh->process_hook_queue();
			
			//Display imported Pages
			$out = '<h2>'.$this->user->lang('ci_imported_pages').'</h2>
					<table class="table">';
			
			foreach($arrImported as $val){
				$out .= '<tr><td>'.$val.'</td></tr>';
			}
			
			$out .= '</table>';
			
			return $out;
		}
		
		private function handle_text($strIntroText, $strFulltext){
			$out = $strIntroText;
			if($strFulltext != "") $out .= '<hr id="system-readmore">'.$strFulltext;
			
			$out = $this->replace_images($out);
			
			return $out;
		}
		
		private function replace_images($strText){
			$output_array = array();
			$arrMatches = preg_match_all("/src=\"(.*)\"/isxmU", $strText, $output_array);
				
			$new = $strText;
				
			$objBBcode = register('bbcode');
			$strImage = "";
			$arrGeneralData = $this->config->get('general_data', 'cmsimport');
			$strUrl = $arrGeneralData['url'];
				
			foreach($output_array[1] as $key => $val){
		
				// src="image/..."
				if(stripos($val, "images") === 0 && $strUrl != ""){
					$strImage = $strUrl.$val;
				}
				
				// src="htt...
				if(stripos($val, "http") === 0){
					$strImage = $val;
				}

				
				if($strImage != ""){
					$strDownloadedImage = $objBBcode->DownloadImage($strImage);

					if($strDownloadedImage){
						$new = str_replace($val, $this->env->root_to_serverpath($strDownloadedImage), $new);
					}
				}
			}
				
			return $new;
		}
		
	}
}
	