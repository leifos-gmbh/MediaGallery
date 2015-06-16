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
	protected $sorting;
	/**
	 * @var string
	 */
	protected $filename;
	/**
	 * @var ilObjMediaGallery
	 */
	protected $object;

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
	 * @param \ilObjMediaGallery $object
	 */
	public function setObject($object)
	{
		$this->object = $object;
	}

	/**
	 * @return \ilObjMediaGallery
	 */
	public function getObject()
	{
		if(!$this->object)
		{
			//$this->setObject();
		}
		return $this->object;
	}

	/**
	 * @return \ilFSStorageMediaGallery
	 */
	protected function getFileSystem()
	{
		return ilFSStorageMediaGallery::_getInstanceByXmgId($this->getGalleryId());
	}

	public function hasPreviewImage()
	{
		return file_exists($this->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));
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

		$res = $ilDB->query("SELECT * FROM rep_robj_xmg_filedata WHERE file_id = ". $ilDB->quote($this->getId(), "integer"));

		if ($res->numRows() > 0)
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

		self::$objects[$this->getId()];

		return true;
	}


	public function getMimeType()
	{
		include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";
		return ilMimeTypeUtil::getMimeType($this->getPath(ilObjMediaGallery::LOCATION_ORIGINALS));
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
		return $this->getFileSystem()->deleteFile(LOCATION_PREVIEWS, $this->getId());
	}

	public function createImagePreviews()
	{
		$info = $this->getFileInfo();

		if($this->getContentType() == ilObjMediaGallery::CONTENT_TYPE_IMAGE)
		{
			// creates ".png" previews vor ".tif" pictures (tif support)
			if($info["extension"] == "tif" || $info["extension"] == "tiff"){
				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS), $this->getPath(LOCATION_THUMBS) .
					$info["filename"]. ".png", "PNG",  $this->getObject()->getSizeThumbs());

				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS), $this->getPath(LOCATION_SIZE_SMALL) .
					$info["filename"]. ".png", "PNG",  $this->getObject()->getSizeSmall());

				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS), $this->getPath(LOCATION_SIZE_MEDIUM) .
					$info["filename"]. ".png","PNG",  $this->getObject()->getSizeMedium());

				ilUtil::convertImage($this->getPath(LOCATION_ORIGINALS), $this->getPath(LOCATION_SIZE_LARGE) .
					$info["filename"]. ".png", "PNG",  $this->getObject()->getSizeLarge());
				return;
			}

			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) , $this->getPath(LOCATION_THUMBS),
				$this->getObject()->getSizeThumbs(), $this->getObject()->getSizeThumbs(), true);

			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) , $this->getPath(LOCATION_SIZE_SMALL),
				$this->getObject()->getSizeSmall(), $this->getObject()->getSizeSmall(), true);

			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) , $this->getPath(LOCATION_SIZE_MEDIUM),
				$this->getObject()->getSizeMedium(), $this->getObject()->getSizeMedium(), true);

			ilUtil::resizeImage($this->getPath(LOCATION_ORIGINALS) , $this->getPath(LOCATION_SIZE_LARGE),
				$this->getObject()->getSizeLarge(),  $this->getObject()->getSizeLarge(), true);
		}
	}

	public function getContentType()
	{
		include_once "./Services/Utilities/classes/class.ilMimeTypeUtil.php";

		$mime = $this->getMimeType();
		if (strpos($mime, 'image') !== false)
		{
			return ilObjMediaGallery::CONTENT_TYPE_IMAGE;
		}
		else if (strpos($mime, 'audio') !== false)
		{
			return ilObjMediaGallery::CONTENT_TYPE_AUDIO;
		}
		else if (strpos($mime, 'video') !== false)
		{
			return ilObjMediaGallery::CONTENT_TYPE_VIDEO;
		}
		else
		{
			$ext = strtolower($this->getFileInfo("extension"));
			if (in_array($ext, array_map('strtolower',  ilObjMediaGallery::_getConfigurationValue('ext_img'))))
			{
				return ilObjMediaGallery::CONTENT_TYPE_IMAGE;
			}

			if (in_array($ext, array_map('strtolower',  ilObjMediaGallery::_getConfigurationValue('ext_vid'))))
			{
				return ilObjMediaGallery::CONTENT_TYPE_VIDEO;
			}

			if (in_array($ext, array_map('strtolower',  ilObjMediaGallery::_getConfigurationValue('ext_aud'))))
			{
				return ilObjMediaGallery::CONTENT_TYPE_AUDIO;
			}

			return ilObjMediaGallery::CONTENT_TYPE_UNKNOWN;
		}
	}

	public function uploadFile($file)
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

	public function uploadPreview()
	{

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

	public static function _getMediaFilesInGallery($a_xmg_id, $a_return_objects = false)
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

			if($a_return_objects)
			{
				$ret[$row["id"]] = self::$objects[$row["id"]];
			}
			else
			{
				$ret[$row["id"]] = $arr;

			}
		}

		return $ret;
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
} 