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

/**
 * Class ilMediaGalleryArchives
 *
 * @author Fabian Wolf <wolf@leifos.com>
 * @version $Id$
 *
 */
class ilMediaGalleryArchives
{
    protected static array $objects = [];
    protected int $xmg_id;
    protected array $archives = [];
    protected ilDBInterface $db;
    protected ilLogger $log;

    public static function _getInstanceByXmgId(int $a_xmg_id): ilMediaGalleryArchives
    {
        if(!array_key_exists($a_xmg_id, self::$objects)) {
            self::$objects[$a_xmg_id] = new self($a_xmg_id);
        }
        return self::$objects[$a_xmg_id];
    }

    public function __construct(int $a_xmg_id)
    {
        global $DIC;
        $this->db = $DIC->database();
        $this->log = $DIC->logger()->root();
        $this->setXmgId($a_xmg_id);
    }

    public function setXmgId(int $xmg_id): void
    {
        $this->xmg_id = $xmg_id;
    }

    public function getXmgId(): int
    {
        return $this->xmg_id;
    }

    protected function setArchives(array $archives): void
    {
        $this->archives = $archives;
    }

    public function getArchives(): array
    {
        if(!count($this->archives) > 0) {
            $this->read();
        }
        return $this->archives;
    }

    protected function getFileSystem(): ilFSStorageMediaGallery
    {
        return ilFSStorageMediaGallery::_getInstanceByXmgId($this->getXmgId());
    }

    public function read(): bool
    {
        if(!$this->getXmgId()) {
            return false;
        }
        $arr = array();
        $res = $this->db->query("SELECT * FROM rep_robj_xmg_downloads WHERE xmg_id = "
            . $this->db->quote($this->getXmgId(), "integer"));
        while($row = $this->db->fetchAssoc($res)) {
            $arr[$row["id"]] = array(
                "id" => $row["id"],
                "xmg_id" => $row["xmg_id"],
                "download_flag" => $row["download_flag"],
                "filename" => $row["filename"],
                "created" => @filectime($this->getPath($row["filename"])),
                "size" => @filesize($this->getPath($row["filename"]))
            );
        }
        $this->setArchives($arr);
        return true;
    }

    public function setDownloadFlags(array $a_archives): bool
    {
        $this->db->manipulate("UPDATE rep_robj_xmg_downloads SET download_flag = "
            . $this->db->quote(0, "integer")
            . " WHERE xmg_id = " . $this->db->quote($this->getXmgId(), "integer"));
        if(count($a_archives) > 0) {
            $this->db->manipulate("UPDATE rep_robj_xmg_downloads SET download_flag = "
                . $this->db->quote(1, "integer")
                . " WHERE xmg_id = " . $this->db->quote($this->getXmgId(), "integer")
                . " AND " . $this->db->in("id", $a_archives, false, "integer"));
        }
        foreach($a_archives as $id) {
            $this->archives[$id]["downloag_flag"] = 1;
        }
        return true;
    }

    protected function addArchive(string $filename): bool
    {
        if(!$this->getXmgId() && !$filename) {
            return false;
        }
        $id = $this->db->nextId('rep_robj_xmg_downloads');
        $arr = array(
            "id" => array("integer", $id),
            "xmg_id" => array("integer", $this->getXmgId()),
            "download_flag" => array("integer", 0),
            "filename" => array("text", $filename)
        );
        $this->db->insert("rep_robj_xmg_downloads", $arr);
        $this->archives[$id] = $arr;
        return true;
    }

    /**
     * @throws IOException
     */
    public function deleteArchives(array $a_archive_ids): bool
    {
        $this->read();
        $this->db->manipulate("DELETE FROM rep_robj_xmg_downloads " .
            "WHERE " . $this->db->in("id", $a_archive_ids, false, 'integer') . "");
        foreach($this->getArchives() as $archive) {
            if(in_array((string) $archive['id'], $a_archive_ids)) {
                $this->getFileSystem()->deleteFileByNameAndLocation($archive["filename"], ilObjMediaGallery::LOCATION_DOWNLOADS);
                unset($this->archives[$archive["id"]]);
            }
        }
        return true;
    }

    /**
     * @throws IOException
     */
    public function renameArchive(string $a_old_name, string $a_new_name): bool
    {
        if($a_old_name && !$a_new_name) {
            return false;
        }
        if($a_new_name == $a_old_name) {
            return true;
        }
        $this->db->manipulate("UPDATE rep_robj_xmg_downloads SET filename = "
            . $this->db->quote($a_new_name, "text")
            . " WHERE filename = "
            . $this->db->quote($a_old_name, "text")
            . " AND xmg_id = "
            . $this->db->quote($this->getXmgId(), "integer"));
        rename($this->getPath($a_old_name), $this->getPath($a_new_name));
        $this->resetCache();
        return true;
    }

    /**
     * @throws IOException
     */
    public function createArchive(array $file_ids, string $a_zip_filename): bool
    {
        if(count($file_ids) <= 0) {
            return false;
        }
        $a_zip_filename = ilFileUtils::getASCIIFilename($a_zip_filename);
        $tmp_dir = ilFileUtils::getDataDir() . "/temp/" . "tmp_" . time();
        if(!file_exists(ilFileUtils::getDataDir() . "/temp/")) {
            ilFileUtils::createDirectory(ilFileUtils::getDataDir() . "/temp/");
        }
        ilFileUtils::createDirectory($tmp_dir);
        foreach ($file_ids as $file_id) {
            $file = ilMediaGalleryFile::_getInstanceById((int) $file_id);
            $this->getFileSystem()->copyFile(
                $file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS) . $file->getLocalFileName(),
                $tmp_dir . '/' . $file->getFilename()
            );
        }
        if(!ilFileUtils::zip($tmp_dir, $tmp_dir . '/' . $a_zip_filename, true)) {
            return false;
        }
        rename($tmp_dir . '/' . $a_zip_filename, $this->getFileSystem()->getFilePath(ilObjMediaGallery::LOCATION_DOWNLOADS, $a_zip_filename));
        ilFileUtils::delDir($tmp_dir);
        $this->addArchive($a_zip_filename);
        return true;
    }

    /**
     * @throws IOException
     */
    public function getPath(string $a_filename): string
    {
        return $this->getFileSystem()->getFilePath(ilObjMediaGallery::LOCATION_DOWNLOADS, $a_filename);
    }

    public function resetCache(): void
    {
        $this->archives = array();
    }

    public function getArchiveFilename(int $a_id): string
    {
        if(!$this->archives[$a_id]) {
            $res = $this->db->query("SELECT filename FROM rep_robj_xmg_downloads WHERE id = "
                . $this->db->quote($a_id, "integer"));
            $row = $this->db->fetchAssoc($res);
            return  $row["filename"];
        }
        return $this->archives[$a_id]['filename'];
    }

    /**
     * @throws IOException
     */
    public static function _clone(int $a_source_xmg_id, int $a_dest_xmg_id): void
    {
        $dest = self::_getInstanceByXmgId($a_dest_xmg_id);
        $source = self::_getInstanceByXmgId($a_source_xmg_id);
        foreach($source->getArchives() as $archive) {
            $s_path = $source->getPath($archive['filename']);
            $d_path = $dest->getPath($archive['filename']);
            @copy($s_path, $d_path);
            $dest->addArchive($archive['filename']);
        }
    }

    public static function _archiveExist(int $a_xmg_id, int $a_archive_id): bool
    {
        return in_array($a_archive_id, array_keys(self::_getInstanceByXmgId($a_xmg_id)->getArchives()));
    }
}
