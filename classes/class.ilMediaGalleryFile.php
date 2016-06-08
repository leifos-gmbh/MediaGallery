<?php
/**
 * Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE
 * Date: 03.06.15
 * Time: 14:38
 */

/**
 * Class ilMediaGalleryFile
 *
 * @author Fabian Wolf <wolf@leifos.com>
 * @version $Id$
 *
 */
class ilMediaGalleryFile
{
	/**
	 * @var int
	 */
	protected $id;
	/**
	 * @var int
	 */
	protected $gallery_id;
	/**
	 * @var string
	 */
	protected $media_id;
	/**
	 * @var string
	 */
	protected $topic;
	/**
	 * @var string
	 */
	protected $title;
	/**
	 * @var string
	 */
	protected $description;
	/**
	 * @var int
	 */
	protected $sorting = 0;
	/**
	 * @var string
	 */
	protected $filename;
	/**
	 * @var string
	 */
	protected $pfilename = "";

	/**
	 * @var ilMediaGalleryPlugin
	 */
	protected $plugin;

	protected static $loaded = false;

	protected static $objects = array();

	public function __construct($a_id = null)
	{
		$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, "Repository", "robj", "MediaGallery");

		if($a_id)
		{
			$this->setId($a_id);
			$this->read();
		}

		//$this->setObject($a_parent_obj);
	}

	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $gallery_id
	 */
	public function setGalleryId($gallery_id)
	{
		$this->gallery_id = $gallery_id;
	}

	/**
	 * @return int
	 */
	public function getGalleryId()
	{
		return $this->gallery_id;
	}

	/**
	 * @param string $media_id
	 */
	public function setMediaId($media_id)
	{
		$this->media_id = $media_id;
	}

	/**
	 * @return string
	 */
	public function getMediaId()
	{
		return $this->media_id;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param int $sorting
	 */
	public function setSorting($sorting)
	{
		$this->sorting = $sorting;
	}

	/**
	 * @return int
	 */
	public function getSorting()
	{
		return $this->sorting;
	}

	/**
	 * @param string $topic
	 */
	public function setTopic($topic)
	{
		$this->topic = $topic;
	}

	/**
	 * @return string
	 */
	public function getTopic()
	{
		return $this->topic;
	}

	/**
	 * @param string $filename
	 */
	public function setFilename($filename)
	{
		$this->filename = $filename;
	}

	/**
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * @param string $pfilename
	 */
	public function setPfilename($pfilename)
	{
		if(($this->pfilename && !$pfilename) || ($this->pfilename != $pfilename && $pfilename))
		{
			$this->deletePreview();
		}

		$this->pfilename = $pfilename;
	}

	/**
	 * @return string
	 */
	public function getPfilename()
	{
		return $this->pfilename;
	}

	/**
	 * @return \ilFSStorageMediaGallery
	 */
	protected function getFileSystem()
	{
		$this->plugin->includeClass("class.ilFSStorageMediaGallery.php");
		return ilFSStorageMediaGallery::_getInstanceByXmgId($this->getGalleryId());
	}

	public function getSize()
	{
		return filesize($this->getPath(ilObjMediaGallery::LOCATION_ORIGINALS));
	}

	public function hasPreviewImage()
	{
		if(!$this->getPfilename())
		{
			return false;
		}
		$path = $this->getPath(ilObjMediaGallery::LOCATION_PREVIEWS);
		return file_exists($path) && !is_dir($path) ;
	}

	/**
	 * @global ilDB $ilDB
	 * @return bool
	 */
	public function read()
	{
		global $ilDB;

		if($this->getId() == null)
		{
			return false;
		}

		$res = $ilDB->query("SELECT * FROM rep_robj_xmg_filedata WHERE id = ". $ilDB->quote($this->getId(), "integer"));

		if (!$res->numRows() > 0)
		{
			return false;
		}
		$row = $ilDB->fetchAssoc($res);
		$this->setValuesByArray($row);
	}

	/**
	 * Set file values by array
	 *
	 * @param $a_array
	 */
	public function setValuesByArray($a_array)
	{
		$this->setGalleryId($a_array["xmg_id"]);
		$this->setMediaId($a_array["media_id"]);
		$this->setTopic($a_array["topic"]);
		$this->setTitle($a_array["title"]);
		$this->setDescription($a_array["description"]);
		$this->setFilename($a_array["filename"]);
		$this->setSorting($a_array["custom"]);
		$this->pfilename = $a_array["pfilename"];
	}

	/**
	 * update file object in db
	 *
	 * @global ilDB $ilDB
	 * @return bool
	 */
	public function update()
	{
		global $ilDB;

		if($this->getId() == null)
		{
			return false;
		}

		$ilDB->update(
			"rep_robj_xmg_filedata",
			$this->getValueArray(true),
			array("id" => array("integer", $this->getId())));

		return true;
	}

	/**
	 * get file values in an array
	 *
	 * @param bool $a_prepare_for_db
	 * @return array
	 */
	public function getValueArray($a_prepare_for_db = false)
	{
		if($a_prepare_for_db)
		{
			return array(
				"id" => array("integer", $this->getId()),
				"xmg_id" => array("integer", $this->getGalleryId()),
				"media_id" => array("text",$this->getMediaId()),
				"topic" => array("text", $this->getTopic()),
				"title" => array("text", $this->getTitle()),
				"description" => array("text", $this->getDescription()),
				"filename" => array("text", $this->getFilename()),
				"custom" => array("integer",$this->getSorting()),
				"pfilename" => array('text', $this->getPfilename())
			);
		}
		else
		{
			return array(
				"id" => $this->getId(),
				"xmg_id" => $this->getGalleryId(),
				"media_id" => $this->getMediaId(),
				"topic" => $this->getTopic(),
				"title" => $this->getTitle(),
				"description" => $this->getDescription(),
				"filename" => $this->getFilename(),
				"custom" => $this->getSorting(),
				"pfilename" => $this->getPfilename()
			);
		}
	}

	/**
	 * create file in db
	 *
	 * @global ilDB $ilDB
	 * @return bool
	 */
	public function create()
	{
		global $ilDB;

		if($this->getGalleryId() == null)
		{
			return false;
		}

		$id = $ilDB->nextId('rep_robj_xmg_filedata');
		$this->setId($id);

		$ilDB->insert("rep_robj_xmg_filedata",$this->getValueArray(true));

		self::$objects[$id] = $this;

		return true;
	}

	/**
	 * deletes file, db entry and preview if necessary
	 *
	 * @return bool
	 */
	public function delete()
	{
		global $ilDB;

		if($this->getId() == null)
		{
			return false;
		}

		if($this->hasPreviewImage())
		{
			$this->deletePreview();
		}

		$query = "DELETE FROM rep_robj_xmg_filedata ".
			"WHERE id = ".$ilDB->quote($this->getId(),'integer')."";

		$res = $ilDB->manipulate($query);

		$this->getFileSystem()->deleteFile($this->getId());

		unset(self::$objects[$this->getId()]);

		return true;
	}

	/**
	 * returns file mime type of given file location
	 *
	 * @param int $a_location default original
	 * @return mixed|string
	 */
	public function getMimeType($a_location  = ilObjMediaGallery::LOCATION_ORIGINALS)
	{
		include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";
		return ilMimeTypeUtil::getMimeType($this->getPath($a_location));
	}

	/**
	 * returns file info of given file location
	 *
	 * @param string $a_key default null
	 * @param int $a_location default original
	 * @return mixed
	 */
	public function getFileInfo($a_key = null, $a_location  = ilObjMediaGallery::LOCATION_ORIGINALS)
	{
		$info = pathinfo($this->getPath($a_location));

		if($a_key)
		{
			return $info[$a_key];
		}

		return $info;
	}

	/**
	 * returns file path
	 *
	 * @param int $a_location
	 * @return string
	 */
	public function getPath($a_location)
	{
		if($a_location == ilObjMediaGallery::LOCATION_PREVIEWS)
		{
			return $this->getFileSystem()->getFilePath($a_location, $this->getPfilename());
		}

		return $this->getFileSystem()->getFilePath($a_location, $this->getId());
	}

	/**
	 * deletes file preview if necessary
	 *
	 * @return bool
	 */
	protected function deletePreview()
	{
		if(!self::$loaded)
		{
			self::_getMediaFilesInGallery($this->getGalleryId(),true);
			self::$loaded = true;
		}

		$counter = 0;

		foreach(self::$objects as $id => $object)
		{
			if($this->getGalleryId() == $object->getGalleryId() && $object->getPfilename() == $this->getPfilename())
			{
				$counter++;
			}
		}
		if($counter == 1)
		{
			$this->getFileSystem()->deleteFile( $this->getPfilename(), LOCATION_PREVIEWS);
		}
		return true;
	}

	/**
	 * creates large, medium, small and thumb image preveiw
	 */
	public function createImagePreviews()
	{
		$info = $this->getFileInfo();

		if($this->getContentType() == ilObjMediaGallery::CONTENT_TYPE_IMAGE)
		{
			// creates ".png" previews vor ".tif" pictures (tif support)
			if($info["extension"] == "tif" || $info["extension"] == "tiff"){
				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS), $this->getPath(LOCATION_THUMBS),
					"PNG", ilObjMediaGallery::IMAGE_SIZE_THUMBS);

				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS), $this->getPath(LOCATION_SIZE_SMALL),
					"PNG",  ilObjMediaGallery::IMAGE_SIZE_SMALL);

				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS), $this->getPath(LOCATION_SIZE_MEDIUM),
					"PNG",  ilObjMediaGallery::IMAGE_SIZE_MEDIUM);

				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS), $this->getPath(LOCATION_SIZE_LARGE),
					"PNG",  ilObjMediaGallery::IMAGE_SIZE_LARGE);
				return;
			}

			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) , $this->getPath(LOCATION_THUMBS),
				ilObjMediaGallery::IMAGE_SIZE_THUMBS,  ilObjMediaGallery::IMAGE_SIZE_THUMBS, true);

			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) , $this->getPath(LOCATION_SIZE_SMALL),
				ilObjMediaGallery::IMAGE_SIZE_SMALL, ilObjMediaGallery::IMAGE_SIZE_SMALL, true);

			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) , $this->getPath(LOCATION_SIZE_MEDIUM),
				ilObjMediaGallery::IMAGE_SIZE_MEDIUM,  ilObjMediaGallery::IMAGE_SIZE_MEDIUM, true);

			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) , $this->getPath(LOCATION_SIZE_LARGE),
				ilObjMediaGallery::IMAGE_SIZE_LARGE,   ilObjMediaGallery::IMAGE_SIZE_LARGE, true);
		}
	}

	/**
	 * returns content type of given file location
	 *
	 * @param int $a_location default original
	 * @return int
	 */
	public function getContentType($a_location  = ilObjMediaGallery::LOCATION_ORIGINALS)
	{
		include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";
		return self::_contentType($this->getMimeType($a_location), $this->getFileInfo("extension", $a_location));
	}

	/**
	 * upload file
	 *
	 * @param string $file
	 * @param string $filename
	 * @return bool
	 */
	public function uploadFile($file, $filename)
	{
		// rename mov files to mp4. gives better compatibility in most browsers
		if (self::_hasExtension($file, 'mov'))
		{
			$new_filename = preg_replace('/(\.mov)/is', '.mp4', $filename);
			if (@rename($file, str_replace($filename, $new_filename, $file)))
			{
				$file = str_replace($filename, $new_filename, $file);
			}
		}

		$valid = ilObjMediaGallery::_getConfigurationValue('ext_aud').','.
			ilObjMediaGallery::_getConfigurationValue('ext_vid').','.
			ilObjMediaGallery::_getConfigurationValue('ext_img').','.
			ilObjMediaGallery::_getConfigurationValue('ext_oth');


		if(!self::_hasExtension($file,$valid))
		{
			$this->delete();
			unlink($file);
			return false;
		}

		$ext = pathinfo($file, PATHINFO_EXTENSION);

		rename($file, $this->getFileSystem()->getPath(ilObjMediaGallery::LOCATION_ORIGINALS).$this->getId().'.'.$ext);
		$this->getFileSystem()->resetCache();

		if($this->getContentType() == ilObjMediaGallery::CONTENT_TYPE_IMAGE)
		{
			$this->createImagePreviews();
		}
		return true;
	}

	protected function performRotate($a_location, $a_direction)
	{
		$cmd = "-rotate " . (($a_direction) ? "-90" : "90") . " ";
		$source = ilUtil::escapeShellCmd($this->getPath($a_location) );
		$target = ilUtil::escapeShellCmd($this->getPath($a_location) );
		$convert_cmd = $source . " " . $cmd." ".$target;
		ilUtil::execConvert($convert_cmd);
	}

	/**
	 * rotate image
	 *
	 * @param int $direction
	 */
	public function rotate($direction)
	{
		if ($this->getContentType() == ilObjMediaGallery::CONTENT_TYPE_IMAGE)
		{
			include_once "./Services/Utilities/classes/class.ilUtil.php";

			$this->performRotate(LOCATION_THUMBS, $direction);
			$this->performRotate(LOCATION_SIZE_SMALL, $direction);
			$this->performRotate(LOCATION_SIZE_MEDIUM, $direction);
			$this->performRotate(LOCATION_SIZE_LARGE, $direction);
			$this->performRotate(LOCATION_ORIGINALS, $direction);

			return true;
		}
		return false;
	}

	/**
	 * rotate preview image
	 *
	 * @param $direction
	 */
	public function rotatePreview($direction)
	{
		if($this->hasPreviewImage())
		{
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			$this->performRotate(LOCATION_PREVIEWS, $direction);
			return true;
		}

		return false;
	}

	/**
	 * returns all files in given gallery object
	 *
	 * @param int $a_xmg_id
	 * @param bool $a_return_objects
	 * @param array $a_filter
	 * @return array
	 */
	public static function _getMediaFilesInGallery($a_xmg_id, $a_return_objects = false, $a_filter = array())
	{
		global $ilDB;
		if(!$a_xmg_id)
		{
			return array();
		}

		$ret = array();

		$a_filter['xmg_id'] = $a_xmg_id;

		$res = $ilDB->query("SELECT * FROM rep_robj_xmg_filedata ". self::_buildWhereStatement($a_filter));

		while($row = $ilDB->fetchAssoc($res))
		{
			$arr = array(
				"id" => $row["id"],
				"xmg_id" => $row["xmg_id"],
				"media_id" => $row["media_id"],
				"topic" => $row["topic"],
				"title" => $row["title"],
				"description" => $row["description"],
				"filename" => $row["filename"],
				"custom" => $row["custom"],
				"pfilename" => $row['pfilename']
			);

			if(!self::$objects[$row["id"]])
			{
				$obj =  new self();
				$obj->setId($row["id"]);
				$obj->setValuesByArray($arr);

				self::$objects[$row["id"]] = $obj;
			}
			else
			{
				$obj = self::$objects[$row["id"]];
			}

			if(isset($a_filter['type']) && $a_filter['type'] && $a_filter['type'] != $obj->getContentType())
			{
				continue;
			}

			if($a_return_objects)
			{
				$ret[$row["id"]] = self::$objects[$row["id"]];
			}
			else
			{
				$ret[$row["id"]] = $arr;
				$ret[$row["id"]]['has_preview'] = $obj->hasPreviewImage();
				$ret[$row["id"]]['content_type'] =  $obj->getContentType();
				$ret[$row["id"]]['size'] =  $obj->getSize();
			}
		}

		return $ret;
	}

	/**
	 * creates missing file previews in a given gallery object
	 *
	 * @param $a_id
	 */
	public static function _createMissingPreviews($a_id)
	{
		$files = ilMediaGalleryFile::_getMediaFilesInGallery($a_id, true);
		foreach ($files as $data)
		{
			if (!@file_exists($data->getPath(ilObjMediaGallery::LOCATION_THUMBS)))
			{
				$data->createImagePreviews();
			}
		}
	}

	/**
	 * returns content type of given mime type and extension
	 *
	 * @param string $a_mime
	 * @param string $a_ext
	 * @return int
	 */
	public static function _contentType($a_mime, $a_ext = "")
	{
		include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";

		if (strpos($a_mime, 'image') !== false)
		{
			return ilObjMediaGallery::CONTENT_TYPE_IMAGE;
		}
		else if (strpos($a_mime, 'audio') !== false)
		{
			return ilObjMediaGallery::CONTENT_TYPE_AUDIO;
		}
		else if (strpos($a_mime, 'video') !== false)
		{
			return ilObjMediaGallery::CONTENT_TYPE_VIDEO;
		}
		else
		{
			$a_ext = str_replace('.', '' , $a_ext);

			if (in_array($a_ext, self::_extConfigToArray('ext_img')))
			{
				return ilObjMediaGallery::CONTENT_TYPE_IMAGE;
			}

			if (in_array($a_ext,  self::_extConfigToArray('ext_vid')))
			{
				return ilObjMediaGallery::CONTENT_TYPE_VIDEO;
			}

			if (in_array($a_ext,  self::_extConfigToArray('ext_aud')))
			{
				return ilObjMediaGallery::CONTENT_TYPE_AUDIO;
			}

			return ilObjMediaGallery::CONTENT_TYPE_UNKNOWN;
		}
	}

	/**
	 * returns array of extensions of given configuration value
	 *
	 * @param string $a_configuration_value
	 * @return array
	 */
	protected static function _extConfigToArray($a_key)
	{
		if(strpos($a_key, 'ext_') === false)
		{
			return array();
		}

		$array = explode(',', ilObjMediaGallery::_getConfigurationValue($a_key));
		$array = array_map('strtolower', $array);
		$array = array_map('trim', $array);
		return $array;
	}

	/**
	 * looks if an file has on of the given extensions
	 *
	 * @param string $file path
	 * @param array $extensions
	 * @return bool
	 */
	public static function _hasExtension($file, $extensions)
	{
		$file_parts = pathinfo($file);
		$arrExtensions = explode(",", $extensions);
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

	/**
	 * get instance by file id
	 *
	 * @param int $a_id
	 * @return self
	 */
	public static function _getInstanceById($a_id)
	{
		if(!self::$objects[$a_id])
		{
			self::$objects[$a_id] = new self($a_id);
		}

		return self::$objects[$a_id];
	}

	/**
	 * clone files from source gallery to destination gallery
	 *
	 * @param $a_source_xmg_id
	 * @param $a_dest_xmg_id
	 */
	public static function _clone($a_source_xmg_id, $a_dest_xmg_id)
	{
		$files = self::_getMediaFilesInGallery($a_source_xmg_id, true);
		$fss = ilFSStorageMediaGallery::_getInstanceByXmgId($a_source_xmg_id);
		$fsd = ilFSStorageMediaGallery::_getInstanceByXmgId($a_dest_xmg_id);

		@copy($fss->getPath(ilObjMediaGallery::LOCATION_PREVIEWS), $fsd->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));

		/**
		 * @var $sfile self
		 */
		foreach($files as $sfile)
		{
			$dfile = new ilMediaGalleryFile();
			$dfile->setValuesByArray($sfile->getValueArray());
			$dfile->setGalleryId($a_dest_xmg_id);
			$dfile->create();
			$ext = pathinfo($sfile->getPath(ilObjMediaGallery::LOCATION_ORIGINALS), PATHINFO_EXTENSION);

			@copy($sfile->getPath(ilObjMediaGallery::LOCATION_ORIGINALS),
				$dfile->getPath(ilObjMediaGallery::LOCATION_ORIGINALS).$dfile->getId().'.'.$ext);

			if($sfile->getContentType() == ilObjMediaGallery::CONTENT_TYPE_IMAGE)
			{
				$ext = pathinfo($sfile->getPath(ilObjMediaGallery::LOCATION_SIZE_LARGE), PATHINFO_EXTENSION);

				copy($sfile->getPath(ilObjMediaGallery::LOCATION_SIZE_LARGE),
					  $dfile->getPath(ilObjMediaGallery::LOCATION_SIZE_LARGE).$dfile->getId().'.'.$ext);
				copy($sfile->getPath(ilObjMediaGallery::LOCATION_SIZE_MEDIUM),
					  $dfile->getPath(ilObjMediaGallery::LOCATION_SIZE_MEDIUM).$dfile->getId().'.'.$ext);
				copy($sfile->getPath(ilObjMediaGallery::LOCATION_SIZE_SMALL),
					  $dfile->getPath(ilObjMediaGallery::LOCATION_SIZE_SMALL).$dfile->getId().'.'.$ext);
				copy($sfile->getPath(ilObjMediaGallery::LOCATION_THUMBS),
					  $dfile->getPath(ilObjMediaGallery::LOCATION_THUMBS).$dfile->getId().'.'.$ext);
			}
		}
	}

	/**
	 * returns next valid filename to prevent two equal filenames
	 *
	 * @param int $a_xmg_id
	 * @param string $a_filename
	 * @param array $a_objects
	 * @param int $a_counter
	 * @return string
	 */
	public static function _getNextValidFilename($a_xmg_id, $a_filename, $a_objects = null, $a_counter = 0)
	{
		if($a_objects == null)
		{
			$objects = self::_getMediaFilesInGallery($a_xmg_id);
		}else
		{
			$objects = $a_objects;
		}

		if($a_counter > 0)
		{
			$base_name = substr($a_filename, 0, strripos($a_filename, '.'));
			$ext = substr($a_filename, strripos($a_filename, '.'));
			$filename = $base_name . '_' . $a_counter. $ext;
		}
		else
		{
			$filename = $a_filename;
		}

		foreach($objects as $object)
		{
			if($object['filename'] == $filename)
			{
				return self::_getNextValidFilename($a_xmg_id, $a_filename, $objects, $a_counter+1);
			}
		}

		return $filename;
	}

	/**
	 * @param array $a_filter
	 * @return string
	 */
	protected static function _buildWhereStatement($a_filter)
	{
		global $ilDB;

		$like_filters = array("media_id", "topic", "title", "description", "filename", "pfilename");

		$where = array();

		foreach($like_filters as $filter)
		{
			if(isset($a_filter[$filter]))
			{
				$where[] = $ilDB->like($filter, 'text', '%'.$a_filter[$filter].'%', false);
			}
		}

		if(isset($a_filter['id']))
		{
			$where[] = 'id = ' . $ilDB->quote($a_filter['id'], 'integer');
		}

		if(isset($a_filter['xmg_id']))
		{
			$where[] = 'xmg_id = ' . $ilDB->quote($a_filter['xmg_id'], 'integer');
		}

		if(count($where))
		{
			return 'WHERE ' . implode(' AND ' , $where);
		}
		else{
			return "";
		}
	}
} 