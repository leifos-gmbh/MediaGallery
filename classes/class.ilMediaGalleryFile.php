<?php
/**
 * Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE
 * Date: 03.06.15
 * Time: 14:38
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
	 * @var ilObjMediaGallery
	 */
	protected $object;

	protected static $loaded  = false;

	protected static $objects = array();

	public function __construct($a_id = null)
	{
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
	 * @return \ilFSStorageMediaGallery
	 */
	protected function getFileSystem()
	{
		return ilFSStorageMediaGallery::_getInstanceByXmgId($this->getGalleryId());
	}

	public function getSize()
	{
		return filesize($this->getPath(ilObjMediaGallery::LOCATION_ORIGINALS));
	}

	public function hasPreviewImage()
	{
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

	public function setValuesByArray($a_array)
	{
		$this->setGalleryId($a_array["xmg_id"]);
		$this->setMediaId($a_array["media_id"]);
		$this->setTopic($a_array["topic"]);
		$this->setTitle($a_array["title"]);
		$this->setDescription($a_array["description"]);
		$this->setFilename($a_array["filename"]);
		$this->setSorting($a_array["custom"]);
	}

	/**
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

	public function getValueArray($a_prepare_for_db = false)
	{
		if($a_prepare_for_db)
		{
			return array(
				"id" => array("integer", $this->getId()),
				"xmg_id" => array("integer", $this->getGalleryId()),
				"media_id" => array("integer",$this->getMediaId()),
				"topic" => array("text", $this->getTopic()),
				"title" => array("text", $this->getTitle()),
				"description" => array("text", $this->getDescription()),
				"filename" => array("text", $this->getFilename()),
				"custom" => array("integer",$this->getSorting())
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
				"custom" => $this->getSorting()
			);
		}
	}

	/**
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

	public function delete()
	{
		global $ilDB;

		if($this->getId() == null)
		{
			return false;
		}

		$query = "DELETE FROM rep_robj_xmg_filedata ".
			"WHERE id = ".$ilDB->quote($this->getId(),'integer')."";

		$res = $ilDB->manipulate($query);

		$this->getFileSystem()->deleteFile($this->getId());

		unset(self::$objects[$this->getId()]);

		return true;
	}


	public function getMimeType($a_location  = ilObjMediaGallery::LOCATION_ORIGINALS)
	{
		include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";
		return ilMimeTypeUtil::getMimeType($this->getPath($a_location));
	}

	public function getFileInfo($a_key = null, $a_location  = ilObjMediaGallery::LOCATION_ORIGINALS)
	{
		$info = pathinfo($this->getPath($a_location));

		if($a_key)
		{
			return $info[$a_key];
		}

		return $info;
	}

	public function getPath($a_location, $a_web = false)
	{
		return $this->getFileSystem()->getFilePath($a_location, $this->getId(), $a_web);
	}

	public function deletePreview()
	{
		return $this->getFileSystem()->deleteFile( $this->getId(), LOCATION_PREVIEWS);
	}

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

	public function getContentType($a_location  = ilObjMediaGallery::LOCATION_ORIGINALS)
	{
		include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";

		return self::_contentType($this->getMimeType($a_location), $this->getFileInfo("extension", $a_location));
	}

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

	public function uploadPreview($a_is_a_copy_of = null)
	{
		$ext = substr($_FILES['filename']["name"],strrpos($_FILES['filename']["name"], '.'));

		if(self::_contentType($_FILES["filename"]["type"], $ext) != ilObjMediaGallery::CONTENT_TYPE_IMAGE)
		{
			return false;
		}

		if(!$a_is_a_copy_of)
		{
			$this->deletePreview();
			$preview_filename = $this->getFileSystem()->getPath(LOCATION_PREVIEWS) . $this->getId() . $ext;
			@move_uploaded_file($_FILES['filename']["tmp_name"], $preview_filename);

		}
		else
		{
			@copy($this->getFileSystem()->getFilePath(LOCATION_PREVIEWS,$a_is_a_copy_of),
				$this->getFileSystem()->getFilePath(LOCATION_PREVIEWS,$this->getId()));
		}

		return true;
	}


	public function rotate($direction)
	{
		if ($this->getContentType() == ilObjMediaGallery::CONTENT_TYPE_IMAGE)
		{
			include_once "./Services/Utilities/classes/class.ilUtil.php";
			$rotation = ($direction) ? "-90" : "90";
			$cmd = "-rotate $rotation ";

			$source = ilUtil::escapeShellCmd($this->getPath(LOCATION_THUMBS) );
			$target = ilUtil::escapeShellCmd($this->getPath(LOCATION_THUMBS) );
			$convert_cmd = $source . " " . $cmd." ".$target;
			ilUtil::execConvert($convert_cmd);

			$source = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_SMALL) );
			$target = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_SMALL) );
			$convert_cmd = $source . " " . $cmd." ".$target;
			ilUtil::execConvert($convert_cmd);

			$source = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_MEDIUM) );
			$target = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_MEDIUM) );
			$convert_cmd = $source . " " . $cmd." ".$target;
			ilUtil::execConvert($convert_cmd);

			$source = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_LARGE) );
			$target = ilUtil::escapeShellCmd($this->getPath(LOCATION_SIZE_LARGE) );
			$convert_cmd = $source . " " . $cmd." ".$target;
			ilUtil::execConvert($convert_cmd);

			$source = ilUtil::escapeShellCmd($this->getPath(LOCATION_ORIGINALS));
			$target = ilUtil::escapeShellCmd($this->getPath(LOCATION_ORIGINALS));
			$convert_cmd = $source . " " . $cmd." ".$target;
			ilUtil::execConvert($convert_cmd);
		}
	}

	public static function _getMediaFilesInGallery($a_xmg_id, $a_return_objects = false, $a_filter = array())
	{
		global $ilDB;
		if(!$a_xmg_id)
		{
			return array();
		}

		$ret = array();

		$res = $ilDB->query("SELECT * FROM rep_robj_xmg_filedata WHERE xmg_id = ". $ilDB->quote($a_xmg_id, "integer"));

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
				"custom" => $row["custom"]
			);

			if(!self::$objects[$row["id"]])
			{
				$obj =  new self();
				$obj->setId($row["id"]);
				$obj->setValuesByArray($arr);

				self::$objects[$row["id"]] = $obj;
			}

			if(count($a_filter) == 0 || in_array(self::$objects[$row["id"]]->getContentType, $a_filter))
			{
				if($a_return_objects)
				{
					$ret[$row["id"]] = self::$objects[$row["id"]];
				}
				else
				{
					$ret[$row["id"]] = $arr;
					$ret[$row["id"]]['has_preview'] = self::$objects[$row["id"]]->hasPreviewImage();
					$ret[$row["id"]]['content_type'] =  self::$objects[$row["id"]]->getContentType();
					$ret[$row["id"]]['size'] =  self::$objects[$row["id"]]->getSize();
				}
			}

		}

		return $ret;
	}

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

	protected static function _extConfigToArray($a_configuration_value)
	{
		if(strpos($a_configuration_value, 'ext_'))
		{
			$a_configuration_value = ilObjMediaGallery::_getConfigurationValue($a_configuration_value);
		}
		$array = explode(',', $a_configuration_value);
		$array = array_map('strtolower', $array);
		$array = array_map('trim', $array);
		return $array;
	}

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

	public static function _clone($a_source_xmg_id, $a_dest_xmg_id)
	{
		$files = self::_getMediaFilesInGallery($a_source_xmg_id, true);
		/**
		 * @var $file self
		 */
		foreach($files as $file)
		{
			$fsource_id = $file->getId();
			$file->setMediaId($a_dest_xmg_id);
			$file->setId(0);
			$file->create();

			if($file->hasPreviewImage())
			{
				$sppath = ilFSStorageMediaGallery::_getInstanceByXmgId($a_source_xmg_id)->getFilePath(ilObjMediaGallery::LOCATION_PREVIEWS,$fsource_id);
				$dppath = $file->getPath(ilObjMediaGallery::LOCATION_PREVIEWS);
				@copy($sppath, $dppath);
			}
		}
	}

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
} 