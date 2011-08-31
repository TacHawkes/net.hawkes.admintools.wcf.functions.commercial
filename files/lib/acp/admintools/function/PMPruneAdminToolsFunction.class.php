<?php
// wcf imports
require_once(WCF_DIR.'lib/acp/admintools/function/AbstractAdminToolsFunction.class.php');
require_once(WCF_DIR.'lib/data/message/attachment/AttachmentsEditor.class.php');
require_once(WCF_DIR.'lib/data/message/pm/PM.class.php');

/**
 * Prunes PMs
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
class PMPruneAdminToolsFunction extends AbstractAdminToolsFunction {

	/**
	 * @see AdminToolsFunction::execute($data)
	 */
	public function execute($data) {
		parent::execute($data);

		$parameters = $data['parameters']['messages.prunePMs'];
		if (!$parameters['time']) return;

		$sql = "SELECT 	pmID, userID 
			FROM 	wcf".WCF_N."_pm
			WHERE 	time < ".(TIME_NOW - $parameters['time']*86400);
		if (!empty($parameters['ignoredUsergroupIDs'])) $sql .= " AND userID NOT IN (SELECT userID FROM wcf".WCF_N."_user_to_groups WHERE groupID IN (".$parameters['ignoredUsergroupIDs']."))";
		if (!empty($parameters['ignoredUserIDs'])) $sql .= "  AND userID NOT IN (".$parameters['ignoredUserIDs'].")";
		if ($parameters['excludeFolders']) $sql .= " AND pmID NOT IN (SELECT pmID FROM wcf".WCF_N."_pm_to_user WHERE folderID > 0)";
		$result = WCF::getDB()->sendQuery($sql);
		$pmIDs = array();
		$userIDs = array();
		while($row = WCF::getDB()->fetchArray($result)) {
			$pmIDs[] = $row['pmID'];
			$userIDs[] = $row['userID'];
		}
		if (count($pmIDs) && count($userIDs)) {
			$this->deleteData(implode(',', $pmIDs));

			PM::updateTotalMessageCount(implode(',', $userIDs));
			PM::updateUnreadMessageCount(implode(',', $userIDs));
			Session::resetSessions(implode(',', $userIDs));
		}
		$this->setReturnMessage('success', WCF::getLanguage()->get('wcf.acp.admintools.function.messages.prunePMs.success', array('$pmCount' => count($pmIDs))));

		$this->executed();
	}

	/**
	 * Deletes the data of the specified messages completely.
	 *
	 * @param	string		$pmIDs
	 */
	protected static function deleteData($pmIDs) {
		// delete recipients
		$sql = "DELETE FROM	wcf".WCF_N."_pm_to_user
			WHERE		pmID IN (".$pmIDs.")";
		WCF::getDB()->sendQuery($sql);

		// delete messages
		$sql = "DELETE FROM	wcf".WCF_N."_pm
			WHERE		pmID IN (".$pmIDs.")";
		WCF::getDB()->sendQuery($sql);

		// delete pm hashes
		$sql = "DELETE FROM	wcf".WCF_N."_pm_hash
			WHERE		pmID IN (".$pmIDs.")";
		WCF::getDB()->registerShutdownUpdate($sql);

		// delete attachments
		$attachments = new AttachmentsEditor($pmIDs, 'pm');
		$attachments->deleteAll();
	}
}
?>