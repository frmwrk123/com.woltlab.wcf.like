<?php
namespace wcf\data\like\object;
use wcf\data\user\User;
use wcf\data\DatabaseObject;

/**
 * Represents a liked object.
 *
 * @author	Marcel Werk
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	data.like.object
 * @category 	Community Framework
 */
class LikeObject extends DatabaseObject {
	/**
	 * @see	wcf\data\DatabaseObject::$databaseTableName
	 */
	protected static $databaseTableName = 'like_object';
	
	/**
	 * @see	wcf\data\DatabaseObject::$databaseIndexName
	 */
	protected static $databaseTableIndexName = 'likeObjectID';
	
	/**
	 * list of users who liked this object
	 * @var array<wcf\data\user\User>
	 */
	protected $users = array();
	
	/**
	 * @see wcf\data\DatabaseObject::handleData();
	 */
	protected function handleData($data) {
		parent::handleData($data);
		
		// get user objects from cache
		if (!empty($data['cachedUsers'])) {
			$cachedUsers = @unserialize($data['cachedUsers']);
			
			if (is_array($cachedUsers)) {
				foreach ($cachedUsers as $cachedUserData) {
					$user = new User(null, $cachedUserData);
					$this->users[$user->userID] = $user;
				}
			}
		}
	}
	
	/**
	 * Gets the first 3 users who liked this object.
	 * 
	 * @return array<wcf\data\user\User>
	 */
	public function getUsers() {
		$this->users;
	}
	
	/**
	 * Gets a like object by type and object id.
	 * 
	 * @param	integer		$likeObjectTypeID
	 * @param	integer		$objectID
	 * @return	wcf\data\like\object\LikeObject
	 */
	public static function getLikeObject($likeObjectTypeID, $objectID) {
		$sql = "SELECT	*
			FROM	wcf".WCF_N."_like_object
			WHERE	likeObjectTypeID = ?
				AND objectID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$likeObjectTypeID,
			$objectID
		));
		$row = $statement->fetchArray();
		
		if (!$row) {
			$row = array();
		}
		
		return new LikeObject(null, $row);
	}
}
