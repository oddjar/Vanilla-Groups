<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['Groups'] = array(
   'Name' => 'Groups',
   'Description' => 'This plugin allows the creation of groups, and user assignment to groups.',
   'Version' => '0.1',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'SettingsUrl' => '/dashboard/plugin/groups',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Johnathon Williams",
   'AuthorEmail' => 'john@oddjar.com',
   'AuthorUrl' => 'http://oddjar.com'
);

class GroupsPlugin extends Gdn_Plugin {
   
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $NumGroups = Gdn::SQL()->Select('gr.GroupID','DISTINCT', 'NumGroups')
         ->From('Group gr')
         ->Get()->NumRows();
      
      $LinkText = T('Groups');
      if ($NumGroups)
         $LinkText .= " ({$NumGroups})";
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Users', T('Users'));
      $Menu->AddLink('Users', $LinkText, 'plugin/groups', 'Garden.Settings.Manage');
   }

   public function PluginController_Groups_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Groups Managment');
      $Sender->AddSideMenu('plugin/groups');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   public function Controller_Index($Sender) {
      $Sender->AddCssFile('admin.css');
      $Sender->AddCssFile($this->GetResource('design/groups.css', FALSE, FALSE));
      
      $GroupList = Gdn::SQL()->Select('*')
         ->From('Group gr')
         ->Get();
      
      $Sender->GroupList = array();
      while ($GroupItems = $GroupList->NextRow(DATASET_TYPE_ARRAY)) {
         $Name = $GroupItems['Name'];
         $ID = $GroupItems['GroupID'];
		 $Sender->GroupList[] = $GroupItems;
      }
      unset($GroupList);
      $Sender->Render($this->GetView('grouping.php'));
   }

   public function Controller_Delete($Sender) {
      $Arguments = $Sender->RequestArgs;
      if (sizeof($Arguments) != 2) return;
      list($Controller, $GroupID) = $Arguments;
            
      Gdn::SQL()->Delete('Group',array(
         'GroupID'      => $GroupID
      ));

	  Gdn::SQL()->Delete('UserGroup',array(
	     'GroupID'      => $GroupID
	  ));
	
	  $Sender->StatusMessage = T("The Group has been deleted.");
      $this->Controller_Index($Sender);
   }


   public function Controller_Add($Sender) {   
	
      if ($Sender->Form->AuthenticatedPostBack()) {
         $Name = $Sender->Form->GetValue('Plugin.Groups.Name');
	  }

      Gdn::SQL()->Insert('Group',array(
         'Name'      => $Name
      ));

	  $Sender->StatusMessage = T("The Group has been added.");
      $this->Controller_Index($Sender);
   }


   public function Controller_Edit($Sender) {   
	
      if ($Sender->Form->AuthenticatedPostBack()) {
         $Name = $Sender->Form->GetValue('Plugin.Groups.Name');
		 $ID = $Sender->Form->GetValue('Plugin.Groups.GroupID');
         try {
            Gdn::SQL()
       	    ->Update('Group gr')
            ->Set('gr.Name', $Name)
            ->Where('gr.GroupID', $ID)
            ->Put();
         } catch(Exception $e) {}
         $Sender->StatusMessage = T("Your changes have been saved.");
         $Sender->RedirectUrl = Url('plugin/groups');

      } else {
		  $Arguments = $Sender->RequestArgs;
	      if (sizeof($Arguments) != 2) return;
	      list($Controller, $GroupID) = $Arguments;
	      $ItemQuery = Gdn::SQL()->Select('*')
	         ->From('Group gr')
			 ->Where('gr.GroupID', $GroupID)
	         ->Get();
		  $GroupItem = $ItemQuery->FirstRow();
		  $Sender->Group = $GroupItem;
	  }
      $Sender->Render($this->GetView('group.php'));
   }
   

   public function Setup() {
      // $this->Structure();
      SaveToConfig('Plugins.Groups.Enabled', TRUE);
   }
   
	public function OnDisable() {
		SaveToConfig('Plugins.Groups.Enabled', FALSE);
	}


   
}