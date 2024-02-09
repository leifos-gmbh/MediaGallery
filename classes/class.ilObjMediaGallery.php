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

use \ILIAS\FileUpload\MimeType;

/**
* Application class for gallery repository object.
* @author Helmut Schottmüller <ilias@aurealis.de>
* $Id$
*/
class ilObjMediaGallery extends ilObjectPlugin implements ilLPStatusPluginInterface
{
    public const LOCATION_ROOT = 0;
    public const LOCATION_ORIGINALS = 1;
    public const LOCATION_THUMBS = 2;
    public const LOCATION_SIZE_SMALL = 3;
    public const LOCATION_SIZE_MEDIUM = 4;
    public const LOCATION_SIZE_LARGE = 5;
    public const LOCATION_DOWNLOADS = 6;
    public const LOCATION_PREVIEWS = 7;
    public const LP_DEACTIVATED = 0;
    public const LP_ACTIVATED = 1;
    public const CONTENT_TYPE_VIDEO = 1;
    public const CONTENT_TYPE_IMAGE = 2;
    public const CONTENT_TYPE_AUDIO = 3;
    public const CONTENT_TYPE_UNKNOWN = 4;
    public const IMAGE_SIZE_THUMBS = 150;
    public const IMAGE_SIZE_SMALL = 800;
    public const IMAGE_SIZE_MEDIUM = 1280;
    public const IMAGE_SIZE_LARGE = 2048;
    public const DIRECT_UPLOAD = "directupload";
    protected int $size_thumbs = 150;
    protected int $size_small = 800;
    protected int $size_medium = 1280;
    protected int $size_large = 2048;
    protected string $sort_order = 'filename';
    protected int $showTitle = 0;
    protected int $download = 0;
    protected string $theme = '';
    protected int $learning_progress = 0;

    public function __construct(int $a_ref_id = 0)
    {
        parent::__construct($a_ref_id);
        $this->plugin = ilMediaGalleryPlugin::_getInstance();
    }

    final public function initType(): void
    {
        $this->setType("xmg");
    }

    public function doCreate(bool $clone_mode = false): void
    {
        ilFSStorageMediaGallery::_getInstanceByXmgId($this->getId())->create();
    }

    public function doRead(): void
    {
        $result = $this->db->queryF(
            "SELECT * FROM rep_robj_xmg_object WHERE obj_fi = %s",
            ['integer'],
            [$this->getId()]
        );
        if ($result->numRows() == 1) {
            $row = $this->db->fetchAssoc($result);
            $this->setShowTitle((int) $row['show_title']);
            $this->setDownload((int) $row['download']);
            $this->setTheme((string) $row['theme']);
            $this->setSortOrder((string) $row['sortorder']);
            $this->setLearningProgressEnabled((int) $row['learning_progress']);
        } else {
            $this->setShowTitle(0);
            $this->setDownload(0);
            $this->setTheme(ilObjMediaGallery::_getConfigurationValue('theme'));
            $this->setSortOrder('filename');
            $this->setLearningProgressEnabled(0);
        }
    }

    public function doUpdate(): void
    {
        $this->db->manipulateF(
            "DELETE FROM rep_robj_xmg_object WHERE obj_fi = %s",
            ['integer'],
            [$this->getId()]
        );
        $this->db->manipulateF(
            "INSERT INTO rep_robj_xmg_object (obj_fi, sortorder, show_title, download, theme, learning_progress) VALUES (%s, %s, %s, %s, %s, %s)",
            ['integer','text','integer', 'integer', 'text', 'integer'],
            [$this->getId(), $this->getSortOrder(), $this->getShowTitle(), $this->getDownload(), $this->getTheme(), $this->getLearningProgressEnabled()]
        );
    }

    public function doDelete(): void
    {
        ilFileUtils::delDir($this->getFS()->getPath(self::LOCATION_ROOT));
        $this->db->manipulateF(
            "DELETE FROM rep_robj_xmg_filedata WHERE xmg_id = %s",
            ['integer'],
            [$this->getId()]
        );
        $this->db->manipulateF(
            "DELETE FROM rep_robj_xmg_downloads WHERE xmg_id = %s",
            ['integer'],
            [$this->getId()]
        );
        $this->db->manipulateF(
            "DELETE FROM rep_robj_xmg_object WHERE obj_fi = %s",
            ['integer'],
            [$this->getId()]
        );
        $access_records = ilMediaGalleryFileAccess::getInstanceByGalleryId($this->getId());
        $access_records->deleteAccessRecordsForGallery();
    }

    protected function doCloneObject(ilObject2 $new_obj, int $a_target_id, ?int $a_copy_id = null): void
    {
        $new_obj->setSortOrder($this->getSortOrder());
        $new_obj->setShowTitle($this->getShowTitle());
        $new_obj->setDownload($this->getDownload());
        $new_obj->setTheme($this->getTheme());
        $new_obj->doUpdate();
        $fss = ilFSStorageMediaGallery::_getInstanceByXmgId($a_copy_id);
        $fss->create();
        ilMediaGalleryFile::_clone($this->getId(), $new_obj->getId());
        ilMediaGalleryArchives::_clone($this->getId(), $new_obj->getId());
    }

    public function getSortOrder(): string
    {
        return $this->sort_order;
    }

    public function getShowTitle(): int
    {
        return ($this->showTitle) ? 1 : 0;
    }

    public function getDownload(): int
    {
        return ($this->download) ? 1 : 0;
    }

    public function getTheme(): string
    {
        if (strlen($this->theme) == 0) {
            return ilObjMediaGallery::_getConfigurationValue('theme');
        } else {
            return $this->theme;
        }
    }

    public function setSortOrder(string $sort_order): void
    {
        $this->sort_order = $sort_order;
    }

    public function setShowTitle(int $show_title): void
    {
        $this->showTitle = $show_title;
    }

    public function setDownload(int $download): void
    {
        $this->download = $download;
    }

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    public function setSizeLarge(int $size_large): void
    {
        $this->size_large = $size_large;
    }

    public function getSizeLarge(): int
    {
        return $this->size_large;
    }

    public function setSizeMedium(int $size_medium): void
    {
        $this->size_medium = $size_medium;
    }

    public function getSizeMedium(): int
    {
        return $this->size_medium;
    }

    public function setSizeThumbs(int $size_thumbs): void
    {
        $this->size_thumbs = $size_thumbs;
    }

    public function getSizeThumbs(): int
    {
        return $this->size_thumbs;
    }

    public function setSizeSmall(int $size_small): void
    {
        $this->size_small = $size_small;
    }

    public function getSizeSmall(): int
    {
        return $this->size_small;
    }

    public function getLearningProgressEnabled(): int
    {
        return $this->learning_progress;
    }

    public function setLearningProgressEnabled(int $learning_progress): void
    {
        $this->learning_progress = $learning_progress;
    }

    public function getFS(): ilFSStorageMediaGallery
    {
        return ilFSStorageMediaGallery::_getInstanceByXmgId($this->getId());
    }

    public static function _getConfigurationValue(string $key, string $default = ""): string
    {
        $setting = new ilSetting("xmg");
        if (
            strcmp($key, 'theme') == 0 &&
            !is_null($setting->get($key)) &&
            strlen($setting->get($key)) == 0
        ) {
            return "dark_rounded";
        } else {
            return $setting->get($key, $default);
        }
    }

    public static function _setConfiguration(string $key, string $value): void
    {
        $setting = new ilSetting("xmg");
        $setting->set($key, $value);
    }

    protected function hasExtension(string $file, string $extensions): bool
    {
        $file_parts = pathinfo($file);
        $arrExtensions = explode(",", $extensions);
        $extMap = MimeType::getExt2MimeMap();
        foreach ($arrExtensions as $ext) {
            if (strlen(trim($ext))) {

                if ($extMap["." . $ext] == MimeType::getMimeType($file) ||
                    strcmp(strtolower($file_parts['extension']), strtolower(trim($ext))) == 0) {
                    return true;
                }

            }
        }
        return false;
    }

    private static function getDirsInDir(string $a_dir): array
    {
        $current_dir = opendir($a_dir);
        $files = [];
        while($entry = readdir($current_dir)) {
            if (
                $entry != "." &&
                $entry != ".." &&
                !@is_file($a_dir . "/" . $entry) &&
                strpos($entry, ".") !== 0
            ) {
                $files[] = $entry;
            }
        }
        ksort($files);
        return $files;
    }

    public function getGalleryThemes(): array
    {
        return self::_getGalleryThemes();
    }

    public static function _getGalleryThemes(): array
    {
        $data = self::getDirsInDir(ilMediaGalleryPlugin::_getInstance()->getDirectory() . '/js/prettyphoto_3.1.5/images/prettyPhoto');
        if (count($data) == 0) {
            $data[] = ilObjMediaGallery::_getConfigurationValue('theme');
        }
        $themes = [];
        foreach ($data as $theme) {
            $themes[$theme] = $theme;
        }
        return $themes;
    }

    public function scaleDimensions($width, $height, $scale): array
    {
        if ($width == 0 || $height == 0 || $scale == 0) {
            return ["width" => $width, "height" => $height];
        }
        $i_width = $width;
        $i_height = $height;
        $f = ($i_width * 1.0) / ($i_height * 1.0);
        if ($f < 1) { // higher
            $i_height = $scale;
            $i_width = round(($scale * 1.0) * $f);
        } else {
            $i_width = $scale;
            $i_height = round(($scale * 1.0) / $f);
        }
        return ["width" => $i_width, "height" => $i_height];
    }

    public function getMimeIconPath(int $a_id): string
    {
        $mime = ilMediaGalleryFile::_getInstanceById($a_id)->getMimeType();
        $res = explode(";", $mime);
        if ($res !== false) {
            $mime = $res[0];
        }
        $ext = ilMediaGalleryFile::_getInstanceById($a_id)->getFileInfo()['extension'];
        $ext = is_null($ext) ? '' : $ext;
        switch (strtolower($ext)) {
            case 'xls':
            case 'xlsx':
                $mime = "application-vnd.ms-excel";
                break;
            case 'doc':
            case 'docx':
                $mime = "application-msword";
                break;
            case 'ppt':
            case 'pptx':
                $mime = "application-vnd.ms-powerpoint";
                break;
        }
        $path = $this->plugin->getDirectory() . "/templates/images/mimetypes/" . str_replace("/", "-", $mime) . ".png";
        if (file_exists($path)) {
            return $path;
        } else {
            return $this->plugin->getDirectory() . '/templates/images/unknown.png';
        }
    }

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function toXml(ilXmlWriter $xml_writer): void
    {
        $media_gallery_attr = [
                "sortorder" => $this->getSortOrder(),
                "show_title" => $this->getShowTitle(),
                "download" => $this->getDownload(),
                "theme" => $this->getTheme()
        ];
        $xml_writer->xmlStartTag("mediagallery", $media_gallery_attr);
        $xml_writer->xmlElement("title", [], $this->getTitle());
        $xml_writer->xmlElement("description", [], $this->getDescription());
        foreach(ilMediaGalleryFile::_getMediaFilesInGallery($this->getId()) as $data) {
            $filedata_attr = [
                "filename" => $data["filename"],
                "media_id" => $data["media_id"],
                "topic" => $data["topic"],
                "custom" => $data["custom"],
                "width" => $data["width"],
                "height" => $data["height"]
            ];
            $xml_writer->xmlStartTag("filedata", $filedata_attr);
            $xml_writer->xmlElement("file_title", [], $data["title"]);
            $xml_writer->xmlElement("file_description", [], $data["description"]);
            // file exists abfrage
            $content = @gzcompress(@file_get_contents($this->getFS()->getFilePath(self::LOCATION_ORIGINALS, $data['id'])), 9);
            $content = base64_encode($content);
            $xml_writer->xmlElement("content", ["mode" => "ZIP"], $content);
            // TODO: replace getFileSystem
            $prev_path = $this->plugin->getFileSystem()->getFilePath(self::LOCATION_PREVIEWS, $data["id"]);
            if(file_exists($prev_path)) {
                $preview = @gzcompress(@file_get_contents($prev_path), 9);
                $preview = base64_encode($preview);
                $preview_attr = [
                    "pfilename" => $data["filename"],
                    "mode" => "ZIP"
                ];
                $xml_writer->xmlElement("preview", $preview_attr, $preview);
            }
            $xml_writer->xmlEndTag("filedata");
        }
        $xml_writer->xmlEndTag("mediagallery");
    }

    public function uploadPreview(): bool
    {
        $ext = substr($_FILES['filename']["name"], strrpos($_FILES['filename']["name"], '.'));
        if(ilMediaGalleryFile::_contentType($_FILES["filename"]["type"], $ext) != self::CONTENT_TYPE_IMAGE) {
            return false;
        }
        $preview_path = $this->getFS()->getFilePath(ilObjMediaGallery::LOCATION_PREVIEWS, $_FILES['filename']["name"]);
        @move_uploaded_file($_FILES['filename']["tmp_name"], $preview_path);
        return true;
    }

    public function getLPCompleted(): array
    {
        if($this->getLearningProgressEnabled() == 0) {
            return [];
        }
        $file_access = ilMediaGalleryFileAccess::getInstanceByGalleryId($this->getId());
        return $file_access->getLpCompleted();
    }

    public function getLPNotAttempted(): array
    {
        return [];
    }

    public function getLPFailed(): array
    {
        return [];
    }

    public function getLPInProgress(): array
    {
        if($this->getLearningProgressEnabled() == 0) {
            return [];
        }
        return $this->getUsersAttempted();
    }

    public function getLPStatusForUser(int $a_user_id): int
    {
        if(in_array($a_user_id, $this->getUsersAttempted())) {
            $users_completed = ilLPStatusWrapper::_getCompleted($this->getId());
            if(in_array($a_user_id, $users_completed)) {
                return ilLPStatus::LP_STATUS_COMPLETED_NUM;
            }
            return ilLPStatus::LP_STATUS_IN_PROGRESS_NUM;
        }
        return ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM;
    }

    protected function getUsersAttempted(): array
    {
        $events = ilChangeEvent::_lookupReadEvents($this->getId());
        $users = [];
        foreach ($events as $event) {
            $users[] = $event['usr_id'];
        }
        return $users;
    }
}
