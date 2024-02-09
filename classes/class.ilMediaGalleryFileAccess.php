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

final class ilMediaGalleryFileAccess
{
    private static array $instances = [];
    private ilDBInterface $ilDB;
    private int $gallery_id;
    private array $accessed_files = [];

    public static function getInstanceByGalleryId(int $gallery_id): ilMediaGalleryFileAccess
    {
        if (!isset(self::$instances[$gallery_id])) {
            self::$instances[$gallery_id] = new self($gallery_id);
        }

        return self::$instances[$gallery_id];
    }

    private function __construct(int $gallery_id)
    {
        global $DIC;
        $this->ilDB = $DIC->database();
        $this->setGalleryId($gallery_id);
        $this->read();
    }

    private function read(): void
    {
        $res = $this->ilDB->query('SELECT * FROM rep_robj_xmg_faccess WHERE gallery_obj_id = ' . $this->ilDB->quote($this->getGalleryId(), 'integer'));
        $accessed_files = array();
        if ($res->numRows() > 0) {
            while ($row = $res->fetchAssoc()) {
                $accessed_files[$row['user_id']][] = $row['file_id'];
            }
        }
        $this->setAccessed($accessed_files);
    }

    public function create(int $file_id, int $user_id): bool
    {
        if(!empty($this->getAccessed()[$user_id]) && in_array($file_id, $this->getAccessed()[$user_id])) {
            return false;
        }
        $next_id = $this->ilDB->nextId('rep_robj_xmg_faccess');
        $query = 'INSERT INTO rep_robj_xmg_faccess (access_id, gallery_obj_id, file_id, user_id) VALUES ('
            . $this->ilDB->quote($next_id, 'integer') . ','
            . $this->ilDB->quote($this->getGalleryId(), 'integer') . ','
            . $this->ilDB->quote($file_id, 'integer') . ','
            . $this->ilDB->quote($user_id, 'integer') . ')';
        $this->ilDB->manipulate($query);
        $accessed = $this->getAccessed();
        $accessed[$user_id][] = $file_id;
        $this->setAccessed($accessed);
        return true;
    }

    public function deleteAccessRecordsForGallery(): bool
    {
        $query = 'DELETE FROM rep_robj_xmg_faccess WHERE gallery_obj_id = '
            . $this->ilDB->quote($this->getGalleryId(), 'integer');
        $this->ilDB->manipulate($query);
        $this->setAccessed(array());
        return true;
    }

    public function deleteAccessRecordsForFile(int $file_id): bool
    {
        $query = 'DELETE FROM rep_robj_xmg_faccess WHERE gallery_obj_id = '
            . $this->ilDB->quote($this->getGalleryId(), 'integer')
            . ' AND file_id = ' . $this->ilDB->quote($file_id, 'integer');
        $this->ilDB->manipulate($query);
        $accessed = $this->getAccessed();
        foreach ($accessed as $user_id => $files) {
            foreach ($files as $file) {
                if($file_id == $file) {
                    unset($accessed[$user_id][$file]);
                }
            }
        }
        return true;
    }

    public static function deleteAllAccessRecordsByUserId(int $user_id): bool
    {
        global $DIC;
        $ilDB = $DIC->database();
        $query = 'DELETE FROM rep_robj_xmg_faccess WHERE user_id = ' . $ilDB->quote($user_id, 'integer');
        $ilDB->manipulate($query);
        return true;
    }

    public function getLpCompleted(): array
    {
        $files = ilMediaGalleryFile::_getMediaFilesInGallery($this->getGalleryId(), false, ['lp_relevant' => 1]);
        if(empty($files) || empty($this->getAccessed())) {
            return array();
        }
        $file_ids = array_keys($files);
        $completed_users = array();
        foreach ($this->getAccessed() as $user_id => $accessed_files) {

            if(empty(array_diff($file_ids, $accessed_files))) {
                $completed_users[] = $user_id;
            }
        }
        return $completed_users;
    }

    public function getGalleryId(): int
    {
        return $this->gallery_id;
    }

    private function setGalleryId(int $gallery_id): void
    {
        $this->gallery_id = $gallery_id;
    }

    public function getAccessed(): array
    {
        return $this->accessed_files;
    }

    private function setAccessed(array $accessed_files): void
    {
        $this->accessed_files = $accessed_files;
    }
}
