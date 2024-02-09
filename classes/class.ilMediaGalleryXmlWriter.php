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
 * @author Fabian Wolf <wolf@leifos.de>
 * @version $Id: $
 * @ingroup
 */

class ilMediaGalleryXmlWriter extends ilXmlWriter
{
    private bool $add_header = true;
    private ?ilObjMediaGallery $object = null;

    public function __construct(bool $a_add_header)
    {
        $this->add_header = $a_add_header;
        parent::__construct();
    }

    public function setObject(ilObjMediaGallery $a_object): void
    {
        $this->object = $a_object;
    }

    /**
     * @throws UnexpectedValueException Thrown if obj_id is not of type webr or no obj_id is given
     */
    public function write(): void
    {
        $this->init();
        if($this->add_header) {
            $this->buildHeader();
        }
        $this->object->toXML($this);
    }

    protected function buildHeader(): bool
    {
        $this->xmlSetDtdDef("<!DOCTYPE WebLinks PUBLIC \"-//ILIAS//DTD MediaGalleryAdministration//EN\" \""
            . ILIAS_HTTP_PATH
            . "/Customizing/global/plugins/Services/Repository/RepositoryObject/MediaGallery/xml/ilias_mediagallery.dtd\">");
        $this->xmlSetGenCmt("Media Gallery Plugin Object");
        $this->xmlHeader();
        return true;
    }

    /**
     * @throws UnexpectedValueException Thrown if obj_id is not of type webr
     */
    protected function init(): void
    {
        $this->xmlClear();
        if(!$this->object) {
            throw new UnexpectedValueException('No object given: ');
        }
    }
}
