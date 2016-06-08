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

include_once('./Services/Table/classes/class.ilTable2GUI.php');
include_once('./Services/WebAccessChecker/classes/class.ilWACSignedPath.php');

/**
*
* @author Helmut Schottmüller <ilias@aurealis.de>
* @version $Id:$
*
* @ingroup ModulesTest
*/

class ilMediaFileTableGUI extends ilTable2GUI
{
	protected $counter;
	protected $plugin;
	protected $customsort;
	/**
	 * @var ilObjMediaGalleryGUI
	 */
	protected $parent_obj;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{
		$this->setId("xmg_mft_".$a_parent_obj->object->getId());
		parent::__construct($a_parent_obj, $a_parent_cmd);

		global $lng, $ilCtrl;

		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		include_once "./Services/Component/classes/class.ilPlugin.php";
		$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, "Repository", "robj", "MediaGallery");
	
		$this->customsort = 1.0;
		$this->setFormName('mediaobjectlist');
		$this->setStyle('table', 'fullwidth');
		$this->addColumn('','f','1%');
		$this->addColumn($this->lng->txt("filename"),'entry', '', '', 'xmg_fn');
		$this->addColumn('','', '', '', 'xmg_preview');
		$this->addColumn('','', '', '', 'xmg_action');
		$this->addColumn($this->plugin->txt("sort"),'custom', '', '', 'xmg_custom');
		$this->addColumn($this->lng->txt("id"),'media_id', '', '', 'xmg_id');
		$this->addColumn($this->plugin->txt("topic"),'topic', '', '', 'xmg_topic');
		$this->addColumn($this->lng->txt("title"),'title', '', '', 'xmg_title');
		$this->addColumn($this->lng->txt("description"),'description', '', '', 'xmg_desc');
	
		$this->setRowTemplate("tpl.mediafiles_row.html", 'Customizing/global/plugins/Services/Repository/RepositoryObject/MediaGallery');

		$this->setDefaultOrderField("entry");
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
//		$this->setExternalSorting(true);

		$this->enable('header');
		$this->initFilter();
	}
	
	public function gallerysort($x, $y) 
	{
		switch ($this->getOrderDirection())
		{
			case 'asc':
				return strnatcasecmp($x[$this->getOrderField()], $y[$this->getOrderField()]);
				break;
			case 'desc':
				return strnatcasecmp($y[$this->getOrderField()], $x[$this->getOrderField()]);
				break;
		}
		return 0;
	} 

	protected function prepareOutput()
	{
		return;
		// use this for external sorting
		$this->determineOffsetAndOrder();
		uasort($this->row_data, array($this, 'gallerysort'));
	}

	function numericOrdering($a_field)
	{
		switch ($a_field)
		{
			case 'custom':
				return true;
			default:
				return false;
		}
	}


	protected function addRotateFields($a_file, $a_preview = false)
	{
		$this->tpl->setCurrentBlock('rotate');
		$this->tpl->setVariable("CONTENT_TYPE", $this->plugin->txt("rotate_image". ($a_preview ? "_preview" : "")));
		$this->ctrl->setParameter($this->parent_obj, "id", $a_file);
		$this->ctrl->setParameter($this->parent_obj, "action", "rotateLeft". ($a_preview ? "Preview": ""));
		$this->tpl->setVariable("URL_ROTATE_LEFT", $this->ctrl->getLinkTarget($this->parent_obj, 'mediafiles'));
		$this->ctrl->setParameter($this->parent_obj, "action", "");
		$this->ctrl->setParameter($this->parent_obj, "id", "");
		$this->tpl->setVariable("TEXT_ROTATE_LEFT", $this->plugin->txt("rotate_left" . ($a_preview ? "_preview": "")));
		$this->ctrl->setParameter($this->parent_obj, "id", $a_file);
		$this->ctrl->setParameter($this->parent_obj, "action", "rotateRight". ($a_preview ? "Preview": ""));
		$this->tpl->setVariable("URL_ROTATE_RIGHT", $this->ctrl->getLinkTarget($this->parent_obj, 'mediafiles'));
		$this->ctrl->setParameter($this->parent_obj, "action", "");
		$this->ctrl->setParameter($this->parent_obj, "id", "");
		$this->tpl->setVariable("TEXT_ROTATE_RIGHT", $this->plugin->txt("rotate_right". ($a_preview ? "_preview" : "")));
		$this->tpl->parseCurrentBlock();
	}
	/**
	 * fill row 
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function fillRow($data)
	{
		global $ilUser,$ilAccess;

		$this->plugin->includeClass("class.ilObjMediaGallery.php");
		$this->tpl->setVariable('CB_ID', $data['id']);
		$this->tpl->setVariable("FILENAME", ilUtil::prepareFormOutput($data['filename']));

		if ($data['has_preview'])
		{
			if($data['content_type'] == ilObjMediaGallery::CONTENT_TYPE_IMAGE)
			{
				$this->addRotateFields($data['id']);
			}
			
			
			$this->tpl->setVariable("PREVIEW", ilWACSignedPath::signFile($this->parent_obj->object->getFS()->getFilePath(LOCATION_PREVIEWS,$data['pfilename'])));
			$this->addRotateFields($data['id'], true);
			$this->tpl->setVariable("PREVIEW_CLASS_BORDER", 'xmg_border');


		}
		else if ($data['content_type'] == ilObjMediaGallery::CONTENT_TYPE_IMAGE )
		{
			$this->tpl->setVariable("PREVIEW", ilWACSignedPath::signFile($this->parent_obj->object->getFS()->getFilePath(LOCATION_THUMBS, $data['id'])));
			$this->addRotateFields($data['id']);
			$this->tpl->setVariable("PREVIEW_CLASS_BORDER", 'xmg_no_border');
		}
		else if ($data['content_type'] == ilObjMediaGallery::CONTENT_TYPE_AUDIO)
		{
			$this->tpl->setVariable("PREVIEW", $this->plugin->getDirectory() . '/templates/images/audio.png');
		}
		else if ($data['content_type'] == ilObjMediaGallery::CONTENT_TYPE_VIDEO)
		{
			$this->tpl->setVariable("PREVIEW", $this->plugin->getDirectory() . '/templates/images/video.png');
		}
		else
		{
			$this->tpl->setVariable("PREVIEW", $this->parent_obj->object->getMimeIconPath($data['id']));
		}
		$this->tpl->setVariable("TEXT_PREVIEW", strlen($data['title']) ? ilUtil::prepareFormOutput($data['title']) : ilUtil::prepareFormOutput($data['filename']));
		$this->tpl->setVariable("ID", $data['entry']);
		if ($data['custom'] == 0) 
		{
			$data['custom'] = $this->customsort;
		}
		$this->customsort += 1.0;
		$this->tpl->setVariable("CUSTOM", $this->getTextFieldValue(sprintf("%.1f", $data['custom'])));
		$this->tpl->setVariable("SIZE", ilUtil::prepareFormOutput($this->formatBytes($data['size'])));
		$this->tpl->setVariable("ELEMENT_ID", $this->getTextFieldValue($data['media_id']));
		$this->tpl->setVariable("TOPIC", $this->getTextFieldValue($data['topic']));
		$this->tpl->setVariable("TITLE", $this->getTextFieldValue($data['title']));
		if ($data['pwidth'] > 0)
		{
			$this->tpl->setVariable("WIDTH", $this->getTextFieldValue($data['pwidth']));
			$this->tpl->setVariable("HEIGHT", $this->getTextFieldValue($data['pheight']));
		}
		else
		{
			$this->tpl->setVariable("WIDTH", $this->getTextFieldValue($data['width']));
			$this->tpl->setVariable("HEIGHT", $this->getTextFieldValue($data['height']));
		}
		$this->tpl->setVariable("DESCRIPTION", $this->getTextFieldValue($data['description']));
	}
	
	protected function getTextFieldValue($value)
	{
		$res = '';
		if (strlen($value))
		{
			$res = ' value="' . ilUtil::prepareFormOutput($value) . '"';
		}
		return $res;
	}
	
	protected function formatBytes($bytes, $precision = 2) 
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	/**
	* Init filter
	*/
	function initFilter()
	{
		global $lng, $rbacreview, $ilUser;
		
		include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
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
?>