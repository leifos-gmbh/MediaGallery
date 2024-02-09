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
 * Show MediaGallery files
 * @author Fabian Wolf <wolf@leifos.com>
 * @version $Id$
 */
class ilObjMediaGallerySubItemListGUI extends ilSubItemListGUI
{
    protected ilMediaGalleryPlugin $plugin;

    protected function getPluginObject(): ilMediaGalleryPlugin
    {
        if(!isset($this->plugin)) {
            $this->plugin = ilMediaGalleryPlugin::_getInstance();
        }
        return $this->plugin;
    }

    /**
     * @throws ilTemplateException
     */
    public function getHTML(): string
    {
        foreach($this->getSubItemIds(true) as $sub_item) {
            if(is_object($this->getHighlighter()) and strlen($this->getHighlighter()->getContent($this->getObjId(), $sub_item))) {
                $this->tpl->setCurrentBlock('sea_fragment');
                $this->tpl->setVariable('TXT_FRAGMENT', $this->getHighlighter()->getContent($this->getObjId(), $sub_item));
                $this->tpl->parseCurrentBlock();
            }
            $file = ilMediaGalleryFile::_getInstanceById($sub_item);
            $this->tpl->setCurrentBlock('subitem');
            switch($file->getContentType()) {
                case ilObjMediaGallery::CONTENT_TYPE_IMAGE:
                    $type = "image";
                    break;
                case ilObjMediaGallery::CONTENT_TYPE_AUDIO:
                    $type = "audio";
                    break;
                case ilObjMediaGallery::CONTENT_TYPE_VIDEO:
                    $type = "video";
                    break;
                default:
                    $type = "other";
                    break;
            }
            $title = $file->getTitle();
            if(!$title) {
                $title = $file->getFilename();
            }
            if($file->hasPreviewImage()) {
                $image = $file->getPath(ilObjMediaGallery::LOCATION_PREVIEWS);
            } elseif(!$file->hasPreviewImage() && $type == "image") {
                $image = $file->getPath(ilObjMediaGallery::LOCATION_THUMBS);
            } elseif($type != "image" && !$file->hasPreviewImage()) {
                $image = $this->plugin->getDirectory() . '/templates/images/' . $type . '.png';
            }
            $this->tpl->setVariable('SUB_ITEM_IMAGE', ilUtil::img($image, $title, '50px'));
            $this->tpl->setVariable('TITLE', $title);
            $this->tpl->setVariable('LINK', $this->getItemListGUI()->getCommandLink("gallery"));
            $this->tpl->setVariable('TARGET', $this->getItemListGUI()->getCommandFrame('123'));
            $this->tpl->parseCurrentBlock();
            if(count($this->getSubItemIds(true)) > 1) {
                $this->parseRelevance($sub_item);
            }
        }
        $this->showDetailsLink();
        return $this->tpl->get();
    }
}
