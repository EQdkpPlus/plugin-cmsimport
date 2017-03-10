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
if (!class_exists("wordpress_importer")){
	class wordpress_importer extends importer_generic {
		
		public $arrSteps = array(
				'user',
				'posts',
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
		
		public function step_user_output(){
			return $this->user->lang('ci_user_import_hint').'<br />';
		}
		
		public function step_user(){
			$objDatabase = $this->objCIFunctions->createConnection();
			
			//Get User
			$objResult = $objDatabase->query('SELECT * FROM __users;');
			if($objResult){
				while($arrUserdata = $objResult->fetchAssoc()){
					if ($this->pdh->get('user', 'check_username', array(sanitize($arrUserdata['user_login']))) != 'false'){
						$strPassword = md5(generateRandomBytes());
						$salt = $this->user->generate_salt();
						$new_password = $this->user->encrypt_password($strPassword, $salt).':'.$salt;
						$arrData = array(
								'username'				=> $arrUserdata['user_login'],
								'user_password'			=> $new_password,
								'user_email'			=> register('encrypt')->encrypt($arrUserdata['user_email']),
								'user_active'			=> 1,
								'rules'					=> 1,
								'user_registered'		=> strtotime($arrUserdata['user_registered']),
						);
						
						$intUserID = $this->pdh->put('user', 'insert_user', array($arrData, false));
						if(!$intUserID) return false;
						
						$arrImported[] = $arrUserdata['user_login'];
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
			
			$arrCategoryIDs = $this->pdh->sort($this->pdh->get('article_categories', 'id_list', array()), 'article_categories', 'sort_id', 'asc');
			$arrCategories = array();

			foreach($arrCategoryIDs as $caid){
				$arrCategories[$caid] = $this->pdh->get('article_categories', 'name_prefix', array($caid)).$this->pdh->get('article_categories', 'name', array($caid));
			}
			
			$out = '<fieldset class="settings" id="{fieldsets.ID}">
		<dl>
			<dt><label>'.$this->user->lang('ci_default_user_pages').'</label></dt>
			<dd>'.(new hdropdown('user', array('options' => $arrUser, 'value' => $this->user->id)))->output().'</dd>
		</dl>
		<dl>
			<dt><label>'.$this->user->lang('ci_default_category_pages').'</label></dt>
			<dd>'.(new hdropdown('category_pages', array('options' => $arrCategories, 'value' => 2)))->output().'</dd>
		</dl>
	</fieldset>';
			
			return $out;
		}
		
		public function step_pages(){
			$defaultUser = $this->in->get('user', 0);
			$intDefaultCategoryPages = $this->in->get('category_pages', 0);
			
			$arrUser = $this->pdh->aget('user', 'name', 0, array($this->pdh->get('user', 'id_list')));
			$arrUserMapping = array();
			foreach($arrUser as $userid => $strUsername){
				$arrUserMapping[clean_username($strUsername)] = $userid;
			}
			
			$objDatabase = $this->objCIFunctions->createConnection();
			$objResult = $objDatabase->query('SELECT p.*, u.user_login FROM __posts p, __users u WHERE p.post_author = u.ID AND (post_type="page") AND (post_status="publish" OR post_status="draft");');
			if($objResult){
				while($arrRow = $objResult->fetchAssoc()){
					//add($strTitle, $strText, $arrTags, $strPreviewimage, $strAlias, $intPublished, 
					//$intFeatured, $intCategory, $intUserID, $intComments, $intVotes,$intDate, 
					//$strShowFrom,$strShowTo, $intHideHeader){
					
					$intArticleID = $this->pdh->put('articles', 'add', array(
						$arrRow['post_title'],
						$this->replace_images($arrRow['post_content']),
						array(),
						'',
						(($arrRow['post_name'] != "") ? $arrRow['post_name'] : $arrRow['post_title']),
						(($arrRow['post_status'] == 'publish') ? 1 : 0),
						0,
						$intDefaultCategoryPages,
						((isset($arrUserMapping[clean_username($arrRow['user_login'])])) ? $arrUserMapping[clean_username($arrRow['user_login'])] : $defaultUser),
						(($arrRow['comment_status'] == 'open') ? 1 : 0 ),
						(($arrRow['comment_status'] == 'open') ? 1 : 0 ),
						(strtotime($arrRow['post_date_gmt'])) ? strtotime($arrRow['post_date_gmt']) : strtotime($arrRow['post_modified_gmt']),
						'',
						'',
						0,
					));
					
					$arrImported[] = $arrRow['post_title'];
					
					if($intArticleID && (int)$arrRow['comment_count'] > 0){
						$objCommentResult = $objDatabase->prepare('SELECT c.*, u.user_login FROM __comments c, __users u WHERE c.user_id = u.ID AND comment_post_ID = ? AND user_id > 0;')->execute($arrRow['ID']);
						if($objCommentResult){
							while($arrCommentRow = $objCommentResult->fetchAssoc()){
								//insert($attach_id, $user_id, $comment, $page, $reply_to) 
								$userId = (isset($arrUserMapping[clean_username($arrCommentRow['user_login'])])) ? $arrUserMapping[clean_username($arrCommentRow['user_login'])] : false;
								
								if($userId){
									$objQuery = $this->db->prepare("INSERT INTO __comments :p")->set(array(
											'attach_id'		=> $intArticleID,
											'date'			=> strtotime($arrCommentRow['comment_date_gmt']),
											'userid'		=> $userId,
											'text'			=> str_replace("\n", "[br]", filter_var($arrCommentRow['comment_content'])),
											'page'			=> 'articles',
											'reply_to'		=> 0,
									))->execute();
									
									if($objQuery){
										$id = $objQuery->insertId;
										$this->pdh->enqueue_hook('comment_update', $id);
									}
								}
							}
						}
					}
				}
			}
			
			$this->pdh->process_hook_queue();
			
			//Display imported Posts
			$out = '<h2>'.$this->user->lang('ci_imported_pages').'</h2>
					<table class="table">';
			
			foreach($arrImported as $val){
				$out .= '<tr><td>'.$val.'</td></tr>';
			}
			
			$out .= '</table>';
			
			return $out;
		}
		
		public function step_posts_output(){
			//Select User that will be used for articles that don't have a user
			$arrUser = $this->pdh->aget('user', 'name', 0, array($this->pdh->get('user', 'id_list')));
				
			$arrCategoryIDs = $this->pdh->sort($this->pdh->get('article_categories', 'id_list', array()), 'article_categories', 'sort_id', 'asc');
			$arrCategories = array();
		
			foreach($arrCategoryIDs as $caid){
				$arrCategories[$caid] = $this->pdh->get('article_categories', 'name_prefix', array($caid)).$this->pdh->get('article_categories', 'name', array($caid));
			}
				
			$out = '<fieldset class="settings" id="{fieldsets.ID}">
		<dl>
			<dt><label>'.$this->user->lang('ci_default_user_pages').'</label></dt>
			<dd>'.(new hdropdown('user', array('options' => $arrUser, 'value' => $this->user->id)))->output().'</dd>
		</dl>
		<dl>
			<dt><label>'.$this->user->lang('ci_default_category_posts').'</label></dt>
			<dd>'.(new hdropdown('category_posts', array('options' => $arrCategories, 'value' => 2)))->output().'</dd>
		</dl>
	</fieldset>';
				
			return $out;
		}
		
		public function step_posts(){
			$defaultUser = $this->in->get('user', 0);
			$intDefaultCategoryPosts = $this->in->get('category_posts', 0);
				
			$arrUser = $this->pdh->aget('user', 'name', 0, array($this->pdh->get('user', 'id_list')));
			$arrUserMapping = array();
			foreach($arrUser as $userid => $strUsername){
				$arrUserMapping[clean_username($strUsername)] = $userid;
			}
				
			$objDatabase = $this->objCIFunctions->createConnection();
			$objResult = $objDatabase->query('SELECT p.*, u.user_login FROM __posts p, __users u WHERE p.post_author = u.ID AND (post_type="post") AND (post_status="publish" OR post_status="draft");');
			if($objResult){
				while($arrRow = $objResult->fetchAssoc()){
					//add($strTitle, $strText, $arrTags, $strPreviewimage, $strAlias, $intPublished,
					//$intFeatured, $intCategory, $intUserID, $intComments, $intVotes,$intDate,
					//$strShowFrom,$strShowTo, $intHideHeader){
						
					$intArticleID = $this->pdh->put('articles', 'add', array(
							$arrRow['post_title'],
							$this->replace_images($arrRow['post_content']),
							array(),
							'',
							(($arrRow['post_name'] != "") ? $arrRow['post_name'] : $arrRow['post_title']),
							(($arrRow['post_status'] == 'publish') ? 1 : 0),
							0,
							$intDefaultCategoryPosts,
							((isset($arrUserMapping[clean_username($arrRow['user_login'])])) ? $arrUserMapping[clean_username($arrRow['user_login'])] : $defaultUser),
							(($arrRow['comment_status'] == 'open') ? 1 : 0 ),
							(($arrRow['comment_status'] == 'open') ? 1 : 0 ),
							(strtotime($arrRow['post_date_gmt'])) ? strtotime($arrRow['post_date_gmt']) : strtotime($arrRow['post_modified_gmt']),
							'',
							'',
							0,
					));
						
					$arrImported[] = $arrRow['post_title'];
						
					if($intArticleID && (int)$arrRow['comment_count'] > 0){
						$objCommentResult = $objDatabase->prepare('SELECT c.*, u.user_login FROM __comments c, __users u WHERE c.user_id = u.ID AND comment_post_ID = ? AND user_id > 0;')->execute($arrRow['ID']);
						if($objCommentResult){
							while($arrCommentRow = $objCommentResult->fetchAssoc()){
								//insert($attach_id, $user_id, $comment, $page, $reply_to)
								$userId = (isset($arrUserMapping[clean_username($arrCommentRow['user_login'])])) ? $arrUserMapping[clean_username($arrCommentRow['user_login'])] : false;
		
								if($userId){
									$objQuery = $this->db->prepare("INSERT INTO __comments :p")->set(array(
											'attach_id'		=> $intArticleID,
											'date'			=> strtotime($arrCommentRow['comment_date_gmt']),
											'userid'		=> $userId,
											'text'			=> str_replace("\n", "[br]", filter_var($arrCommentRow['comment_content'])),
											'page'			=> 'articles',
											'reply_to'		=> 0,
									))->execute();
										
									if($objQuery){
										$id = $objQuery->insertId;
										$this->pdh->enqueue_hook('comment_update', $id);
									}
								}
							}
						}
					}
				}
			}
				
			$this->pdh->process_hook_queue();
				
			//Display imported Posts
			$out = '<h2>'.$this->user->lang('ci_imported_posts').'</h2>
					<table class="table">';
				
			foreach($arrImported as $val){
				$out .= '<tr><td>'.$val.'</td></tr>';
			}
				
			$out .= '</table>';
				
			return $out;
		}
		
		
		private function replace_images($strText){
			$output_array = array();
			$arrMatches = preg_match_all("/src=\"(.*)\"/isxmU", $strText, $output_array);
			
			$new = $strText;
			
			$objBBcode = register('bbcode');
			
			foreach($output_array[1] as $key => $val){
				
				if(stripos($val, "wp-content") && strpos($val, "://")){
					$strImage = $objBBcode->DownloadImage($val);
					if($strImage){
						$new = str_replace($val, $this->env->root_to_serverpath($strImage), $new);
					}		
				}
			}
			
			return $new;
		}
	}
}
	