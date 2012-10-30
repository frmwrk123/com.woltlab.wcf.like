<?php
namespace wcf\data\like\object\type;

/**
 * Any likeable object type should implement this interface.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	data.like.object.type
 * @category	Community Framework
 */
interface ILikeObjectType {
	/**
	 * Gets a like object by its ID.
	 * 
	 * @param	integer		$objectID
	 * @return	wcf\data\like\object\ILikeObject
	 */
	public function getObjectByID($objectID);
	
	/**
	 * Gets like objects by their IDs.
	 * 
	 * @param	array<integer>		$objectIDs
	 * @return	array<wcf\data\like\object\ILikeObject>
	 */
	public function getObjectsByIDs(array $objectIDs);
}
