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
* ListGUI implementation for Gallery object plugin. This one
* handles the presentation in container items (categories, courses, ...)
* together with the corresponfing ...Access class.
*
* PLEASE do not create instances of larger classes here. Use the
* ...Access class to get DB data and keep it small.
*
* @author Helmut Schottmüller <ilias@aurealis.de>
*/
class ilObjMediaGalleryListGUI extends ilObjectPluginListGUI
{
    public function initType(): void
    {
        $this->setType("xmg");
    }

    public function getGuiClass(): string
    {
        return "ilObjMediaGalleryGUI";
    }

    public function initCommands(): array
    {
        return [
            [
                "permission" => "read",
                "cmd" => "gallery",
                "default" => true
            ],
            [
                "permission" => "write",
                "cmd" => "mediafiles",
                "txt" => $this->txt("edit"),
                "default" => false
            ],
        ];
    }

    public function getProperties(): array
    {
        return [];
    }
}
