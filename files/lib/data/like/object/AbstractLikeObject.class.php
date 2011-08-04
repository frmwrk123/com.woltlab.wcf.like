<?php
namespace wcf\data\like\object;
use wcf\data\like\object\type\LikeObjectType;
use wcf\data\like\Like;
use wcf\data\DatabaseObjectDecorator;

/**
 * Provides a default implementation for like objects.
 *
 * @author	Alexander Ebert
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	data.like.object
 * @category 	Community Framework
 */
abstract class AbstractLikeObject extends DatabaseObjectDecorator implements ILikeObject {
	/**
	 * @see wcf\data\DatabaseObjectDecorator::$baseClass
	 */
	protected static $baseClass = 'wcf\data\like\object\LikeObject';
	
	protected $objectType = null;
	
	/**
	 * @see	wcf\data\like\object\ILikeObject::increaseLikeCounter()
	 */
	public function increaseLikeCounter($isDislike) { }
	
	/**
	 * @see	wcf\data\like\object\ILikeObject::decreaseLikeCounter()
	 */
	public function decreaseLikeCounter(Like $like) { }
	
	public function getObjectType() {
		return $this->objectType;
	}
	
	public function setObjectType(LikeObjectType $objectType) {
		$this->objectType = $objectType;
	}
}
