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
*
* @author Helmut Schottmüller <ilias@aurealis.de>
* @version $Id:$
*
* @ingroup ModulesTest
*/

class ilMediaFileTableGUI extends ilTable2GUI
{
    protected int $counter;
    protected ilMediaGalleryPlugin $plugin;
    protected float $custom_sort;
    protected int $lp_active;

    /**
     * @throws ilCtrlException
     * @throws ilException
     * @throws Exception
     */
    public function __construct(
        ?object $a_parent_obj,
        string $a_parent_cmd
    ) {
        $this->setId("xmg_mft_" . $a_parent_obj->getMediaGalleryObject()->getId());
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->plugin = ilMediaGalleryPlugin::_getInstance();
        $this->lp_active = $this->parent_obj->getMediaGalleryObject()->getLearningProgressEnabled();
        $this->custom_sort = 1.0;
        $this->setFormName('mediaobjectlist');
        $this->setStyle('table', 'fullwidth');
        $this->addColumn('', 'f', '1%');
        $this->addColumn($this->lng->txt("filename"), 'filename', '', false, 'xmg_fn');
        $this->addColumn('', '', '', false, 'xmg_preview');
        $this->addColumn('', '', '', false, 'xmg_action');
        $this->addColumn($this->plugin->txt("sort"), 'custom', '', false, 'xmg_custom');
        $this->addColumn($this->lng->txt("id"), 'media_id', '', false, 'xmg_id');
        if ($this->lp_active == 1) {
            $this->addColumn($this->lng->txt("learning_progress"), 'lp_relevant', '', false, 'xmg_lp');
        }
        $this->addColumn($this->plugin->txt("topic"), 'topic', '', false, 'xmg_topic');
        $this->addColumn($this->lng->txt("title"), 'title', '', false, 'xmg_title');
        $this->addColumn($this->lng->txt("description"), 'description', '', false, 'xmg_desc');
        $this->setRowTemplate("tpl.mediafiles_row.html", 'Customizing/global/plugins/Services/Repository/RepositoryObject/MediaGallery');
        $this->setDefaultOrderField("filename");
        $this->setDefaultOrderDirection("asc");
        $this->setFilterCommand('filterMedia');
        $this->setResetCommand('resetFilterMedia');
        $this->addMultiCommand('addPreview', $this->plugin->txt('add_preview'));
        $this->addMultiCommand('deletePreview', $this->plugin->txt('delete_preview'));
        $this->addMultiCommand('createArchiveFromSelection', $this->plugin->txt('add_to_archive'));
        $this->addMultiCommand('deleteFile', $this->lng->txt('delete'));
        $this->addCommandButton('deleteFile', $this->lng->txt('delete'));
        $this->addCommandButton('saveAllFileData', $this->plugin->txt('save_all'));
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));
        $this->setSelectAllCheckbox('file');
        $this->enable('header');
        $this->initFilter();
    }

    public function gallerySort($x, $y): int
    {
        $order_field = $this->getOrderField();
        if(!$x[$order_field] && !$y[$order_field]) {
            $order_field = 'custom';
        }
        switch ($this->getOrderDirection()) {
            case 'asc':
                return strnatcasecmp($x[$order_field], $y[$order_field]);
            case 'desc':
                return strnatcasecmp($y[$order_field], $x[$order_field]);
        }
        return 0;
    }

    public function numericOrdering($a_field): bool
    {
        return $a_field === 'custom';
    }

    /**
     * @throws ilCtrlException
     * @throws ilTemplateException
     */
    protected function addRotateFields(string $a_file, $a_preview = false)
    {
        $this->tpl->setCurrentBlock('rotate');
        $this->tpl->setVariable("CONTENT_TYPE", $this->plugin->txt("rotate_image" . ($a_preview ? "_preview" : "")));
        $this->ctrl->setParameter($this->parent_obj, "id", $a_file);
        $this->ctrl->setParameter($this->parent_obj, "action", "rotateLeft" . ($a_preview ? "Preview" : ""));
        $this->tpl->setVariable("URL_ROTATE_LEFT", $this->ctrl->getLinkTarget($this->parent_obj, 'mediafiles'));
        $this->ctrl->setParameter($this->parent_obj, "action", "");
        $this->ctrl->setParameter($this->parent_obj, "id", "");
        $this->tpl->setVariable("TEXT_ROTATE_LEFT", $this->plugin->txt("rotate_left" . ($a_preview ? "_preview" : "")));
        $this->ctrl->setParameter($this->parent_obj, "id", $a_file);
        $this->ctrl->setParameter($this->parent_obj, "action", "rotateRight" . ($a_preview ? "Preview" : ""));
        $this->tpl->setVariable("URL_ROTATE_RIGHT", $this->ctrl->getLinkTarget($this->parent_obj, 'mediafiles'));
        $this->ctrl->setParameter($this->parent_obj, "action", "");
        $this->ctrl->setParameter($this->parent_obj, "id", "");
        $this->tpl->setVariable("TEXT_ROTATE_RIGHT", $this->plugin->txt("rotate_right" . ($a_preview ? "_preview" : "")));
        $this->tpl->parseCurrentBlock();
    }

    /**
     * @throws ilCtrlException
     * @throws ilTemplateException
     * @throws ilWACException
     */
    public function fillRow(array $a_set): void
    {
        $this->tpl->setVariable('CB_ID', $a_set['id']);
        $this->tpl->setVariable("FILENAME", ilLegacyFormElementsUtil::prepareFormOutput($a_set['filename']));
        if ($a_set['has_preview']) {
            if(((int) $a_set['content_type']) === ilObjMediaGallery::CONTENT_TYPE_IMAGE) {
                $this->addRotateFields($a_set['id']);
            }
            $this->tpl->setVariable("PREVIEW", ilWACSignedPath::signFile($this->parent_obj->getMediaGalleryObject()->getFS()->getFilePath(ilObjMediaGallery::LOCATION_PREVIEWS, $a_set['pfilename'])));
            $this->addRotateFields($a_set['id'], true);
            $this->tpl->setVariable("PREVIEW_CLASS_BORDER", 'xmg_border');
        } elseif (((int) $a_set['content_type']) === ilObjMediaGallery::CONTENT_TYPE_IMAGE) {
            $this->tpl->setVariable("PREVIEW", ilWACSignedPath::signFile($this->parent_obj->getMediaGalleryObject()->getFS()->getFilePath(ilObjMediaGallery::LOCATION_THUMBS, $a_set['id'])));
            $this->addRotateFields($a_set['id']);
            $this->tpl->setVariable("PREVIEW_CLASS_BORDER", 'xmg_no_border');
        } elseif (((int) $a_set['content_type']) === ilObjMediaGallery::CONTENT_TYPE_AUDIO) {
            $this->tpl->setVariable("PREVIEW", $this->plugin->getDirectory() . '/templates/images/audio.png');
        } elseif (((int) $a_set['content_type']) === ilObjMediaGallery::CONTENT_TYPE_VIDEO) {
            $this->tpl->setVariable("PREVIEW", $this->plugin->getDirectory() . '/templates/images/video.png');
        } else {
            $this->tpl->setVariable("PREVIEW", $this->parent_obj->getMediaGalleryObject()->getMimeIconPath((int) $a_set['id']));
        }
        $this->tpl->setVariable("TEXT_PREVIEW", strlen($a_set['title']) ? ilLegacyFormElementsUtil::prepareFormOutput($a_set['title']) : ilLegacyFormElementsUtil::prepareFormOutput($a_set['filename']));
        $this->tpl->setVariable("ID", $a_set['filename']);
        if (((int) $a_set['custom']) === 0) {
            $a_set['custom'] = $this->custom_sort;
        }
        $this->custom_sort += 1.0;
        $this->tpl->setVariable("CUSTOM", $this->getTextFieldValue(sprintf("%.1f", $a_set['custom'])));
        $this->tpl->setVariable("SIZE", ilLegacyFormElementsUtil::prepareFormOutput($this->formatBytes($a_set['size'])));
        $this->tpl->setVariable("ELEMENT_ID", $this->getTextFieldValue((string) $a_set['media_id']));
        $this->tpl->setVariable("TOPIC", $this->getTextFieldValue((string) $a_set['topic']));
        $this->tpl->setVariable("TITLE", $this->getTextFieldValue((string) $a_set['title']));
        if ($this->lp_active == 1) {
            $this->tpl->setCurrentBlock("learning_progress");
            $this->tpl->setVariable("LP_CB_ID", $a_set["id"]);
            if ($a_set['lp_relevant'] == 1) {
                $this->tpl->setVariable("LP_CHECKED", "checked");
            }
            $this->tpl->parseCurrentBlock("learning_progress");
        }
        if (((int) $a_set['pwidth']) > 0) {
            $this->tpl->setVariable("WIDTH", $this->getTextFieldValue((string) $a_set['pwidth']));
            $this->tpl->setVariable("HEIGHT", $this->getTextFieldValue((string) $a_set['pheight']));
        } else {
            $this->tpl->setVariable("WIDTH", $this->getTextFieldValue((string) $a_set['width']));
            $this->tpl->setVariable("HEIGHT", $this->getTextFieldValue((string) $a_set['height']));
        }
        $this->tpl->setVariable("DESCRIPTION", $this->getTextFieldValue((string) $a_set['description']));
    }

    protected function getTextFieldValue(string $value): string
    {
        return strlen($value)
            ? ' value="' . ilLegacyFormElementsUtil::prepareFormOutput($value) . '"'
            : '';
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * @throws Exception
     */
    public function initFilter(): void
    {
        // media type
        $options = array(
            '' => $this->plugin->txt('all_media_types'),
            ilObjMediaGallery::CONTENT_TYPE_IMAGE => $this->plugin->txt('image'),
            ilObjMediaGallery::CONTENT_TYPE_AUDIO => $this->plugin->txt('audio'),
            ilObjMediaGallery::CONTENT_TYPE_VIDEO => $this->plugin->txt('video'),
            ilObjMediaGallery::CONTENT_TYPE_UNKNOWN => $this->plugin->txt('unknown'),
        );
        $si = new ilSelectInputGUI($this->plugin->txt("media_type"), "f_type");
        $si->setOptions($options);
        $this->addFilterItem($si);
        $si->readFromSession();
        $this->filter["f_type"] = $si->getValue();
        // filename
        $entry = new ilTextInputGUI($this->plugin->txt("filename"), "f_filename");
        $entry->setMaxLength(64);
        $entry->setSize(20);
        $this->addFilterItem($entry);
        $entry->readFromSession();
        $this->filter["f_filename"] = $entry->getValue();
        // id
        $mid = new ilTextInputGUI($this->plugin->txt("id"), "f_media_id");
        $mid->setMaxLength(64);
        $mid->setSize(20);
        $this->addFilterItem($mid);
        $mid->readFromSession();
        $this->filter["f_media_id"] = $mid->getValue();
        // topic
        $topic = new ilTextInputGUI($this->plugin->txt("topic"), "f_topic");
        $topic->setMaxLength(64);
        $topic->setSize(20);
        $this->addFilterItem($topic);
        $topic->readFromSession();
        $this->filter["f_topic"] = $topic->getValue();
        // title
        $ti = new ilTextInputGUI($this->plugin->txt("title"), "f_title");
        $ti->setMaxLength(64);
        $ti->setSize(20);
        $this->addFilterItem($ti);
        $ti->readFromSession();
        $this->filter["f_title"] = $ti->getValue();
        // description
        $ti = new ilTextInputGUI($this->plugin->txt("description"), "f_description");
        $ti->setMaxLength(64);
        $ti->setSize(20);
        $this->addFilterItem($ti);
        $ti->readFromSession();
        $this->filter["f_description"] = $ti->getValue();
    }
}
