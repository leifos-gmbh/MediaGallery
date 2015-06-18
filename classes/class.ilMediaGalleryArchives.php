<?php
/**
 * Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE
 * Date: 15.06.15
 * Time: 12:03
 */

class ilMediaGalleryArchives
{
	/**
	 * @var array
	 */
	protected static $objects = array();

	/**
	 * @var int
	 */
	protected $xmg_id;

	/**
	 * @var array
	 */
	protected $archives = array();

	/**
	 * @param int $a_xmg_id
	 * @return self
	 */
	public static function _getInstanceByXmgId($a_xmg_id)
	{
		if(!self::$objects[$a_xmg_id])
		{
			self::$objects[$a_xmg_id] = new self($a_xmg_id);
		}
		return self::$objects[$a_xmg_id];
	}

	public function __construct($a_xmg_id)
	{
		$this->setXmgId($a_xmg_id);
	}

	/**
	 * @param int $xmg_id
	 */
	public function setXmgId($xmg_id)
	{
		$this->xmg_id = $xmg_id;
	}

	/**
	 * @return int
	 */
	public function getXmgId()
	{
		return $this->xmg_id;
	}

	/**
	 * @param array $archives
	 */
	protected function setArchives($archives)
	{
		$this->archives = $archives;
	}

	/**
	 * @return array
	 */
	public function getArchives()
	{
		if(!count($this->archives) > 0)
		{
			$this->read();
		}

		return $this->archives;
	}

	/**
	 * @return \ilFSStorageMediaGallery
	 */
	protected function getFileSystem()
	{
		return ilFSStorageMediaGallery::_getInstanceByXmgId($this->getXmgId());
	}

	public function read()
	{
		global $ilDB;

		if(!$this->getXmgId())
		{
			return array();
		}

		$arr = array();

		$res = $ilDB->query("SELECT * FROM rep_robj_xmg_downloads WHERE xmg_id = ". $ilDB->quote($this->getXmgId(), "integer"));

		while($row = $ilDB->fetchAssoc($res))
		{
			$arr[$row["id"]] = array(
				"id" => $row["id"],
				"xmg_id" => $row["xmg_id"],
				"download_flag" => $row["download_flag"],
				"filename" => $row["filename"],
				"created" => filectime($this->getPath($row["filename"]))
			);
		}

		$this->setArchives($arr);

		return true;
	}

	public function setDownloadFlags(array $a_archives)
	{
		global $ilDB;

		if(!$this->getXmgId() && !count($a_archives) > 0)
		{
			return false;
		}

		$ilDB->manipulate("UPDATE rep_robj_xmg_downloads SET downloag_flag = 1 ".
			"WHERE xmg_id = ". $ilDB->quote($this->getXmgId(), "integer").
			" AND ". $ilDB->in("id", $a_archives));

		foreach($a_archives as $id)
		{
			$this->archives[$id]["downloag_flag"] = 1;
		}

		return true;
	}

	protected function addArchive($filename)
	{
		global $ilDB;

		if(!$this->getXmgId() && !$filename)
		{
			return false;
		}

		$id = $ilDB->nextId('rep_robj_xmg_downloads');

		$arr = array(
			"id" => array("integer", $id),
			"xmg_id" => array("integer", $this->getXmgId()),
			"download_flag" => array("integer", 0),
			"filename" => array("text", $filename)
		);

		$ilDB->insert("rep_robj_xmg_downloads", $arr);

		$this->archives[$id] = $arr;
		return true;
	}

	public function deleteArchives(array $a_archive_ids)
	{
		global $ilDB;

		if(is_array($a_archive_ids))
		{
			return false;
		}

		$ilDB->manipulate("DELETE FROM rep_robj_xmg_downloads ".
			"WHERE ".$ilDB->in("id",$a_archive_ids)."");

		foreach($this->getArchives() as $archive)
		{
			if(in_array($archive["id"], $a_archive_ids))
			{
				$this->getFileSystem()->deleteFile($archive["filename"], ilObjMediaGallery::LOCATION_DOWNLOADS);
				unset($this->archives[$archive["id"]]);
			}
		}

		return true;
	}

	public function renameArchive($a_archive_id, $a_new_name)
	{
		global $ilDB;

		if($a_archive_id && !$a_new_name)
		{
			return false;
		}

		$ilDB->manipulate("UPDATE rep_robj_xmg_downloads SET filename = ".$ilDB->quote($a_new_name, "text").
			" WHERE id = ". $ilDB->quote($a_archive_id, "integer")."");

		$this->archives[$a_archive_id]["filename"] = $a_new_name;

		return true;
	}

	public function createArchive($a_file_array, $a_zip_filename)
	{
		$files = array();
		$a_zip_filename = ilUtil::getASCIIFilename($a_zip_filename);

		$tmp_dir = $this->getFileSystem()->getPath()."tmp_".time();

		ilUtil::createDirectory($tmp_dir);

		foreach ((array) $a_file_array as $file_id)
		{
			$file = ilMediaGalleryFile::_getInstanceById($file_id);
			$this->getFileSystem()->copyFile(
				$file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS),
				$tmp_dir."/". $file->getFilename()
			);
		}

		ilUtil::zip($files, $this->getFileSystem()->getPath(ilObjMediaGallery::LOCATION_DOWNLOADS) . $a_zip_filename, false);

		$this->addArchive($a_zip_filename);
	}

	public function getPath($a_filename, $a_web = false)
	{
		return $this->getFileSystem()->getFilePath(ilObjMediaGallery::LOCATION_DOWNLOADS, $a_filename, $a_web);
	}

	public function  resetCache()
	{
		$this->archives = array();
	}

	public static function _archiveExist($a_xmg_id, $a_archive_id)
	{
		return in_array($a_archive_id, array_keys(self::_getInstanceByXmgId($a_xmg_id)->getArchives()));
	}
} 