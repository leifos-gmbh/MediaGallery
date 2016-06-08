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
	/**
	 * @var ilMediaGalleryPlugin
	 */
	protected $plugin;
	protected $size_thumbs = 150;
	protected $size_small = 800;
	protected $size_medium = 1280;
	protected $size_large = 2048;
	protected $sortorder = 'entry';
	protected $showTitle = 0;
	protected $download = 0;
	protected $theme = '';

	const LOCATION_ROOT = 0;
	const LOCATION_ORIGINALS = 1;
	const LOCATION_THUMBS = 2;
	const LOCATION_SIZE_SMALL = 3;
	const LOCATION_SIZE_MEDIUM = 4;
	const LOCATION_SIZE_LARGE = 5;
	const LOCATION_DOWNLOADS = 6;
	const LOCATION_PREVIEWS = 7;


	const CONTENT_TYPE_VIDEO = 1;
	const CONTENT_TYPE_IMAGE = 2;
	const CONTENT_TYPE_AUDIO = 3;
	const CONTENT_TYPE_UNKNOWN = 4;
	
	const IMAGE_SIZE_THUMBS = 150;
	const IMAGE_SIZE_SMALL = 800;
	const IMAGE_SIZE_MEDIUM = 1280;
	const IMAGE_SIZE_LARGE = 2048;


	
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
		$this->plugin->includeClass("class.ilFSStorageMediaGallery.php");
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
		ilFSStorageMediaGallery::_getInstanceByXmgId($this->getId())->create();
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
		ilUtil::delDir($this->getFS()->getPath(self::LOCATION_ROOT));

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
	 * @param self $new_obj
	 * @param int $a_target_id
	 * @param int $a_copy_id
	 */
	function doCloneObject($new_obj, $a_target_id,$a_copy_id)
	{
		/*ilUtil::rCopy($this->fs->getPath(self::LOCATION_PREVIEWS), $new_obj->fs->getPath(self::LOCATION_PREVIEWS));
		ilUtil::rCopy($this->fs->getPath(self::LOCATION_DOWNLOADS), $new_obj->fs->getPath(self::LOCATION_DOWNLOADS));
		ilUtil::rCopy($this->fs->getPath(self::LOCATION_ORIGINALS), $new_obj->fs->getPath(self::LOCATION_ORIGINALS));
		$this->cloneMediaFiles($new_obj);
		$this->cloneArchive($new_obj);*/

		$new_obj->setSortOrder($this->getSortOrder());
		$new_obj->setShowTitle($this->getShowTitle());
		$new_obj->setDownload($this->getDownload());
		$new_obj->setTheme($this->getTheme());
		$new_obj->doUpdate();
		$fss = ilFSStorageMediaGallery::_getInstanceByXmgId($a_copy_id);
		$fss->create();
		$this->plugin->includeClass("class.ilMediaGalleryFile.php");
		ilMediaGalleryFile::_clone($this->getId(), $new_obj->getId());
		$this->plugin->includeClass("class.ilMediaGalleryArchives.php");
		ilMediaGalleryArchives::_clone($this->getId(), $new_obj->getId());
		//$new_obj->createMissingPreviews();
		//$new_obj->restoreCustomPreviews();
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

	/**
	 * @param int $size_large
	 */
	public function setSizeLarge($size_large)
	{
		$this->size_large = $size_large;
	}

	/**
	 * @return int
	 */
	public function getSizeLarge()
	{
		return $this->size_large;
	}

	/**
	 * @param int $size_medium
	 */
	public function setSizeMedium($size_medium)
	{
		$this->size_medium = $size_medium;
	}

	/**
	 * @return int
	 */
	public function getSizeMedium()
	{
		return $this->size_medium;
	}

	/**
	 * @param int $size_thumbs
	 */
	public function setSizeThumbs($size_thumbs)
	{
		$this->size_thumbs = $size_thumbs;
	}

	/**
	 * @return int
	 */
	public function getSizeThumbs()
	{
		return $this->size_thumbs;
	}

	/**
	 * @param int $size_small
	 */
	public function setSizeSmall($size_small)
	{
		$this->size_small = $size_small;
	}

	/**
	 * @return int
	 */
	public function getSizeSmall()
	{
		return $this->size_small;
	}


	/**
	 * @return \ilFSStorageMediaGallery
	 */
	public function getFS()
	{
		return ilFSStorageMediaGallery::_getInstanceByXmgId($this->getId());
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
	
	public function getMimeIconPath($a_id)
	{
		include_once("./Services/Utilities/classes/class.ilFileUtils.php");
		$mime = ilMediaGalleryFile::_getInstanceById($a_id)->getMimeType();
		$res = explode(";", $mime);
		if ($res !== false)
		{
			$mime = $res[0];
		}
		$ext = ilMediaGalleryFile::_getInstanceById($a_id)->getFileInfo('extension');
		switch (strtolower($ext))
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
		
		foreach(ilMediaGalleryFile::_getMediaFilesInGallery($this->getId()) as $data)
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
			$content = @gzcompress(@file_get_contents($this->getFS()->getFilePath(self::LOCATION_ORIGINALS, $data['id'])), 9);
			$content = base64_encode($content);
			$xml_writer->xmlElement("content", array("mode" => "ZIP"), $content);

			$prev_path = $this->plugin->getFileSystem()->getFilePath(self::LOCATION_PREVIEWS, $data["id"]);
			
			if(file_exists($prev_path))
			{
				$preview = @gzcompress(@file_get_contents($prev_path), 9);
				$preview = base64_encode($preview);
				$preview_attr = array(
					"pfilename" => $data["filename"],
					"mode" => "ZIP"
				);
				
				$xml_writer->xmlElement("preview", $preview_attr, $preview);
			}
			$xml_writer->xmlEndTag("filedata");
		}
		
		$xml_writer->xmlEndTag("mediagallery");
	}

	public function uploadPreview()
	{
		$ext = substr($_FILES['filename']["name"],strrpos($_FILES['filename']["name"], '.'));

		if(ilMediaGalleryFile::_contentType($_FILES["filename"]["type"], $ext) != self::CONTENT_TYPE_IMAGE)
		{
			return false;
		}

		$preview_path = $this->getFS()->getFilePath(LOCATION_PREVIEWS, $_FILES['filename']["name"]);
		@move_uploaded_file($_FILES['filename']["tmp_name"], $preview_path);

		return true;
	}
}
?>