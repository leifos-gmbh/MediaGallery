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
 * Class ilFSStorageMediaGallery
 *
 * @author Fabian Wolf <wolf@leifos.com>
 * @version $Id$
 *
 */
class ilFSStorageMediaGallery extends ilFileSystemAbstractionStorage
{
    protected static array $objects = [];
    protected array $files_cache;
    protected array $mime_cache;
    protected ilLogger $log;

    public static function _getInstanceByXmgId(int $a_xmg_id): ilFSStorageMediaGallery
    {
        if(!self::$objects[$a_xmg_id]) {
            self::$objects[$a_xmg_id] = new self($a_xmg_id);
        }
        return self::$objects[$a_xmg_id];
    }

    /**
     * deletes folder ./data/[client]/sec/ilXmg recursively
     */
    public static function _deletePluginData(): void
    {
        $fs = new self();
        $path = ilFileUtils::getWebspaceDir();
        $path = ilFileUtils::removeTrailingPathSeparators($path);
        $path .= '/' . "sec";
        $path = ilFileUtils::removeTrailingPathSeparators($path);
        $path .= '/';
        // Append path prefix
        $path .= ($fs->getPathPrefix() . '/');
        $fs->deleteDirectory($path);
    }

    public function __construct(int $a_container_id = 0, bool $a_path_conversion = false)
    {
        global $DIC;
        parent::__construct(
            ilFileSystemAbstractionStorage::STORAGE_SECURED,
            $a_path_conversion,
            $a_container_id
        );
        $this->log = $DIC->logger()->root();
    }

    public function getPathPrefix(): string
    {
        return 'ilXmg';
    }

    public function getPathPostfix(): string
    {
        return 'xmg';
    }

    /**
     * @throws IOException
     */
    public function getFilePath(int $a_location, string $a_file_id = ''): string
    {
        $path = $this->getPath($a_location);
        switch ($a_location) {
            case ilObjMediaGallery::LOCATION_THUMBS:
            case ilObjMediaGallery::LOCATION_SIZE_SMALL:
            case ilObjMediaGallery::LOCATION_SIZE_MEDIUM:
            case ilObjMediaGallery::LOCATION_SIZE_LARGE:
                if($this->getMimeType($a_file_id) == 'image/tiff') {
                    $path .= $a_file_id . ".png";
                } else {
                    $f_name = $this->getFilename($a_file_id);
                    if(!$f_name) {
                        $f_name = $this->getFilename($a_file_id, $a_location);
                    }
                    $path .= $f_name;
                }
                break;
            case ilObjMediaGallery::LOCATION_ORIGINALS:
                $path .= $this->getFilename($a_file_id);
                break;
            case ilObjMediaGallery::LOCATION_PREVIEWS:
            case ilObjMediaGallery::LOCATION_DOWNLOADS:
                $path .= $a_file_id;
                break;
        }
        return $path;
    }

    /**
     * return exact file name of a give file id and location
     * @throws IOException
     * @returns bool|string
     */
    protected function getFilename(string $a_file_id, int $a_location = ilObjMediaGallery::LOCATION_ORIGINALS)
    {
        if(!isset($this->files_cache[$a_location])) {
            if(!file_exists($this->getPath($a_location))) {
                ilFileUtils::makeDir($this->getPath($a_location));
            }
            $this->files_cache[$a_location] = scandir($this->getPath($a_location));
        }
        foreach($this->files_cache[$a_location] as $name) {
            $f_name = pathinfo($this->getPath($a_location) . $name, PATHINFO_FILENAME);
            if($f_name === $a_file_id) {
                return $name;
            }
        }
        return false;
    }

    /**
     * @throws IOException
     */
    public function deleteFileByNameAndLocation(string $file_name, int $location): bool
    {
        $path = $this->getFilePath($location, $file_name);
        if(
            is_dir($path) ||
            !$this->fileExists($path) ||
            !parent::deleteFile($path)
        ) {
            return false;
        }
        if(isset($this->files_cache[$location])) {
            unset($this->files_cache[$location]);
        }
        if(isset($this->mime_cache[$file_name][$location])) {
            unset($this->mime_cache[$file_name][$location]);
        }
        $this->log->debug("Deleted file (file|location): (" . $file_name . "|" . $location . ")");
        return true;
    }

    /**
     * deletes all file of a given file id or deletes a file at a given location
     * @throws IOException
     */
    public function deleteFileByName(string $file_name): bool
    {
        $locations = [
            ilObjMediaGallery::LOCATION_PREVIEWS,
            ilObjMediaGallery::LOCATION_THUMBS,
            ilObjMediaGallery::LOCATION_SIZE_LARGE,
            ilObjMediaGallery::LOCATION_SIZE_MEDIUM,
            ilObjMediaGallery::LOCATION_SIZE_SMALL,
            ilObjMediaGallery::LOCATION_ORIGINALS
        ];
        $deleted_a_file = false;
        foreach ($locations as $location) {
            if(!$this->deleteFileByNameAndLocation($file_name, $location)) {
                continue;
            }
            $deleted_a_file = true;
        }
        return $deleted_a_file;
    }

    /**
     * @param int|string $a_location
     * @throws IOException
     */
    public function deleteDir($a_location): bool
    {
        if (is_dir($a_location)) {
            parent::deleteDirectory($a_location);
            return true;
        }
        if (in_array(
            $a_location,
            [
                ilObjMediaGallery::LOCATION_PREVIEWS,
                ilObjMediaGallery::LOCATION_ORIGINALS,
                ilObjMediaGallery::LOCATION_DOWNLOADS,
                ilObjMediaGallery::LOCATION_SIZE_LARGE,
                ilObjMediaGallery::LOCATION_SIZE_MEDIUM,
                ilObjMediaGallery::LOCATION_SIZE_SMALL,
                ilObjMediaGallery::LOCATION_THUMBS,
                ilObjMediaGallery::LOCATION_ROOT
            ]
        )
        ) {
            parent::deleteDirectory($this->getPath($a_location));
            return true;
        }
        return false;
    }

    public function getMimeType(string $a_file_id, int $a_location = ilObjMediaGallery::LOCATION_ORIGINALS): string
    {
        if(!isset($this->mime_cache[$a_file_id][$a_location])) {
            $this->mime_cache[$a_file_id][$a_location] = MimeType::lookupMimeType($this->getFilePath($a_location, $a_file_id));
        }
        return 	$this->mime_cache[$a_file_id][$a_location];
    }

    /**
     * @throws IOException
     */
    public function getPath(?int $a_location = null): string
    {
        $path = parent::getLegacyAbsolutePath() . '/';
        if(!$a_location) {
            return $path;
        }
        switch ($a_location) {
            case ilObjMediaGallery::LOCATION_ORIGINALS:
                $path .= 'originals/';
                break;
            case ilObjMediaGallery::LOCATION_THUMBS:
                $path .= 'thumbs/';
                break;
            case ilObjMediaGallery::LOCATION_SIZE_SMALL:
                $path .= 'small/';
                break;
            case ilObjMediaGallery::LOCATION_SIZE_MEDIUM:
                $path .= 'medium/';
                break;
            case ilObjMediaGallery::LOCATION_SIZE_LARGE:
                $path .= 'large/';
                break;
            case ilObjMediaGallery::LOCATION_PREVIEWS:
                $path .= 'previews/';
                break;
            case ilObjMediaGallery::LOCATION_DOWNLOADS:
                $path .= 'downloads/';
        }
        return $path;
    }

    /**
     * @throws IOException
     */
    public function create(): void
    {
        ilFileUtils::makeDir($this->getPath(ilObjMediaGallery::LOCATION_ORIGINALS));
        ilFileUtils::makeDir($this->getPath(ilObjMediaGallery::LOCATION_DOWNLOADS));
        ilFileUtils::makeDir($this->getPath(ilObjMediaGallery::LOCATION_SIZE_SMALL));
        ilFileUtils::makeDir($this->getPath(ilObjMediaGallery::LOCATION_SIZE_MEDIUM));
        ilFileUtils::makeDir($this->getPath(ilObjMediaGallery::LOCATION_SIZE_LARGE));
        ilFileUtils::makeDir($this->getPath(ilObjMediaGallery::LOCATION_PREVIEWS));
        ilFileUtils::makeDir($this->getPath(ilObjMediaGallery::LOCATION_THUMBS));
    }

    public function resetCache(): void
    {
        $this->files_cache = [];
        $this->mime_cache = [];
    }
}
