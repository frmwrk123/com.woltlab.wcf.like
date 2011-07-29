<?php
namespace wcf\data\like\object\type;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\IDatabaseObjectProcessor;

/**
 * Provides default a implementation for user notification types.
 *
 * @author	Alexander Ebert
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	data.like.object.type
 * @category 	Community Framework
 */
abstract class AbstractLikeObjectType extends DatabaseObjectDecorator implements IDatabaseObjectProcessor, ILikeObjectType {
	/**
	 * @see wcf\data\DatabaseObjectDecorator::$baseClass
	 */
	protected static $baseClass = 'wcf\data\like\object\type\LikeObjectType';
	
	/**
	 * @see	wcf\data\like\object\type\ILikeObjectType::increaseLikeCounter()
	 */
	public function increaseLikeCounter() { }
	
	/**
	 * @see	wcf\data\like\object\type\ILikeObjectType::decreaseLikeCounter()
	 */
	public function decreaseLikeCounter() { }
}
