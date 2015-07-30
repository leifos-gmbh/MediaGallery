<?php
/**
 * Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE
 * Date: 11.06.15
 * Time: 13:08
 */
include_once("./Services/FileSystem/classes/class.ilFileSystemStorage.php");

/**
 * Class ilFSStorageMediaGallery
 *
 * @author Fabian Wolf <wolf@leifos.com>
 * @version $Id$
 *
 */
class ilFSStorageMediaGallery extends ilFileSystemStorage
{
	private $log;

	/**
	 * @var array
	 */
	protected static $objects = array();

	/**
	 * @var array
	 */
	protected $files_cache;

	/**
	 * @var array
	 */
	protected $mime_cache;

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

	/**
	 * Constructor
	 *
	 * @access public
	 *
	 */
	public function __construct($a_container_id = 0)
	{
		global $log;

		$this->log = $log;
		parent::__construct(ilFileSystemStorage::STORAGE_SECURED,false,$a_container_id);
	}

	function getPathPrefix()
	{
		return 'ilXmg';
	}
	function getPathPostfix()
	{
		return 'xmg';
	}

	/**
	 * returns file path of a given file id or file name at a given location
	 *
	 * @param int $a_location
	 * @param int $a_file_id
	 * @return string
	 */
	function getFilePath($a_location,$a_file_id = 0)
	{
		$path = $this->getPath($a_location);

		switch ($a_location)
		{
			case ilObjMediaGallery::LOCATION_THUMBS:
			case ilObjMediaGallery::LOCATION_SIZE_SMALL:
			case ilObjMediaGallery::LOCATION_SIZE_MEDIUM:
			case ilObjMediaGallery::LOCATION_SIZE_LARGE:
				if($this->getMimeType($a_file_id) == 'image/tiff')
				{
					$path .= $a_file_id.".png";
				}
				else
				{
					$fname = $this->getFilename($a_file_id);

					if(!$fname)
					{
						$fname = $this->getFilename($a_file_id, $a_location);
					}

					$path .= $fname;
				}

				break;
			case ilObjMediaGallery::LOCATION_ORIGINALS:
				$path .= $this->getFilename($a_file_id);
				break;
			case ilObjMediaGallery::LOCATION_PREVIEWS:
			case ilObjMediaGallery::LOCATION_DOWNLOADS:
				$path .= $a_file_id;
				break;
		}

		return $path;
	}

	/**
	 * return exact file name of a give file id and location
	 *
	 * @param int $a_file_id
	 * @param int $a_location
	 * @return bool
	 */
	protected function getFilename($a_file_id, $a_location = ilObjMediaGallery::LOCATION_ORIGINALS)
	{
		if(!isset($this->files_cache[$a_location]))
		{
			if(!file_exists($this->getPath($a_location)))
			{
				ilUtil::makeDir($this->getPath($a_location));
			}

			$this->files_cache[$a_location] = scandir($this->getPath($a_location));
		}

		foreach($this->files_cache[$a_location]  as $name)
		{
			$fname = pathinfo($this->getPath($a_location). $name, PATHINFO_FILENAME );
			if($fname == $a_file_id)
			{
				return $name;
			}
		}

		return false;
	}

	/**
	 * deletes all file of a given file id or deletes a file at a given location
	 *
	 * @param int $a_file_id
	 * @param int  $a_location
	 * @return bool
	 */
	public function deleteFile($a_file_id, $a_location = null)
	{
		if($a_location == null)
		{
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_PREVIEWS);
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_THUMBS);
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_SIZE_LARGE);
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_SIZE_MEDIUM);
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_SIZE_SMALL);
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_ORIGINALS);
			return true;
		}

		$path = $this->getFilePath($a_location, $a_file_id);

		if(is_dir($path))
		{
			return false;
		}

		$ret = parent::deleteFile($path);

		if(isset($this->files_cache[$a_location]))
		{
			unset($this->files_cache[$a_location]);
		}

		if(isset($this->mime_cache[$a_file_id][$a_location]))
		{
			unset($this->mime_cache[$a_file_id][$a_location]);
		}

		return $ret;
	}

	/**
	 * delete directory
	 *
	 * @param int|string $a_location
	 * @return bool
	 */
	public function deleteDir($a_location)
	{
		if(is_dir($a_location))
		{
			parent::deleteDirectory($a_location);
			return true;
		}

		if( in_array($a_location, array(
				ilObjMediaGallery::LOCATION_PREVIEWS,
				ilObjMediaGallery::LOCATION_ORIGINALS,
				ilObjMediaGallery::LOCATION_DOWNLOADS,
				ilObjMediaGallery::LOCATION_SIZE_LARGE,
				ilObjMediaGallery::LOCATION_SIZE_MEDIUM,
				ilObjMediaGallery::LOCATION_SIZE_SMALL,
				ilObjMediaGallery::LOCATION_THUMBS,
				ilObjMediaGallery::LOCATION_ROOT
			)))
		{
			parent::deleteDirectory($this->getPath($a_location));
			return true;
		}
		return false;
	}

	/**
	 * returns mime type of a give file at a given location
	 *
	 * @param int $a_file_id
	 * @param int $a_location
	 * @return string
	 */
	public function getMimeType($a_file_id, $a_location = ilObjMediaGallery::LOCATION_ORIGINALS)
	{

		if(!isset($this->mime_cache[$a_file_id][$a_location]))
		{
			include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";
			$this->mime_cache[$a_file_id][$a_location] = ilMimeTypeUtil::getMimeType($this->getFilePath($a_location, $a_file_id));
		}

		return 	$this->mime_cache[$a_file_id][$a_location];
	}

	/**
	 * returns folder path of a given location
	 *
	 * @param int $a_location
	 * @return string
	 */
	public function getPath($a_location = null)
	{

		$path = parent::getPath().'/';

		if(!$a_location)
		{
			return $path;
		}

		switch ($a_location)
		{
			case ilObjMediaGallery::LOCATION_ORIGINALS:
				$path .= 'originals/';
				break;
			case ilObjMediaGallery::LOCATION_THUMBS:
				$path .= 'thumbs/';
				break;
			case ilObjMediaGallery::LOCATION_SIZE_SMALL:
				$path .= 'small/';
				break;
			case ilObjMediaGallery::LOCATION_SIZE_MEDIUM:
				$path .= 'medium/';
				break;
			case ilObjMediaGallery::LOCATION_SIZE_LARGE:
				$path .= 'large/';
				break;
			case ilObjMediaGallery::LOCATION_PREVIEWS:
				$path .= 'previews/';
				break;
			case ilObjMediaGallery::LOCATION_DOWNLOADS:
				$path .= 'downloads/';
		}

		return $path;
	}

	/**
	 * create directory structure
	 *
	 * @return bool
	 */
	public function create()
	{
		if(!parent::create())
		{
			return false;
		}

		ilUtil::makeDir($this->getPath(ilObjMediaGallery::LOCATION_ORIGINALS));
		ilUtil::makeDir($this->getPath(ilObjMediaGallery::LOCATION_DOWNLOADS));
		ilUtil::makeDir($this->getPath(ilObjMediaGallery::LOCATION_SIZE_SMALL));
		ilUtil::makeDir($this->getPath(ilObjMediaGallery::LOCATION_SIZE_MEDIUM));
		ilUtil::makeDir($this->getPath(ilObjMediaGallery::LOCATION_SIZE_LARGE));
		ilUtil::makeDir($this->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));
		ilUtil::makeDir($this->getPath(ilObjMediaGallery::LOCATION_THUMBS));
		return true;
	}

	/**
	 * reset all caches
	 */
	public function resetCache()
	{
		$this->files_cache = array();
		$this->mime_cache = array();
	}
} 