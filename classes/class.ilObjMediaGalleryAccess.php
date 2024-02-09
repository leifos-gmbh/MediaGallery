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
* Access/Condition checking for MediaGallery object
* Please do not create instances of large application classes (like ilObjMediaGallery)
* Write small methods within this class to determin the status.
* @author Helmut Schottmüller <ilias@aurealis.de>
* @version $Id$
*/
class ilObjMediaGalleryAccess extends ilObjectPluginAccess implements ilWACCheckingClass
{
    public function _checkAccess(string $cmd, string $permission, int $ref_id, int $obj_id, ?int $user_id = null): bool
    {
        return true;
    }

    public function canBeDelivered(ilWACPath $ilWACPath): bool
    {
        return true;
        // TODO: Check WAC functionality.
        /*
        ilLoggerFactory::getLogger('xmg')->debug('Check access for path: ' . $ilWACPath->getPath());
        preg_match("/\\/xmg_([\\d]*)\\//uism", $ilWACPath->getPath(), $results);

        ilLoggerFactory::getLogger('xmg')->dump($results);

        foreach (ilObject2::_getAllReferences($results[1]) as $ref_id) {
            if ($ilAccess->checkAccess('read', '', $ref_id)) {
                ilLoggerFactory::getLogger('xmg')->debug('Check access: granted');
                return true;
            }
        }

        ilLoggerFactory::getLogger('xmg')->debug('Check access: failed');

        return false;
        */
    }
}
