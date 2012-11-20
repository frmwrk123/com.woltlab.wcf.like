<?php
namespace wcf\data\like;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\like\LikeHandler;
use wcf\system\user\activity\event\UserActivityEventHandler;
use wcf\system\WCF;

/**
 * Executes like-related actions.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	data.like
 * @category	Community Framework
 */
class LikeAction extends AbstractDatabaseObjectAction {
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\like\LikeEditor';
	
	/**
	 * Validates parameters for like-related actions.
	 */
	public function validateLike() {
		if (!MODULE_LIKE) {
			throw new PermissionDeniedException();
		}
		
		if (!isset($this->parameters['data']['containerID'])) {
			throw new UserInputException('containerID');
		}
		
		if (!isset($this->parameters['data']['objectID'])) {
			throw new UserInputException('objectID');
		}
		
		if (!isset($this->parameters['data']['objectType'])) {
			throw new UserInputException('objectType');
		}
		if (LikeHandler::getInstance()->getObjectType($this->parameters['data']['objectType']) === null) {
			throw new UserInputException('objectType');
		}
		
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
				UserActivityEventHandler::getInstance()->fireEvent($objectType->objectType.'.recentActivityEvent', $objectType->packageID, $this->parameters['data']['objectID']);
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
}
