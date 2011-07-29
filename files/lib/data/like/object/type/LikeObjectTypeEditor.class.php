<?php
namespace wcf\data\like\object\type;
use wcf\data\DatabaseObjectEditor;

/**
 * Extends the LikeObjectType object with functions to create, update and delete liked object types.
 *
 * @author	Marcel Werk
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	data.like.object.type
 * @category 	Community Framework
 */
class LikeObjectTypeEditor extends DatabaseObjectEditor {
	/**
	 * @see	wcf\data\DatabaseObjectEditor::$baseClass
	 */
	protected static $baseClass = 'wcf\data\like\object\type\LikeObjectType';
}
