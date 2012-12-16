<?php
use wcf\system\WCF;

/**
 * @author	Marcel Werk
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
// hijack members list sort field
$sql = "UPDATE	wcf".WCF_N."_option
	SET	selectOptions = ".(WCF::getDB()->getDBType() == 'wcf\system\database\MySQLDatabase' ? "CONCAT(selectOptions, '\n', 'likedReceived:wcf.like.likesReceived')" : "(selectOptions || '\n' || 'likedReceived:wcf.like.likesReceived')")."
	WHERE	optionName = ?";
$statement = WCF::getDB()->prepareStatement($sql);
$statement->execute(array('members_list_default_sort_field'));
