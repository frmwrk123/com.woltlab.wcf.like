<?php
namespace wcf\data\like\object\type;

/**
 * Any likeable object type should implement this interface.
 *
 * @author	Marcel Werk
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	data.like.object
 * @category 	Community Framework
 */
interface ILikeObjectType {
	/**
	 * Returns the title of this likeable.
	 * 
	 * @return	string
	 */
	public function getTitle();
	
	/**
	 * Returns the url to this likeable.
	 * 
	 * @return	string
	 */
	public function getURL();
	
	/**
	 * Returns the user id of the owner of this object.
	 * 
	 * @return	integer
	 */
	public function getUserID();
	
	/**
	 * Returns the id of this object.
	 * 
	 * @return	integer
	 */
	public function getObjectID();
	
	/**
	 * Gets the object type.
	 * 
	 * @return	wcf\data\like\object\type\LikeObjectType
	 */
	public function getObjectType();
	
	/**
	 * Increases the like counter of this object.
	 */
	public function increaseLikeCounter();
	
	/**
	 * Decreases the like counter of this object.
	 */
	public function decreaseLikeCounter();
}
