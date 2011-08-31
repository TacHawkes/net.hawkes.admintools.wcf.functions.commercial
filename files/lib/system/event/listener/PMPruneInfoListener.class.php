<?php
// wcf imports
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');

/**
 * Shows an info box about PM deletion
 *
 * This file is part of Admin Tools 2.
 *
 * Admin Tools 2 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Admin Tools 2 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Admin Tools 2.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author	Oliver Kliebisch
 * @copyright	2009 Oliver Kliebisch
 * @license	GNU General Public License <http://www.gnu.org/licenses/>
 * @package	net.hawkes.admintools.wcf.functions.commercial
 * @subpackage 	acp.admintools.function
 * @category 	WCF
 */
class PMPruneInfoListener implements EventListener {
	
	/**	 
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		$sql = "SELECT 	optionName, optionValue 
			FROM 	wcf".WCF_N."_admin_tools_option
			WHERE 	optionName IN ('messages.prunePMs.showInfoBox', 'messages.prunePMs.showInfoBoxToAll', 'messages.prunePMs.ignoredUserIDs', 'messages.prunePMs.ignoredUsergroupIDs', 'messages.prunePMs.time')";
		$result = WCF::getDB()->sendQuery($sql);
		$options = array();
		while ($row = WCF::getDB()->fetchArray($result)) {
			$options[$row['optionName']] = $row['optionValue'];
		}		
		if ($options['messages.prunePMs.time'] && $options['messages.prunePMs.showInfoBox']) {
			$showMessage = true;
			if (!$options['messages.prunePMs.showInfoBoxToAll']) {
				$ignoredUserIDs = explode(',', $options['messages.prunePMs.ignoredUserIDs']);
				if (in_array(WCF::getUser()->userID, $ignoredUserIDs)) {
					$showMessage = false;
				}
				
				$ignoredUsergroupIDs = explode(',', $options['messages.prunePMs.ignoredUsergroupIDs']);
				$groupIDs = WCF::getUser()->getGroupIDs();
				foreach ($groupIDs as $groupID) {
					if (in_array($groupID, $ignoredUsergroupIDs)) {
						$showMessage = false;
						break;
					}
				}
			}
			
			if ($showMessage) {
				WCF::getTPL()->append('userMessages', '<p class="info">'.WCF::getLanguage()->get('wcf.acp.admintools.function.messages.prunePMs.infoBox', array('$pruneTime' => $options['messages.prunePMs.time'])).'</p>');
			}
		}
		
	}
}
?>