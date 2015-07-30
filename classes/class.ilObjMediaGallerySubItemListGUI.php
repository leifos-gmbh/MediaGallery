<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Object/classes/class.ilSubItemListGUI.php';

/** 
* Show MediaGallery files
*
 * @author Fabian Wolf <wolf@leifos.com>
 * @version $Id$
 *
 */
class ilObjMediaGallerySubItemListGUI extends ilSubItemListGUI
{
	/**
	 * @var ilMediaGalleryPlugin
	 */
	protected $plugin;

	/**
	 *
	 * @return ilMediaGalleryPlugin
	 */
	protected function getPluginObject()
	{
		if(!$this->plugin)
		{
			$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, "Repository", "robj", "MediaGallery");
		}
		return $this->plugin;
	}


	/**
	 * get html 
	 * @return
	 */
	public function getHTML()
	{
		global $lng, $ilCtrl;

		foreach($this->getSubItemIds(true) as $sub_item)
		{
			if(is_object($this->getHighlighter()) and strlen($this->getHighlighter()->getContent($this->getObjId(),$sub_item)))
			{
				$this->tpl->setCurrentBlock('sea_fragment');
				$this->tpl->setVariable('TXT_FRAGMENT',$this->getHighlighter()->getContent($this->getObjId(),$sub_item));
				$this->tpl->parseCurrentBlock();
			}
			$plugin = $this->getPluginObject();
			$plugin->includeClass("class.ilMediaGalleryFile.php");
			$file = ilMediaGalleryFile::_getInstanceById($sub_item);


			$this->tpl->setCurrentBlock('subitem');
			$plugin->includeClass("class.ilObjMediaGallery.php");

			switch($file->getContentType())
			{
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

			$this->tpl->setVariable('SUBITEM_TYPE',$plugin->txt($type));
			$this->tpl->setVariable('SEPERATOR',':');

			$this->tpl->setVariable('LINK', $this->getItemListGUI()->getCommandLink("gallery"));
			$this->tpl->setVariable('TARGET',$this->getItemListGUI()->getCommandFrame('123'));

			$title = $file->getTitle();

			if(!$title)
			{
				$title = $file->getFilename();
			}

			$this->tpl->setVariable('TITLE',$title);
			$this->tpl->parseCurrentBlock();

			if(count($this->getSubItemIds(true)) > 1)
			{
				$this->parseRelevance($sub_item);
			}

			$this->tpl->parseCurrentBlock();
		}

		$this->showDetailsLink();

		return $this->tpl->get();
	}
}
?>
