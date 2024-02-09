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
 * MediaGallery repository object plugin
 * @author Helmut Schottmüller <ilias@aurealis.de>
 * @version $Id$
 */
class ilMediaGalleryPlugin extends ilRepositoryObjectPlugin
{
    protected static ilMediaGalleryPlugin $instance;
    protected const PLUGIN_ID = "xmg";
    protected const PLUGIN_NAME = "MediaGallery";

    public static function _getInstance(): ilMediaGalleryPlugin
    {
        global $DIC;
        if (isset(self::$instance)) {
            return self::$instance;
        }
        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC["component.factory"];
        /** @var ilMediaGalleryPlugin $plugin */
        $plugin = $component_factory->getPlugin(self::PLUGIN_ID);
        return $plugin;
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    public function uninstallCustom(): void
    {
        if ($this->db->tableExists('rep_robj_xmg_filedata')) {
            $this->db->dropTable('rep_robj_xmg_filedata');
        }
        if ($this->db->tableExists('rep_robj_xmg_downloads')) {
            $this->db->dropTable('rep_robj_xmg_downloads');
        }
        if ($this->db->tableExists('rep_robj_xmg_object')) {
            $this->db->dropTable('rep_robj_xmg_object');
        }
        ilFSStorageMediaGallery::_deletePluginData();
        $query = "DELETE FROM il_wac_secure_path " .
            "WHERE path = " . $this->db->quote('ilXmg', 'text');
        $res = $this->db->manipulate($query);
        $setting = new ilSetting("xmg");
        $setting->deleteAll();
    }

    public function allowCopy(): bool
    {
        return true;
    }
}
