<?php
namespace wcf\system\cache\builder;
use wcf\data\like\object\type\LikeObjectTypeList;
use wcf\system\cache\ICacheBuilder;

/**
 * Caches like object types.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.like
 * @subpackage	system.cache
 * @category 	Community Framework
 */
class CacheBuilderLikeObjectType implements ICacheBuilder {
	/**
	 * @see	wcf\system\cache\ICacheBuilder::getData()
	 */
	public function getData($cacheResource) {
		$data = array();
		
		// get like object types
		$typeList = new LikeObjectTypeList();
		$typeList->sqlLimit = 0;
		$typeList->readObjects();
		
		foreach ($typeList->getObjects() as $objectType) {
			if (!isset($data[$objectType->objectTypeName])) {
				$data[$objectType->objectTypeName] = array();
			}
			
			$data[$objectType->objectTypeName][$objectType->packageID] = $objectType;
		}
		
		return $data;
	}
}
