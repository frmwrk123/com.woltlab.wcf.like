<?php
namespace wcf\data\like;
use wcf\data\object\type\IObjectTypeProvider;

/**
 * Default interface for like object type providers.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	data.like
 * @category	Community Framework
 */
interface ILikeObjectTypeProvider extends IObjectTypeProvider {
	/**
	 * Validates permissions for given object id.
	 * 
	 * @param	integer		$objectID
	 * @return	boolean
	 */
	public function checkPermissions($objectID);
}
