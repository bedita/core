<?php
/*-----8<--------------------------------------------------------------------
 * 
 * BEdita - a semantic content management framework
 * 
 * Copyright 2008 ChannelWeb Srl, Chialab Srl
 * 
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published 
 * by the Free Software Foundation, either version 3 of the License, or 
 * (at your option) any later version.
 * BEdita is distributed WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the Affero GNU General Public License for more details.
 * You should have received a copy of the Affero GNU General Public License 
 * version 3 along with BEdita (see LICENSE.AGPL).
 * If not, see <http://gnu.org/licenses/agpl-3.0.html>.
 * 
 *------------------------------------------------------------------->8-----
 */

/**
 * 
 * @link			http://www.bedita.com
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 * 
 * $Id$
 */
class Card extends BEAppObjectModel {

	public $searchFields = array("title" => 8 , "description" => 4, 
		"company_name" => 3, "city" => 4);

	var $actsAs 	= array(
			'CompactResult' 		=> array("MailGroup"),
			'SearchTextSave',
			'ForeignDependenceSave' => array('BEObject'),
			'DeleteObject' 			=> 'objects',
	); 
	
	protected $modelBindings = array( 
				"detailed" =>  array("BEObject" => array("ObjectType", 
															"UserCreated", 
															"UserModified", 
															"Permissions",
															"CustomProperties",
															"LangText",
															"RelatedObject",
															"Category",
															"User"), "MailGroup"),

				"default" => array("BEObject" => array("CustomProperties", 
									"LangText", "ObjectType", 
									"Category", "RelatedObject" )),

				"minimum" => array("BEObject" => array("ObjectType"))		
		);
	
	var $hasAndBelongsToMany = array(
			"MailGroup" => array(
							"joinTable"	=> "mail_group_cards"
						)
		);
		
	function beforeValidate() {
		
		$this->checkDate('birthdate');
		$this->checkDate('deathdate');

		return true;
	}
	
	// save join with mail groups
	function afterSave($created) {
		
		if (!empty($this->data["Card"]["joinGroup"])) {
		
			if (empty($this->id))
				throw new BeditaException(__("Error saving card", true), "Missing model id in afterSave.");
			
			$mailGroupCard = ClassRegistry::init("MailGroupCard");
			
			$mailGroupCard->deleteAll(array("card_id" => $this->id));
				
			foreach ($this->data["Card"]["joinGroup"] as $joinData) {
				
				// rebuild active join
				if (!empty($joinData["mail_group_id"])) {
					$joinData["card_id"] = $this->id;
					
					if (empty($joinData["hash"])) {
						$groupname = $this->MailGroup->field("group_name", array("id" => $joinData["mail_group_id"]));
						$joinData["hash"] = md5($this->id . microtime() . $groupname);
					}
					
					if (empty($joinData["created"]))
						unset($joinData["created"]);
										
					$mailGroupCard->create();
					if (!$mailGroupCard->save($joinData))
						throw new BeditaException(__("Error creating join between card and groups", true), "Saving error");

				}
			}
			
		}

	}
}
?>
