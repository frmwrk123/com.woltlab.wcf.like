<?php
namespace wcf\data\like\object;
use wcf\data\like\object\type\LikeObjectType;
use wcf\data\like\Like;
use wcf\data\IDatabaseObjectProcessor;

/**
 * Any likeable object should implement this interface.
 *
 * @author	Marcel Werk
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	data.like.object
 * @category 	Community Framework
 */
interface ILikeObject extends IDatabaseObjectProcessor {
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
	 * 
	 * @param	boolean			$isDislike
	 */
	public function increaseLikeCounter($isDislike);
	
	/**
	 * Decreases the like counter of this object.
	 * 
	 * @param	wcf\data\like\Like	$like
	 */
	public function decreaseLikeCounter(Like $like);
	
	/**
	 * Sets the object type.
	 * 
	 * @param	wcf\data\like\object\type\LikeObjectType
	 */
	public function setObjectType(LikeObjectType $objectType);
}
