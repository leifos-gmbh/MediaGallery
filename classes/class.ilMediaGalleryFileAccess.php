<?php
/**
 * Class ilMediaGalleryFileAccess
 *
 * @author Marvin Barz<barz@leifos.com>
 * @version $Id$
 *
 */

final class ilMediaGalleryFileAccess
{
    /**
     * @var array
     */
    private static $instances = array();

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
    public static function getInstance($gallery_id, $user_id = 0)
    {
        if (static::$instances[$gallery_id] === null) {
            static::$instances[$gallery_id] = new static($gallery_id, $user_id);
        }

        return static::$instances[$gallery_id];
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct($gallery_id, $user_id)
    {
        global $DIC;

        $this->ilDB = $DIC->database();
        $this->setGalleryId($gallery_id);
        $this->setUserId($user_id);
        $this->read();

    }

    private function read()
    {

        $user_query = '';
        if($this->getUserId() != 0) {
            $user_query = ' AND user_id = ' . $this->ilDB->quote($this->getUserId(), 'integer');
        }

        $res = $this->ilDB->query('SELECT * FROM rep_robj_xmg_faccess WHERE gallery_obj_id = ' . $this->ilDB->quote($this->getGalleryId(), 'integer') . $user_query );

        $accessed_files = array();
        if ($res->numRows() > 0) {
            while ($row = $res->fetchAssoc()) {
                $accessed_files[$row['user_id']][] = $row['file_id'];
            }
        }


        $this->setAccessed($accessed_files);
    }

    public function create($file_id)
    {
        global $DIC;

        if($this->getUserId() == 0 || (!empty($this->getAccessed()) && in_array($file_id, $this->getAccessed()[$this->getUserId()]))) {
            return false;
        }

        $next_id = $this->ilDB->nextId('rep_robj_xmg_faccess');
        $query = 'INSERT INTO rep_robj_xmg_faccess (access_id, gallery_obj_id, file_id, user_id)
                    VALUES (' . $this->ilDB->quote($next_id, 'integer') . ',' . $this->ilDB->quote($this->getGalleryId(), 'integer') . ',' . $this->ilDB->quote($file_id, 'integer') . ',' . $this->ilDB->quote($this->getUserId(), 'integer') . ')';

        $this->ilDB->manipulate($query);

        return true;
    }

    public function delete($file_id = null)
    {
        if($this->getUserId() == 0) {
            return false;
        }

        $file_query = '';
        if(!empty($file_id)) {
            $file_query = ' AND ' . $this->ilDB->quote($file_id, 'integer');
        }

        $query = 'DELETE FROM rep_robj_xmg_faccess WHERE gallery_obj_id = ' . $this->ilDB->quote($this->getGalleryId(), 'integer') .
            ' AND user_id = ' . $this->ilDB->quote($this->getUserId(), 'integer') . $file_query;

        $this->ilDB->manipulate($query);

        return true;
    }

    /**
     * Returns array of users who visited all LP-relevant files
     *
     * @return array
     */
    public function getLpCompleted()
    {
        global $DIC;

        $files = ilMediaGalleryFile::_getMediaFilesInGallery($this->getGalleryId(), 0, ['lp_relevant' => 1]);

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
