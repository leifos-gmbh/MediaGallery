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
use ILIAS\FileUpload\MimeType;

/**
 * Class ilMediaGalleryFile
 * @author Fabian Wolf <wolf@leifos.com>
 * @version $Id$
 *
 */
class ilMediaGalleryFile
{
    protected int $id;
    protected int $gallery_id;
    protected string $media_id;
    protected string $topic;
    protected string $title;
    protected string $description;
    protected int $sorting = 0;
    protected string $filename;
    protected ?string $pfilename = "";
    protected int $lp_relevant = 0;
    protected ilMediaGalleryPlugin $plugin;
    protected static bool $loaded = false;
    protected static array $objects = [];
    protected ilDBInterface $db;
    protected ilLogger $log;

    public function __construct(?int $a_id = null)
    {
        global $DIC;
        $this->db = $DIC->database();
        $this->log = $DIC->logger()->root();
        $this->plugin = ilMediaGalleryPlugin::_getInstance();
        if($a_id) {
            $this->setId($a_id);
            $this->read();
        }
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setGalleryId(int $gallery_id): void
    {
        $this->gallery_id = $gallery_id;
    }

    public function getGalleryId(): int
    {
        return $this->gallery_id;
    }

    public function setMediaId(string $media_id): void
    {
        $this->media_id = $media_id;
    }

    public function getMediaId(): string
    {
        return $this->media_id ?? '';
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function setSorting(int $sorting): void
    {
        $this->sorting = $sorting;
    }

    public function getSorting(): int
    {
        return $this->sorting;
    }

    public function setTopic(string $topic): void
    {
        $this->topic = $topic;
    }

    public function getTopic(): ?string
    {
        return $this->topic ?? '';
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getLocalFileName(): string
    {
        return $this->getId() . "." . $this->getFileInfo()["extension"];
    }

    public function setPfilename(?string $p_filename): void
    {
        if(($this->pfilename && !$p_filename) || ($this->pfilename != $p_filename && $p_filename)) {
            $this->deletePreview();
        }
        $this->pfilename = $p_filename;
    }

    public function getPfilename(): string
    {
        return $this->pfilename ?? '';
    }

    public function getLpRelevant(): int
    {
        return $this->lp_relevant;
    }

    public function setLpRelevant(int $lp_relevant): void
    {
        $this->lp_relevant = $lp_relevant;
    }

    protected function getFileSystem(): ilFSStorageMediaGallery
    {
        return ilFSStorageMediaGallery::_getInstanceByXmgId($this->getGalleryId());
    }

    public function getSize(): int
    {
        return filesize($this->getPath(ilObjMediaGallery::LOCATION_ORIGINALS));
    }

    public function hasPreviewImage(): bool
    {
        if(!$this->getPfilename()) {
            return false;
        }
        $path = $this->getPath(ilObjMediaGallery::LOCATION_PREVIEWS);
        return file_exists($path) && !is_dir($path) ;
    }

    public function read(): bool
    {
        if($this->getId() == null) {
            return false;
        }
        $res = $this->db->query("SELECT * FROM rep_robj_xmg_filedata WHERE id = "
            . $this->db->quote($this->getId(), "integer"));
        if (!$res->numRows() > 0) {
            return false;
        }
        $row = $this->db->fetchAssoc($res);
        $this->setValuesByArray($row);
        return true;
    }

    public function setValuesByArray(array $a_array): void
    {
        $this->setGalleryId((int) $a_array["xmg_id"]);
        $this->setMediaId((string) $a_array["media_id"]);
        $this->setTopic((string) $a_array["topic"]);
        $this->setTitle((string) $a_array["title"]);
        $this->setDescription((string) $a_array["description"]);
        $this->setFilename((string) $a_array["filename"]);
        $this->setSorting((int) $a_array["custom"]);
        $this->pfilename = (string) $a_array["pfilename"];
        $this->setLpRelevant((int) $a_array["lp_relevant"]);
    }

    public function update(): bool
    {
        if(is_null($this->getId())) {
            return false;
        }
        $this->db->update(
            "rep_robj_xmg_filedata",
            $this->getValueArray(true),
            ["id" => ["integer", $this->getId()]]
        );
        return true;
    }

    public function getValueArray(bool $a_prepare_for_db = false): array
    {
        if($a_prepare_for_db) {
            return [
                "id" => ["integer", $this->getId()],
                "xmg_id" => ["integer", $this->getGalleryId()],
                "media_id" => ["text", $this->getMediaId()],
                "topic" => ["text", $this->getTopic()],
                "title" => ["text", $this->getTitle()],
                "description" => ["text", $this->getDescription()],
                "filename" => ["text", $this->getFilename()],
                "custom" => ["integer",$this->getSorting()],
                "pfilename" => ['text', $this->getPfilename()],
                "lp_relevant" => ["integer", $this->getLpRelevant()]
            ];
        } else {
            return [
                "id" => $this->getId(),
                "xmg_id" => $this->getGalleryId(),
                "media_id" => $this->getMediaId(),
                "topic" => $this->getTopic(),
                "title" => $this->getTitle(),
                "description" => $this->getDescription(),
                "filename" => $this->getFilename(),
                "custom" => $this->getSorting(),
                "pfilename" => $this->getPfilename(),
                "lp_relevant" => $this->getLpRelevant()
            ];
        }
    }

    public function create(): bool
    {
        if(is_null($this->getGalleryId())) {
            return false;
        }
        $id = $this->db->nextId('rep_robj_xmg_filedata');
        $this->setId($id);
        $this->db->insert("rep_robj_xmg_filedata", $this->getValueArray(true));
        self::$objects[$id] = $this;
        return true;
    }

    /**
     * @throws IOException
     */
    public function delete(): bool
    {
        if(is_null($this->getId())) {
            return false;
        }
        if($this->hasPreviewImage()) {
            $this->deletePreview();
        }
        if (
            !$this->getFileSystem()->deleteFileByName($this->getFilename()) &&
            !$this->getFileSystem()->deleteFileByName((string) $this->getId())
        ) {
            return false;
        }
        $query = "DELETE FROM rep_robj_xmg_filedata " .
            "WHERE id = " . $this->db->quote($this->getId(), 'integer');
        $this->db->manipulate($query);
        $access_records = ilMediaGalleryFileAccess::getInstanceByGalleryId($this->getGalleryId());
        $access_records->deleteAccessRecordsForFile($this->getId());
        unset(self::$objects[$this->getId()]);
        return true;
    }

    public function getMimeType(int $a_location  = ilObjMediaGallery::LOCATION_ORIGINALS): string
    {
        return MimeType::lookupMimeType($this->getPath($a_location));
    }

    public function getFileInfo(int $a_location = ilObjMediaGallery::LOCATION_ORIGINALS): array
    {
        return pathinfo($this->getPath($a_location) . $this->getFilename());
    }

    public function getPath(int $a_location): string
    {
        if($a_location == ilObjMediaGallery::LOCATION_PREVIEWS) {
            return $this->getFileSystem()->getFilePath($a_location, $this->getPfilename());
        }
        return $this->getFileSystem()->getFilePath($a_location, $this->getFilename());
    }

    /**
     * @throws IOException
     */
    protected function deletePreview(): bool
    {
        if(!self::$loaded) {
            self::_getMediaFilesInGallery($this->getGalleryId(), true);
            self::$loaded = true;
        }
        $counter = 0;
        foreach(self::$objects as $id => $object) {
            if($this->getGalleryId() == $object->getGalleryId() && $object->getPfilename() == $this->getPfilename()) {
                $counter++;
            }
        }
        if($counter == 1) {
            $this->getFileSystem()->deleteFileByNameAndLocation($this->getPfilename(), ilObjMediaGallery::LOCATION_PREVIEWS);
        }
        return true;
    }


    protected function createImagePreviewFromTiff(
        int $location,
        int $size
    ) {
        $file_name = $this->getLocalFileName();
        ilShellUtil::convertImage(
            $this->getPath(ilObjMediaGallery::LOCATION_ORIGINALS) . $file_name,
            $this->getPath($location) . $file_name,
            "PNG",
            $size . 'x' . $size
        );
    }

    protected function createImagePreview(
        int $location,
        int $size
    ) {
        $file_name = $this->getLocalFileName();
        ilShellUtil::resizeImage(
            $this->getPath(ilObjMediaGallery::LOCATION_ORIGINALS) . $file_name,
            $this->getPath($location) . $file_name,
            $size,
            $size,
            true
        );
    }

    protected function createImagePreviews(): void
    {
        if ($this->getContentType() !== ilObjMediaGallery::CONTENT_TYPE_IMAGE) {
            return;
        }
        $info = $this->getFileInfo();
        $image_data = [
            [
                "location" => ilObjMediaGallery::LOCATION_THUMBS,
                "size" => ilObjMediaGallery::IMAGE_SIZE_THUMBS
            ],
            [
                "location" => ilObjMediaGallery::LOCATION_SIZE_SMALL,
                "size" => ilObjMediaGallery::IMAGE_SIZE_SMALL
            ],
            [
                "location" => ilObjMediaGallery::LOCATION_SIZE_MEDIUM,
                "size" => ilObjMediaGallery::IMAGE_SIZE_MEDIUM
            ],
            [
                "location" => ilObjMediaGallery::LOCATION_SIZE_LARGE,
                "size" => ilObjMediaGallery::IMAGE_SIZE_LARGE
            ],
        ];
        foreach ($image_data as $data) {
            if(in_array($info["extension"], ["tif", "tiff"])) {
                $this->createImagePreviewFromTiff((int) $data["location"], (int) $data["size"]);
                continue;
            }
            $this->createImagePreview((int) $data["location"], (int) $data["size"]);
        }
    }

    public function getContentType(int $a_location = ilObjMediaGallery::LOCATION_ORIGINALS): int
    {
        return self::_contentType(
            $this->getMimeType($a_location),
            is_null($this->getFileInfo($a_location)["extension"]) ? "" : $this->getFileInfo($a_location)["extension"]
        );
    }

    public function uploadFile(string $file, string $filename): bool
    {
        // rename mov files to mp4. gives better compatibility in most browsers
        if (self::_hasExtension($file, 'mov')) {
            $new_filename = preg_replace('/(\.mov)/is', '.mp4', $filename);
            if (@rename($file, str_replace($filename, $new_filename, $file))) {
                $file = str_replace($filename, $new_filename, $file);
            }
        }
        $valid = ilObjMediaGallery::_getConfigurationValue('ext_aud') . ',' .
            ilObjMediaGallery::_getConfigurationValue('ext_vid') . ',' .
            ilObjMediaGallery::_getConfigurationValue('ext_img') . ',' .
            ilObjMediaGallery::_getConfigurationValue('ext_oth');
        if(!self::_hasExtension($file, $valid)) {
            $this->delete();
            unlink($file);
            return false;
        }
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        rename($file, $this->getFileSystem()->getPath(ilObjMediaGallery::LOCATION_ORIGINALS) . $this->getId() . '.' . $ext);
        $this->getFileSystem()->resetCache();
        if($this->getContentType() == ilObjMediaGallery::CONTENT_TYPE_IMAGE) {
            $this->log->error("Creating image preview.");
            $this->createImagePreviews();
        }
        return true;
    }

    protected function performRotate(
        string $file_name,
        int $a_location,
        int $a_direction
    ): void {
        $cmd = "-rotate " . (($a_direction) ? "-90" : "90") . " ";
        $source = ilShellUtil::escapeShellCmd($this->getPath($a_location) . $file_name);
        $target = ilShellUtil::escapeShellCmd($this->getPath($a_location) . $file_name);
        $convert_cmd = $this->quote($source) . " " . $cmd . " " . $this->quote($target);
        ilShellUtil::execConvert($convert_cmd);
    }

    protected function quote(string $value): string
    {
        return "'" . $value . "'";
    }
    public function rotate(int $direction): bool
    {
        if ($this->getContentType() == ilObjMediaGallery::CONTENT_TYPE_IMAGE) {
            $file_name = $this->getLocalFileName();
            $this->performRotate($file_name, ilObjMediaGallery::LOCATION_THUMBS, $direction);
            $this->performRotate($file_name, ilObjMediaGallery::LOCATION_SIZE_SMALL, $direction);
            $this->performRotate($file_name, ilObjMediaGallery::LOCATION_SIZE_MEDIUM, $direction);
            $this->performRotate($file_name, ilObjMediaGallery::LOCATION_SIZE_LARGE, $direction);
            $this->performRotate($file_name, ilObjMediaGallery::LOCATION_ORIGINALS, $direction);
            return true;
        }
        return false;
    }

    public function rotatePreview(int $direction): bool
    {
        if($this->hasPreviewImage()) {
            $this->performRotate('', ilObjMediaGallery::LOCATION_PREVIEWS, $direction);
            return true;
        }
        return false;
    }

    public static function _getMediaFilesInGallery(
        int $a_xmg_id,
        bool $a_return_objects = false,
        array $a_filter = []
    ): array {
        global $DIC;
        $ilDB = $DIC->database();
        if(!$a_xmg_id) {
            return [];
        }
        $ret = [];
        $a_filter['xmg_id'] = $a_xmg_id;
        $res = $ilDB->query("SELECT * FROM rep_robj_xmg_filedata " . self::_buildWhereStatement($a_filter));
        while($row = $ilDB->fetchAssoc($res)) {
            $arr = [
                "id" => $row["id"],
                "xmg_id" => $row["xmg_id"],
                "media_id" => $row["media_id"],
                "topic" => $row["topic"],
                "title" => $row["title"],
                "description" => $row["description"],
                "filename" => $row["filename"],
                "custom" => $row["custom"],
                "pfilename" => $row['pfilename'],
                "lp_relevant" => $row["lp_relevant"]
            ];
            if(!self::$objects[(int) $row["id"]]) {
                $obj =  new self();
                $obj->setId((int) $row["id"]);
                $obj->setValuesByArray($arr);
                self::$objects[(int) $row["id"]] = $obj;
            } else {
                $obj = self::$objects[$row["id"]];
            }
            if(isset($a_filter['type']) && $a_filter['type'] && $a_filter['type'] != $obj->getContentType()) {
                continue;
            }
            if($a_return_objects) {
                $ret[(int) $row["id"]] = self::$objects[(int) $row["id"]];
            } else {
                $ret[(int) $row["id"]] = $arr;
                $ret[(int) $row["id"]]['has_preview'] = $obj->hasPreviewImage();
                $ret[(int) $row["id"]]['content_type'] =  $obj->getContentType();
                $ret[(int) $row["id"]]['size'] =  $obj->getSize();
            }
        }
        return $ret;
    }

    public static function _createMissingPreviews(int $a_id): void
    {
        $files = ilMediaGalleryFile::_getMediaFilesInGallery($a_id, true);
        foreach ($files as $data) {
            if (!@file_exists($data->getPath(ilObjMediaGallery::LOCATION_THUMBS))) {
                $data->createImagePreviews();
            }
        }
    }

    public static function _contentType(string $a_mime, string $a_ext = ""): int
    {
        if (strpos($a_mime, 'image') !== false) {
            return ilObjMediaGallery::CONTENT_TYPE_IMAGE;
        } elseif (strpos($a_mime, 'audio') !== false) {
            return ilObjMediaGallery::CONTENT_TYPE_AUDIO;
        } elseif (strpos($a_mime, 'video') !== false) {
            return ilObjMediaGallery::CONTENT_TYPE_VIDEO;
        } else {
            $a_ext = str_replace('.', '', $a_ext);
            if (in_array($a_ext, self::_extConfigToArray('ext_img'))) {
                return ilObjMediaGallery::CONTENT_TYPE_IMAGE;
            }
            if (in_array($a_ext, self::_extConfigToArray('ext_vid'))) {
                return ilObjMediaGallery::CONTENT_TYPE_VIDEO;
            }
            if (in_array($a_ext, self::_extConfigToArray('ext_aud'))) {
                return ilObjMediaGallery::CONTENT_TYPE_AUDIO;
            }
            return ilObjMediaGallery::CONTENT_TYPE_UNKNOWN;
        }
    }

    protected static function _extConfigToArray(string $a_key): array
    {
        if(!str_contains($a_key, 'ext_')) {
            return [];
        }
        $array = explode(',', ilObjMediaGallery::_getConfigurationValue($a_key));
        $array = array_map('strtolower', $array);
        return array_map('trim', $array);
    }

    public static function _hasExtension(string $file, string $extensions): bool
    {
        $file_parts = pathinfo($file);
        $arrExtensions = explode(",", $extensions);
        foreach ($arrExtensions as $ext) {
            if (strlen(trim($ext))) {
                if (strcmp(strtolower($file_parts['extension']), strtolower(trim($ext))) == 0) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function _getInstanceById(int $a_id): ilMediaGalleryFile
    {
        if(!self::$objects[$a_id]) {
            self::$objects[$a_id] = new self($a_id);
        }
        return self::$objects[$a_id];
    }

    public static function _clone(int $a_source_xmg_id, int $a_dest_xmg_id): void
    {
        $files = self::_getMediaFilesInGallery($a_source_xmg_id, true);
        $fss = ilFSStorageMediaGallery::_getInstanceByXmgId($a_source_xmg_id);
        $fsd = ilFSStorageMediaGallery::_getInstanceByXmgId($a_dest_xmg_id);
        @copy($fss->getPath(ilObjMediaGallery::LOCATION_PREVIEWS), $fsd->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));
        /**
         * @var $s_file self
         */
        foreach($files as $s_file) {
            $d_file = new ilMediaGalleryFile();
            $d_file->setValuesByArray($s_file->getValueArray());
            $d_file->setGalleryId($a_dest_xmg_id);
            $d_file->create();
            $ext = pathinfo($s_file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS), PATHINFO_EXTENSION);
            @copy(
                $s_file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS),
                $d_file->getPath(ilObjMediaGallery::LOCATION_ORIGINALS) . $d_file->getId() . '.' . $ext
            );
            if($s_file->getContentType() == ilObjMediaGallery::CONTENT_TYPE_IMAGE) {
                $ext = pathinfo($s_file->getPath(ilObjMediaGallery::LOCATION_SIZE_LARGE), PATHINFO_EXTENSION);
                copy(
                    $s_file->getPath(ilObjMediaGallery::LOCATION_SIZE_LARGE),
                    $d_file->getPath(ilObjMediaGallery::LOCATION_SIZE_LARGE) . $d_file->getId() . '.' . $ext
                );
                copy(
                    $s_file->getPath(ilObjMediaGallery::LOCATION_SIZE_MEDIUM),
                    $d_file->getPath(ilObjMediaGallery::LOCATION_SIZE_MEDIUM) . $d_file->getId() . '.' . $ext
                );
                copy(
                    $s_file->getPath(ilObjMediaGallery::LOCATION_SIZE_SMALL),
                    $d_file->getPath(ilObjMediaGallery::LOCATION_SIZE_SMALL) . $d_file->getId() . '.' . $ext
                );
                copy(
                    $s_file->getPath(ilObjMediaGallery::LOCATION_THUMBS),
                    $d_file->getPath(ilObjMediaGallery::LOCATION_THUMBS) . $d_file->getId() . '.' . $ext
                );
            }
        }
    }

    public static function _getNextValidFilename(
        int $a_xmg_id,
        string $a_filename,
        ?array $a_objects = null,
        int $a_counter = 0
    ): string {
        if($a_objects == null) {
            $objects = self::_getMediaFilesInGallery($a_xmg_id);
        } else {
            $objects = $a_objects;
        }
        if($a_counter > 0) {
            $base_name = substr($a_filename, 0, strripos($a_filename, '.'));
            $ext = substr($a_filename, strripos($a_filename, '.'));
            $filename = $base_name . '_' . $a_counter . $ext;
        } else {
            $filename = $a_filename;
        }
        foreach($objects as $object) {
            if($object['filename'] == $filename) {
                return self::_getNextValidFilename($a_xmg_id, $a_filename, $objects, $a_counter + 1);
            }
        }
        return $filename;
    }

    protected static function _buildWhereStatement(array $a_filter): string
    {
        global $DIC;
        $ilDB = $DIC->database();
        $like_filters = ["media_id", "topic", "title", "description", "filename", "pfilename"];
        $where = [];
        foreach($like_filters as $filter) {
            if(isset($a_filter[$filter])) {
                $where[] = $ilDB->like($filter, 'text', '%' . $a_filter[$filter] . '%', false);
            }
        }
        if(isset($a_filter['id'])) {
            $where[] = 'id = ' . $ilDB->quote($a_filter['id'], 'integer');
        }
        if(isset($a_filter['xmg_id'])) {
            $where[] = 'xmg_id = ' . $ilDB->quote($a_filter['xmg_id'], 'integer');
        }
        if(isset($a_filter['lp_relevant'])) {
            $where[] = 'lp_relevant = ' . $ilDB->quote($a_filter['lp_relevant'], 'integer');
        }
        if(count($where)) {
            return 'WHERE ' . implode(' AND ', $where);
        } else {
            return "";
        }
    }
}
