<?php

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");

define("LOCATION_ROOT", 0);
define("LOCATION_ORIGINALS", 1);
define("LOCATION_THUMBS", 2);
define("LOCATION_SIZE_SMALL", 3);
define("LOCATION_SIZE_MEDIUM", 4);
define("LOCATION_SIZE_LARGE", 5);
define("LOCATION_DOWNLOADS", 6);
define("LOCATION_PREVIEWS", 7);

/**
* Application class for gallery repository object.
*
* @author Helmut Schottmüller <ilias@aurealis.de>
*
* $Id$
*/
class ilObjMediaGallery extends ilObjectPlugin
{
	protected $plugin;
	protected $size_thumbs = 150;
	protected $size_small = 800;
	protected $size_medium = 1280;
	protected $size_large = 2048;
	protected $sortorder = 'entry';
	protected $showTitle = 0;
	protected $download = 0;
	protected $theme = '';
	
	/**
	* Constructor
	*
	* @access	public
	*/
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
		include_once "./Services/Component/classes/class.ilPlugin.php";
		$this->plugin = self::_getPluginObject();
	}
	

	/**
	* Get type.
	* The initType() method must set the same ID as the plugin ID.
	*/
	final function initType()
	{
		$this->setType("xmg");
	}
	
	/**
	* Create object
	* This method is called, when a new repository object is created.
	* The Object-ID of the new object can be obtained by $this->getId().
	* You can store any properties of your object that you need.
	* It is also possible to use multiple tables.
	* Standard properites like title and description are handled by the parent classes.
	*/
	function doCreate()
	{
		global $ilDB;
		// $myID = $this->getId();

	}
	
	/**
	* Read data from db
	* This method is called when an instance of a repository object is created and an existing Reference-ID is provided to the constructor.
	* All you need to do is to read the properties of your object from the database and to call the corresponding set-methods.
	*/
	function doRead()
	{
		global $ilDB;

		$result = $ilDB->queryF("SELECT * FROM rep_robj_xmg_object WHERE obj_fi = %s",
			array('integer'),
			array($this->getId())
		);
		if ($result->numRows() == 1)
		{
			$row = $ilDB->fetchAssoc($result);
			$this->setShowTitle($row['show_title']);
			$this->setDownload($row['download']);
			$this->setTheme($row['theme']);
			$this->setSortOrder($row['sortorder']);
		}
		else
		{
			$this->setShowTitle(0);
			$this->setDownload(0);
			$this->setTheme(ilObjMediaGallery::_getConfigurationValue('theme'));
			$this->setSortOrder('entry');
		}
	}
	
	/**
	* Update data
	* This method is called, when an existing object is updated.
	*/
	function doUpdate()
	{
		global $ilDB;

		$affectedRows = $ilDB->manipulateF("DELETE FROM rep_robj_xmg_object WHERE obj_fi = %s",
			array('integer'),
			array($this->getId())
		);
		$result = $ilDB->manipulateF("INSERT INTO rep_robj_xmg_object (obj_fi, sortorder, show_title, download, theme) VALUES (%s, %s, %s, %s, %s)",
			array('integer','text','integer', 'integer', 'text'),
			array($this->getId(), $this->getSortOrder(), $this->getShowTitle(), $this->getDownload(), $this->getTheme())
		);
	}
	
	/**
	* Delete data from db
	* This method is called, when a repository object is finally deleted from the system.
	* It is not called if an object is moved to the trash.
	*/
	function doDelete()
	{
		global $ilDB;
		// $myID = $this->getId();
		ilUtil::delDir($this->getPath(LOCATION_ROOT));
		
		$affectedRows = $ilDB->manipulateF("DELETE FROM rep_robj_xmg_filedata WHERE xmg_id = %s",
			array('integer'),
			array($this->getId())
		);
		$affectedRows = $ilDB->manipulateF("DELETE FROM rep_robj_xmg_downloads WHERE xmg_id = %s",
			array('integer'),
			array($this->getId())
		);
		$affectedRows = $ilDB->manipulateF("DELETE FROM rep_robj_xmg_object WHERE obj_fi = %s",
			array('integer'),
			array($this->getId())
		);
	}
	
	/**
	* Do Cloning
	* This method is called, when a repository object is copied.
	*/
	function doCloneObject($new_obj, $a_target_id,$a_copy_id)
	{
		ilUtil::rCopy($this->getPath(LOCATION_PREVIEWS), $new_obj->getPath(LOCATION_PREVIEWS));
		ilUtil::rCopy($this->getPath(LOCATION_DOWNLOADS), $new_obj->getPath(LOCATION_DOWNLOADS));
		ilUtil::rCopy($this->getPath(LOCATION_ORIGINALS), $new_obj->getPath(LOCATION_ORIGINALS));
		$this->cloneMediaFiles($new_obj);
		$this->cloneArchive($new_obj);
		$new_obj->setSortOrder($this->getSortOrder());
		$new_obj->setShowTitle($this->getShowTitle());
		$new_obj->setDownload($this->getDownload());
		$new_obj->setTheme($this->getTheme());
		$new_obj->doUpdate();
		$new_obj->createMissingPreviews();
		$new_obj->restoreCustomPreviews();
	}
	
	private function cloneMediaFiles($new_obj)
	{
		foreach($this->getMediaFiles() as $data)
		{
			$new_obj->saveFileData($data["filename"],
					$data["media_id"],
					$data["topic"],
					$data["title"],
					$data["description"],
					$data["custom"],
					$data["width"],
					$data["height"]);
		}
	}
	
	private function cloneArchive($new_obj)
	{
		$files =  array();
		
		foreach($this->getArchives() as $data)
		{
			if($data["download"] === true)
			{
				$files[] = $data["entry"];
			}
		}
		
		$new_obj->saveArchiveData($files);
	}

	public function getSortOrder()
	{
		return $this->sortorder;
	}

	public function getShowTitle()
	{
		return ($this->showTitle) ? 1 : 0;
	}

	public function getDownload()
	{
		return ($this->download) ? 1 : 0;
	}

	public function getTheme()
	{
		if (strlen($this->theme) == 0)
		{
			return ilObjMediaGallery::_getConfigurationValue('theme');
		}
		else
		{
			return $this->theme;
		}
	}
	
	public function setSortOrder($sortorder)
	{
		$this->sortorder = $sortorder;
	}
	
	public function setShowTitle($showtitle)
	{
		$this->showTitle = $showtitle;
	}

	public function setDownload($download)
	{
		$this->download = $download;
	}

	public function setTheme($theme)
	{
		$this->theme = $theme;
	}

	private function getDataPath()
	{
		return CLIENT_WEB_DIR . "/mediagallery/" . $this->getId() . "/";
	}

	private function getDataPathWeb()
	{
		include_once "./Services/Utilities/classes/class.ilUtil.php";
		$datadir = ilUtil::removeTrailingPathSeparators(CLIENT_WEB_DIR) . "/mediagallery/" . $this->getId() . "/";
		return str_replace(ilUtil::removeTrailingPathSeparators(ILIAS_ABSOLUTE_PATH), ilUtil::removeTrailingPathSeparators(ILIAS_HTTP_PATH), $datadir);
	}

	public static function _getConfigurationValue($key, $default = "")
	{
		include_once './Services/Administration/classes/class.ilSetting.php';
		$setting = new ilSetting("xmg");
		if (strcmp($key, 'theme') == 0 && strlen($setting->get($key)) == 0)
		{
			return "dark_rounded";
		}
		else
		{
			return $setting->get($key, $default);
		}
	}

	public static function _setConfiguration($key, $value)
	{
		include_once './Services/Administration/classes/class.ilSetting.php';
		$setting = new ilSetting("xmg");
		$setting->set($key, $value);
	}

	/**
	 * Returns a full path for a location.
	 *
	 * @param int $location Location Const
	 * @param string $filename returns the path with filename
	 * @return string path
	 */
	public function getPath($location = 0, $filename = "")
	{
		switch ($location)
		{
			case LOCATION_ORIGINALS:
				$path = $this->getDataPath() . "media/originals/";
				break;
			case LOCATION_THUMBS:
				$path = $this->getDataPath() . "media/thumbs/";
				break;
			case LOCATION_SIZE_SMALL:
				$path = $this->getDataPath() . "media/small/";
				break;
			case LOCATION_SIZE_MEDIUM:
				$path = $this->getDataPath() . "media/medium/";
				break;
			case LOCATION_SIZE_LARGE:
				$path = $this->getDataPath() . "media/large/";
				break;
			case LOCATION_DOWNLOADS:
				$path = $this->getDataPath() . "media/downloads/";
				break;
			case LOCATION_PREVIEWS:
				$path = $this->getDataPath() . "media/previews/";
				break;
			default:
				$path = $this->getDataPath();
				break;
		}

		if (!@file_exists($path)) ilUtil::makeDirParents($path);


		if($filename)
		{
			//returns the path as a ".png" file if ".tiff" is given. Originals stay ".tiff". (tiff support)
			$fpath = $path . $filename;
			$ext = pathinfo($fpath, PATHINFO_EXTENSION);
			$name = pathinfo($fpath, PATHINFO_FILENAME);

			if ($location != LOCATION_ORIGINALS && ($ext == "tif" || $ext == "tiff"))
			{
				return $path.$name.".png";
			}

			return $fpath;
		}
		else
		{
			return $path;
		}


	}

	/**
	 * Returns a full web path for a location.
	 *
	 * @param int $location Location Const
	 * @param string $filename returns the path with filename
	 * @return string webpath
	 */
	public function getPathWeb($location = 0, $filename = "")
	{
		switch ($location)
		{
			case LOCATION_ORIGINALS:
				$path = $this->getDataPathWeb() . "media/originals/";
				break;
			case LOCATION_THUMBS:
				$path = $this->getDataPathWeb() . "media/thumbs/";
				break;
			case LOCATION_SIZE_SMALL:
				$path = $this->getDataPathWeb() . "media/small/";
				break;
			case LOCATION_SIZE_MEDIUM:
				$path = $this->getDataPathWeb() . "media/medium/";
				break;
			case LOCATION_SIZE_LARGE:
				$path = $this->getDataPathWeb() . "media/large/";
				break;
			case LOCATION_DOWNLOADS:
				$path = $this->getDataPathWeb() . "media/downloads/";
				break;
			case LOCATION_PREVIEWS:
				$path = $this->getDataPathWeb() . "media/previews/";
				break;
			default:
				$path = $this->getDataPathWeb();
				break;
		}

		if($filename)
		{
			//returns the path as a ".png" file if ".tiff" is given. Originals stay ".tiff". (tiff support)
			$fpath = $path . $filename;
			$ext = pathinfo($fpath, PATHINFO_EXTENSION);
			$name = pathinfo($fpath, PATHINFO_FILENAME);

			if ($location != LOCATION_ORIGINALS && ($ext == "tif" || $ext == "tiff"))
			{
				return $path.$name.".png";
			}

			return $fpath;
		}
		else
		{
			return $path;
		}
	}

	/**
	 * returns true if a file has an specific extension. also it checks equal mimetypes (.tif == .tiff)
	 *
	 * @param $file
	 * @param $extensions
	 * @return bool
	 */
	protected function hasExtension($file, $extensions)
	{
		include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";

		$file_parts = pathinfo($file);
		$arrExtensions = split(",", $extensions);//TODO: Split is deprecated
		$extMap = ilMimeTypeUtil::getExt2MimeMap();
		foreach ($arrExtensions as $ext)
		{
			if (strlen(trim($ext)))
			{

				if ($extMap[".".$ext] == ilMimeTypeUtil::getMimeType($file) ||
					strcmp(strtolower($file_parts['extension']),strtolower(trim($ext))) == 0)
				{
					return true;
				}

			}
		}
		return false;
	}
	
	public function processNewUpload($file)
	{
		$saveData = true;
		$width = 0;
		$height = 0;
		$file_parts = pathinfo($file);
		$filename = $file_parts['basename'];
		if ($this->isImage($file))
		{
			if ($this->hasExtension($file, ilObjMediaGallery::_getConfigurationValue('ext_img')))
			{
				include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";
				if (ilUtil::deducibleSize(ilMimeTypeUtil::getMimeType("", $file, "")))
				{
					$imgsize = getimagesize($file);
					if (is_array($imgsize))
					{
						$width = $imgsize[0];
						$height = $imgsize[1];
					}
				}
				$this->createPreviews($filename);
			}
			else
			{

				@unlink($file);
				$saveData = false;
			}
		}
		else if ($this->isAudio($file))
		{
			if (!$this->hasExtension($file, ilObjMediaGallery::_getConfigurationValue('ext_aud'))) 
			{
				@unlink($file);
				$saveData = false;
			}
		}
		else if ($this->isVideo($file))
		{
			if (!$this->hasExtension($file, ilObjMediaGallery::_getConfigurationValue('ext_vid')))
			{
				@unlink($file);
				$saveData = false;
			}
			// rename mov files to mp4. gives better compatibility in most browsers
			if ($saveData && $this->hasExtension($file, 'mov'))
			{
				$new_filename = preg_replace('/(\.mov)/is', '.mp4', $filename);
				if (@rename($file, str_replace($filename, $new_filename, $file)))
				{
					$filename = $new_filename;
				}
			}
		}
		else
		{
			if (!$this->hasExtension($file, ilObjMediaGallery::_getConfigurationValue('ext_aud').','.ilObjMediaGallery::_getConfigurationValue('ext_vid').','.ilObjMediaGallery::_getConfigurationValue('ext_img').','.ilObjMediaGallery::_getConfigurationValue('ext_oth')))
			{
				@unlink($file);
				$saveData = false;
			}
		}
		if ($saveData) $this->saveFileData($filename, '', '', $filename, '', $this->getFileDataCount()+1, $width, $height);

	}
	
	private function getFilesInDir($a_dir)
	{
		$current_dir = opendir($a_dir);

		$files = array();
		while($entry = readdir($current_dir))
		{
			if ($entry != "." && $entry != ".." && !@is_dir($a_dir."/".$entry) && strpos($entry, ".") !== 0)
			{
				$size = filesize($a_dir."/".$entry);
				$files[$entry] = array("type" => "file", "entry" => $entry,
				"size" => $size);
			}
		}
		ksort($files);
		return $files;
	}

	private static function getDirsInDir($a_dir)
	{
		$current_dir = opendir($a_dir);

		$files = array();
		while($entry = readdir($current_dir))
		{
			if ($entry != "." && $entry != ".." && !@is_file($a_dir."/".$entry) && strpos($entry, ".") !== 0)
			{
				array_push($files, $entry);
			}
		}
		ksort($files);
		return $files;
	}

	protected static function _getPluginObject()
	{
		return ilPlugin::getPluginObject(IL_COMP_SERVICE, "Repository", "robj", "MediaGallery");
	}

	public function getGalleryThemes()
	{
		return self::_getGalleryThemes();
	}
	
	public static function _getGalleryThemes()
	{
		$data = self::getDirsInDir(self::_getPluginObject()->getDirectory() . '/js/prettyphoto_3.1.5/images/prettyPhoto');
		if (count($data) == 0)
		{
			array_push($data, ilObjMediaGallery::_getConfigurationValue('theme'));
		}
		$themes = array();
		foreach ($data as $theme)
		{
			$themes[$theme] = $theme;
		}
		return $themes;
	}
	
	public function deleteFile($filename)
	{
		global $ilDB;

		$path = $this->getPath(LOCATION_ORIGINALS) . $filename;
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		$name = pathinfo($path, PATHINFO_FILENAME);

		$file = $this->getPath(LOCATION_ORIGINALS, $filename);
		if(file_exists($file))unlink($file);
		$file = $this->getPath(LOCATION_THUMBS, $filename);
		if(file_exists($file))unlink($file);
		$file = $this->getPath(LOCATION_SIZE_SMALL, $filename);
		if(file_exists($file))unlink($file);
		$file = $this->getPath(LOCATION_SIZE_MEDIUM, $filename);
		if(file_exists($file))unlink($file);
		$file = $this->getPath(LOCATION_SIZE_LARGE, $filename);
		if(file_exists($file))unlink($file);


		//fallback if old .tif previews existing

		if($ext == "tif" || $ext == "tiff")
		{
			$file = $this->getPath(LOCATION_THUMBS). $filename;
			if(file_exists($file))unlink($file);
			$file = $this->getPath(LOCATION_SIZE_SMALL). $filename;
			if(file_exists($file))unlink($file);
			$file = $this->getPath(LOCATION_SIZE_MEDIUM). $filename;
			if(file_exists($file))unlink($file);
			$file = $this->getPath(LOCATION_SIZE_LARGE). $filename;
			if(file_exists($file))unlink($file);
		}

		$data = $this->getMediaFileData($filename);
		if (strlen($data['pfilename']))
		{
			@unlink($this->getPath(LOCATION_PREVIEWS) . $data['pfilename']);
		}
		
		$affectedRows = $ilDB->manipulateF("DELETE FROM rep_robj_xmg_filedata WHERE xmg_id = %s AND filename = %s",
			array('integer','text'),
			array($this->getId(), $filename)
		);
	}
	
	public function deletePreview($files)
	{
		if (is_array($files))
		{
			foreach ($files as $filename)
			{
				$data = $this->getMediaFileData($filename);
				if (strlen($data['pfilename']))
				{
					@unlink($this->getPath(LOCATION_PREVIEWS) . $data['pfilename']);
					$this->updateFileDataAfterDeletePreview($filename);
				}
			}
		}
	}

	public function deleteArchive($filename)
	{
		global $ilDB;
		
		@unlink($this->getPath(LOCATION_DOWNLOADS) . $filename);
		
		$affectedRows = $ilDB->manipulateF("DELETE FROM rep_robj_xmg_downloads WHERE xmg_id = %s AND filename = %s",
			array('integer','text'),
			array($this->getId(), $filename)
		);
	}

	public function downloadArchiveExists($filename)
	{
		if (file_exists($this->getPath(LOCATION_DOWNLOADS) . ilUtil::getASCIIFilename($filename)))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function renameArchive($old, $new)
	{
		rename($this->getPath(LOCATION_DOWNLOADS) . ilUtil::getASCIIFilename($old), $this->getPath(LOCATION_DOWNLOADS) . ilUtil::getASCIIFilename($new));
	}
	
	public function zipSelectedFiles($fileArray, $zipFilename)
	{
		$files = array();
		foreach ($fileArray as $filename)
		{
			array_push($files, $this->getPath(LOCATION_ORIGINALS, $filename) );
		}
		ilUtil::zip($files, $this->getPath(LOCATION_ORIGINALS) . ilUtil::getASCIIFilename($zipFilename), false);
		rename($this->getPath(LOCATION_ORIGINALS) . ilUtil::getASCIIFilename($zipFilename), $this->getPath(LOCATION_DOWNLOADS) . ilUtil::getASCIIFilename($zipFilename));
	}
	
	public function rotate($filename, $direction)
	{
		if ($this->isImage($filename))
		{
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			$rotation = ($direction) ? "-90" : "90";
			$cmd = "-rotate $rotation ";

			$source = ilUtil::escapeShellCmd($this->getPath(LOCATION_THUMBS, $filename) );
			$target = ilUtil::escapeShellCmd($this->getPath(LOCATION_THUMBS, $filename) );
			$convert_cmd = $source . " " . $cmd." ".$target;
			ilUtil::execConvert($convert_cmd);

			$source = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_SMALL, $filename) );
			$target = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_SMALL, $filename) );
			$convert_cmd = $source . " " . $cmd." ".$target;
			ilUtil::execConvert($convert_cmd);

			$source = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_MEDIUM, $filename) );
			$target = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_MEDIUM, $filename) );
			$convert_cmd = $source . " " . $cmd." ".$target;
			ilUtil::execConvert($convert_cmd);

			$source = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_LARGE, $filename) );
			$target = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_LARGE, $filename) );
			$convert_cmd = $source . " " . $cmd." ".$target;
			ilUtil::execConvert($convert_cmd);

			$source = ilUtil::escapeShellCmd($this->getPath(LOCATION_ORIGINALS, $filename));
			$target = ilUtil::escapeShellCmd($this->getPath(LOCATION_ORIGINALS, $filename));
			$convert_cmd = $source . " " . $cmd." ".$target;
			ilUtil::execConvert($convert_cmd);

			$imgsize = getimagesize($this->getPath(LOCATION_ORIGINALS, $filename) );
			$width = $imgsize[0];
			$height = $imgsize[1];
			$this->updateFileDataAfterRotate($filename, $width, $height);
		}
	}
	
	public function saveArchiveData($downloads)
	{
		global $ilDB;
		$affectedRows = $ilDB->manipulateF("DELETE FROM rep_robj_xmg_downloads WHERE xmg_id = %s",
			array('integer'),
			array($this->getId())
		);
		if (is_array($downloads))
		{
			foreach ($downloads as $filename)
			{
				if (strlen($filename))
				{
					$result = $ilDB->manipulateF("INSERT INTO rep_robj_xmg_downloads (xmg_id, filename) VALUES (%s, %s)",
						array('integer','text'),
						array($this->getId(), $filename)
					);
				}
			}
		}
	}
	
	public function scaleDimensions($width, $height, $scale)
	{
		if ($width == 0 || $height == 0 || $scale == 0) return array("width" => $width, "height" => $height);
		$iwidth = $width;
		$iheight = $height;
		$f = ($iwidth*1.0) / ($iheight*1.0);
		if ($f < 1) // higher
		{
			$iheight = $scale;
			$iwidth = round(($scale*1.0)*$f);
		}
		else
		{
			$iwidth = $scale;
			$iheight = round(($scale*1.0)/$f);
		}
		return array("width" => $iwidth, "height" => $iheight);
	}

	public function updateFileDataAfterRotate($filename, $width, $height)
	{
		global $ilDB;
		$result = $ilDB->manipulateF("UPDATE rep_robj_xmg_filedata SET width = %s, height = %s WHERE filename = %s",
			array('integer','integer','text'),
			array($width, $height, $filename)
		);
	}

	public function updateFileDataAfterDeletePreview($filename)
	{
		global $ilDB;
		$result = $ilDB->manipulateF("UPDATE rep_robj_xmg_filedata SET pwidth = %s, pheight = %s, pfilename = %s WHERE filename = %s",
			array('integer','integer','text', 'text'),
			array(0, 0, null, $filename)
		);
	}

	public function updatePreviewSize($filename, $width, $height, $previewfilename)
	{
		global $ilDB;
		$result = $ilDB->manipulateF("UPDATE rep_robj_xmg_filedata SET pwidth = %s, pheight = %s, pfilename = %s WHERE filename = %s",
			array('integer','integer','text', 'text'),
			array($width, $height, $previewfilename, $filename)
		);
	}

	public function saveFileData($filename, $id, $topic, $title, $description, $custom, $width, $height)
	{
		global $ilDB;
		$affectedRows = $ilDB->manipulateF("DELETE FROM rep_robj_xmg_filedata WHERE xmg_id = %s AND filename = %s",
			array('integer','text'),
			array($this->getId(), $filename)
		);
		$result = $ilDB->manipulateF("INSERT INTO rep_robj_xmg_filedata (xmg_id, filename, media_id, topic, title, description, custom, width, height) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
			array('integer','text','text','text','text','text','float','integer','integer'),
			array($this->getId(), $filename, $id, $topic, $title, $description, $custom, $width, $height)
		);
	}
	
	protected function getFileDataCount()
	{
		global $ilDB;
		
		$result = $ilDB->queryF("SELECT * FROM rep_robj_xmg_filedata WHERE xmg_id = %s",
			array('integer'),
			array($this->getId())
		);
		return $result->numRows();
	}
	
	public function getArchives()
	{
		global $ilDB;
		
		$data = $this->getFilesInDir($this->getPath(LOCATION_DOWNLOADS));
		$result = $ilDB->queryF("SELECT * FROM rep_robj_xmg_downloads WHERE xmg_id = %s",
			array('integer'),
			array($this->getId())
		);
		$allowed = array();
		if ($result->numRows() > 0)
		{
			while ($row = $ilDB->fetchAssoc($result))
			{
				array_push($allowed, $row['filename']);
			}
		}
		foreach ($data as $fn => $filedata)
		{
			$data[$fn]['created'] = filectime($this->getPath(LOCATION_DOWNLOADS).$fn);
			if (in_array($fn, $allowed)) 
			{
				$data[$fn]['download'] = true;
			}
			else
			{
				$data[$fn]['download'] = false;
			}
		}
		return $data;
	}
	
	public function getMediaFiles($arrFilter = array())
	{
		global $ilDB;
		
		$filter = (count($arrFilter) > 0) ? true : false;
		$data = $this->getFilesInDir($this->getPath(LOCATION_ORIGINALS));
		$result = $ilDB->queryF("SELECT * FROM rep_robj_xmg_filedata WHERE xmg_id = %s",
			array('integer'),
			array($this->getId())
		);
		$filteredData = array();
		if ($result->numRows() > 0)
		{
			while ($row = $ilDB->fetchAssoc($result))
			{
				$data[$row['filename']] = array_merge($data[$row['filename']], $row);

				if ($filter)
				{
					$inFilter = true;
					foreach ($arrFilter as $key => $value)
					{
						if ($inFilter)
						{
							if (strcmp($key, 'type') == 0)
							{
								switch ($value)
								{
									case 'image':
										$inFilter = $this->isImage($row['filename']);
										break;
									case 'audio':
										$inFilter = $this->isAudio($row['filename']);
										break;
									case 'video':
										$inFilter = $this->isVideo($row['filename']);
										break;
									case 'unknown':
										$inFilter = $this->isUnknown($row['filename']);
										break;
								}
							}
							else
							{
								if (strpos($data[$row['filename']][$key], $value) === false)
								{
									$inFilter = false;
								}
							}
						}
					}
					if ($inFilter)
					{
						$filteredData[$row['filename']] = $data[$row['filename']];
					}
				}
			}
		}
		if ($filter)
		{
			return $filteredData;
		}
		else
		{
			return $data;
		}
	}

	public function getMediaFileData($filename)
	{
		global $ilDB;
		
		$result = $ilDB->queryF("SELECT * FROM rep_robj_xmg_filedata WHERE xmg_id = %s AND filename = %s",
			array('integer','text'),
			array($this->getId(), $filename)
		);
		if ($result->numRows() > 0)
		{
			while ($row = $ilDB->fetchAssoc($result))
			{
				return $row;
			}
		}
		return array();
	}

	public function getMediaObjectCount()
	{
		$dir = $this->getFilesInDir($this->getPath(LOCATION_ORIGINALS));
		return count($dir);
	}

	public function createMissingPreviews()
	{
		$files = $this->getMediaFiles();
		foreach ($files as $filename => $data)
		{
			if (!@file_exists($this->getPath(LOCATION_THUMBS,$filename)))
			{
				$this->createPreviews($filename);
			}
		}
	}
	
	/**
	 * Reads all preview pics and write them into the database
	 */
	public function restoreCustomPreviews()
	{
		$previews = $this->getFilesInDir($this->getPath(LOCATION_PREVIEWS));
		
		foreach ($previews as $preview => $data)
		{
			$filename = substr($preview, 0, (strrpos($preview,'.')-strlen($preview)));
			$data = getimagesize($this->getPath(LOCATION_PREVIEWS)."/".$preview);
			$width = $data[0];
			$height = $data[1];
			$this->updatePreviewSize($filename, $width, $height, $preview);
			var_dump($filename,$width,$height,$preview);
		}
	}

	protected function createPreviews($filename)
	{
		$path = $this->getPath(LOCATION_ORIGINALS) . $filename;
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		$name = pathinfo($path, PATHINFO_FILENAME);


		if($this->isImage($filename))
		{
			// creates ".png" previews vor ".tif" pictures (tif support)
			if($ext == "tif" || $ext == "tiff"){
				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS) . $filename, $this->getPath(LOCATION_THUMBS) . $name. ".png", "PNG",  $this->size_thumbs);
				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS) . $filename, $this->getPath(LOCATION_SIZE_SMALL) . $name. ".png", "PNG",  $this->size_small);
				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS) . $filename, $this->getPath(LOCATION_SIZE_MEDIUM) . $name. ".png","PNG",  $this->size_medium);
				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS) . $filename, $this->getPath(LOCATION_SIZE_LARGE) . $name. ".png", "PNG",  $this->size_large);
				return;
			}
			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) . $filename, $this->getPath(LOCATION_THUMBS) . $filename, $this->size_thumbs, $this->size_thumbs, true);
			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) . $filename, $this->getPath(LOCATION_SIZE_SMALL) . $filename, $this->size_small, $this->size_small, true);
			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) . $filename, $this->getPath(LOCATION_SIZE_MEDIUM) . $filename, $this->size_medium, $this->size_medium, true);
			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) . $filename, $this->getPath(LOCATION_SIZE_LARGE) . $filename, $this->size_large, $this->size_large, true);
		}

	}
	
	public function uploadPreviewForFiles($filenames, $tempfile, $filetype)
	{
		if (is_array($filenames))
		{
			$extension = '';
			if ($_FILES["filename"]["type"] == "image/png")
			{
				$extension = ".png";
			}
			else
			{
				$extension = ".jpg";
			}
			$first = true;
			$preview_filename = '';
			$width = 0;
			$height = 0;
			foreach ($filenames as $filename)
			{
				if ($first)
				{
					$preview_filename = $this->getPath(LOCATION_PREVIEWS) . $filename . $extension;
					@move_uploaded_file($tempfile, $preview_filename);
					$imgsize = getimagesize($preview_filename);
					$width = $imgsize[0];
					$height = $imgsize[1];
					$this->updatePreviewSize($filename, $width, $height, $filename . $extension);
					$first = false;
				}
				else
				{
					@copy($preview_filename, $this->getPath(LOCATION_PREVIEWS) . $filename . $extension);
					$this->updatePreviewSize($filename, $width, $height, $filename . $extension);
				}
			}
		}
	}
	
	public function isImage($filename)
	{
		if (strcmp($this->plugin->txt('image'), ilObjMediaGallery::getContentType($filename)) == 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function isVideo($filename)
	{
		if (strcmp($this->plugin->txt('video'), ilObjMediaGallery::getContentType($filename)) == 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function getMimeIconPath($filename)
	{
		include_once("./Services/Utilities/classes/class.ilFileUtils.php");
		$mime = ilFileUtils::_lookupMimeType($this->getPath(LOCATION_ORIGINALS, $filename) );
		$res = explode(";", $mime);
		if ($res !== false)
		{
			$mime = $res[0];
		}
		$file_parts = pathinfo($this->getPath(LOCATION_ORIGINALS, $filename) );
		switch (strtolower($file_parts['extension']))
		{
			case 'xls':
			case 'xlsx':
				$mime = "application-vnd.ms-excel";
				break;
			case 'doc':
			case 'docx':
				$mime = "application-msword";
				break;
			case 'ppt':
			case 'pptx':
				$mime = "application-vnd.ms-powerpoint";
				break;
		}
		$path = $this->plugin->getDirectory() . "/templates/images/mimetypes/" . str_replace("/", "-", $mime) . ".png";
		if (file_exists($path))
		{
			return $path;
		}
		else
		{
			return $this->plugin->getDirectory() . '/templates/images/unknown.png';
		}
	}

	public function isAudio($filename)
	{
		if (strcmp($this->plugin->txt('audio'), ilObjMediaGallery::getContentType($filename)) == 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function isUnknown($filename)
	{
		if (strcmp($this->plugin->txt('unknown'), ilObjMediaGallery::getContentType($filename)) == 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function _hasExtension($file, $extensions)
	{
		$file_parts = pathinfo($file);
		$arrExtensions = split(",", $extensions);
		foreach ($arrExtensions as $ext)
		{
			if (strlen(trim($ext)))
			{
				if (strcmp(strtolower($file_parts['extension']),strtolower(trim($ext))) == 0)
				{
					return true;
				}
			}
		}
		return false;
	}
	
	public static function getContentType($filename)
	{
		include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";
		include_once "./Services/Component/classes/class.ilPlugin.php";
		$plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, "Repository", "robj", "MediaGallery");
		$mime = ilMimeTypeUtil::getMimeType("", $filename, "");
		if (strpos($mime, 'image') !== false)
		{
			return $plugin->txt("image");
		}
		else if (strpos($mime, 'audio') !== false)
		{
			return $plugin->txt("audio");
		}
		else if (strpos($mime, 'video') !== false)
		{
			return $plugin->txt("video");
		}
		else
		{
			if (ilObjMediaGallery::_hasExtension($filename, ilObjMediaGallery::_getConfigurationValue('ext_img'))) return $plugin->txt("image");
			if (ilObjMediaGallery::_hasExtension($filename, ilObjMediaGallery::_getConfigurationValue('ext_vid'))) return $plugin->txt("video");
			if (ilObjMediaGallery::_hasExtension($filename, ilObjMediaGallery::_getConfigurationValue('ext_aud'))) return $plugin->txt("audio");
			return $plugin->txt("unknown");
		}
	}

	public function formatBytes($bytes, $precision = 2) 
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}
	
	/**
	 * 
	 * @param ilXmlWriter $xml_writer
	 */
	public function toXml($xml_writer)
	{
		$media_gallery_attr = array(
				"sortorder" => $this->getSortOrder(),
				"show_title" => $this->getShowTitle(),
				"download" => $this->getDownload(),
				"theme" => $this->getTheme()
		);
		
		$xml_writer->xmlStartTag("mediagallery", $media_gallery_attr);
		
		$xml_writer->xmlElement("title", array(),$this->getTitle());
		$xml_writer->xmlElement("description", array(), $this->getDescription());
		
		foreach($this->getMediaFiles() as $data)
		{
			$filedata_attr = array(
				"filename" => $data["filename"],
				"media_id" => $data["media_id"],
				"topic" => $data["topic"],
				"custom" => $data["custom"],
				"width" => $data["width"],
				"height" => $data["height"]
			);
			
			$xml_writer->xmlStartTag("filedata", $filedata_attr);
			
			$xml_writer->xmlElement("file_title", array(), $data["title"]);
			$xml_writer->xmlElement("file_description", array(),$data["description"]);
			
			// file exists abfrage
			$content = @gzcompress(@file_get_contents($this->getPath(LOCATION_ORIGINALS.$data["filename"])), 9);
			$content = base64_encode($content);
			$xml_writer->xmlElement("content", array("mode" => "ZIP"), $content);
			
			if($data["pfilename"] && file_exists($this->getPath(LOCATION_PREVIEWS).$data["pfilename"]))
			{
				$preview = @gzcompress(@file_get_contents($this->getPath(LOCATION_PREVIEWS).$data["pfilename"]), 9);
				$preview = base64_encode($preview);
				$preview_attr = array(
					"pfilename" => $data["pfilename"],
					"pwidth" => $data["pwidth"],
					"pheight" => $data["pheight"],
					"mode" => "ZIP"
				);
				
				$xml_writer->xmlElement("preview", $preview_attr, $preview);
			}
			$xml_writer->xmlEndTag("filedata");
		}
		
		$xml_writer->xmlEndTag("mediagallery");
	}


}
?>