<?php
namespace wcf\system\event\listener;
use wcf\system\event\IEventListener;

/**
 * Adds 'likes received' sort field for members list.
 * 
 * @author 	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	system.event.listener
 * @category 	Community Framework
 */
class LikeMembersListPageListener implements IEventListener {
	/**
	 * @see wcf\system\event\IEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		$eventObj->validSortFields[] = 'likesReceived';
	}
}
