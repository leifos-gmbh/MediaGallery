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

	/**
	 * read data from db
	 *
	 * @return array|bool
	 */
	public function read()
	{
		global $ilDB;

		if(!$this->getXmgId())
		{
			return false;
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
				"created" => filectime($this->getPath($row["filename"])),
				"size" => filesize($this->getPath($row["filename"]))
			);
		}

		$this->setArchives($arr);

		return true;
	}

	/**
	 * write download flags into db
	 *
	 * @param array $a_archives
	 * @return bool
	 */
	public function setDownloadFlags(array $a_archives)
	{
		global $ilDB;

		if(!$this->getXmgId() && !is_array($a_archives))
		{
			return false;
		}
		$ilDB->manipulate("UPDATE rep_robj_xmg_downloads SET download_flag = ". $ilDB->quote(0, "integer").
			" WHERE xmg_id = ". $ilDB->quote($this->getXmgId(), "integer"));

		if(count($a_archives) > 0)
		{
			$ilDB->manipulate("UPDATE rep_robj_xmg_downloads SET download_flag = ". $ilDB->quote(1, "integer").
				" WHERE xmg_id = ". $ilDB->quote($this->getXmgId(), "integer").
				" AND ". $ilDB->in("id", $a_archives, false, "integer"));
		}


		foreach($a_archives as $id)
		{
			$this->archives[$id]["downloag_flag"] = 1;
		}

		return true;
	}

	/**
	 * write archive into db
	 *
	 * @param string $filename
	 * @return bool
	 */
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

	/**
	 * delete archives in filesystem and in db
	 *
	 * @param array $a_archive_ids
	 * @return bool
	 */
	public function deleteArchives(array $a_archive_ids)
	{
		global $ilDB;

		if(!is_array($a_archive_ids))
		{
			return false;
		}

		$this->read();

		$ilDB->manipulate("DELETE FROM rep_robj_xmg_downloads ".
			"WHERE ".$ilDB->in("id",$a_archive_ids, false, 'integer')."");

		foreach($this->getArchives() as $archive)
		{
			if(in_array((string) $archive['id'], $a_archive_ids))
			{
				$this->getFileSystem()->deleteFile($archive["filename"], ilObjMediaGallery::LOCATION_DOWNLOADS);
				unset($this->archives[$archive["id"]]);
			}
		}
		return true;
	}

	/**
	 * renames archive by archive name
	 *
	 * @param string $a_old_name
	 * @param string $a_new_name
	 * @return bool
	 */
	public function renameArchive($a_old_name, $a_new_name)
	{
		global $ilDB;

		if($a_old_name && !$a_new_name)
		{
			return false;
		}

		if($a_new_name == $a_old_name)
		{
			return true;
		}
		$ilDB->manipulate("UPDATE rep_robj_xmg_downloads SET filename = ".$ilDB->quote($a_new_name, "text").
			" WHERE filename = ". $ilDB->quote($a_old_name, "text")." AND xmg_id = " . $ilDB->quote($this->getXmgId(), "integer"));

		rename($this->getPath($a_old_name), $this->getPath($a_new_name));

		$this->resetCache();

		return true;
	}

	/**
	 * creates new archive by file array
	 *
	 * @param array $a_file_array
	 * @param string $a_zip_filename
	 * @return bool
	 */
	public function createArchive($a_file_array, $a_zip_filename)
	{
		if(count($a_file_array) <= 0)
		{
			return false;
		}
		$a_zip_filename = ilUtil::getASCIIFilename($a_zip_filename);

		$tmp_dir = ilUtil::getDataDir() . "/temp/"."tmp_".time();

		if(!file_exists(ilUtil::getDataDir() . "/temp/"))
		{
			ilUtil::createDirectory(ilUtil::getDataDir() . "/temp/");
		}

		ilUtil::createDirectory($tmp_dir);

		foreach ((array) $a_file_array as $file_id)
		{
			$file = ilMediaGalleryFile::_getInstanceById($file_id);
			$path = $tmp_dir. '/' .$file->getFilename();
			$this->getFileSystem()->copyFile(
				$file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS),
				$path
			);
		}

		$ret = ilUtil::zip($tmp_dir,  $tmp_dir. '/' .$a_zip_filename, true);

		rename($tmp_dir. '/' .$a_zip_filename, $this->getFileSystem()->getFilePath(ilObjMediaGallery::LOCATION_DOWNLOADS, $a_zip_filename));
		//var_dump($ret, $files,$this->getFileSystem()->getFilePath(ilObjMediaGallery::LOCATION_DOWNLOADS, $a_zip_filename));
		$this->getFileSystem()->deleteDir($tmp_dir);

		if(!$ret)
		{
			return false;
		}

		$this->addArchive($a_zip_filename);

		return true;
	}

	/**
	 * returns archive path
	 *
	 * @param string $a_filename
	 * @return string
	 */
	public function getPath($a_filename)
	{
		return $this->getFileSystem()->getFilePath(ilObjMediaGallery::LOCATION_DOWNLOADS, $a_filename);
	}

	/**
	 * reset all caches
	 */
	public function  resetCache()
	{
		$this->archives = array();
	}

	/**
	 * returns archive filename by archive id
	 *
	 * @param int $a_id
	 * @return string
	 */
	public function getArchiveFilename($a_id)
	{
		global $ilDB;

		if(!$this->archives[$a_id])
		{
			$res = $ilDB->query("SELECT filename FROM rep_robj_xmg_downloads WHERE id = ". $ilDB->quote($a_id, "integer"));

			$row = $ilDB->fetchAssoc($res);

			return  $row["filename"];
		}

		return $this->archives[$a_id]['filename'];

	}

	/**
	 * clones all archives from  source gallery object to destination gallery object
	 *
	 * @param int $a_source_xmg_id
	 * @param int $a_dest_xmg_id
	 */
	public static function _clone($a_source_xmg_id, $a_dest_xmg_id)
	{
		$dest = self::_getInstanceByXmgId($a_dest_xmg_id);
		$source = self::_getInstanceByXmgId($a_source_xmg_id);
		foreach($source->getArchives() as $archive)
		{
			$s_path = $source->getPath($archive['filename']);
			$d_path = $dest->getPath($archive['filename']);

			@copy($s_path, $d_path);

			$dest->addArchive($archive['filename']);
		}
	}

	/**
	 * looks if archive exists by gallery id and archive id
	 *
	 * @param int $a_xmg_id
	 * @param int $a_archive_id
	 * @return bool
	 */
	public static function _archiveExist($a_xmg_id, $a_archive_id)
	{
		return in_array($a_archive_id, array_keys(self::_getInstanceByXmgId($a_xmg_id)->getArchives()));
	}
} 