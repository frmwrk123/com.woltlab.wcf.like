<?php
namespace wcf\system\like;
use wcf\data\like\object\type\ILikeObjectType;
use wcf\data\like\object\type\LikeObjectType;
use wcf\data\like\object\LikeObject;
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
	 * @param	integer		$objectID
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
	 * @param	array			$objectIDs
	 * @return	integer			number of loaded result sets
	 */
	public function loadLikeObjects(LikeObjectType $likeObjectType, array $objectIDs) {
		$i = 0;
		
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("likeObjectTypeID = ?", array($likeObjectType->likeObjectTypeID));
		$conditions->add("objectID IN (?)", array($objectIDs));
		$parameters = $conditions->getParameters();
		
		if (WCF::getUser()->userID) {
			$sql = "SELECT		like_object.*,
						CASE WHEN like.userID IS NOT NULL THEN 1 ELSE 0 END AS liked
				FROM		wcf".WCF_N."_like_object like_object
				LEFT JOIN	wcf".WCF_N."_like like
				ON		(like.likeObjectTypeID = like_object.likeObjectTypeID
						AND like.objectID = like_object.objectID
						AND like.userID = ?
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
	 * @param	wcf\data\like\object\type\ILikeObjectType	$likeable
	 * @param	wcf\data\user\User		$user
	 * @param	integer		$time		
	 */
	public function like(ILikeObjectType $likeable, User $user, $time = TIME_NOW) {
		// verify if object is already liked by user
		$like = Like::getLike($likeable->getObjectType()->likeObjectTypeID, $likeable->getObjectID(), 1); // DEBUG ONLY: $user->userID);
		if ($like->likeID) {
			return false;
		}
		else {
			var_dump($like);exit;
		}
		
		// save like
		LikeEditor::create(array(
			'objectID' => $likeable->getObjectID(),
			'likeObjectTypeID' => $likeable->getObjectType()->likeObjectTypeID,
			'objectUserID' => $likeable->getUserID(),
			'userID' => 1,// DEBUG ONLY: $user->userID,
			'time' => $time
		));
		
		// create / update like cache
		$likeObject = Like::getLikeObject($likeable->getObjectType()->likeObjectTypeID, $likeable->getObjectID());
		if ($likeObject->likeObjectID) {
			// build update data
			$updateData = array('likes' => $likeObject->likes + 1);
			if (count($likeObject->getUsers()) < 3) {
				$users = unserialize($likeObject->cachedUsers);
				$users[$user->userID] = array('userID' => $user->userID, 'username' => $user->username);
				$updateData['cachedUsers'] = serialize($users);
			}
			
			// update data
			$likeObjectEditor = new LikeObjectEditor($likeObject);
			$likeObjectEditor->update($updateData);
		}
		else {
			// create cache
			LikeObjectEditor::create(array(
				'likeObjectTypeID' => $likeable->getObjectType()->likeObjectTypeID,
				'objectID' => $likeable->getObjectID(),
				'objectUserID' => $likeable->getUserID(),
				'likes' => 1,
				'cachedUsers' => serialize(array($user->userID => array('userID' => $user->userID, 'username' => $user->username)))
			));
		}
		
		// update owner's like counter 
		$userEditor = new UserEditor(new User($likeable->getUserID()));
		$userEditor->update(array(
			'likes' => $userEditor->likes + 1
		));
		
		// update object's like counter
		$likeable->increaseLikeCounter();
		
		// TODO: create notification
		
		return true;
	}
	
	/**
	 * Deletes the like of an object.
	 * 
	 * @param	wcf\data\like\object\type\ILikeObjectType	$likeable
	 * @param	wcf\data\user\User		$user
	 */
	public function unlike(ILikeObjectType $likeable, User $user) {
		// get like
		$like = Like::getLike($likeable->getObjectType()->likeObjectTypeID, $likeable->getObjectID(), 1); // DEBUG ONLY: $user->userID);
		if (!$like->likeID) {
			return false;
		}
		// delete like
		$editor = new LikeEditor($like);
		$editor->delete();
		
		// update like object cache
		$likeObject = LikeObject::getLikeObject($likeable->getObjectType()->likeObjectTypeID, $likeable->getObjectID());
		if ($likeObject->likeObjectID) {
			// build update data
			$updateData = array('likes' => $likeObject->likes - 1);
			$users = $likeObject->getUsers();
			if (isset($users[$user->userID])) {
				unset($users[$user->userID]);
				$updateData['cachedUsers'] = serialize($users);
			}
			
			// update data
			$likeObjectEditor = new LikeObjectEditor($likeObject);
			$likeObjectEditor->update($updateData);
		}
		
		// update owner's like counter 
		$user = new UserEditor(new User($likeable->getUserID()));
		$user->update(array(
			'likes' => $user->likes - 1
		));
		
		// update object's like counter
		$likeable->decreaseLikeCounter();
		
		return true;
	}
}
