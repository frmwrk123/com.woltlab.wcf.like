<?php
namespace wcf\data\like\object\type;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of like object types.
 * 
 * @author 	Alexander Ebert
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	data.like.object.type
 * @category 	Community Framework
 */
class LikeObjectTypeList extends DatabaseObjectList {
	/**
	 * @see	wcf\data\DatabaseObjectList::$className
	 */
	public $className = 'wcf\data\like\object\type\LikeObjectType';
}
