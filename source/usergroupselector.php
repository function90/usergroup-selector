<?php
/**
 * @package 	Plugin Usergroupselector for Joomla! 3.X
 * @version 	0.0.1
 * @author 		Function90.com
 * @copyright 	C) 2013- Function90.com
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
**/

defined('_JEXEC') or die;

class plgUserUsergroupselector extends JPlugin
{
	protected $autoloadLanguage = true;
		
	public function onUserAfterSave($user, $isnew, $success, $msg)
	{
		$allowed_groups = $this->params->get('allowed_groups');
		if($isnew && $success){
			$input = JFactory::getApplication()->input;
			$requestData  = $input->post->get('jform', array(), 'array');
			if(isset($requestData['usergroupselector'])){
				$usergroups = array();
				if($this->params->get('allowMultiple', false)){
					foreach($requestData['usergroupselector'] as $usergroup){
						if(in_array($usergroup, $allowed_groups)){				
							$usergroups[] = $usergroup;
						}
					}
				}
				else{
					if(in_array($requestData['usergroupselector'], $allowed_groups)){				
						$usergroups[] = $requestData['usergroupselector'];	
					}
				}

				if(!empty($usergroups)){
					foreach($usergroups as $groupid){
					    $groups[] = $user['id'] . ',' . $groupid;
					}
					
					$this->setJoomlaUserGroups($user['id'], $groups);
				}
			}
		}
	}
	public function onContentPrepareForm($form, $data)
	{
		$app = JFactory::getApplication();
		if($app->isAdmin()){
			return true;
		}
		
		if($form->getName() != 'com_users.registration'){
			return true;
		}
		
		$groups = $this->getJoomlaUserGroups();
		
		$allowed_groups = $this->params->get('allowed_groups');
		
		$xml = "<fieldset name='usergroupselector'>
					<field 						
						name='usergroupselector'
						label='".$this->params->get('label')."'
						description='".$this->params->get('desc')."'";
		
		if($this->params->get('allowMultiple', false) != false){
			$xml .= ' type="checkboxes"';
			$xml .= ' multiple="true"';
		}
		else{
			$xml .= ' type="list"';
		}

		if($this->params->get('required', false)){
			$xml .= ' required="true"';
		}

		$default = $this->params->get('default_group', false);
		if($default != false){
			$xml .= ' default="'.$default.'"';
		}

		$xml .= ">";
		
		foreach($groups as $groupid => $group){
			if(in_array($groupid, $allowed_groups)){
				$xml .= "<option value='".$groupid."'><![CDATA[ ".$this->xmlEscape($group->title)." ]]></option>";
			}
		}
						
		$xml .=	"</field>
				</fieldset>
				";
		
		$form->setField(new SimpleXMLElement($xml), null, true, 'usergroupselector');
	}
	
	function xmlEscape($string) 
	{
    	return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
	}

	public function getJoomlaUserGroups()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__usergroups'))
			->order('title');
		$db->setQuery($query);
		return $db->loadObjectList('id');
	}
	
	public function setJoomlaUserGroups($userid, $groups)
	{
	    $db = JFactory::getDbo();
	    $query = $db->getQuery(true)
    	    ->delete('#__user_usergroup_map')
    	    ->where('user_id' . ' = ' . $userid);
	    
	    $db->setQuery($query);
	    $db->execute();
	    
	    $query->clear()
    	    ->insert('#__user_usergroup_map')
    	    ->columns('`user_id`, `group_id`')
    	    ->values($groups);
	    $db->setQuery($query);
	    $db->query();
	}
}
