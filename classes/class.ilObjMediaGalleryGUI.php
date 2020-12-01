<?php

include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");

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
*
*/
class ilObjMediaGalleryGUI extends ilObjectPluginGUI
{
	/**
	 * @var ilMediaGalleryPlugin
	 */
	protected $plugin;
	protected $sortkey;
	/**
	 * @var ilObjMediaGallery
	 */
	public $object;
	
	/**
	* Initialisation
	*/
	protected function afterConstructor()
	{
		// anything needed after object has been constructed
		// - gallery: append my_id GET parameter to each request
		//   $ilCtrl->saveParameter($this, array("my_id"));
		//$this->object->setId($this->object_id);

		include_once "./Services/Component/classes/class.ilPlugin.php";
		$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, "Repository", "robj", "MediaGallery");
		$this->plugin->includeClass("class.ilMediaGalleryFile.php");
		$this->plugin->includeClass("class.ilMediaGalleryArchives.php");
	}

	/**
	* Get type.
	*/
	final function getType()
	{
		return "xmg";
	}

	/**
	* Handles all commmands of this class, centralizes permission checks
  */
	function performCommand($cmd)
	{
		switch ($cmd)
		{
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
			case "export":// list all commands that need read permission here
				$this->checkPermission("read");
				$this->$cmd();
				break;
		}
	}

	/**
	* After object has been created -> jump to this command
	*/
	function getAfterCreationCmd()
	{
		return "editProperties";
	}

	/**
	* Get standard command
  */
	function getStandardCmd()
	{
		return "gallery";
	}


	/**
	* show information screen
	*/
	function infoScreen()
	{
		global $ilAccess, $ilUser, $lng, $ilCtrl, $tpl, $ilTabs;

		$ilTabs->setTabActive("info_short");

		$this->checkPermission("visible");

		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);

		$info->addSection($this->txt("plugininfo"));
		$info->addProperty($lng->txt("name"), $this->txt("obj_xmg"));
		$info->addProperty($lng->txt("version"), xmg_version);
		/*$info->addProperty('Developer', 'Helmut Schottmüller');
		$info->addProperty('Kontakt', 'ilias@aurealis.de');
		$info->addProperty('&nbsp;', 'Aurealis');
		$info->addProperty('&nbsp;', '');
		$info->addProperty('&nbsp;', "http://www.aurealis.de");*/



		$info->enablePrivateNotes();

		// general information
		$lng->loadLanguageModule("meta");

		$this->addInfoItems($info);


		// forward the command
		$ret = $ilCtrl->forwardCommand($info);


		//$tpl->setContent($ret);
	}
	//
	// DISPLAY TABS
	//

	protected function setSubTabs($cmd)
	{
		global $ilTabs;
	
		switch ($cmd)
		{
			case "mediafiles":
				$ilTabs->addSubTabTarget("list",
					$this->ctrl->getLinkTarget($this, "mediafiles"),
					array("mediafiles"),
					"", "");
			case 'upload':
				$ilTabs->addSubTabTarget("upload",
					$this->ctrl->getLinkTarget($this, "upload"),
					array("upload", "uploadPreview", "addPreview"),
					"", "");
				break;
		}
	}

	/**
	* Set tabs
	*/
	function setTabs()
	{
		global $ilTabs, $ilCtrl, $ilAccess;

		// tab for the "show content" command
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("mediafiles", $this->txt("mediafiles"), $ilCtrl->getLinkTarget($this, "mediafiles"));
		}

		if ($ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("gallery", $this->txt("gallery"), $ilCtrl->getLinkTarget($this, "gallery"));
		}
		
		// standard info screen tab
		$this->addInfoTab();

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}
		
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("archives", $this->txt("archives"), $ilCtrl->getLinkTarget($this, "archives"));
		}
		
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
            /*
             * Export feature isn't ready yet,
             */
			//$ilTabs->addTab("export", $this->txt("export"), $ilCtrl->getLinkTarget($this, "export"));
		}

        include_once './Services/Tracking/classes/class.ilLearningProgressAccess.php';
        if(ilLearningProgressAccess::checkAccess($this->object->getRefId()) && $this->object->getLearningProgress())
        {
            $ilTabs->addTab(
                'learning_progress',
                $this->txt('learning_progress'),
                $ilCtrl->getLinkTargetByClass('illearningprogressgui',''));
        }

		// standard epermission tab
		$this->addPermissionTab();
	}


	// THE FOLLOWING METHODS IMPLEMENT SOME EXAMPLE COMMANDS WITH COMMON FEATURES
	// YOU MAY REMOVE THEM COMPLETELY AND REPLACE THEM WITH YOUR OWN METHODS.

	//
	// Edit properties form
	//

	/**
	* Edit Properties. This commands uses the form class to display an input form.
	*/
	function editProperties()
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$tpl->setContent($this->form->getHTML());
	}

	/**
	* Init  form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initPropertiesForm()
	{
		global $ilCtrl;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		// title
		$ti = new ilTextInputGUI($this->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);

		// description
		$ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
		$this->form->addItem($ta);

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

		$lp_radio = new ilRadioGroupInputGUI($this->txt('learning_progress_mode'), 'learning_progress');

		$lp_option_deac = new ilRadioOption($this->txt('lp_deactivated'), ilObjMediaGallery::LP_DEACTIVATED);
		$lp_option_deac->setInfo($this->txt('lp_deactivated_info'));
		$lp_radio->addOption($lp_option_deac);

		$lp_option_acti = new ilRadioOption($this->txt('lp_by_objects'), ilObjMediaGallery::LP_ACTIVATED);
		$lp_option_acti->setInfo($this->txt('lp_by_objects_info'));
		$lp_radio->addOption($lp_option_acti);

		$this->form->addItem($lp_radio);

		// theme
		$theme = new ilSelectInputGUI($this->plugin->txt("gallery_theme"), "theme");
		$theme_options = $this->object->getGalleryThemes();
		$theme->setOptions($theme_options);
		$this->form->addItem($theme);

		// tile image
		$obj_service =  $this->getObjectService();
		$this->form = $obj_service->commonSettings()->legacyForm($this->form, $this->object)->addTileImage();

		$this->form->addCommandButton("updateProperties", $this->txt("save"));

		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}

	/**
	* Get values for edit properties form
	*/
	function getPropertiesValues()
	{
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["sort"] = $this->object->getSortOrder();
		$values["show_download"] = $this->object->getDownload();
		$values["show_title"] = $this->object->getShowTitle();
		$values["theme"] = $this->object->getTheme();
		$values["learning_progress"] = $this->object->getLearningProgress();
		$this->form->setValuesByArray($values);
	}

	/**
	* Update properties
	*/
	public function updateProperties()
	{
		global $tpl, $lng, $ilCtrl;

		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setSortOrder($this->form->getInput("sort"));
			$this->object->setShowTitle($this->form->getInput("show_title"));
			$this->object->setDownload($this->form->getInput("show_download"));
			$this->object->setTheme($this->form->getInput("theme"));
			$this->object->setLearningProgress($this->form->getInput("learning_progress"));

			// tile image
			$obj_service =  $this->getObjectService();
			$obj_service->commonSettings()->legacyForm($this->form, $this->object)->saveTileImage();

			$this->object->update();
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			ilLPStatusWrapper::_refreshStatus($this->object->getId());
			$ilCtrl->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}
	
	function saveAllArchiveData()
	{
		$data = array();
		if (is_array($_POST['download']))
		{
			$data = array_keys($_POST['download']);
		}

		$archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);

		$archives->setDownloadFlags($data);

		ilUtil::sendSuccess($this->plugin->txt('archive_data_saved'), true);
		$this->ctrl->redirect($this, 'archives');
	}
	
	function deleteArchive()
	{
		if (!is_array($_POST['file']))
		{
			ilUtil::sendInfo($this->plugin->txt('please_select_archive_to_delete'), true);
		}
		else
		{
			$archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
			$archives->deleteArchives($_POST['file']);
			ilUtil::sendSuccess(sprintf((count($_POST['file']) == 1) ? $this->plugin->txt('archive_deleted') : $this->plugin->txt('archives_deleted'), count($_POST['file'])), true);
		}
		$this->ctrl->redirect($this, 'archives');
	}
	
	function createNewArchive()
	{
		$zip_name = ilUtil::getASCIIFilename(sprintf("%s_%s.zip", $this->object->getTitle(), time()));
		$archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
		$archives->createArchive(array_keys(ilMediaGalleryFile::_getMediaFilesInGallery($this->object_id)), $zip_name);
		$this->ctrl->redirect($this, "archives");
	}
	
	function archives()
	{
		global $ilTabs, $ilToolbar, $ilCtrl;
	
		unset($_SESSION['archiveFilename']);
		$ilTabs->activateTab("archives");
		$this->plugin->includeClass("class.ilMediaFileDownloadArchivesTableGUI.php");
		$table_gui = new ilMediaFileDownloadArchivesTableGUI($this, 'archives');
		$archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
		$table_gui->setData($archives->getArchives());

		$ilToolbar->addButton($this->plugin->txt("new_archive"), $ilCtrl->getLinkTarget($this, "createNewArchive"));
		$ilToolbar->setFormAction($ilCtrl->getFormAction($this));

		$this->tpl->setVariable('ADM_CONTENT', $table_gui->getHTML());	
	}

	function download()
	{
		$archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
		$filename = $archives->getArchiveFilename($_POST['archive']);
		if(!file_exists($archives->getPath($filename)))
		{
			ilUtil::sendFailure($this->plugin->txt('file_not_found'));
			$this->ctrl->redirect($this, 'gallery');
		}
		ilUtil::deliverFile($archives->getPath($filename), $filename, 'application/zip');
		$this->ctrl->redirect($this, 'gallery');
	}
	
	function gallerysort($x, $y) 
	{
		return strnatcasecmp($x[$this->sortkey], $y[$this->sortkey]);
	} 

	public function gallery()
	{
		global $ilTabs, $DIC;

        ilChangeEvent::_lookupReadEvents($this->object_id, $DIC->user()->getId());

		$ilTabs->activateTab("gallery");
		$this->plugin->includeClass("class.ilMediaGalleryGUI.php");
		$gallery = new ilMediaGalleryGUI($this, $this->plugin);
		$gallery->setFileData(ilMediaGalleryFile::_getMediaFilesInGallery($this->object_id));
		$gallery->setArchiveData(ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id)->getArchives());
		$this->tpl->setVariable("ADM_CONTENT", $gallery->getHTML());
	}
	
	function downloadOriginal()
	{
		$file = ilMediaGalleryFile::_getInstanceById($_GET['id']);

		if(!file_exists($file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS)))
		{
			ilUtil::sendFailure($this->plugin->txt('file_not_found'));
			$this->ctrl->redirect($this, 'gallery');
		}

		if ($this->object->getDownload())
		{
			ilUtil::deliverFile($file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS) , $file->getFilename(), $file->getMimeType());
		}
		else
		{
			$this->ctrl->redirect($this, "gallery");
		}
	}

	function downloadOther()
	{
		$file = ilMediaGalleryFile::_getInstanceById($_GET['id']);

		if(!file_exists($file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS)))
		{
			ilUtil::sendFailure($this->plugin->txt('file_not_found'));
			$this->ctrl->redirect($this, 'gallery');
		}

		ilUtil::deliverFile($file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS) , $file->getFilename(), $file->getMimeType());
	}
	
	function filterMedia()
	{
		$this->plugin->includeClass("class.ilMediaFileTableGUI.php");
		$table_gui = new ilMediaFileTableGUI($this, 'mediafiles');
		$table_gui->resetOffset();
		$table_gui->writeFilterToSession();
		$this->ctrl->redirect($this, 'mediafiles');
	}

	function resetFilterMedia()
	{
		$this->plugin->includeClass("class.ilMediaFileTableGUI.php");
		$table_gui = new ilMediaFileTableGUI($this, 'mediafiles');
		$table_gui->resetOffset();
		$table_gui->resetFilter();
		$this->ctrl->redirect($this, 'mediafiles');
	}

	protected function performAction($a_file, $a_action)
	{
		$file = ilMediaGalleryFile::_getInstanceById($a_file);

		switch($a_action)
		{
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
				break;
		}
		return $ret;
	}
	
	public function mediafiles()
	{
		global $ilTabs;
		if (isset($_GET['action']) && isset($_GET['id']))
		{
			$this->performAction($_GET['id'], $_GET['action']);
			ilUtil::sendSuccess($this->plugin->txt("image_rotated"), true);
			$this->ctrl->setParameter($this, "action", "");
			$this->ctrl->redirect($this, 'mediafiles');
			return;
		}
		$this->setSubTabs("mediafiles");
		$ilTabs->activateTab("mediafiles");
		$this->tpl->addCss($this->plugin->getStyleSheetLocation("xmg.css"));
		$this->plugin->includeClass("class.ilMediaFileTableGUI.php");
		$table_gui = new ilMediaFileTableGUI($this, 'mediafiles');
		$arrFilter = array();
		foreach ($table_gui->getFilterItems() as $item)
		{
			if ($item->getValue() !== false)
			{
				$arrFilter[substr($item->getPostVar(), 2)] = $item->getValue();
			}
		}
		$mediafiles = ilMediaGalleryFile::_getMediaFilesInGallery($this->object_id, false, $arrFilter);
		// recalculate custom sort keys
		$tmpsortkey = $this->sortkey;
		$this->sortkey = 'custom';
		uasort($mediafiles, array($this, 'gallerysort'));
		$counter = 1.0;
//		$lp_active = $this->object->getLearningProgress();
		foreach ($mediafiles as $id => $fdata)
		{
			$mediafiles[$id]['custom'] = $counter;
//			$mediafiles[$id]['lp_active'] = $lp_active;
			$counter += 1.0;
		}
		$this->sortkey = $tmpsortkey;
		$table_gui->setData($mediafiles);
		$this->tpl->setVariable('ADM_CONTENT', $table_gui->getHTML());	
	}
	
	public function createMissingPreviews()
	{
		ilMediaGalleryFile::_createMissingPreviews($this->object_id);
		$this->ctrl->redirect($this, 'gallery');
	}
	
	public function createArchiveFromSelection()
	{
		global $ilTabs, $tpl;
		if (!is_array($_POST['file']))
		{
			ilUtil::sendInfo($this->plugin->txt('please_select_file_to_create_archive'), true);
			$this->ctrl->redirect($this, 'archives');
		}
		else
		{
			$zipfile = sprintf("%s_%s", $this->object->getTitle(), time());

			$_SESSION["archive_files"] = $_POST["file"];
			$ilTabs->activateTab("archives");
			$this->initArchiveFilenameForm("create");
			$this->form->getItemByPostVar("filename")->setValue($zipfile);
			$tpl->setContent($this->form->getHTML());
		}
	}

	public function saveNewArchive()
	{
		global $tpl, $ilTabs;
		if (!is_array($_SESSION['archive_files']))
		{
			ilUtil::sendInfo($this->plugin->txt('please_select_file_to_create_archive'), true);
			$this->ctrl->redirect($this, 'archives');
		}
		$archive = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);

		if(file_exists($archive->getPath($_POST["filename"] . ".zip")))
		{
			ilUtil::sendFailure($this->plugin->txt('please_select_unique_archive_name'));
			$ilTabs->activateTab("archives");
			$this->initArchiveFilenameForm("create");
			$this->form->getItemByPostVar("filename")->setValue($_POST["filename"]);
			$tpl->setContent($this->form->getHTML());
			return;
		}

		$archive->createArchive($_SESSION['archive_files'], $_POST["filename"] . ".zip");
		unset($_SESSION["archive_files"]);
		$this->ctrl->redirect($this, 'archives');
	}
	
	public function addPreview()
	{
		global $tpl, $ilTabs;

		if (!is_array($_POST['file']))
		{
			ilUtil::sendInfo($this->plugin->txt('please_select_file_to_add_preview'), true);
			$this->ctrl->redirect($this, 'mediafiles');
		}
		else
		{
			$_SESSION['previewFiles'] = $_POST['file'];
		}
		$this->setSubTabs("mediafiles");
		$ilTabs->activateTab("mediafiles");
		$this->initPreviewUploadForm();
		$tpl->setContent($this->form->getHTML());
	}

	public function deletePreview()
	{
		global $tpl, $ilTabs;

		if (!is_array($_POST['file']))
		{
			ilUtil::sendInfo($this->plugin->txt('please_select_file_to_delete_preview'), true);
			$this->ctrl->redirect($this, 'mediafiles');
		}

		foreach($_POST['file'] as $fid)
		{
			$file = ilMediaGalleryFile::_getInstanceById($fid);
			if($file)
			{
				$file->setPfilename(null);
				$file->update();
			}
		}

		ilUtil::sendSuccess($this->plugin->txt('previews_deleted'), true);
		$this->ctrl->redirect($this, 'mediafiles');
	}

	public function uploadPreview()
	{
		global $tpl, $lng, $ilCtrl, $ilTabs;

		$this->setSubTabs("mediafiles");
		$ilTabs->activateTab("mediafiles");
		$this->initPreviewUploadForm();
		if ($this->form->checkInput())
		{
			$this->object->uploadPreview();

			foreach($_SESSION['previewFiles'] as $fid)
			{
				$file = ilMediaGalleryFile::_getInstanceById($fid);
				if($file && $_FILES['filename']["tmp_name"])
				{
					$file->setPfilename($_FILES['filename']["name"]);
					$file->update();
				}
			}
			unset($_SESSION['previewFiles']);
			$ilCtrl->redirect($this, "mediafiles");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}

	protected function initPreviewUploadForm()
	{
		global $ilCtrl;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		// filename
		$ti = new ilFileInputGUI($this->txt("filename"), "filename");
		$ti->setRequired(true);
		$ti->setSuffixes(array('jpg','jpeg','png'));
		$this->form->addItem($ti);

		$this->form->addCommandButton("uploadPreview", $this->txt("upload"));
		$this->form->addCommandButton("mediafiles", $this->txt("cancel"));

		$this->form->setTitle($this->plugin->txt("add_preview"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}

	public function changeArchiveFilename()
	{
		global $tpl, $ilTabs;

		if (!is_array($_POST['file']))
		{
			ilUtil::sendInfo($this->plugin->txt('please_select_archive_to_rename'), true);
			$this->ctrl->redirect($this, 'archives');
		}
		else if (count($_POST['file']) > 1)
		{
			ilUtil::sendInfo($this->plugin->txt('please_select_archive_to_rename'), true);
			$this->ctrl->redirect($this, 'archives');
		}
		else
		{
			$archive = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);

			foreach ($_POST['file'] as $file)
			{
				$_SESSION['archiveFilename'] = substr($archive->getArchiveFilename($file), 0, -4);
			}
		}

		$ilTabs->activateTab("archives");
		$this->initArchiveFilenameForm();
		$this->getArchiveFilenameValues();
		$tpl->setContent($this->form->getHTML());
	}

	public function setArchiveFilename()
	{
		global $tpl, $ilTabs;
		
		$ilTabs->activateTab("archives");
		$this->initArchiveFilenameForm();
		$this->getArchiveFilenameValues();
		$tpl->setContent($this->form->getHTML());
	}

	protected function getArchiveFilenameValues()
	{
		$values["filename"] = $_SESSION['archiveFilename'];
		$this->form->setValuesByArray($values);
	}

	public function renameArchiveFilename()
	{
		if($_SESSION['archiveFilename'] == $_POST['filename'])
		{
			ilUtil::sendSuccess($this->plugin->txt('rename_successful'), true);
			unset($_SESSION['archiveFilename']);
			$this->ctrl->redirect($this, 'archives');
		}
		elseif (file_exists(ilFSStorageMediaGallery::_getInstanceByXmgId($this->object_id)
								->getFilePath(ilObjMediaGallery::LOCATION_DOWNLOADS, $_POST['filename'].".zip")))
		{
			ilUtil::sendFailure($this->plugin->txt('please_select_unique_archive_name'), true);
			$this->ctrl->redirect($this, 'setArchiveFilename');
		}
		else
		{
			if (strlen($_SESSION['archiveFilename']) && strlen($_POST['filename']))
			{
				$archives = ilMediaGalleryArchives::_getInstanceByXmgId($this->object_id);
				$archives->renameArchive($_SESSION['archiveFilename'] . '.zip', $_POST['filename'] . '.zip');
				unset($_SESSION['archiveFilename']);

				ilUtil::sendSuccess($this->plugin->txt('rename_successful'), true);
				$this->ctrl->redirect($this, 'archives');
			}
			else
			{
				$this->ctrl->redirect($this, 'archives');
			}
		}
	}

	protected function initArchiveFilenameForm($a_mode = "edit")
	{
		global $ilCtrl;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		// filename
		$ti = new ilTextInputGUI($this->txt("filename"), "filename");
		$ti->setRequired(true);
		$ti->setSuffix(".zip");
		$ti->setValue($_SESSION['archiveFilename']);
		$this->form->addItem($ti);

		$this->form->setTitle($this->plugin->txt("saveArchiveFilename"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));

		if($a_mode == "edit")
		{
			$this->form->addCommandButton("renameArchiveFilename", $this->txt("save"));
			$this->form->addCommandButton("archives", $this->txt("cancel"));
		}
		elseif($a_mode == "create")
		{
			$this->form->addCommandButton("saveNewArchive", $this->txt("save"));
			$this->form->addCommandButton("mediafiles", $this->txt("cancel"));
		}
	}

	public function deleteFile()
	{
		if (!is_array($_POST['file']))
		{
			ilUtil::sendInfo($this->plugin->txt('please_select_file_to_delete'), true);
		}
		else
		{
			foreach ($_POST['file'] as $fid)
			{
				ilMediaGalleryFile::_getInstanceById($fid)->delete();
			}
			ilUtil::sendSuccess(sprintf((count($_POST['file']) == 1) ? $this->plugin->txt('file_deleted') : $this->plugin->txt('files_deleted'), count($_POST['file'])), true);
		}
		$this->ctrl->redirect($this, 'mediafiles');
	}

	public function saveAllFileData()
	{
		foreach (array_keys($_POST['id']) as $fid)
		{

			$file = ilMediaGalleryFile::_getInstanceById($fid);
			$file->setMediaId($_POST['id'][$fid]);
			$file->setTopic( $_POST['topic'][$fid]);
			$file->setTitle($_POST['title'][$fid]);
			$file->setDescription($_POST['description'][$fid]);
			$file->setSorting(is_numeric($_POST['custom'][$fid])?($_POST['custom'][$fid]*10):0);
			if(isset($_POST['lp_relevant'][$fid])) {
                $file->setLpRelevant(1);
            } else {
			    $file->setLpRelevant(0);
            }
			$file->update();
		}
		ilUtil::sendSuccess($this->plugin->txt('file_data_saved'), true);
		$this->ctrl->redirect($this, 'mediafiles');
	}
	
	protected function normalizeUtf8String( $s)
	{
		$org = $s;
		// maps German (umlauts) and other European characters onto two characters before just removing diacritics
		$s    = preg_replace( '@\x{00c4}@u'    , "AE",    $s );    // umlaut Ä => AE
		$s    = preg_replace( '@\x{00d6}@u'    , "OE",    $s );    // umlaut Ö => OE
		$s    = preg_replace( '@\x{00dc}@u'    , "UE",    $s );    // umlaut Ü => UE
		$s    = preg_replace( '@\x{00e4}@u'    , "ae",    $s );    // umlaut ä => ae
		$s    = preg_replace( '@\x{00f6}@u'    , "oe",    $s );    // umlaut ö => oe
		$s    = preg_replace( '@\x{00fc}@u'    , "ue",    $s );    // umlaut ü => ue
		$s    = preg_replace( '@\x{00f1}@u'    , "ny",    $s );    // ñ => ny
		$s    = preg_replace( '@\x{00ff}@u'    , "yu",    $s );    // ÿ => yu


		if (class_exists("Normalizer", $autoload = false))
		{
			$s    = Normalizer::normalize( $s, Normalizer::FORM_C );
		}

		$s    = preg_replace( '@\pM@u'        , "",    $s );    // removes diacritics

		$s    = preg_replace( '@\x{00df}@u'    , "ss",    $s );    // maps German ß onto ss
		$s    = preg_replace( '@\x{00c6}@u'    , "AE",    $s );    // Æ => AE
		$s    = preg_replace( '@\x{00e6}@u'    , "ae",    $s );    // æ => ae
		$s    = preg_replace( '@\x{0132}@u'    , "IJ",    $s );    // ? => IJ
		$s    = preg_replace( '@\x{0133}@u'    , "ij",    $s );    // ? => ij
		$s    = preg_replace( '@\x{0152}@u'    , "OE",    $s );    // Œ => OE
		$s    = preg_replace( '@\x{0153}@u'    , "oe",    $s );    // œ => oe

		$s    = preg_replace( '@\x{00d0}@u'    , "D",    $s );    // Ð => D
		$s    = preg_replace( '@\x{0110}@u'    , "D",    $s );    // Ð => D
		$s    = preg_replace( '@\x{00f0}@u'    , "d",    $s );    // ð => d
		$s    = preg_replace( '@\x{0111}@u'    , "d",    $s );    // d => d
		$s    = preg_replace( '@\x{0126}@u'    , "H",    $s );    // H => H
		$s    = preg_replace( '@\x{0127}@u'    , "h",    $s );    // h => h
		$s    = preg_replace( '@\x{0131}@u'    , "i",    $s );    // i => i
		$s    = preg_replace( '@\x{0138}@u'    , "k",    $s );    // ? => k
		$s    = preg_replace( '@\x{013f}@u'    , "L",    $s );    // ? => L
		$s    = preg_replace( '@\x{0141}@u'    , "L",    $s );    // L => L
		$s    = preg_replace( '@\x{0140}@u'    , "l",    $s );    // ? => l
		$s    = preg_replace( '@\x{0142}@u'    , "l",    $s );    // l => l
		$s    = preg_replace( '@\x{014a}@u'    , "N",    $s );    // ? => N
		$s    = preg_replace( '@\x{0149}@u'    , "n",    $s );    // ? => n
		$s    = preg_replace( '@\x{014b}@u'    , "n",    $s );    // ? => n
		$s    = preg_replace( '@\x{00d8}@u'    , "O",    $s );    // Ø => O
		$s    = preg_replace( '@\x{00f8}@u'    , "o",    $s );    // ø => o
		$s    = preg_replace( '@\x{017f}@u'    , "s",    $s );    // ? => s
		$s    = preg_replace( '@\x{00de}@u'    , "T",    $s );    // Þ => T
		$s    = preg_replace( '@\x{0166}@u'    , "T",    $s );    // T => T
		$s    = preg_replace( '@\x{00fe}@u'    , "t",    $s );    // þ => t
		$s    = preg_replace( '@\x{0167}@u'    , "t",    $s );    // t => t

		// remove all non-ASCii characters
		$s    = preg_replace( '@[^\0-\x80]@u'    , "",    $s ); 

		// possible errors in UTF8-regular-expressions
		if (empty($s))
			return $org;
		else
			return $s;
	}

	public function uploadFile()
	{
		global $ilLog;
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
		$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

		// Clean the fileName for security reasons
		$fileName = $this->normalizeUtf8String($fileName);
		$fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

		// Make sure the fileName is unique with chunking support. Ignores Extensions
		$ext = pathinfo($targetDir.$fileName, PATHINFO_EXTENSION);
		$name = pathinfo($targetDir.$fileName, PATHINFO_FILENAME);

		// glob returns array() if file does not exist: open_basesir off
		// glob return FALSE if file does not exist: open_basedir on
		if(glob($targetDir.$name.".*") && !file_exists($targetDir.$name.".".$ext.".part")) 
		{
			$count = 1;
			while(glob($targetDir.$name."_".$count.".*"))
			{
				$count++;
			}
			if($chunks >= 2 && $chunk > 0)
			{
				$count--;
			}

			$fileName = $name . '_' . $count . ".".$ext;
		}

		$filePath = $targetDir . $fileName;

		// Create target dir
		if (!file_exists($targetDir))
			@mkdir($targetDir);

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
		} else
			die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');


		// Look for the content type header
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
			$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

		if (isset($_SERVER["CONTENT_TYPE"]))
			$contentType = $_SERVER["CONTENT_TYPE"];

		// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
		if (strpos($contentType, "multipart") !== false) 
		{
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) 
			{
				// Open temp file
				$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen($_FILES['file']['tmp_name'], "rb");

					if ($in) {
						while ($buff = fread($in, 4096))
							fwrite($out, $buff);
					} else
					{
						$ilLog->write('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
						die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
					}
					fclose($in);
					fclose($out);
					@unlink($_FILES['file']['tmp_name']);
				} else
				{
					$ilLog->write('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
					die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
				}
			} 
			else
			{
				$ilLog->write('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
				die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
			}
		} else {
			// Open temp file
			$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else
				{
					$ilLog->write('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
					die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
				}

				fclose($in);
				fclose($out);
			} else
			{
				$ilLog->write('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
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

	public function upload()
	{
		global $ilTabs, $ilCtrl, $lng;

		if(isset($_GET["upload"]))
		{
			ilUtil::sendSuccess($this->plugin->txt("new_file_added"));
		}

		$this->setSubTabs("mediafiles");
		$ilTabs->activateTab("mediafiles");
		$template = $this->plugin->getTemplate("tpl.upload.html");
		$template->setVariable("FILE_ALERT", $this->plugin->txt('upload_file_alert'));
		$this->plugin->includeClass("class.ilObjMediaGallery.php");

		$filter = array(
			$this->plugin->txt('image_files') => $ext_img = ilObjMediaGallery::_getConfigurationValue('ext_img'),
			$this->plugin->txt('video_files') => $ext_vid = ilObjMediaGallery::_getConfigurationValue('ext_vid'),
			$this->plugin->txt('audio_files') => $ext_aud = ilObjMediaGallery::_getConfigurationValue('ext_aud'),
			$this->plugin->txt('other_files') => $ext_oth = ilObjMediaGallery::_getConfigurationValue('ext_oth')
		);
		$filter_txt =  'filters: [';
		$first = true;

		foreach($filter as $title => $value)
		{
			if(!$first){$filter_txt .= ',';}
			$filter_txt .= '{title : "' . $title . '", extensions : "' . $value . '"}';
			$first = false;

			$template->setCurrentBlock('file_extensions');
			$template->setVariable('TYPE_TITLE', $title);
			$template->setVariable('ALLOWED_EXTENSIONS', $value);
			$template->parseCurrentBlock();
		}
		$filter_txt .= '],';
		$template->setVariable("FILTERS", $filter_txt);

		$template->setVariable("UPLOAD_URL", html_entity_decode(ILIAS_HTTP_PATH . "/" . $ilCtrl->getLinkTarget($this, 'uploadFile')));
		$template->setVariable("MAX_FILE_SIZE", ilObjMediaGallery::_getConfigurationValue("max_upload", 100) . "mb");
		$this->tpl->addCss($this->plugin->getDirectory() . "/js/jquery.plupload.queue/css/jquery.plupload.queue.css");
		$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/plupload.full.js");
		$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/jquery.plupload.queue/jquery.plupload.queue.js");

		//change language
		$lang = $lng->getUserLanguage();
		$lang_path = $this->plugin->getDirectory() . "/js/i18n/".$lang.".js";

		if(file_exists($lang_path))
		{
			$this->tpl->addJavascript($this->plugin->getDirectory() . "/js/i18n/de.js");
		}

		$this->tpl->setVariable("ADM_CONTENT", $template->get());
	}
	
	/**
	 * Export Tab for Ilias xml exports
	 */
	public function export()
	{
		global $ilTabs, $ilToolbar, $ilCtrl;
		$ilTabs->activateTab("export");
		$ilCtrl->setParameter($this,"download",1);
		$ilToolbar->addButton("Exportdatei erzeugen (XML)",$ilCtrl->getLinkTarget($this, "export"));
		$ilCtrl->clearParameters($this);
		
		if(isset($_GET["download"]))
		{
			$this->plugin->includeClass("class.ilMediaGalleryXmlWriter.php");
			$xml_writer = new ilMediaGalleryXmlWriter(true);
			$xml_writer->setObject($this->object);
			$xml_writer->write();
			
			ilUtil::deliverData($xml_writer->xmlDumpMem(), "media_gallery_" . time(). ".xml", "text/xml", $charset = "utf8");
			$ilCtrl->redirect($this, "export");
		}
	}


    /**
     * Init creation froms
     *
     * this will create the default creation forms: new, import, clone
     *
     * @param	string	$a_new_type
     * @return	array
     */
    protected function initCreationForms($a_new_type)
    {
        $forms = array(
            self::CFORM_NEW => $this->initCreateForm($a_new_type),
            /**
             *  MediaGallery Import and Export isn't ready yet.
             */
            //self::CFORM_IMPORT => $this->initImportForm($a_new_type),
            self::CFORM_CLONE => $this->fillCloneTemplate(null, $a_new_type)
        );

        return $forms;
    }

	/**
	 * @inheritdoc
	 */
	protected function importFileObject($parent_id = null, $a_catch_errors = true)
    {
        global $objDefinition, $tpl, $ilErr;

        if(!$parent_id)
        {
            $parent_id = $_GET["ref_id"];
        }
        $new_type = $_REQUEST["new_type"];

        // create permission is already checked in createObject. This check here is done to prevent hacking attempts
        if (!$this->checkPermissionBool("create", "", $new_type))
        {
            $ilErr->raiseError($this->lng->txt("no_create_permission"));
        }

        $this->lng->loadLanguageModule($new_type);
        $this->ctrl->setParameter($this, "new_type", $new_type);

        $form = $this->initImportForm($new_type);
        if ($form->checkInput())
        {
            include_once("./Services/Export/classes/class.ilImport.php");
            $imp = new ilImport((int)$parent_id);

            $new_id = $imp->importObject(null, $_FILES["importfile"]["tmp_name"],
                $_FILES["importfile"]["name"], $new_type);

            if ($new_id > 0)
            {
                $this->ctrl->setParameter($this, "new_type", "");

                $newObj = ilObjectFactory::getInstanceByObjId($new_id);

                $this->plugin->includeClass("class.ilMediaGalleryImporter.php");

                $importer = new ilMediaGalleryImporter($newObj,$imp);
                $importer->init();
                $importer->importXmlRepresentation();

                $this->afterImport($newObj);
            }
            else
            {
                return;
            }
        }
        // display form to correct errors
        $form->setValuesByPost();
        $tpl->setContent($form->getHtml());
    }
}


?>