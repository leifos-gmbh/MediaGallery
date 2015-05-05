<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * 
 *
 * @author Fabian Wolf <wolf@leifos.de>
 * @version $Id: $
 * @ingroup 
 */

include_once("./Services/Xml/classes/class.ilXmlWriter.php");

class ilMediaGalleryXmlWriter extends ilXmlWriter
{
	private $add_header = true;
	
	private $object = null;

	/**
	 * Constructor
	 */
	public function __construct($a_add_header)
	{
		$this->add_header = $a_add_header;
		parent::__construct();
	}
	
	/**
	 * 
	 * @param ilObjMediaGallery $a_object
	 */
	public function setObject($a_object)
	{
		$this->object = $a_object;
	}
	
	/**
	 * Write XML
	 * @return 
	 * @throws UnexpectedValueException Thrown if obj_id is not of type webr or no obj_id is given 
	 */
	public function write()
	{
		$this->init();
		if($this->add_header)
		{
			$this->buildHeader();
		}
		$this->object->toXML($this);
	}
	
	/**
	 * Build XML header
	 * @return 
	 */
	protected function buildHeader()
	{
		$this->xmlSetDtdDef("<!DOCTYPE WebLinks PUBLIC \"-//ILIAS//DTD MediaGalleryAdministration//EN\" \"".ILIAS_HTTP_PATH."/Customizing/global/plugins/Services/Repository/RepositoryObject/MediaGallery/xml/ilias_mediagallery.dtd\">");
		$this->xmlSetGenCmt("Media Gallery Plugin Object");
		$this->xmlHeader();

		return true;
	}
	
	
	/**
	 * Init xml writer
	 * @return bool
	 * @throws UnexpectedValueException Thrown if obj_id is not of type webr 
	 */
	protected function init()
	{
		$this->xmlClear();
		
		if(!$this->object)
		{
			throw new UnexpectedValueException('No object given: ');
		}
	}
}
?>
