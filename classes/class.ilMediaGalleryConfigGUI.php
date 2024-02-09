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
 * MediaGallery configuration user interface class
 *
 * @author Helmut Schottmüller <ilias@aurealis.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilMediaGalleryConfigGUI : ilObjComponentSettingsGUI
 */
class ilMediaGalleryConfigGUI extends ilPluginConfigGUI
{
    protected ilGlobalTemplateInterface $tpl;
    protected ilCtrl $ilCtrl;

    public function __construct()
    {
        global $DIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ilCtrl = $DIC->ctrl();
    }

    public function performCommand(string $cmd): void
    {
        switch ($cmd) {
            case "configure":
            case "save":
                $this->$cmd();
                break;
        }
    }

    /**
     * @throws ilCtrlException
     */
    public function configure(): void
    {
        $form = $this->initConfigurationForm();
        $this->tpl->setContent($form->getHTML());
    }

    //
    // From here on, this is just an gallery implementation using
    // a standard form (without saving anything)
    //
    /**
     * @throws ilCtrlException
     */
    public function initConfigurationForm(): ilPropertyFormGUI
    {
        global $lng, $ilCtrl;
        $pl = $this->getPluginObject();
        $form = new ilPropertyFormGUI();
        $form->addCommandButton("save", $lng->txt("save"));
        $form->setTitle($pl->txt("mediagallery_plugin_configuration"));
        $form->setFormAction($ilCtrl->getFormAction($this));
        $ext_img = new ilTextInputGUI($pl->txt("ext_img"), "ext_img");
        $ext_img->setValue(ilObjMediaGallery::_getConfigurationValue('ext_img'));
        $form->addItem($ext_img);
        $ext_vid = new ilTextInputGUI($pl->txt("ext_vid"), "ext_vid");
        $ext_vid->setValue(ilObjMediaGallery::_getConfigurationValue('ext_vid'));
        $form->addItem($ext_vid);
        $ext_aud = new ilTextInputGUI($pl->txt("ext_aud"), "ext_aud");
        $ext_aud->setValue(ilObjMediaGallery::_getConfigurationValue('ext_aud'));
        $form->addItem($ext_aud);
        $ext_oth = new ilTextInputGUI($pl->txt("ext_oth"), "ext_oth");
        $ext_oth->setValue(ilObjMediaGallery::_getConfigurationValue('ext_oth'));
        $form->addItem($ext_oth);
        $theme = new ilSelectInputGUI($pl->txt("gallery_theme"), "theme");
        $theme->setRequired(true);
        $theme->setValue(ilObjMediaGallery::_getConfigurationValue('theme'));
        $theme_options = ilObjMediaGallery::_getGalleryThemes();
        $theme->setOptions($theme_options);
        $form->addItem($theme);
        $max_upload = new ilNumberInputGUI($pl->txt("max_upload_size"), "max_upload");
        $max_upload->setValue(ilObjMediaGallery::_getConfigurationValue("max_upload", "100"));
        $max_upload->setSuffix("MB");
        $max_upload->setRequired(true);
        $max_upload->setMinValue(1);
        $form->addItem($max_upload);
        return $form;
    }

    /**
     * @throws ilCtrlException
     */
    public function save(): void
    {
        $pl = $this->getPluginObject();
        $form = $this->initConfigurationForm();
        if ($form->checkInput()) {
            ilObjMediaGallery::_setConfiguration('ext_img', str_replace(' ', '', $_POST['ext_img']));
            ilObjMediaGallery::_setConfiguration('ext_vid', str_replace(' ', '', $_POST['ext_vid']));
            ilObjMediaGallery::_setConfiguration('ext_aud', str_replace(' ', '', $_POST['ext_aud']));
            ilObjMediaGallery::_setConfiguration('ext_oth', str_replace(' ', '', $_POST['ext_oth']));
            ilObjMediaGallery::_setConfiguration('theme', $_POST['theme']);
            ilObjMediaGallery::_setConfiguration('max_upload', $_POST['max_upload']);
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                $pl->txt('configuration_saved'),
                true
            );
            $this->ilCtrl->redirect($this, "configure");
        } else {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHtml());
        }
    }
}
