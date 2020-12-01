<?php

final class MediaGalleryFileAccess
{
    /**
     * @var MediaGalleryFileAccess
     */
    private static $instance = null;

    /**
     * @var \ilDBInterface
     */
    private $ilDB;

    /**
     * @var int
     */
    private $gallery_id;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var array
     */
    private $accessed_files = array();

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance($gallery_id = 0, $user_id = 0)
    {
        if (static::$instance === null) {
            static::$instance = new static($gallery_id, $user_id);
        }

        return static::$instance;
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct($gallery_id, $user_id)
    {
        global $DIC;

        $this->ilDB = $DIC->database();

        if($gallery_id != 0 && $user_id != 0) {
            $this->setGalleryId($gallery_id);
            $this->setUserId($user_id);
            $this->read();
        }

    }

    private function read()
    {
        $res = $this->ilDB->query('SELECT * FROM rep_robj_xmg_faccess WHERE gallery_obj_id = ' . $this->ilDB->quote($this->getGalleryId(), 'integer') . ' AND user_id = ' . $this->ilDB->quote($this->ilDB->quote($this->getUserId()), 'integer'));

        $accessed_files = array();
        if ($res->numRows() > 0) {
            while ($row = $res->fetchAssoc()) {
                $accessed_files[] = $row['file_id'];
            }
        }

        $this->setAccessed($accessed_files);
    }

    public function create($file_id)
    {
        $next_id = $this->ilDB->nextId('rep_robj_xmg_faccess');

        $accessed_files = $this->getAccessed();

        if(in_array($file_id, $accessed_files)) {
            return false;
        }

        $query = 'INSERT INTO rep_robj_xmg_faccess (access_id, gallery_obj_id, file_id, user_id)
                    VALUES (' . $this->ilDB->quote($next_id, 'integer') . ',' . $this->ilDB->quote($this->getGalleryId(), 'integer') . ',' . $this->ilDB->quote($this->ilDB->quote($file_id, 'integer')) . ',' . $this->ilDB->quote($this->ilDB->quote($this->getUserId()), 'integer') . ')';

        $this->ilDB->manipulate($query);

        return true;
    }

    public function delete($file_id = null)
    {
        $file_query = '';

        if(!empty($file_id)) {
            $file_query = ' AND ' . $this->ilDB->quote($this->ilDB->quote($file_id, 'integer'));
        }

        $query = 'DELETE FROM rep_robj_xmg_faccess WHERE gallery_obj_id = ' . $this->ilDB->quote($this->getGalleryId(), 'integer') .
            ' AND user_id = ' . $this->ilDB->quote($this->ilDB->quote($this->getUserId()), 'integer') . $file_query;

        $this->ilDB->manipulate($query);

        return true;
    }

    /**
     * @return int
     */
    public function getGalleryId()
    {
        return $this->gallery_id;
    }

    /**
     * @param int $gallery_id
     */
    public function setGalleryId($gallery_id)
    {
        $this->gallery_id = $gallery_id;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * @return array
     */
    public function getAccessed()
    {
        return $this->accessed_files;
    }

    /**
     * @param array $accessed_files
     */
    public function setAccessed($accessed_files)
    {
        $this->accessed_files = $accessed_files;
    }
}
