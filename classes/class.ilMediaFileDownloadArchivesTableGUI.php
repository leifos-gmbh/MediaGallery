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

class ilMediaFileDownloadArchivesTableGUI extends ilTable2GUI
{
    protected ilMediaGalleryPlugin $plugin;
    protected int $counter;

    /**
     * @throws ilCtrlException
     * @throws ilException
     */
    public function __construct(
        ?object $a_parent_obj,
        string $a_parent_cmd
    ) {
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->plugin = ilMediaGalleryPlugin::_getInstance();
        $this->setId("xmg_arch_" . $a_parent_obj->getMediaGalleryObject()->getId());
        $this->setFormName('downloadarchives');
        $this->setStyle('table', 'fullwidth');
        $this->counter = 1;
        $this->addColumn('', 'f', '1%');
        $this->addColumn($this->plugin->txt("filename"), 'filename', '', false, 'xmg_arch_filename');
        $this->addColumn($this->plugin->txt("size"), 'size', '', false, 'xmg_arch_size');
        $this->addColumn($this->plugin->txt("download_archive"), 'download', '', false, 'xmg_arch_download');
        $this->addColumn($this->plugin->txt("created"), 'created', '', false, 'xmg_arch_created');
        $this->setRowTemplate("tpl.mediafiles_archive_row.html", 'Customizing/global/plugins/Services/Repository/RepositoryObject/MediaGallery');
        $this->setDefaultOrderField("filename");
        $this->setDefaultOrderDirection("asc");
        $this->addMultiCommand('deleteArchive', $this->lng->txt('delete'));
        $this->addMultiCommand('changeArchiveFilename', $this->lng->txt('rename'));
        $this->addCommandButton('saveAllArchiveData', $this->plugin->txt('save_all'));
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));
        $this->setSelectAllCheckbox('file');
        $this->setLimit(9999);
        $this->enable('header');
        $this->initFilter();
    }

    public function numericOrdering($a_field): bool
    {
        return $a_field === 'size';
    }

    /**
     * @throws ilDateTimeException
     */
    public function fillRow(array $a_set): void
    {
        $this->tpl->setVariable('CB_ID', $a_set['id']);
        $this->tpl->setVariable("FILENAME", ilLegacyFormElementsUtil::prepareFormOutput($a_set['filename']));
        $this->tpl->setVariable("SIZE", ilLegacyFormElementsUtil::prepareFormOutput($this->formatBytes((int) $a_set['size'])));
        $this->tpl->setVariable("CREATED", ilDatePresentation::formatDate(new ilDate($a_set["created"], IL_CAL_UNIX)));
        if ($a_set['download_flag']) {
            $this->tpl->setVariable("CHECKED_DOWNLOAD", ' checked="checked"');
        }
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
}
