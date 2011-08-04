<?php
namespace wcf\system\like;
use wcf\data\like\object\type\LikeObjectType;
use wcf\data\like\object\ILikeObject;
use wcf\data\like\object\LikeObject;
use wcf\data\like\object\LikeObjectEditor;
use wcf\data\like\Like;
use wcf\data\like\LikeEditor;
use wcf\data\user\User;
use wcf\data\user\UserEditor;
use wcf\system\cache\CacheHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\SingletonFactory;
use wcf\system\WCF;

/**
 * Handles the likes of liked objects.
 * 
 * Usage (retrieve all likes for a list of objects):
 * // get type object
 * $likeObjectType = LikeHandler::getInstance()->getLikeObjectType('com.woltlab.wcf.foo.bar');
 * // load like data
 * LikeHandler::getInstance()->loadLikeObjects($likeObjectType, $objectIDs);
 * // get like data
 * $likeObjects = LikeHandler::getInstance()->getLikeObjects($likeObjectType);
 *
 * @author	Marcel Werk
 * @copyright	2009-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	system.like
 * @category 	Community Framework
 */
class LikeHandler extends SingletonFactory {
	/**
	 * loaded like objects
	 * @var array<array>
	 */
	protected $likeObjectCache = array();
	
	/**
	 * cached like object types
	 * @var	array<array>
	 */
	protected $likeObjectTypeCache = null;
	
	/**
	 * Creates a new LikeHandler instance.
	 */
	protected function init() {
		// load cache
		CacheHandler::getInstance()->addResource(
			'likeObjectTypes',
			WCF_DIR.'cache/cache.likeObjectTypes.php',
			'wcf\system\cache\builder\CacheBuilderLikeObjectType'
		);
		$this->likeObjectTypeCache = CacheHandler::getInstance()->get('likeObjectTypes');
	}
	
	/**
	 * Returns a like object type from cache.
	 * 
	 * @return	wcf\data\like\object\type\LikeObjectType
	 */
	public function getLikeObjectType($objectName, $packageID = PACKAGE_ID) {
		if (isset($this->likeObjectTypeCache[$objectName][$packageID])) {
			return $this->likeObjectTypeCache[$objectName][$packageID];
		}
		
		return null;
	}
	
	/**
	 * Gets a like object. 
	 * 
	 * @param	wcf\data\like\object\type\LikeObjectType	$likeObjectType
	 * @param	integer						$objectID
	 * @return	wcf\data\like\object\LikeObject
	 */
	public function getLikeObject(LikeObjectType $likeObjectType, $objectID) {
		if (isset($this->likeObjectCache[$likeObjectType->likeObjectTypeID][$objectID])) {
			return $this->likeObjectCache[$likeObjectType->likeObjectTypeID][$objectID];
		}
		
		return null;
	}
	
	/**
	 * Gets the like objects of a specific object type. 
	 * 
	 * @param	wcf\data\like\object\type\LikeObjectType	$likeObjectType
	 * @return	array<wcf\data\like\object\LikeObject>
	 */
	public function getLikeObjects(LikeObjectType $likeObjectType) {
		if (isset($this->likeObjectCache[$likeObjectType->likeObjectTypeID])) {
			return $this->likeObjectCache[$likeObjectType->likeObjectTypeID];
		}
		
		return array();
	}
	
	/**
	 * Loads the like data for a set of objects.
	 * 
	 * @param	wcf\data\like\object\type\LikeObjectType	$likeObjectType
	 * @param	array						$objectIDs
	 * @return	integer						number of loaded result sets
	 */
	public function loadLikeObjects(LikeObjectType $likeObjectType, array $objectIDs) {
		$i = 0;
		
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("like_object.likeObjectTypeID = ?", array($likeObjectType->likeObjectTypeID));
		$conditions->add("like_object.objectID IN (?)", array($objectIDs));
		$parameters = $conditions->getParameters();
		
		if (1) { // DEBUG ONLY: WCF::getUser()->userID) {
			$sql = "SELECT		like_object.*,
						CASE WHEN like_table.userID IS NOT NULL THEN 1 ELSE 0 END AS liked
				FROM		wcf".WCF_N."_like_object like_object
				LEFT JOIN	wcf".WCF_N."_like like_table
				ON		(like_table.likeObjectTypeID = like_object.likeObjectTypeID
						AND like_table.objectID = like_object.objectID
						AND like_table.userID = ?)
				".$conditions;
			
			array_unshift($parameters, WCF::getUser()->userID);
		}
		else {
			$sql = "SELECT		like_object.*, 0 AS liked
				FROM		wcf".WCF_N."_like_object like_object
				".$conditions;
		}
		
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($parameters);
		while ($row = $statement->fetchArray()) {
			$this->likeObjectCache[$likeObjectType->likeObjectTypeID][$row['objectID']] = new LikeObject(null, $row);
			$i++;
		}
		
		return $i;
	}
	
	/**
	 * Saves the like of an object.
	 * 
	 * @param	wcf\data\like\object\type\ILikeObject	$likeable
	 * @param	wcf\data\user\User			$user
	 * @param	boolean					$isDislike
	 * @param	integer					$time		
	 */
	public function like(ILikeObject $likeable, User $user, $isDislike, $time = TIME_NOW) {
		// verify if object is already liked by user
		$like = Like::getLike($likeable->getObjectType()->likeObjectTypeID, $likeable->getObjectID(), 1); // DEBUG ONLY: $user->userID);
		$likeValue = ($isDislike) ? Like::DISLIKE : Like::LIKE;
		
		// get like object
		$likeObject = LikeObject::getLikeObject($likeable->getObjectType()->likeObjectTypeID, $likeable->getObjectID());
		
		// if vote is identically just revert the vote
		if ($like->likeID && ($like->likeValue == $likeValue)) {
			return $this->revertLike($like, $likeObject, $user);
		}
		
		// update existing object
		if ($likeObject->likeObjectID) {
			$likes = $likeObject->likes;
			$dislikes = $likeObject->dislikes;
			$cumulativeLikes = $likeObject->cumulativeLikes;
			
			// previous (dis-)like already exists
			if ($like->likeID) {
				// revert like and replace it with a dislike
				if ($like->likeValue == Like::LIKE) {
					$likes--;
					$dislikes++;
					$cumulativeLikes -= 2;
				}
				else {
					// revert dislike and replace it with a like
					$likes++;
					$dislikes--;
					$cumulativeLikes += 2;
				}
			}
			else {
				if (!$isDislike) {
					$likes++;
					$cumulativeLikes++;
				}
				else {
					$dislikes++;
					$cumulativeLikes--;
				}
			}
			
			// build update date
			$updateData = array(
				'likes' => $likes,
				'dislikes' => $dislikes,
				'cumulativeLikes' => $cumulativeLikes
			);
			
			if (count($likeObject->getUsers()) < 3) {
				$users = unserialize($likeObject->cachedUsers);
				$users[$user->userID] = array($user->userID => array('userID' => $user->userID, 'username' => $user->username));
				$updateData['cachedUsers'] = serialize($users);
			}
			
			// update data
			$likeObjectEditor = new LikeObjectEditor($likeObject);
			$likeObjectEditor->update($updateData);
		}
		else {
			// create cache
			$likeObject = LikeObjectEditor::create(array(
				'likeObjectTypeID' => $likeable->getObjectType()->likeObjectTypeID,
				'objectID' => $likeable->getObjectID(),
				'objectUserID' => $likeable->getUserID(),
				'likes' => ($isDislike) ? 0 : 1,
				'dislikes' => ($isDislike) ? 1 : 0,
				'cumulativeLikes' => ($isDislike) ? -1 : 1,
				'cachedUsers' => serialize(array($user->userID => array('userID' => $user->userID, 'username' => $user->username)))
			));
		}
		
		// update owner's like counter
		if ($like->likeID) {
			$userEditor = new UserEditor(new User($likeable->getUserID()));
			$userEditor->update(array(
				'likes' => ($like->likeValue == Like::LIKE) ? $userEditor->likes -1 : $userEditor->likes + 1
			));
		}
		else if (!$isDislike) {
			$userEditor = new UserEditor(new User($likeable->getUserID()));
			$userEditor->update(array(
				'likes' => $userEditor->likes + 1
			));
		}
		
		if (!$like->likeID) {
			// save like
			$like = LikeEditor::create(array(
				'objectID' => $likeable->getObjectID(),
				'likeObjectTypeID' => $likeable->getObjectType()->likeObjectTypeID,
				'objectUserID' => $likeable->getUserID(),
				'userID' => 1,// DEBUG ONLY: $user->userID,
				'time' => $time,
				'likeValue' => ($isDislike) ? Like::DISLIKE : Like::LIKE
			));
		}
		else {
			$likeEditor = new LikeEditor($like);
			$likeEditor->update(array(
				'time' => $time,
				'likeValue' => ($isDislike) ? Like::DISLIKE : Like::LIKE
			));
		}
		
		// update object's like counter
		$likeable->increaseLikeCounter($isDislike);
		
		// TODO: create notification
		
		return $this->loadLikeStatus($likeObject, $user);
	}
	
	/**
	 * Reverts the like of an object.
	 * 
	 * @param	wcf\data\like\Like			$like
	 * @param	wcf\data\like\object\LikeObject		$likeObject
	 * @param	wcf\data\user\User			$user
	 */
	public function revertLike(Like $like, LikeObject $likeObject, User $user) {
		// delete like
		$editor = new LikeEditor($like);
		$editor->delete();
		
		// update like object cache
		$likes = $likeObject->likes;
		$dislikes = $likeObject->dislikes;
		$cumulativeLikes = $likeObject->cumulativeLikes;
			
		if ($like->likeValue == Like::LIKE) {
			$likes--;
			$cumulativeLikes--;
		}
		else {
			$dislikes--;
			$cumulativeLikes++;
		}
			
		// build update data
		$updateData = array(
			'likes' => $likes,
			'dislikes' => $dislikes,
			'cumulativeLikes' => $cumulativeLikes
		);
			
		$users = $likeObject->getUsers();
		if (isset($users[1])) { // DEBUG ONLY: $user->userID])) {
			unset($users[1]); // DEBUG ONLY: $user->userID]);
			$updateData['cachedUsers'] = serialize($users);
		}
			
		$likeObjectEditor = new LikeObjectEditor($likeObject);
		if (empty($users)) {
			// remove object instead
			$likeObjectEditor->delete();
		}
		else {
			// update data
			$likeObjectEditor->update($updateData);
		}
		
		// update owner's like counter
		if ($like->likeValue == Like::LIKE) {
			$user = new UserEditor(new User($likeObject->getUserID()));
			$user->update(array(
				'likes' => $user->likes - 1
			));
		}
		
		// update object's like counter
		$likeable->decreaseLikeCounter($like);
		
		return $this->loadLikeStatus($likeObject, $user);
	}
	
	protected function loadLikeStatus(LikeObject $likeObject, User $user) {
		$sql = "SELECT		like_object.likes, like_object.dislikes, like_object.cumulativeLikes,
					CASE WHEN like_table.likeValue IS NOT NULL THEN like_table.likeValue ELSE 0 END AS liked
			FROM		wcf".WCF_N."_like_object like_object
			LEFT JOIN	wcf".WCF_N."_like like_table
			ON		(like_table.objectID = like_object.objectID
					AND like_table.userID = ?)
			WHERE		like_object.likeObjectID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			1, // DEBUG ONLY: $user->userID
			$likeObject->likeObjectID
		));
		$row = $statement->fetchArray();
		
		return $row;
	}
}
