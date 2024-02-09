<?php

/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/

declare(strict_types=1);

/**
 * Class ilMediaGalleryGUI
 * @author Fabian Wolf <wolf@leifos.com>
 * @version $Id$
 */
class ilMediaGalleryGUI
{
    protected array $file_data;
    protected array $archive_data;
    protected ilTemplate $ctpl;
    protected string $sortkey;
    protected ilGlobalTemplateInterface $tpl;
    protected bool $preview_flag = false;
    protected ilMediaGalleryPlugin $plugin;
    protected ilCtrl $ctrl;
    protected ilObjMediaGalleryGUI $parent;
    protected ilObjMediaGallery $object;
    protected int $counter = 0;

    public function __construct(ilObjMediaGalleryGUI $parent, $plugin)
    {
        global $DIC;
        $this->object = $parent->getMediaGalleryObject();
        $this->parent = $parent;
        $this->plugin = $plugin;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->initTemplate();
    }

    protected function initTemplate(): void
    {
        $this->tpl->addCss($this->plugin->getStyleSheetLocation("xmg.css"));
        $this->tpl->addCss($this->plugin->getDirectory() . "/js/prettyphoto_3.1.5/css/prettyPhoto.css");
        $this->tpl->addJavaScript($this->plugin->getDirectory() . "/js/recordFileAccess.js");
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/prettyphoto_3.1.5/js/jquery.prettyPhoto.js");
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/domready.js");
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/flowplayer.js");
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/html5media.js");
    }

    public function setFileData(array $file_data): void
    {
        $this->file_data = $file_data;
    }

    public function getFileData(): array
    {
        return $this->file_data;
    }

    public function setArchiveData(array $archive_data)
    {
        $this->archive_data = $archive_data;
    }

    public function getArchiveData(): array
    {
        return $this->archive_data;
    }

    /**
     * @throws ilCtrlException
     * @throws ilTemplateException
     * @throws ilWACException
     */
    protected function fillRow(array $media_gallery_file_info): void
    {
        $media_gallery_file = ilMediaGalleryFile::_getInstanceById((int) $media_gallery_file_info["id"]);
        $this->preview_flag = $media_gallery_file->hasPreviewImage();
        $this->counter++;
        switch($media_gallery_file->getContentType()) {
            case ilObjMediaGallery::CONTENT_TYPE_IMAGE:
                $tpl_element = $this->fillRowImage($media_gallery_file);
                break;
            case ilObjMediaGallery::CONTENT_TYPE_VIDEO:
                $tpl_element = $this->fillRowVideo($media_gallery_file);
                break;
            case ilObjMediaGallery::CONTENT_TYPE_AUDIO:
                $tpl_element = $this->fillRowAudio($media_gallery_file);
                break;
            case ilObjMediaGallery::CONTENT_TYPE_UNKNOWN:
                $tpl_element = $this->fillRowOther($media_gallery_file);
                break;
        }
        if($this->object->getDownload()) {
            $tpl_title = $this->plugin->getTemplate("tpl.gallery.download.html");
            $this->ctrl->setParameter($this->parent, 'id', $media_gallery_file->getId());
            $tpl_title->setVariable('URL_DOWNLOAD', $this->ctrl->getLinkTarget($this->parent, "downloadOriginal"));
        } else {
            $tpl_title = $this->plugin->getTemplate("tpl.gallery.title.html");
        }
        if ($this->object->getShowTitle() && strlen($media_gallery_file->getTitle())) {
            $tpl_title->setVariable('MEDIA_TITLE', ilLegacyFormElementsUtil::prepareFormOutput($media_gallery_file->getTitle()));
        } else {
            $tpl_title->setVariable('MEDIA_TITLE', ilLegacyFormElementsUtil::prepareFormOutput($media_gallery_file->getFilename()));
        }
        $element_title = $tpl_title->get();
        $this->ctpl->setVariable("TXT_EXPAND_IMAGE_TITLE", $this->plugin->txt("expand_image_title"));
        $this->ctpl->setVariable("TXT_EXPAND_IMAGE", $this->plugin->txt("expand_image"));
        $this->ctpl->setVariable("TXT_NEXT", $this->plugin->txt("next"));
        $this->ctpl->setVariable("TXT_PREVIOUS", $this->plugin->txt("previous"));
        $this->ctpl->setVariable("TXT_CLOSE", $this->plugin->txt("close"));
        $this->ctpl->setVariable("TXT_START_SLIDESHOW", $this->plugin->txt("playpause"));
        $this->ctpl->setCurrentBlock('media');
        $this->ctpl->setVariable('GALLERY_ELEMENT', $tpl_element->get() . $element_title);
        $this->ctpl->parseCurrentBlock();
    }

    /**
     * @throws ilCtrlException
     * @throws ilTemplateException
     * @throws ilWACException
     */
    protected function fillRowVideo(ilMediaGalleryFile $a_set): ilTemplate
    {
        $file_parts = $a_set->getFileInfo();
        switch(strtolower($file_parts['extension'])) {
            case "swf":
                $tpl_element = $this->plugin->getTemplate("tpl.gallery.qt.html");
                if ($this->preview_flag) {
                    list($i_width, $i_height) = getimagesize($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));
                    $scale = $this->object->scaleDimensions($i_width, $i_height, 150);
                    $width = $scale['width'];
                    $height = $scale['height'];
                    $tpl_element->setCurrentBlock('size');
                    $tpl_element->setVariable('WIDTH', $width + 2);
                    $tpl_element->setVariable('HEIGHT', $height + 2);
                    $tpl_element->setVariable('MARGIN_TOP', round((158.0 - $height) / 2.0));
                    $tpl_element->setVariable('MARGIN_LEFT', round((158.0 - $width) / 2.0));
                    $tpl_element->parseCurrentBlock();
                    $tpl_element->setCurrentBlock('imgsize');
                    $tpl_element->setVariable('IMG_WIDTH', $width);
                    $tpl_element->setVariable('IMG_HEIGHT', $height);
                } else {
                    $tpl_element->setCurrentBlock('size');
                    $tpl_element->setVariable('WIDTH', "150");
                    $tpl_element->setVariable('HEIGHT', "150");
                    $tpl_element->setVariable('MARGIN_TOP', "4");
                    $tpl_element->setVariable('MARGIN_LEFT', "4");
                }
                $tpl_element->parseCurrentBlock();
                $tpl_element->setVariable('URL_VIDEO', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_ORIGINALS)));
                $tpl_element->setVariable('CAPTION', ilLegacyFormElementsUtil::prepareFormOutput($a_set->getDescription()));
                if ($this->preview_flag) {
                    $tpl_element->setVariable('URL_THUMBNAIL', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS)));
                } else {
                    $tpl_element->setVariable('URL_THUMBNAIL', $this->plugin->getDirectory() . '/templates/images/video.png');
                }
                $tpl_element->setVariable('ALT_THUMBNAIL', ilLegacyFormElementsUtil::prepareFormOutput($a_set->getTitle()));
                break;
            case "mov":
            default:
                $tpl_element = $this->plugin->getTemplate("tpl.gallery.vid.html");
                if ($this->preview_flag) {
                    list($i_width, $i_height) = getimagesize($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));
                    $scale = $this->object->scaleDimensions($i_width, $i_height, 150);
                    $width = $scale['width'];
                    $height = $scale['height'];
                    $tpl_element->setCurrentBlock('size');
                    $tpl_element->setVariable('WIDTH', $width + 2);
                    $tpl_element->setVariable('HEIGHT', $height + 2);
                    $tpl_element->setVariable('MARGIN_TOP', round((158.0 - $height) / 2.0));
                    $tpl_element->setVariable('MARGIN_LEFT', round((158.0 - $width) / 2.0));
                    $tpl_element->parseCurrentBlock();
                    $tpl_element->setCurrentBlock('imgsize');
                    $tpl_element->setVariable('IMG_WIDTH', $width);
                    $tpl_element->setVariable('IMG_HEIGHT', $height);
                } else {
                    $tpl_element->setCurrentBlock('size');
                    $tpl_element->setVariable('WIDTH', "150");
                    $tpl_element->setVariable('HEIGHT', "150");
                    $tpl_element->setVariable('MARGIN_TOP', "4");
                    $tpl_element->setVariable('MARGIN_LEFT', "4");
                }
            $tpl_element->parseCurrentBlock();
            $tpl_element->setVariable('INLINE_SECTION', "aud" . $this->counter);
                $tpl_element->setVariable('URL_VIDEO', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_ORIGINALS)));
                if(strtolower($file_parts['extension']) == 'mov') {
                    $tpl_element->setVariable('TYPE_VIDEO', "video/mp4; codecs=avc1.42E01E, mp4a.40.2");
                } else {
                    $tpl_element->setVariable('TYPE_VIDEO', $a_set->getMimeType());
                }
                $tpl_element->setVariable('CAPTION', ilLegacyFormElementsUtil::prepareFormOutput(($a_set->getDescription())));
                if ($this->preview_flag) {
                    $tpl_element->setVariable('URL_THUMBNAIL', ilWACSignedPath::signFile($a_set->getPath(ilObjMediaGallery::LOCATION_PREVIEWS)));
                } else {
                    $tpl_element->setVariable('URL_THUMBNAIL', $this->plugin->getDirectory() . '/templates/images/video.png');
                }
                $tpl_element->setVariable('ALT_THUMBNAIL', ilLegacyFormElementsUtil::prepareFormOutput(($a_set->getTitle())));
                break;
        }
        $this->ctrl->setParameter($this->parent, 'file_id', $a_set->getId());
        $tpl_element->setVariable("VID_URL", $this->ctrl->getLinkTarget($this->parent, 'recordFileAccess', '', true));
        return $tpl_element;
    }

    /**
     * @throws ilCtrlException
     * @throws ilTemplateException
     * @throws ilWACException
     */
    protected function fillRowImage(ilMediaGalleryFile $media_gallery_file): ilTemplate
    {
        $tpl_element = $this->plugin->getTemplate("tpl.gallery.img.html");
        $location = $this->preview_flag ?
            ilObjMediaGallery::LOCATION_PREVIEWS :
            ilObjMediaGallery::LOCATION_ORIGINALS;
        list($i_width, $i_height) = getimagesize($media_gallery_file->getPath($location));
        if ($i_width > 0 && $i_height > 0) {
            $scale = $this->object->scaleDimensions($i_width, $i_height, 150);
            $width = $scale['width'];
            $height = $scale['height'];
            $tpl_element->setCurrentBlock('size');
            $tpl_element->setVariable('WIDTH', $width + 2);
            $tpl_element->setVariable('HEIGHT', $height + 2);
            $tpl_element->setVariable('MARGIN_TOP', round((158.0 - $height) / 2.0));
            $tpl_element->setVariable('MARGIN_LEFT', round((158.0 - $width) / 2.0));
            $tpl_element->parseCurrentBlock();
            $tpl_element->setCurrentBlock('imgsize');
            $tpl_element->setVariable('IMG_WIDTH', $width);
            $tpl_element->setVariable('IMG_HEIGHT', $height);
        } else {
            $tpl_element->setCurrentBlock('size');
            $tpl_element->setVariable('WIDTH', "150");
            $tpl_element->setVariable('HEIGHT', "150");
            $tpl_element->setVariable('MARGIN_TOP', "4");
            $tpl_element->setVariable('MARGIN_LEFT', "4");
        }
        $tpl_element->parseCurrentBlock();
        $this->ctrl->setParameter($this->parent, 'file_id', $media_gallery_file->getId());
        $tpl_element->setVariable('IMG_URL', $this->ctrl->getLinkTarget($this->parent, 'recordFileAccess', '', true));
        $tpl_element->setVariable('URL_FULLSCREEN', ilWACSignedPath::signFile($media_gallery_file->getPath(ilObjMediaGallery::LOCATION_SIZE_LARGE)));
        $tpl_element->setVariable('CAPTION', ilLegacyFormElementsUtil::prepareFormOutput(($media_gallery_file->getDescription())));
        if ($this->preview_flag) {
            $tpl_element->setVariable('URL_THUMBNAIL', ilWACSignedPath::signFile($media_gallery_file->getPath(ilObjMediaGallery::LOCATION_PREVIEWS)));
        } else {
            $tpl_element->setVariable('URL_THUMBNAIL', ilWACSignedPath::signFile($media_gallery_file->getPath(ilObjMediaGallery::LOCATION_THUMBS) . $media_gallery_file->getLocalFileName()));
        }
        $tpl_element->setVariable('ALT_THUMBNAIL', ilLegacyFormElementsUtil::prepareFormOutput(($media_gallery_file->getTitle())));
        return $tpl_element;
    }

    protected function fillRowAudio(ilMediaGalleryFile $media_gallery_file): ilTemplate
    {
        $tpl_element = $this->plugin->getTemplate("tpl.gallery.aud.html");
        if ($this->preview_flag) {
            list($i_width, $i_height) = getimagesize($media_gallery_file->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));
            $scale = $this->object->scaleDimensions($i_width, $i_height, 150);
            $width = $scale['width'];
            $height = $scale['height'];
            $tpl_element->setCurrentBlock('size');
            $tpl_element->setVariable('WIDTH', $width + 2);
            $tpl_element->setVariable('HEIGHT', $height + 2);
            $tpl_element->setVariable('MARGIN_TOP', round((158.0 - $height) / 2.0));
            $tpl_element->setVariable('MARGIN_LEFT', round((158.0 - $width) / 2.0));
            $tpl_element->parseCurrentBlock();
            $tpl_element->setCurrentBlock('imgsize');
            $tpl_element->setVariable('IMG_WIDTH', $width);
            $tpl_element->setVariable('IMG_HEIGHT', $height);
        } else {
            $tpl_element->setCurrentBlock('size');
            $tpl_element->setVariable('WIDTH', "150");
            $tpl_element->setVariable('HEIGHT', "150");
            $tpl_element->setVariable('MARGIN_TOP', "4");
            $tpl_element->setVariable('MARGIN_LEFT', "4");
        }
        $tpl_element->parseCurrentBlock();
        $this->ctrl->setParameter($this->parent, 'file_id', $media_gallery_file->getId());
        $tpl_element->setVariable('AUDIO_URL', $this->ctrl->getLinkTarget($this->parent, 'recordFileAccess', '', true));
        $tpl_element->setVariable('INLINE_SECTION', "aud" . $this->counter);
        $tpl_element->setVariable('URL_AUDIO', ilWACSignedPath::signFile($media_gallery_file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS)));
        $tpl_element->setVariable('CAPTION', ilLegacyFormElementsUtil::prepareFormOutput(($media_gallery_file->getDescription())));
        if ($this->preview_flag) {
            $tpl_element->setVariable('URL_THUMBNAIL', ilWACSignedPath::signFile($media_gallery_file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS)));
        } else {
            $tpl_element->setVariable('URL_THUMBNAIL', $this->plugin->getDirectory() . '/templates/images/audio.png');
        }
        $tpl_element->setVariable('ALT_THUMBNAIL', ilLegacyFormElementsUtil::prepareFormOutput(($media_gallery_file->getTitle())));
        return $tpl_element;
    }

    /**
     * @throws ilCtrlException
     * @throws ilTemplateException
     * @throws ilWACException
     */
    protected function fillRowOther(ilMediaGalleryFile $media_gallery_file): ilTemplate
    {
        $tpl_element = $this->plugin->getTemplate("tpl.gallery.other.html");
        if ($this->preview_flag) {
            list($i_width, $i_height) = getimagesize($media_gallery_file->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));
            $tpl_element->setCurrentBlock('size');
            $tpl_element->setVariable('WIDTH', $i_width + 2);
            $tpl_element->setVariable('HEIGHT', $i_height + 2);
            $tpl_element->setVariable('MARGIN_TOP', round((158.0 - $i_height) / 2.0));
            $tpl_element->setVariable('MARGIN_LEFT', round((158.0 - $i_width) / 2.0));
            $tpl_element->parseCurrentBlock();
            $tpl_element->setCurrentBlock('imgsize');
            $tpl_element->setVariable('IMG_WIDTH', $i_width);
            $tpl_element->setVariable('IMG_HEIGHT', $i_height);
            $tpl_element->parseCurrentBlock();
            $fullwidth = $i_width;
            $fullheight = $i_height;
            if ($i_width > 500 || $i_height > 500) {
                $scale = $this->object->scaleDimensions($fullwidth, $fullheight, 500);
                $fullwidth = $scale['width'];
                $fullheight = $scale['height'];
            }
            $tpl_element->setCurrentBlock('imgsizeinline');
            $tpl_element->setVariable('IMG_WIDTH', $fullwidth);
            $tpl_element->setVariable('IMG_HEIGHT', $fullheight);
        } else {
            $tpl_element->setCurrentBlock('size');
            $tpl_element->setVariable('WIDTH', "150");
            $tpl_element->setVariable('HEIGHT', "150");
            $tpl_element->setVariable('MARGIN_TOP', "4");
            $tpl_element->setVariable('MARGIN_LEFT', "4");
        }
        $tpl_element->parseCurrentBlock();
        $tpl_element->setVariable('CAPTION', ilLegacyFormElementsUtil::prepareFormOutput(($media_gallery_file->getDescription())));
        if ($this->preview_flag) {
            $tpl_element->setVariable('URL_THUMBNAIL', ilWACSignedPath::signFile($media_gallery_file->getPath(ilObjMediaGallery::LOCATION_PREVIEWS)));
        } else {
            $tpl_element->setVariable('URL_THUMBNAIL', $this->object->getMimeIconPath($media_gallery_file->getId()));
        }
        $this->ctrl->setParameter($this->parent, 'file_id', $media_gallery_file->getId());
        $tpl_element->setVariable('OTH_URL', $this->ctrl->getLinkTarget($this->parent, 'recordFileAccess', '', true));
        $tpl_element->setVariable('INLINE_SECTION', "oth" . $this->counter);
        $this->ctrl->setParameter($this->parent, 'id', $media_gallery_file->getId());
        $tpl_element->setVariable('URL_DOWNLOAD', $this->ctrl->getLinkTarget($this->parent, "downloadOther"));
        $tpl_element->setVariable('URL_DOWNLOADICON', $this->plugin->getDirectory() . '/templates/images/download.png');
        $tpl_element->setVariable('ALT_THUMBNAIL', ilLegacyFormElementsUtil::prepareFormOutput(($media_gallery_file->getTitle())));
        return $tpl_element;
    }

    /**
     * @throws ilCtrlException
     * @throws ilTemplateException
     * @throws ilWACException
     */
    public function getHTML(): string
    {
        $this->tpl->addCss($this->plugin->getStyleSheetLocation("xmg.css"));
        $this->tpl->addCss($this->plugin->getDirectory() . "/js/prettyphoto_3.1.5/css/prettyPhoto.css");
        $this->tpl->addJavaScript($this->plugin->getDirectory() . "/js/recordFileAccess.js");
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/prettyphoto_3.1.5/js/jquery.prettyPhoto.js");
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/domready.js");
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/flowplayer.js");
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/html5media-master/html5media.js");
        $media_files = $this->getFileData();
        $this->ctpl = $this->plugin->getTemplate("tpl.gallery.html");
        $counter = 0;
        $this->sortkey = $this->object->getSortOrder();
        if (!strlen($this->sortkey)) {
            $this->sortkey = 'filename';
        }
        uasort($media_files, array($this, 'gallerySort'));
        foreach ($media_files as $f_data) {
            $this->fillRow($f_data);
        }
        $archives = $this->getArchiveData();
        $downloads = array();
        foreach ($archives as $id => $f_data) {
            if ($f_data['download_flag'] == 1) {
                $size = filesize($this->object->getFS()->getFilePath(ilObjMediaGallery::LOCATION_DOWNLOADS, $f_data["filename"]));
                $downloads[$id] = $f_data["filename"] . ' (' . $this->object->formatBytes($size) . ')';
            }
        }
        if (count($downloads)) {
            global $ilToolbar, $lng;
            $si = new ilSelectInputGUI($this->plugin->txt("archive") . ':', "archive");
            $si->setOptions($downloads);
            $ilToolbar->addInputItem($si, true);
            $ilToolbar->addFormButton($lng->txt("download"), 'download');
            $ilToolbar->setFormAction($this->ctrl->getFormAction($this->parent));
        }
        $this->ctpl->setVariable("THEME", $this->object->getTheme());
        return $this->ctpl->get();
    }

    protected function gallerySort(array $x, array $y): int
    {
        if(!$x[$this->sortkey] && !$y[$this->sortkey]) {
            return strnatcasecmp($x['custom'], $y['custom']);
        }
        return strnatcasecmp($x[$this->sortkey], $y[$this->sortkey]);
    }
}
