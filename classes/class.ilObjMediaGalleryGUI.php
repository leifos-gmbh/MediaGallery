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

use ILIAS\Filesystem\Exception\IOException;
use ILIAS\HTTP\Services as ilHttpServices;

/**
* User Interface class for gallery repository object.
*
* User interface classes process GET and POST parameter and call
* application classes to fulfill certain tasks.
*
* @author Helmut Schottmüller <ilias@aurealis.de>
*
* $Id$
*
* Integration into control structure:
* - The GUI class is called by ilRepositoryGUI
* - GUI classes used by this class are ilPermissionGUI (provides the rbac
*   screens) and ilInfoScreenGUI (handles the info screen).
*
* @ilCtrl_isCalledBy ilObjMediaGalleryGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
* @ilCtrl_Calls ilObjMediaGalleryGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI
* @ilCtrl_Calls ilObjMediaGalleryGUI: ilCommonActionDispatcherGUI, ilLearningProgressGUI
*/
class ilObjMediaGalleryGUI extends ilObjectPluginGUI
{
    protected string $sortkey = "";
    protected ilHttpServices $http;
    protected ?ilObjMediaGallery $media_object;
    protected ilPropertyFormGUI $form;
    protected ilLogger $log;

    public function __construct(int $a_ref_id = 0, int $a_id_type = self::REPOSITORY_NODE_ID, int $a_parent_node_id = 0)
    {
        global $DIC;
        parent::__construct($a_ref_id, $a_id_type, $a_parent_node_id);
        $this->http = $DIC->http();
        $this->log = $DIC->logger()->root();
        $this->plugin = ilMediaGalleryPlugin::_getInstance();
        $this->media_object = $this->object;
    }

    public function getMediaGalleryObject(): ilObjMediaGallery
    {
        return $this->media_object;
    }

    final public function getType(): string
    {
        return "xmg";
    }

    /**
     * @throws ilCtrlException
     */
    public static function _goto(array $a_target): void
    {
        global $DIC;
        $ilCtrl = $DIC->ctrl();
        $target_array = explode("_", $a_target[0]);
        $ref_id = $target_array[0];
        $param = $target_array[1];
        $ilCtrl->setParameterByClass(ilObjMediaGalleryGUI::class, "ref_id", $ref_id);
        if ($param == ilObjMediaGallery::DIRECT_UPLOAD) {
            $ilCtrl->redirectByClass(
                [
                    ilObjPluginDispatchGUI::class,
                    ilObjMediaGalleryGUI::class
                ],
                "upload"
            );
        } else {
            $ilCtrl->redirectByClass(
                [
                    ilObjPluginDispatchGUI::class,
                    ilObjMediaGalleryGUI::class
                ],
                "gallery"
            );
        }
    }

    public function performCommand($cmd): void
    {
        switch ($cmd) {
            case "editProperties":		// list all commands that need write permission here
            case "mediafiles":
            case "uploadFile":
            case "upload":
            case "deleteFile":
            case "createArchiveFromSelection":
            case "renameArchiveFilename":
            case "setArchiveFilename":
            case "changeArchiveFilename":
            case "saveAllFileData":
            case "updateProperties":
            case "filterMedia":
            case "addPreview":
            case "deletePreview":
            case "uploadPreview":
            case "resetFilterMedia":
            case "createMissingPreviews":
            case "archives":
            case "deleteArchive":
            case "saveAllArchiveData":
            case "createNewArchive":
            case "importFile":
            case "saveNewArchive":
                $this->checkPermission("write");
                $this->$cmd();
                break;
            case "download":
            case "downloadOriginal":
            case "downloadOther":
            case "gallery":
            case "recordFileAccess":
            case "export":// list all commands that need read permission here
                $this->checkPermission("read");
                $this->$cmd();
                break;
        }
    }

    public function getAfterCreationCmd(): string
    {
        return "editProperties";
    }

    public function getStandardCmd(): string
    {
        return "gallery";
    }

    public function infoScreen(): void
    {
        $this->tabs->activateTab("info_short");
        $this->checkPermission("visible");
        $info = new ilInfoScreenGUI($this);
        $info->addSection($this->txt("plugininfo"));
        $info->addProperty($this->lng->txt("name"), $this->txt("obj_xmg"));
        $info->addProperty($this->lng->txt("version"), 'xmg_version');
        $info->addProperty(
            $this->plugin->txt("perma_link_upload"),
            $this->getDirectUploadLinkHTML(),
            ""
        );
        $info->enablePrivateNotes();
        // general information
        $this->lng->loadLanguageModule("meta");
        $this->addInfoItems($info);
        // forward the command
        $this->ctrl->forwardCommand($info);
    }

    /**
     * Create perma link for upload with custom template, since using permalinkgui overlaps with it's js select function
     * @throws ilTemplateException
     */
    protected function getDirectUploadLinkHTML(): string
    {
        $tpl = $this->plugin->getTemplate("tpl.direct_upload_link.html");
        $href = ilLink::_getStaticLink(
            $this->object->getRefId(),
            $this->object->getType(),
            true,
            "_" . ilObjMediaGallery::DIRECT_UPLOAD
        );
        $tpl->setVariable("LINK", $href);
        return $tpl->get();
    }

    protected function setSubTabs($cmd): void
    {
        switch ($cmd) {
            case "mediafiles":
                $this->tabs->addSubTabTarget(
                    "list",
                    $this->ctrl->getLinkTarget($this, "mediafiles"),
                    array("mediafiles"),
                    "",
                    ""
                );
                // no break
            case 'upload':
                $this->tabs->addSubTabTarget(
                    "upload",
                    $this->ctrl->getLinkTarget($this, "upload"),
                    array("upload", "uploadPreview", "addPreview"),
                    "",
                    ""
                );
                break;
        }
    }

    /**
     * @throws ilCtrlException
     */
    public function setTabs(): void
    {
        // tab for the "show content" command
        if ($this->access->checkAccess("write", "", $this->object->getRefId())) {
            $this->tabs->addTab("mediafiles", $this->txt("mediafiles"), $this->ctrl->getLinkTarget($this, "mediafiles"));
        }
        if ($this->access->checkAccess("read", "", $this->object->getRefId())) {
            $this->tabs->addTab("gallery", $this->txt("gallery"), $this->ctrl->getLinkTarget($this, "gallery"));
        }
        $this->addInfoTab();
        if ($this->access->checkAccess("write", "", $this->object->getRefId())) {
            $this->tabs->addTab("properties", $this->txt("properties"), $this->ctrl->getLinkTarget($this, "editProperties"));
        }
        if ($this->access->checkAccess("write", "", $this->object->getRefId())) {
            $this->tabs->addTab("archives", $this->txt("archives"), $this->ctrl->getLinkTarget($this, "archives"));
        }
        if(ilLearningProgressAccess::checkAccess($this->object->getRefId()) && $this->object->getLearningProgressEnabled()) {
            $this->tabs->addTab(
                'learning_progress',
                $this->txt('learning_progress'),
                $this->ctrl->getLinkTargetByClass('illearningprogressgui', '')
            );
        }
        $this->addPermissionTab();
    }

    /**
     * @throws ilCtrlException
     */
    public function editProperties()
    {
        $this->tabs->activateTab("properties");
        $this->initPropertiesForm();
        $this->getPropertiesValues();
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * @throws ilCtrlException
     */
    public function initPropertiesForm()
    {
        $this->form = new ilPropertyFormGUI();
        $this->form->setTitle($this->txt("edit_properties"));
        // title
        $ti = new ilTextInputGUI($this->txt("title"), "title");
        $ti->setRequired(true);
        $this->form->addItem($ti);
        // description
        $ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
        $this->form->addItem($ta);
        $pres = new ilFormSectionHeaderGUI();
        $pres->setTitle($this->plugin->txt("presentation"));
        $this->form->addItem($pres);
        // theme
        $theme = new ilSelectInputGUI($this->plugin->txt("gallery_theme"), "theme");
        $theme_options = $this->media_object->getGalleryThemes();
        $theme->setOptions($theme_options);
        $this->form->addItem($theme);
        // sort
        $so = new ilSelectInputGUI($this->plugin->txt("sort_order"), "sort");
        $so->setOptions(
            array(
                'filename' => $this->txt('filename'),
                'media_id' => $this->txt('id'),
                'topic' => $this->txt('topic'),
                'title' => $this->txt('title'),
                'description' => $this->txt('description'),
                'custom' => $this->txt('individual'),
            )
        );
        $this->form->addItem($so);
        $st = new ilCheckboxInputGUI($this->txt('show_title'), 'show_title');
        $st->setInfo($this->txt("show_title_description"));
        $this->form->addItem($st);
        $sd = new ilCheckboxInputGUI($this->txt('show_download'), 'show_download');
        $sd->setInfo($this->txt("show_download_description"));
        $this->form->addItem($sd);
        // tile image
        $obj_service =  $this->getObjectService();
        $this->form = $obj_service->commonSettings()->legacyForm($this->form, $this->object)->addTileImage();
        $lp_section = new ilFormSectionHeaderGUI();
        $lp_section->setTitle($this->plugin->txt("learning_progress"));
        $this->form->addItem($lp_section);
        $lp_radio = new ilRadioGroupInputGUI($this->txt('learning_progress_mode'), 'learning_progress');
        $lp_option_deac = new ilRadioOption($this->txt('lp_deactivated'), (string) ilObjMediaGallery::LP_DEACTIVATED);
        $lp_option_deac->setInfo($this->txt('lp_deactivated_info'));
        $lp_radio->addOption($lp_option_deac);
        $lp_option_acti = new ilRadioOption($this->txt('lp_by_objects'), (string) ilObjMediaGallery::LP_ACTIVATED);
        $lp_option_acti->setInfo($this->txt('lp_by_objects_info'));
        $lp_radio->addOption($lp_option_acti);
        $this->form->addItem($lp_radio);
        $this->form->addCommandButton("updateProperties", $this->txt("save"));
        $this->form->setFormAction($this->ctrl->getFormAction($this));
    }

    public function getPropertiesValues(): void
    {
        $values["title"] = $this->object->getTitle();
        $values["desc"] = $this->object->getDescription();
        $values["sort"] = $this->media_object->getSortOrder();
        $values["show_download"] = $this->media_object->getDownload();
        $values["show_title"] = $this->media_object->getShowTitle();
        $values["theme"] = $this->media_object->getTheme();
        $values["learning_progress"] = $this->media_object->getLearningProgressEnabled();
        $this->form->setValuesByArray($values);
    }

    /**
     * @throws ilCtrlException
     */
    public function updateProperties(): void
    {
        $this->initPropertiesForm();
        if ($this->form->checkInput()) {
            $this->object->setTitle((string) $this->form->getInput("title"));
            $this->object->setDescription((string) $this->form->getInput("desc"));
            $this->media_object->setSortOrder((string) $this->form->getInput("sort"));
            $this->media_object->setShowTitle((int) $this->form->getInput("show_title"));
            $this->media_object->setDownload((int) $this->form->getInput("show_download"));
            $this->media_object->setTheme((string) $this->form->getInput("theme"));
            $this->media_object->setLearningProgressEnabled((int) $this->form->getInput("learning_progress"));
            // tile image
            $obj_service =  $this->getObjectService();
            $obj_service->commonSettings()->legacyForm($this->form, $this->object)->saveTileImage();
            $this->object->update();
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                $this->plugin->txt('msg_obj_modified'),
                true
            );
            ilLPStatusWrapper::_refreshStatus($this->object->getId());
            $this->ctrl->redirect($this, "editProperties");
        }
        $this->form->setValuesByPost();
        $this->tpl->setContent($this->form->getHtml());
    }

    /**
     * @throws ilCtrlException
     */
    public function saveAllArchiveData(): void
    {
        $data = array();
        if (is_array($_POST['download'])) {
            $data = array_keys($_POST['download']);
        }
        $archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
        $archives->setDownloadFlags($data);
        $this->tpl->setOnScreenMessage(
            ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
            $this->plugin->txt('archive_data_saved'),
            true
        );
        $this->ctrl->redirect($this, 'archives');
    }

    /**
     * @throws ilCtrlException
     */
    public function deleteArchive(): void
    {
        if (!is_array($_POST['file'])) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_INFO,
                $this->plugin->txt('please_select_archive_to_delete'),
                true
            );
        } else {
            $archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
            $archives->deleteArchives($_POST['file']);
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                sprintf((count($_POST['file']) == 1)
                    ? $this->plugin->txt('archive_deleted')
                    : $this->plugin->txt('archives_deleted'), count($_POST['file'])),
                true
            );
        }
        $this->ctrl->redirect($this, 'archives');
    }

    /**
     * @throws ilCtrlException
     * @throws IOException
     */
    public function createNewArchive()
    {
        $zip_name = ilFileUtils::getASCIIFilename(sprintf("%s_%s.zip", $this->object->getTitle(), time()));
        $archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
        $archives->createArchive(array_keys(ilMediaGalleryFile::_getMediaFilesInGallery($this->object_id)), $zip_name);
        $this->ctrl->redirect($this, "archives");
    }

    /**
     * @throws ilCtrlException
     * @throws ilException
     */
    public function archives(): void
    {
        unset($_SESSION['archiveFilename']);
        $this->tabs->activateTab("archives");
        $table_gui = new ilMediaFileDownloadArchivesTableGUI($this, 'archives');
        $archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
        $table_gui->setData($archives->getArchives());
        $this->toolbar->addButton($this->plugin->txt("new_archive"), $this->ctrl->getLinkTarget($this, "createNewArchive"));
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
        $this->tpl->setVariable('ADM_CONTENT', $table_gui->getHTML());
    }

    /**
     * @throws ilCtrlException
     */
    public function download(): void
    {
        $archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
        $filename = $archives->getArchiveFilename((int) $_POST['archive']);
        if(!file_exists($archives->getPath($filename))) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin->txt('file_not_found'),
                true
            );
            $this->ctrl->redirect($this, 'gallery');
        }
        ilFileDelivery::deliverFileLegacy($archives->getPath($filename), $filename, 'application/zip');
        $this->ctrl->redirect($this, 'gallery');
    }

    public function gallerysort(array $x, array $y): int
    {
        return strnatcasecmp($x[$this->sortkey], $y[$this->sortkey]);
    }

    public function gallery(): void
    {
        ilChangeEvent::_recordReadEvent('xmg', $this->object->getRefId(), $this->object->getId(), $this->user->getId());
        $this->tabs->activateTab("gallery");
        $gallery = new ilMediaGalleryGUI($this, $this->plugin);
        $gallery->setFileData(ilMediaGalleryFile::_getMediaFilesInGallery($this->object_id));
        $gallery->setArchiveData(ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id)->getArchives());
        $this->tpl->setVariable("ADM_CONTENT", $gallery->getHTML());
    }

    /**
     * @throws ilCtrlException
     */
    public function downloadOriginal(): void
    {
        $file = ilMediaGalleryFile::_getInstanceById((int)$this->http->request()->getQueryParams()['id']);
        if(!file_exists($file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS))) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin->txt('file_not_found'),
                true
            );
            $this->ctrl->redirect($this, 'gallery');
        }
        if ($this->media_object->getDownload()) {
            ilFileDelivery::deliverFileLegacy(
                $file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS) . $file->getId() . '.' . $file->getFileInfo()["extension"],
                $file->getFilename(),
                $file->getMimeType()
            );
        } else {
            $this->ctrl->redirect($this, "gallery");
        }
    }

    /**
     * @throws ilCtrlException
     */
    public function downloadOther(): void
    {
        $file = ilMediaGalleryFile::_getInstanceById($this->http->request()->getQueryParams()['id']);
        if(!file_exists($file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS))) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin->txt('file_not_found'),
                true
            );
            $this->ctrl->redirect($this, 'gallery');
        }
        ilFileDelivery::deliverFileLegacy($file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS), $file->getFilename(), $file->getMimeType());
    }

    /**
     * @throws ilCtrlException
     */
    public function filterMedia(): void
    {
        $table_gui = new ilMediaFileTableGUI($this, 'mediafiles');
        $table_gui->resetOffset();
        $table_gui->writeFilterToSession();
        $this->ctrl->redirect($this, 'mediafiles');
    }

    /**
     * @throws ilCtrlException
     */
    public function resetFilterMedia(): void
    {
        $table_gui = new ilMediaFileTableGUI($this, 'mediafiles');
        $table_gui->resetOffset();
        $table_gui->resetFilter();
        $this->ctrl->redirect($this, 'mediafiles');
    }

    protected function performAction(int $a_file, string $a_action): bool
    {
        $file = ilMediaGalleryFile::_getInstanceById($a_file);
        switch($a_action) {
            case "rotateLeft":
                $ret = $file->rotate(1);
                break;
            case "rotateRight":
                $ret = $file->rotate(0);
                break;
            case "rotateLeftPreview":
                $ret = $file->rotatePreview(1);
                break;
            case "rotateRightPreview":
                $ret = $file->rotatePreview(0);
                break;
            default:
                return false;
        }
        return $ret;
    }

    /**
     * @throws ilCtrlException
     */
    public function mediafiles(): void
    {
        if (isset($this->http->request()->getQueryParams()['action']) && isset($this->http->request()->getQueryParams()['id'])) {
            $this->performAction((int) $this->http->request()->getQueryParams()['id'], $this->http->request()->getQueryParams()['action']);
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                $this->plugin->txt('image_rotated'),
                true
            );
            $this->ctrl->setParameter($this, "action", "");
            $this->ctrl->redirect($this, 'mediafiles');
            return;
        }
        if(isset($this->http->request()->getQueryParams()["upload"])) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                $this->plugin->txt('new_file_added'),
                true
            );
        }
        $this->setSubTabs("mediafiles");
        $this->tabs->activateTab("mediafiles");
        $this->tpl->addCss($this->plugin->getStyleSheetLocation("xmg.css"));
        $table_gui = new ilMediaFileTableGUI($this, 'mediafiles');
        $arrFilter = array();
        foreach ($table_gui->getFilterItems() as $item) {
            if ($item->getValue() !== false) {
                $arrFilter[substr($item->getPostVar(), 2)] = $item->getValue();
            }
        }
        $media_files = ilMediaGalleryFile::_getMediaFilesInGallery($this->object_id, false, $arrFilter);
        // recalculate custom sort keys
        $tmp_sort_key = $this->sortkey;
        $this->sortkey = 'custom';
        uasort($media_files, array($this, 'gallerysort'));
        $counter = 1.0;
        foreach ($media_files as $id => $fdata) {
            $media_files[$id]['custom'] = $counter;
            $counter += 1.0;
        }
        $this->sortkey = $tmp_sort_key;
        $table_gui->setData($media_files);
        $this->tpl->setVariable('ADM_CONTENT', $table_gui->getHTML());
    }

    /**
     * @throws ilCtrlException
     */
    public function createMissingPreviews(): void
    {
        ilMediaGalleryFile::_createMissingPreviews($this->object_id);
        $this->ctrl->redirect($this, 'gallery');
    }

    /**
     * @throws ilCtrlException
     */
    public function createArchiveFromSelection(): void
    {
        if (!is_array($_POST['file'])) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_INFO,
                $this->plugin->txt('please_select_file_to_create_archive'),
                true
            );
            $this->ctrl->redirect($this, 'archives');
        } else {
            $zip_file = sprintf("%s_%s", $this->object->getTitle(), time());
            $_SESSION["archive_files"] = $_POST["file"];
            $this->tabs->activateTab("archives");
            $this->initArchiveFilenameForm("create");
            $this->form->getItemByPostVar("filename")->setValue($zip_file);
            $this->tpl->setContent($this->form->getHTML());
        }
    }

    /**
     * @throws ilCtrlException
     */
    public function saveNewArchive(): void
    {
        if (!is_array($_SESSION['archive_files'])) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_INFO,
                $this->plugin->txt('please_select_file_to_create_archive'),
                true
            );
            $this->ctrl->redirect($this, 'archives');
        }
        $archive = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
        if(file_exists($archive->getPath($_POST["filename"] . ".zip"))) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin->txt('please_select_unique_archive_name'),
                true
            );
            $this->tabs->activateTab("archives");
            $this->initArchiveFilenameForm("create");
            $this->form->getItemByPostVar("filename")->setValue($_POST["filename"]);
            $this->tpl->setContent($this->form->getHTML());
            return;
        }
        $archive->createArchive($_SESSION['archive_files'], $_POST["filename"] . ".zip");
        unset($_SESSION["archive_files"]);
        $this->ctrl->redirect($this, 'archives');
    }

    /**
     * @throws ilCtrlException
     */
    public function addPreview(): void
    {
        if (!is_array($_POST['file'])) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_INFO,
                $this->plugin->txt('please_select_file_to_add_preview'),
                true
            );
            $this->ctrl->redirect($this, 'mediafiles');
        } else {
            $_SESSION['previewFiles'] = $_POST['file'];
        }
        $this->setSubTabs("mediafiles");
        $this->tabs->activateTab("mediafiles");
        $this->initPreviewUploadForm();
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * @throws ilCtrlException
     */
    public function deletePreview(): void
    {
        if (!is_array($_POST['file'])) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_INFO,
                $this->plugin->txt('please_select_file_to_delete_preview'),
                true
            );
            $this->ctrl->redirect($this, 'mediafiles');
        }
        foreach($_POST['file'] as $fid) {
            $file = ilMediaGalleryFile::_getInstanceById((int) $fid);
            $file->setPfilename(null);
            $file->update();
        }
        $this->tpl->setOnScreenMessage(
            ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
            $this->plugin->txt('previews_deleted'),
            true
        );
        $this->ctrl->redirect($this, 'mediafiles');
    }

    /**
     * @throws ilCtrlException
     */
    public function uploadPreview(): void
    {
        $this->setSubTabs("mediafiles");
        $this->tabs->activateTab("mediafiles");
        $this->initPreviewUploadForm();
        if ($this->form->checkInput()) {
            $this->media_object->uploadPreview();
            foreach($_SESSION['previewFiles'] as $fid) {
                $file = ilMediaGalleryFile::_getInstanceById((int) $fid);
                if($_FILES['filename']["tmp_name"]) {
                    $file->setPfilename($_FILES['filename']["name"]);
                    $file->update();
                }
            }
            unset($_SESSION['previewFiles']);
            $this->ctrl->redirect($this, "mediafiles");
        }
        $this->form->setValuesByPost();
        $this->tpl->setContent($this->form->getHtml());
    }

    protected function initPreviewUploadForm()
    {
        $this->form = new ilPropertyFormGUI();
        // filename
        $ti = new ilFileInputGUI($this->txt("filename"), "filename");
        $ti->setRequired(true);
        $ti->setSuffixes(array('jpg','jpeg','png'));
        $this->form->addItem($ti);
        $this->form->addCommandButton("uploadPreview", $this->txt("upload"));
        $this->form->addCommandButton("mediafiles", $this->txt("cancel"));
        $this->form->setTitle($this->plugin->txt("add_preview"));
        $this->form->setFormAction($this->ctrl->getFormAction($this));
    }

    /**
     * @throws ilCtrlException
     */
    public function changeArchiveFilename(): void
    {
        if (
            !is_array($_POST['file']) ||
            count($_POST['file']) > 1
        ) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_INFO,
                $this->plugin->txt('please_select_archive_to_rename'),
                true
            );
            $this->ctrl->redirect($this, 'archives');
        } else {
            $archive = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
            foreach ($_POST['file'] as $file) {
                $_SESSION['archiveFilename'] = substr($archive->getArchiveFilename((int) $file), 0, -4);
            }
        }
        $this->tabs->activateTab("archives");
        $this->initArchiveFilenameForm();
        $this->getArchiveFilenameValues();
        $this->tpl->setContent($this->form->getHTML());
    }

    public function setArchiveFilename(): void
    {
        $this->tabs->activateTab("archives");
        $this->initArchiveFilenameForm();
        $this->getArchiveFilenameValues();
        $this->tpl->setContent($this->form->getHTML());
    }

    protected function getArchiveFilenameValues(): void
    {
        $values["filename"] = $_SESSION['archiveFilename'];
        $this->form->setValuesByArray($values);
    }

    /**
     * @throws ilCtrlException
     */
    public function renameArchiveFilename(): void
    {
        if($_SESSION['archiveFilename'] == $_POST['filename']) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                $this->plugin->txt('rename_successful'),
                true
            );
            unset($_SESSION['archiveFilename']);
            $this->ctrl->redirect($this, 'archives');
        } elseif (file_exists(ilFSStorageMediaGallery::_getInstanceByXmgId($this->object_id)->getFilePath(ilObjMediaGallery::LOCATION_DOWNLOADS, $_POST['filename'] . ".zip"))) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin->txt('please_select_unique_archive_name'),
                true
            );
            $this->ctrl->redirect($this, 'setArchiveFilename');
        } else {
            if (strlen($_SESSION['archiveFilename']) && strlen($_POST['filename'])) {
                $archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
                $archives->renameArchive($_SESSION['archiveFilename'] . '.zip', $_POST['filename'] . '.zip');
                unset($_SESSION['archiveFilename']);
                $this->tpl->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                    $this->plugin->txt('rename_successful'),
                    true
                );
            }
            $this->ctrl->redirect($this, 'archives');
        }
    }

    protected function initArchiveFilenameForm($a_mode = "edit"): void
    {
        $this->form = new ilPropertyFormGUI();
        // filename
        $ti = new ilTextInputGUI($this->txt("filename"), "filename");
        $ti->setRequired(true);
        $ti->setSuffix(".zip");
        $ti->setValue($_SESSION['archiveFilename']);
        $this->form->addItem($ti);
        $this->form->setTitle($this->plugin->txt("saveArchiveFilename"));
        $this->form->setFormAction($this->ctrl->getFormAction($this));
        if($a_mode == "edit") {
            $this->form->addCommandButton("renameArchiveFilename", $this->txt("save"));
            $this->form->addCommandButton("archives", $this->txt("cancel"));
        } elseif($a_mode == "create") {
            $this->form->addCommandButton("saveNewArchive", $this->txt("save"));
            $this->form->addCommandButton("mediafiles", $this->txt("cancel"));
        }
    }

    /**
     * @throws ilCtrlException
     */
    public function deleteFile(): void
    {
        if (!is_array($_POST['file'])) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_INFO,
                $this->plugin->txt('please_select_file_to_delete'),
                true
            );
        } else {
            foreach ($_POST['file'] as $fid) {
                ilMediaGalleryFile::_getInstanceById((int) $fid)->delete();
            }
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                sprintf((count($_POST['file']) == 1)
                    ? $this->plugin->txt('file_deleted')
                    : $this->plugin->txt('files_deleted'), count($_POST['file'])),
                true
            );
        }
        $this->ctrl->redirect($this, 'mediafiles');
    }

    /**
     * @throws ilCtrlException
     */
    public function saveAllFileData(): void
    {
        if(is_null($_POST['id'])) {
            ;
            $this->ctrl->redirect($this, 'mediafiles');
        }
        foreach (array_keys($_POST['id']) as $fid) {
            $file = ilMediaGalleryFile::_getInstanceById((int) $fid);
            $file->setMediaId((string) $_POST['id'][$fid]);
            $file->setTopic((string) $_POST['topic'][$fid]);
            $file->setTitle((string) $_POST['title'][$fid]);
            $file->setDescription((string) $_POST['description'][$fid]);
            $file->setSorting(is_numeric($_POST['custom'][$fid]) ? (((int) $_POST['custom'][$fid]) * 10) : 0);
            if(isset($_POST['lp_relevant'][$fid])) {
                $file->setLpRelevant(1);
            } else {
                $file->setLpRelevant(0);
            }
            $file->update();
        }
        $this->tpl->setOnScreenMessage(
            ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
            $this->plugin->txt('file_data_saved'),
            true
        );
        $this->ctrl->redirect($this, 'mediafiles');
    }

    protected function normalizeUtf8String($s): string
    {
        $org = $s;
        // maps German (umlauts) and other European characters onto two characters before just removing diacritics
        $s = preg_replace('@\x{00c4}@u', "AE", $s);    // umlaut Ä => AE
        $s = preg_replace('@\x{00d6}@u', "OE", $s);    // umlaut Ö => OE
        $s = preg_replace('@\x{00dc}@u', "UE", $s);    // umlaut Ü => UE
        $s = preg_replace('@\x{00e4}@u', "ae", $s);    // umlaut ä => ae
        $s = preg_replace('@\x{00f6}@u', "oe", $s);    // umlaut ö => oe
        $s = preg_replace('@\x{00fc}@u', "ue", $s);    // umlaut ü => ue
        $s = preg_replace('@\x{00f1}@u', "ny", $s);    // ñ => ny
        $s = preg_replace('@\x{00ff}@u', "yu", $s);    // ÿ => yu
        if (class_exists("Normalizer", $autoload = false)) {
            $s = Normalizer::normalize($s, Normalizer::FORM_C);
        }
        $s = preg_replace('@\pM@u', "", $s);    // removes diacritics
        $s = preg_replace('@\x{00df}@u', "ss", $s);    // maps German ß onto ss
        $s = preg_replace('@\x{00c6}@u', "AE", $s);    // Æ => AE
        $s = preg_replace('@\x{00e6}@u', "ae", $s);    // æ => ae
        $s = preg_replace('@\x{0132}@u', "IJ", $s);    // ? => IJ
        $s = preg_replace('@\x{0133}@u', "ij", $s);    // ? => ij
        $s = preg_replace('@\x{0152}@u', "OE", $s);    // Œ => OE
        $s = preg_replace('@\x{0153}@u', "oe", $s);    // œ => oe
        $s = preg_replace('@\x{00d0}@u', "D", $s);    // Ð => D
        $s = preg_replace('@\x{0110}@u', "D", $s);    // Ð => D
        $s = preg_replace('@\x{00f0}@u', "d", $s);    // ð => d
        $s = preg_replace('@\x{0111}@u', "d", $s);    // d => d
        $s = preg_replace('@\x{0126}@u', "H", $s);    // H => H
        $s = preg_replace('@\x{0127}@u', "h", $s);    // h => h
        $s = preg_replace('@\x{0131}@u', "i", $s);    // i => i
        $s = preg_replace('@\x{0138}@u', "k", $s);    // ? => k
        $s = preg_replace('@\x{013f}@u', "L", $s);    // ? => L
        $s = preg_replace('@\x{0141}@u', "L", $s);    // L => L
        $s = preg_replace('@\x{0140}@u', "l", $s);    // ? => l
        $s = preg_replace('@\x{0142}@u', "l", $s);    // l => l
        $s = preg_replace('@\x{014a}@u', "N", $s);    // ? => N
        $s = preg_replace('@\x{0149}@u', "n", $s);    // ? => n
        $s = preg_replace('@\x{014b}@u', "n", $s);    // ? => n
        $s = preg_replace('@\x{00d8}@u', "O", $s);    // Ø => O
        $s = preg_replace('@\x{00f8}@u', "o", $s);    // ø => o
        $s = preg_replace('@\x{017f}@u', "s", $s);    // ? => s
        $s = preg_replace('@\x{00de}@u', "T", $s);    // Þ => T
        $s = preg_replace('@\x{0166}@u', "T", $s);    // T => T
        $s = preg_replace('@\x{00fe}@u', "t", $s);    // þ => t
        $s = preg_replace('@\x{0167}@u', "t", $s);    // t => t
        // remove all non-ASCii characters
        $s = preg_replace('@[^\0-\x80]@u', "", $s);
        // possible errors in UTF8-regular-expressions
        if (empty($s)) {
            return $org;
        } else {
            return $s;
        }
    }

    /**
     * @throws IOException
     */
    public function uploadFile(): void
    {
        // HTTP headers for no cache etc
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        // Settings
        $targetDir = ilFSStorageMediaGallery::_getInstanceByXmgId($this->object_id)->getPath(ilObjMediaGallery::LOCATION_ORIGINALS);
        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds
        // 5 minutes execution time
        @set_time_limit(5 * 60);
        // Get parameters
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
        $fileName = $_REQUEST["name"] ?? '';
        // Clean the fileName for security reasons
        $fileName = $this->normalizeUtf8String($fileName);
        $fileName = preg_replace('/[^\w\._]+/', '_', $fileName);
        // Make sure the fileName is unique with chunking support. Ignores Extensions
        $ext = pathinfo($targetDir . $fileName, PATHINFO_EXTENSION);
        $name = pathinfo($targetDir . $fileName, PATHINFO_FILENAME);
        // glob returns array() if file does not exist: open_basesir off
        // glob return FALSE if file does not exist: open_basedir on
        if(glob($targetDir . $name . ".*") && !file_exists($targetDir . $name . "." . $ext . ".part")) {
            $count = 1;
            while(glob($targetDir . $name . "_" . $count . ".*")) {
                $count++;
            }
            if($chunks >= 2 && $chunk > 0) {
                $count--;
            }
            $fileName = $name . '_' . $count . "." . $ext;
        }
        $filePath = $targetDir . $fileName;
        // Create target dir
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }
        // Remove old temp files
        if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . $file;

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        } else {
            die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
        }
        // Look for the content type header
        if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
            $contentType = $_SERVER["HTTP_CONTENT_TYPE"];
        }
        if (isset($_SERVER["CONTENT_TYPE"])) {
            $contentType = $_SERVER["CONTENT_TYPE"];
        }
        // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
        if (strpos($contentType, "multipart") !== false) {
            if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                // Open temp file
                $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
                if ($out) {
                    // Read binary input stream and append it to temp file
                    $in = fopen($_FILES['file']['tmp_name'], "rb");
                    if ($in) {
                        while ($buff = fread($in, 4096)) {
                            fwrite($out, $buff);
                        }
                    } else {
                        $this->log->write('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                    }
                    fclose($in);
                    fclose($out);
                    @unlink($_FILES['file']['tmp_name']);
                } else {
                    $this->log->write('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
                    die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
                }
            } else {
                $this->log->write('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
            }
        } else {
            // Open temp file
            $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
            if ($out) {
                // Read binary input stream and append it to temp file
                $in = fopen("php://input", "rb");

                if ($in) {
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                } else {
                    $this->log->write('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                    die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                }
                fclose($in);
                fclose($out);
            } else {
                $this->log->write('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            }
        }
        // Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$filePath}.part", $filePath);
            $file = new ilMediaGalleryFile();
            $file->setFilename(ilMediaGalleryFile::_getNextValidFilename($this->object_id, $fileName));
            $file->setGalleryId($this->object_id);
            $file->create();
            $file->uploadFile($filePath, $fileName);
        }
        // Return JSON-RPC response
        die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
    }

    /**
     * @throws ilCtrlException
     * @throws ilTemplateException
     */
    public function upload(): void
    {
        $this->setSubTabs("mediafiles");
        $this->tabs->activateTab("mediafiles");
        $template = $this->plugin->getTemplate("tpl.upload.html");
        $template->setVariable("FILE_ALERT", $this->plugin->txt('upload_file_alert'));
        $filter = array(
            $this->plugin->txt('image_files') => ilObjMediaGallery::_getConfigurationValue('ext_img'),
            $this->plugin->txt('video_files') => ilObjMediaGallery::_getConfigurationValue('ext_vid'),
            $this->plugin->txt('audio_files') => ilObjMediaGallery::_getConfigurationValue('ext_aud'),
            $this->plugin->txt('other_files') => ilObjMediaGallery::_getConfigurationValue('ext_oth')
        );
        $filter_txt =  'filters: [';
        $first = true;
        foreach($filter as $title => $value) {
            if(!$first) {
                $filter_txt .= ',';
            }
            $filter_txt .= '{title : "' . $title . '", extensions : "' . $value . '"}';
            $first = false;
            $template->setCurrentBlock('file_extensions');
            $template->setVariable('TYPE_TITLE', $title);
            $template->setVariable('ALLOWED_EXTENSIONS', $value);
            $template->parseCurrentBlock();
        }
        $filter_txt .= '],';
        $this->tpl->addCss($this->plugin->getDirectory() . "/js/jquery.plupload.queue/css/jquery.plupload.queue.css");
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/plupload.full.js");
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/jquery.plupload.queue/jquery.plupload.queue.js");
        //change language
        $lang = $this->lng->getUserLanguage();
        $lang_path = $this->plugin->getDirectory() . "/js/i18n/" . $lang . ".js";
        if(file_exists($lang_path)) {
            $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/i18n/de.js");
        }
        $js_template = $this->plugin->getTemplate("tpl.plupload_master.js");
        $js_template->setVariable("FILTERS", $filter_txt);
        $js_template->setVariable("UPLOAD_URL", html_entity_decode(ILIAS_HTTP_PATH . "/" . $this->ctrl->getLinkTarget($this, 'uploadFile')));
        $js_template->setVariable("MAX_FILE_SIZE", ilObjMediaGallery::_getConfigurationValue("max_upload", "100") . "mb");
        $this->tpl->addOnLoadCode($js_template->get());
        $this->tpl->setVariable("ADM_CONTENT", $template->get());
    }

    /**
     * @throws ilCtrlException
     */
    public function export(): void
    {
        $this->tabs->activateTab("export");
        $this->ctrl->setParameter($this, "download", 1);
        $this->toolbar->addButton("Exportdatei erzeugen (XML)", $this->ctrl->getLinkTarget($this, "export"));
        $this->ctrl->clearParameters($this);
        if(isset($this->http->request()->getQueryParams()["download"])) {
            $xml_writer = new ilMediaGalleryXmlWriter(true);
            $xml_writer->setObject($this->media_object);
            $xml_writer->write();
            ilUtil::deliverData($xml_writer->xmlDumpMem(), "media_gallery_" . time() . ".xml", "text/xml");
            $this->ctrl->redirect($this, "export");
        }
    }

    protected function initCreationForms(string $new_type): array
    {
        return [
            self::CFORM_NEW => $this->initCreateForm($new_type),
            self::CFORM_CLONE => $this->fillCloneTemplate(null, $new_type)
        ];
    }

    /**
     * @throws ilCtrlException
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    protected function importFileObject(int $parent_id = null): void
    {
        if(!$parent_id) {
            $parent_id = $this->http->request()->getQueryParams()["ref_id"];
        }
        $new_type = $_REQUEST["new_type"];
        // create permission is already checked in createObject. This check here is done to prevent hacking attempts
        if (!$this->checkPermissionBool("create", "", $new_type)) {
            $this->error->raiseError($this->lng->txt("no_create_permission"));
        }
        $this->lng->loadLanguageModule($new_type);
        $this->ctrl->setParameter($this, "new_type", $new_type);
        $form = $this->initImportForm($new_type);
        if ($form->checkInput()) {
            $imp = new ilImport((int)$parent_id);
            $new_id = $imp->importObject(
                null,
                $_FILES["importfile"]["tmp_name"],
                $_FILES["importfile"]["name"],
                $new_type
            );
            if ($new_id > 0) {
                $this->ctrl->setParameter($this, "new_type", "");
                $newObj = ilObjectFactory::getInstanceByObjId($new_id);
                $importer = new ilMediaGalleryImporter($newObj, $imp);
                $importer->init();
                $importer->importXmlRepresentation();
                $this->afterImport($newObj);
            } else {
                return;
            }
        }
        // display form to correct errors
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHtml());
    }

    /**
     * Records access of file. Is called through Ajax
     */
    public function recordFileAccess(): void
    {
        $params = $this->http->request()->getQueryParams();
        $access = ilMediaGalleryFileAccess::getInstanceByGalleryId($this->object->getId());
        $access->create($params['file_id'], $this->user->getId());
    }
}
