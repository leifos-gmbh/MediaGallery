<?php
/**
 * Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE
 * Date: 11.06.15
 * Time: 13:08
 */
include_once("./Services/FileSystem/classes/class.ilFileSystemStorage.php");

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
		parent::__construct(ilFileSystemStorage::STORAGE_WEB,false,$a_container_id);
	}

	function getPathPrefix()
	{
		return 'mediagallery';
	}
	function getPathPostfix()
	{
		return 'xmg';
	}

	/**
	 * Clone course data directory
	 *
	 * @access public
	 * @static
	 *
	 * @param string $a_source_id obj_id source
	 * @param string $a_target_id obj_id target
	 */
	public static function _clone($a_source_id,$a_target_id)
	{
		$source = new self($a_source_id);
		$target = new self($a_target_id);

		$target->create();
		self::_copyDirectory($source->getAbsolutePath(),$target->getAbsolutePath());

		unset($source);
		unset($target);
		return true;
	}

	function getFilePath($a_location,$a_file_id = 0, $a_web = false)
	{
		$path = $this->getPath($a_location, $a_web);

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
					$path .= $this->getFilename($a_file_id);
				}

				break;
			case ilObjMediaGallery::LOCATION_ORIGINALS:
				$path .= $this->getFilename($a_file_id);
				break;
			case ilObjMediaGallery::LOCATION_DOWNLOADS:
				$path .= $a_file_id;
				break;
			case ilObjMediaGallery::LOCATION_PREVIEWS:
				$path .= $this->getFilename($a_file_id, ilObjMediaGallery::LOCATION_PREVIEWS);
				break;
		}

		return $path;
	}

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
			$fname = pathinfo($this->getFilePath($a_location, $a_file_id), PATHINFO_FILENAME );
			if($fname == $name)
			{
				return $name;
			}
		}

		return false;
	}

	public function deleteFile($a_file_id, $a_location = null)
	{
		if($a_location = null)
		{
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_ORIGINALS);
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_PREVIEWS);
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_THUMBS);
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_SIZE_LARGE);
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_SIZE_MEDIUM);
			$this->deleteFile($a_file_id,  ilObjMediaGallery::LOCATION_SIZE_SMALL);
		}

		if(isset($this->files_cache[$a_location]))
		{
			unset($this->files_cache[$a_location]);
		}

		if(isset($this->mime_cache[$a_file_id][$a_location]))
		{
			unset($this->mime_cache[$a_file_id][$a_location]);
		}

		return parent::deleteFile($this->getFilePath($a_location, $a_file_id));
	}

	public function getWebPath()
	{
		include_once "./Services/Utilities/classes/class.ilUtil.php";
		$datadir = ilUtil::removeTrailingPathSeparators(CLIENT_WEB_DIR) . self::_createPathFromId($this->getContainerId(),$this->getPathPostfix());
		return str_replace(ilUtil::removeTrailingPathSeparators(ILIAS_ABSOLUTE_PATH),
			ilUtil::removeTrailingPathSeparators(ILIAS_HTTP_PATH), $datadir);
	}

	public function getMimeType($a_file_id, $a_location = ilObjMediaGallery::LOCATION_ORIGINALS)
	{
		if($this->mime_cache[$a_file_id][$a_location])
		{
			$this->mime_cache[$a_file_id][$a_location] =
				ilMimeTypeUtil::getMimeType($this->getFilePath($a_location, $a_file_id));
		}

		return 	$this->mime_cache[$a_file_id][$a_location];
	}

	public function getPath($a_location = null, $a_web = false)
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

	public function resetCache()
	{
		$this->files_cache = array();
		$this->mime_cache = array();
	}
} 