<?php
namespace wcf\data\like\object\type;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\IDatabaseObjectProcessor;

/**
 * Provides a default implementation for like object types.
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
}
