<?php
/**
 * Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE
 * Date: 16.06.15
 * Time: 14:42
 */

include_once('./Services/WebAccessChecker/classes/class.ilWACSignedPath.php');

/**
 * Class ilMediaGalleryGUI
 *
 * @author Fabian Wolf <wolf@leifos.com>
 * @version $Id$
 *
 */
class ilMediaGalleryGUI
{
	/**
	 * @var array
	 */
	protected $file_data;
	/**
	 * @var array
	 */
	protected $archive_data;

	/**
	 * @var ilTemplate
	 */
	protected $ctpl;

	protected $sortkey;

	/**
	 * @var ilTemplate
	 */
	protected $tpl;

	protected $preview_flag = false;

	/**
	 * @var ilMediaGalleryPlugin
	 */
	protected $plugin;

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilObjMediaGalleryGUI
	 */
	protected $parent;
	/**
	 * @var ilObjMediaGallery
	 */
	protected $object;

	protected $counter = 0;

	public function __construct($parent, $plugin)
	{
		global $tpl, $ilCtrl;

		$this->object = $parent->object;

		$this->parent = $parent;

		$this->plugin = $plugin;

		$this->tpl = $tpl;

		$this->ctrl = $ilCtrl;

		$this->init();
	}

	/**
	 * init template
	 */
	protected function init()
	{
		$this->tpl->addCss($this->plugin->getStyleSheetLocation("xmg.css"));
		$this->tpl->addCss($this->plugin->getDirectory() . "/js/prettyphoto_3.1.5/css/prettyPhoto.css");
		$this->tpl->addJavaScript($this->plugin->getDirectory() . "/js/recordFileAccess.js");
		$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/prettyphoto_3.1.5/js/jquery.prettyPhoto.js");
		$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/domready.js");
		$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/flowplayer.js");
		$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/html5media.js");
	}

	/**
	 * @param array $file_data
	 */
	public function setFileData($file_data)
	{
		$this->file_data = $file_data;
	}

	/**
	 * @return array
	 */
	public function getFileData()
	{
		return $this->file_data;
	}

	/**
	 * @param array $archive_data
	 */
	public function setArchiveData($archive_data)
	{
		$this->archive_data = $archive_data;
	}

	/**
	 * @return array
	 */
	public function getArchiveData()
	{
		return $this->archive_data;
	}

	/**
	 * fill row
	 * @param array $a_set
	 */
	protected function fillRow($a_set)
	{
		$a_set = ilMediaGalleryFile::_getInstanceById($a_set["id"]);

		$this->preview_flag = $a_set->hasPreviewImage();
		$this->counter++;

		switch($a_set->getContentType())
		{
			case ilObjMediaGallery::CONTENT_TYPE_IMAGE:
				$tpl_element = $this->image($a_set);
				break;
			case ilObjMediaGallery::CONTENT_TYPE_VIDEO:
				$tpl_element = $this->video($a_set);
				break;
			case ilObjMediaGallery::CONTENT_TYPE_AUDIO:
				$tpl_element = $this->audio($a_set);
				break;
			case ilObjMediaGallery::CONTENT_TYPE_UNKNOWN:
				$tpl_element = $this->other($a_set);
				break;
		}

		if($this->object->getDownload())
		{
			$tpl_title = $this->plugin->getTemplate("tpl.gallery.download.html");
			$this->ctrl->setParameter($this->parent, 'id', $a_set->getId());
			$tpl_title->setVariable('URL_DOWNLOAD', $this->ctrl->getLinkTarget($this->parent, "downloadOriginal"));
		}
		else
		{
			$tpl_title = $this->plugin->getTemplate("tpl.gallery.title.html");
		}


		if ($this->object->getShowTitle() && strlen($a_set->getTitle()))
		{
			$tpl_title->setVariable('MEDIA_TITLE', ilUtil::prepareFormOutput($a_set->getTitle()));
		}
		else
		{
			$tpl_title->setVariable('MEDIA_TITLE', ilUtil::prepareFormOutput($a_set->getFilename()));
		}

		$elementtitle = $tpl_title->get();

		$this->ctpl->setVariable("TXT_EXPAND_IMAGE_TITLE", $this->plugin->txt("expand_image_title"));
		$this->ctpl->setVariable("TXT_EXPAND_IMAGE", $this->plugin->txt("expand_image"));
		$this->ctpl->setVariable("TXT_NEXT", $this->plugin->txt("next"));
		$this->ctpl->setVariable("TXT_PREVIOUS", $this->plugin->txt("previous"));
		$this->ctpl->setVariable("TXT_CLOSE", $this->plugin->txt("close"));
		$this->ctpl->setVariable("TXT_START_SLIDESHOW", $this->plugin->txt("playpause"));
		$this->ctpl->setCurrentBlock('media');
		$this->ctpl->setVariable('GALLERY_ELEMENT', $tpl_element->get() . $elementtitle);
		$this->ctpl->parseCurrentBlock();
	}

	/**
	 * fill row video
	 * @param ilMediaGalleryFile $a_set
	 * @return ilTemplate
	 */
	protected function video($a_set)
	{
		$file_parts = $a_set->getFileInfo();

		switch(strtolower($file_parts['extension']))
		{
			case "swf":
				$tpl_element = $this->plugin->getTemplate("tpl.gallery.qt.html");
				if ($this->preview_flag)
				{
					list($iwidth, $iheight) = getimagesize($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));

					$scale = $this->object->scaleDimensions($iwidth, $iheight, 150);
					$width = $scale['width'];
					$height = $scale['height'];
					$tpl_element->setCurrentBlock('size');
					$tpl_element->setVariable('WIDTH', $width+2);
					$tpl_element->setVariable('HEIGHT', $height+2);
					$tpl_element->setVariable('MARGIN_TOP', round((158.0-$height)/2.0));
					$tpl_element->setVariable('MARGIN_LEFT', round((158.0-$width)/2.0));
					$tpl_element->parseCurrentBlock();
					$tpl_element->setCurrentBlock('imgsize');
					$tpl_element->setVariable('IMG_WIDTH', $width);
					$tpl_element->setVariable('IMG_HEIGHT', $height);
					$tpl_element->parseCurrentBlock();
				}
				else
				{
					$tpl_element->setCurrentBlock('size');
					$tpl_element->setVariable('WIDTH', "150");
					$tpl_element->setVariable('HEIGHT', "150");
					$tpl_element->setVariable('MARGIN_TOP', "4");
					$tpl_element->setVariable('MARGIN_LEFT', "4");
					$tpl_element->parseCurrentBlock();
				}
				$tpl_element->setVariable('URL_VIDEO', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_ORIGINALS, true)));
				$tpl_element->setVariable('CAPTION', ilUtil::prepareFormOutput($a_set->getDescription()));
				if ($this->preview_flag)
				{
					$tpl_element->setVariable('URL_THUMBNAIL', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS, true)));
				}
				else
				{
					$tpl_element->setVariable('URL_THUMBNAIL', $this->plugin->getDirectory() . '/templates/images/video.png');
				}
				$tpl_element->setVariable('ALT_THUMBNAIL', ilUtil::prepareFormOutput($a_set->getTitle()));
				break;
			case "mov":
			default:
				$tpl_element = $this->plugin->getTemplate("tpl.gallery.vid.html");

				if ($this->preview_flag)
				{
					list($iwidth, $iheight) = getimagesize($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));

					$scale = $this->object->scaleDimensions($iwidth, $iheight, 150);
					$width = $scale['width'];
					$height = $scale['height'];
					$tpl_element->setCurrentBlock('size');
					$tpl_element->setVariable('WIDTH', $width+2);
					$tpl_element->setVariable('HEIGHT', $height+2);
					$tpl_element->setVariable('MARGIN_TOP', round((158.0-$height)/2.0));
					$tpl_element->setVariable('MARGIN_LEFT', round((158.0-$width)/2.0));
					$tpl_element->parseCurrentBlock();
					$tpl_element->setCurrentBlock('imgsize');
					$tpl_element->setVariable('IMG_WIDTH', $width);
					$tpl_element->setVariable('IMG_HEIGHT', $height);
					$tpl_element->parseCurrentBlock();
				}
				else
				{
					$tpl_element->setCurrentBlock('size');
					$tpl_element->setVariable('WIDTH', "150");
					$tpl_element->setVariable('HEIGHT', "150");
					$tpl_element->setVariable('MARGIN_TOP', "4");
					$tpl_element->setVariable('MARGIN_LEFT', "4");
					$tpl_element->parseCurrentBlock();
				}
				$tpl_element->setVariable('INLINE_SECTION', "aud".$this->counter);
				$tpl_element->setVariable('URL_VIDEO', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_ORIGINALS)));

				if(strtolower($file_parts['extension']) == 'mov')
				{
					$tpl_element->setVariable('TYPE_VIDEO', "video/mp4; codecs=avc1.42E01E, mp4a.40.2");
				}
				else
				{
					$tpl_element->setVariable('TYPE_VIDEO', $a_set->getMimeType());
				}

				$tpl_element->setVariable('CAPTION', ilUtil::prepareFormOutput($a_set->getDescription()));
				if ($this->preview_flag)
				{
					$tpl_element->setVariable('URL_THUMBNAIL', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS)));
				}
				else
				{
					$tpl_element->setVariable('URL_THUMBNAIL', $this->plugin->getDirectory() . '/templates/images/video.png');
				}
				$tpl_element->setVariable('ALT_THUMBNAIL', ilUtil::prepareFormOutput($a_set->getTitle()));
				break;
		}

        $this->ctrl->setParameter($this->parent, 'file_id', $a_set->getId());
        $tpl_element->setVariable("VID_URL", $this->ctrl->getLinkTarget($this->parent, 'recordFileAccess', '', 'true'));
		return $tpl_element;
	}

	/**
	 * fill row image
	 * @param ilMediaGalleryFile $a_set
	 * @return ilTemplate
	 */
	protected function image($a_set)
	{
		$tpl_element = $this->plugin->getTemplate("tpl.gallery.img.html");

		$location = $this->preview_flag ?
			ilObjMediaGallery::LOCATION_PREVIEWS :
			ilObjMediaGallery::LOCATION_ORIGINALS;

		list($iwidth, $iheight) = getimagesize($a_set->getPath($location));

		if ($iwidth > 0 && $iheight > 0)
		{
			$scale = $this->object->scaleDimensions($iwidth, $iheight, 150);
			$width = $scale['width'];
			$height = $scale['height'];
			$tpl_element->setCurrentBlock('size');
			$tpl_element->setVariable('WIDTH', $width+2);
			$tpl_element->setVariable('HEIGHT', $height+2);
			$tpl_element->setVariable('MARGIN_TOP', round((158.0-$height)/2.0));
			$tpl_element->setVariable('MARGIN_LEFT', round((158.0-$width)/2.0));
			$tpl_element->parseCurrentBlock();
			$tpl_element->setCurrentBlock('imgsize');
			$tpl_element->setVariable('IMG_WIDTH', $width);
			$tpl_element->setVariable('IMG_HEIGHT', $height);
			$tpl_element->parseCurrentBlock();
		}
		else
		{
			$tpl_element->setCurrentBlock('size');
			$tpl_element->setVariable('WIDTH', "150");
			$tpl_element->setVariable('HEIGHT', "150");
			$tpl_element->setVariable('MARGIN_TOP', "4");
			$tpl_element->setVariable('MARGIN_LEFT', "4");
			$tpl_element->parseCurrentBlock();
		}
        $this->ctrl->setParameter($this->parent, 'file_id', $a_set->getId());
        $tpl_element->setVariable('IMG_URL', $this->ctrl->getLinkTarget($this->parent, 'recordFileAccess', '', 'true'));
		$tpl_element->setVariable('URL_FULLSCREEN', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_SIZE_LARGE, true)));
		$tpl_element->setVariable('CAPTION', ilUtil::prepareFormOutput($a_set->getDescription()));
		if ($this->preview_flag)
		{
			$tpl_element->setVariable('URL_THUMBNAIL', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS, true)));
		}
		else
		{
			$tpl_element->setVariable('URL_THUMBNAIL',ilWACSignedPath::signFile( $a_set->getPath(ilObjMediaGallery::LOCATION_THUMBS, true)));
		}
		$tpl_element->setVariable('ALT_THUMBNAIL', ilUtil::prepareFormOutput($a_set->getTitle()));

		return $tpl_element;
	}
	/**
	 * fill row audio
	 * @param ilMediaGalleryFile $a_set
	 * @return ilTemplate
	 */
	protected function audio($a_set)
	{
		$tpl_element = $this->plugin->getTemplate("tpl.gallery.aud.html");
		if ($this->preview_flag)
		{
			list($iwidth, $iheight) = getimagesize($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));

			$scale = $this->object->scaleDimensions($iwidth, $iheight, 150);
			$width = $scale['width'];
			$height = $scale['height'];
			$tpl_element->setCurrentBlock('size');
			$tpl_element->setVariable('WIDTH', $width+2);
			$tpl_element->setVariable('HEIGHT', $height+2);
			$tpl_element->setVariable('MARGIN_TOP', round((158.0-$height)/2.0));
			$tpl_element->setVariable('MARGIN_LEFT', round((158.0-$width)/2.0));
			$tpl_element->parseCurrentBlock();
			$tpl_element->setCurrentBlock('imgsize');
			$tpl_element->setVariable('IMG_WIDTH', $width);
			$tpl_element->setVariable('IMG_HEIGHT', $height);
			$tpl_element->parseCurrentBlock();
		}
		else
		{
			$tpl_element->setCurrentBlock('size');
			$tpl_element->setVariable('WIDTH', "150");
			$tpl_element->setVariable('HEIGHT', "150");
			$tpl_element->setVariable('MARGIN_TOP', "4");
			$tpl_element->setVariable('MARGIN_LEFT', "4");
			$tpl_element->parseCurrentBlock();
		}
        $this->ctrl->setParameter($this->parent, 'file_id', $a_set->getId());
        $tpl_element->setVariable('AUDIO_URL', $this->ctrl->getLinkTarget($this->parent, 'recordFileAccess', '', 'true'));
		$tpl_element->setVariable('INLINE_SECTION', "aud".$this->counter);
		$tpl_element->setVariable('URL_AUDIO', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_ORIGINALS, true)) );
		$tpl_element->setVariable('CAPTION', ilUtil::prepareFormOutput($a_set->getDescription()));
		if ($this->preview_flag)
		{
			$tpl_element->setVariable('URL_THUMBNAIL',ilWACSignedPath::signFile( $a_set->getPath(ilObjMediaGallery::LOCATION_ORIGINALS, true)));
		}
		else
		{
			$tpl_element->setVariable('URL_THUMBNAIL', $this->plugin->getDirectory() . '/templates/images/audio.png');
		}
		$tpl_element->setVariable('ALT_THUMBNAIL', ilUtil::prepareFormOutput($a_set->getTitle()));

		return $tpl_element;
	}
	/**
	 * fill row other
	 * @param ilMediaGalleryFile $a_set
	 * @return ilTemplate
	 */
	protected function other($a_set)
	{
		$tpl_element = $this->plugin->getTemplate("tpl.gallery.other.html");

		if ($this->preview_flag)
		{
			list($iwidth, $iheight) = getimagesize($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));

			$tpl_element->setCurrentBlock('size');
			$tpl_element->setVariable('WIDTH', $iwidth+2);
			$tpl_element->setVariable('HEIGHT', $iheight+2);
			$tpl_element->setVariable('MARGIN_TOP', round((158.0-$iheight)/2.0));
			$tpl_element->setVariable('MARGIN_LEFT', round((158.0-$iwidth)/2.0));
			$tpl_element->parseCurrentBlock();
			$tpl_element->setCurrentBlock('imgsize');
			$tpl_element->setVariable('IMG_WIDTH', $iwidth);
			$tpl_element->setVariable('IMG_HEIGHT', $iheight);
			$tpl_element->parseCurrentBlock();
			$fullwidth = $iwidth;
			$fullheight = $iheight;
			if ($iwidth > 500 || $iheight > 500)
			{
				$scale = $this->object->scaleDimensions($fullwidth, $fullheight, 500);
				$fullwidth = $scale['width'];
				$fullheight = $scale['height'];
			}
			$tpl_element->setCurrentBlock('imgsizeinline');
			$tpl_element->setVariable('IMG_WIDTH', $fullwidth);
			$tpl_element->setVariable('IMG_HEIGHT', $fullheight);
			$tpl_element->parseCurrentBlock();
		}
		else
		{
			$tpl_element->setCurrentBlock('size');
			$tpl_element->setVariable('WIDTH', "150");
			$tpl_element->setVariable('HEIGHT', "150");
			$tpl_element->setVariable('MARGIN_TOP', "4");
			$tpl_element->setVariable('MARGIN_LEFT', "4");
			$tpl_element->parseCurrentBlock();
		}
		$tpl_element->setVariable('CAPTION', ilUtil::prepareFormOutput($a_set->getDescription()));
		if ($this->preview_flag)
		{
			$tpl_element->setVariable('URL_THUMBNAIL', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS)));
		}
		else
		{
			$tpl_element->setVariable('URL_THUMBNAIL', $this->object->getMimeIconPath($a_set->getId()));
		}

		$this->ctrl->setParameter($this->parent, 'file_id', $a_set->getId());
        $tpl_element->setVariable('OTH_URL', $this->ctrl->getLinkTarget($this->parent, 'recordFileAccess', '', 'true'));
		$tpl_element->setVariable('INLINE_SECTION', "oth". $this->counter);
		$this->ctrl->setParameter($this->parent, 'id', $a_set->getId());
		$tpl_element->setVariable('URL_DOWNLOAD', $this->ctrl->getLinkTarget($this->parent, "downloadOther"));
		$tpl_element->setVariable('URL_DOWNLOADICON', $this->plugin->getDirectory() . '/templates/images/download.png');
		$tpl_element->setVariable('ALT_THUMBNAIL', ilUtil::prepareFormOutput($a_set->getTitle()));

		return $tpl_element;
	}

	/**
	 * returns media gallery html
	 * @return string HTML
	 */
	public function getHTML()
	{
		$this->tpl->addCss($this->plugin->getStyleSheetLocation("xmg.css"));
		$this->tpl->addCss($this->plugin->getDirectory() . "/js/prettyphoto_3.1.5/css/prettyPhoto.css");
        $this->tpl->addJavaScript($this->plugin->getDirectory() . "/js/recordFileAccess.js");
		$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/prettyphoto_3.1.5/js/jquery.prettyPhoto.js");
		$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/domready.js");
		$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/flowplayer.js");
		$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/html5media.js");
		$mediafiles = $this->getFileData();
		$this->ctpl = $this->plugin->getTemplate("tpl.gallery.html");
		$counter = 0;
		$this->sortkey = $this->object->getSortOrder();
		if (!strlen($this->sortkey)) $this->sortkey = 'filename';
		uasort($mediafiles, array($this, 'gallerysort'));

		/**
		 * @var ilMediaGalleryFile $fdata
		 */

		foreach ($mediafiles as $fdata)
		{
			$this->fillRow($fdata);
		}

		$archives = $this->getArchiveData();
		$downloads = array();
		foreach ($archives as $id => $fdata)
		{
			if ($fdata['download_flag'] == 1)
			{
				$size = filesize($this->object->getFS()->getFilePath(ilObjMediaGallery::LOCATION_DOWNLOADS,$fdata["filename"]));
				$downloads[$id] = $fdata["filename"] . ' ('.$this->object->formatBytes($size).')';
			}
		}
		if (count($downloads))
		{
			global $ilToolbar, $lng;
			include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
			$si = new ilSelectInputGUI($this->plugin->txt("archive").':', "archive");
			$si->setOptions($downloads);
			$ilToolbar->addInputItem($si, true);
			$ilToolbar->addFormButton($lng->txt("download"), 'download');
			$ilToolbar->setFormAction($this->ctrl->getFormAction($this->parent));
		}

		$this->ctpl->setVariable("THEME", $this->object->getTheme());

		return $this->ctpl->get();
	}

	/**
	 * sort funciton
	 *
	 * @param array $x
	 * @param array $y
	 * @return int
	 */
	protected function gallerysort($x, $y)
	{
		if(!$x[$this->sortkey] && !$y[$this->sortkey])
		{
			//fallback if one falue is empty
			return strnatcasecmp($x['custom'], $y['custom']);
		}

		return strnatcasecmp($x[$this->sortkey], $y[$this->sortkey]);
	}
} 