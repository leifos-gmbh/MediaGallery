<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");
 
/**
* MediaGallery repository object plugin
*
* @author Helmut Schottmüller <ilias@aurealis.de>
* @version $Id$
*
*/
class ilMediaGalleryPlugin extends ilRepositoryObjectPlugin
{
	function getPluginName()
	{
		return "MediaGallery";
	}

	public function uninstallCustom()
	{
		global $ilDB;

		if ($ilDB->tableExists('rep_robj_xmg_filedata'))
		{
			$ilDB->dropTable('rep_robj_xmg_filedata');
		}

		if ($ilDB->tableExists('rep_robj_xmg_downloads'))
		{
			$ilDB->dropTable('rep_robj_xmg_downloads');
		}

		if ($ilDB->tableExists('rep_robj_xmg_object'))
		{
			$ilDB->dropTable('rep_robj_xmg_object');
		}
		$this->includeClass("class.ilFSStorageMediaGallery.php");
		ilFSStorageMediaGallery::_deletePluginData();

		$query = "DELETE FROM il_wac_secure_path ".
			"WHERE path = ".$ilDB->quote('ilXmg','text');

		$res = $ilDB->manipulate($query);

		include_once './Services/Administration/classes/class.ilSetting.php';
		$setting = new ilSetting("xmg");
		$setting->deleteAll();
	}

    /**
     * decides if this repository plugin can be copied
     *
     * @return bool
     */
    public function allowCopy()
    {
        return true;
    }
}
?>