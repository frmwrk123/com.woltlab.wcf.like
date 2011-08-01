<?php
namespace wcf\data\like;
use wcf\data\DatabaseObject;
use wcf\system\WCF;

/**
 * Represents a like of an object.
 *
 * @author	Marcel Werk
 * @copyright	2009-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	data.like
 * @category 	Community Framework
 */
class Like extends DatabaseObject {
	/**
	 * @see	wcf\data\DatabaseObject::$databaseTableName
	 */
	protected static $databaseTableName = 'like';
	
	/**
	 * @see	wcf\data\DatabaseObject::$databaseIndexName
	 */
	protected static $databaseTableIndexName = 'likeID';
	
	/**
	 * Gets a like by type, object id and user id.
	 * 
	 * @param	integer		$likeObjectTypeID
	 * @param	integer		$objectID
	 * @param	integer		$userID
	 * @return	Like
	 */
	public static function getLike($likeObjectTypeID, $objectID, $userID) {
		$sql = "SELECT	*
			FROM	wcf".WCF_N."_like
			WHERE	likeObjectTypeID = ?
				AND objectID = ?
				AND objectUserID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$likeObjectTypeID,
			$objectID,
			$userID
		));
		
		$row = $statement->fetchArray();
		
		if (!$row) {
			$row = array();
		}
		
		return new Like(null, $row);
	}
}
