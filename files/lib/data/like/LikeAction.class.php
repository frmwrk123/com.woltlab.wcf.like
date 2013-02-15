<?php
namespace wcf\data\like;
use wcf\system\user\GroupedUserList;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\like\LikeHandler;
use wcf\system\user\activity\event\UserActivityEventHandler;
use wcf\system\WCF;

/**
 * Executes like-related actions.
 * 
 * @todo	Add validation of permissions for each object being liked (including statistics)
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	data.like
 * @category	Community Framework
 */
class LikeAction extends AbstractDatabaseObjectAction {
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::$allowGuestAccess
	 */
	protected $allowGuestAccess = array('getLikeDetails');
	
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\like\LikeEditor';
	
	/**
	 * Validates parameters to fetch like details.
	 */
	public function validateGetLikeDetails() {
		$this->validateObjectParameters();
	}
	
	/**
	 * Returns like details.
	 * 
	 * @return	array<string>
	 */
	public function getLikeDetails() {
		$sql = "SELECT	userID, likeValue
			FROM	wcf".WCF_N."_like
			WHERE	objectID = ?
				AND objectTypeID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$this->parameters['data']['objectID'],
			LikeHandler::getInstance()->getObjectType($this->parameters['data']['objectType'])->objectTypeID
		));
		$data = array(
			Like::LIKE => array(),
			Like::DISLIKE => array()
		);
		while ($row = $statement->fetchArray()) {
			$data[$row['likeValue']][] = $row['userID'];
		}
		
		$values = array();
		if (!empty($data[Like::LIKE])) {
			$values[Like::LIKE] = new GroupedUserList(WCF::getLanguage()->get('wcf.like.details.like'));
			$values[Like::LIKE]->addUserIDs($data[Like::LIKE]);
		}
		if (!empty($data[Like::DISLIKE])) {
			$values[Like::DISLIKE] = new GroupedUserList(WCF::getLanguage()->get('wcf.like.details.dislike'));
			$values[Like::DISLIKE]->addUserIDs($data[Like::DISLIKE]);
		}
		
		// load user profiles
		GroupedUserList::loadUsers();
		
		WCF::getTPL()->assign(array(
			'groupedUsers' => $values
		));
		
		return array(
			'containerID' => $this->parameters['data']['containerID'],
			'template' => WCF::getTPL()->fetch('groupedUserList')
		);
	}
	
	/**
	 * Validates parameters for like-related actions.
	 */
	public function validateLike() {
		$this->validateObjectParameters();
		
		// check permissions
		if (!WCF::getUser()->userID || !WCF::getSession()->getPermission('user.like.canLike')) {
			throw new PermissionDeniedException();	
		}
	}
	
	/**
	 * @see	wcf\data\like\LikeAction::updateLike()
	 */
	public function like() {
		return $this->updateLike(Like::LIKE);
	}
	
	/**
	 * @see	wcf\data\like\LikeAction::validateLike()
	 */
	public function validateDislike() {
		$this->validateLike();
	}
	
	/**
	 * @see	wcf\data\like\LikeAction::updateLike()
	 */
	public function dislike() {
		return $this->updateLike(Like::DISLIKE);
	}
	
	/**
	 * Sets like/dislike for an object, executing this method again with the same parameters
	 * will revert the status (removing like/dislike).
	 * 
	 * @return	array
	 */
	protected function updateLike($likeValue) {
		$objectType = LikeHandler::getInstance()->getObjectType($this->parameters['data']['objectType']);
		$objectProvider = $objectType->getProcessor();
		$likeableObject = $objectProvider->getObjectByID($this->parameters['data']['objectID']);
		$likeableObject->setObjectType($objectType);
		$likeData = LikeHandler::getInstance()->like($likeableObject, WCF::getUser(), $likeValue);
		
		// fire activity event
		if ($likeData['data']['liked'] == 1) {
			if (UserActivityEventHandler::getInstance()->getObjectTypeID($objectType->objectType.'.recentActivityEvent')) {
				UserActivityEventHandler::getInstance()->fireEvent($objectType->objectType.'.recentActivityEvent', $this->parameters['data']['objectID']);
			}
		}
		
		// get stats
		return array(
			'likes' => ($likeData['data']['likes'] === null) ? 0 : $likeData['data']['likes'],
			'dislikes' => ($likeData['data']['dislikes'] === null) ? 0 : $likeData['data']['dislikes'],
			'cumulativeLikes' => ($likeData['data']['cumulativeLikes'] === null) ? 0 : $likeData['data']['cumulativeLikes'],
			'isLiked' => ($likeData['data']['liked'] == 1) ? 1 : 0,
			'isDisliked' => ($likeData['data']['liked'] == -1) ? 1 : 0,
			'containerID' => $this->parameters['data']['containerID'],
			'users' => $likeData['users']
		);
	}
	
	/**
	 * Validates permissions for given object.
	 */
	protected function validateObjectParameters() {
		if (!MODULE_LIKE) {
			throw new PermissionDeniedException();
		}
		
		$this->readString('containerID', false, 'data');
		$this->readInteger('objectID', false, 'data');
		$this->readString('objectType', false, 'data');
		
		if (LikeHandler::getInstance()->getObjectType($this->parameters['data']['objectType']) === null) {
			throw new UserInputException('objectType');
		}
		
		// TODO: Validate object permissions
	}
}
