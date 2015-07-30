<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/FileSystem/classes/class.ilFileSystemStorageWebAccessChecker.php";

/**
* Class ilMediaGalleryWebAccessChecker
*
* @author Fabian Wolf <wolf@leifos.com>
* @version $Id$
*
* @ingroup ModulesPoll
*/
class ilMediaGalleryWebAccessChecker extends ilFileSystemStorageWebAccessChecker
{
	public function isValidPath(array $a_path)
	{
		// last element is file
		array_pop($a_path);
		array_pop($a_path);

		// 3rd to last: directory with object id
		$dir = array_pop($a_path);

		// extract id from directory title
		$obj_id = (int)array_pop(explode("_", $dir));
		if((int)$obj_id)
		{
			$this->object_id = $obj_id;
			return true;
		}
	}
}
