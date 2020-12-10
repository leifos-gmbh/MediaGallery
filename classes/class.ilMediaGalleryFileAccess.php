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
     * @var array
     */
    private $accessed_files = array();

    /**
     * gets the instance via lazy initialization (created on first usage)
     *
     * @param int $gallery_id
     */
    public static function getInstanceByGalleryId(int $gallery_id) : ilMediaGalleryFileAccess
    {
        if (!isset(self::$instances[$gallery_id])) {
            self::$instances[$gallery_id] = new self($gallery_id);
        }

        return self::$instances[$gallery_id];
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     *
     * @param int $gallery_id
     */
    private function __construct(int $gallery_id)
    {
        global $DIC;

        $this->ilDB = $DIC->database();
        $this->setGalleryId($gallery_id);
        $this->read();

    }

    private function read()
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

    public function create($file_id, $user_id) : bool
    {
        global $DIC;

        if(!empty($this->getAccessed()[$user_id]) && in_array($file_id, $this->getAccessed()[$user_id])) {
            return false;
        }

        $next_id = $this->ilDB->nextId('rep_robj_xmg_faccess');
        $query = 'INSERT INTO rep_robj_xmg_faccess (access_id, gallery_obj_id, file_id, user_id)
                    VALUES (' . $this->ilDB->quote($next_id, 'integer') . ',' . $this->ilDB->quote($this->getGalleryId(), 'integer') . ',' . $this->ilDB->quote($file_id, 'integer') . ',' . $this->ilDB->quote($user_id, 'integer') . ')';

        $this->ilDB->manipulate($query);

        $accessed = $this->getAccessed();
        $accessed[$user_id][] = $file_id;
        $this->setAccessed($accessed);

        return true;
    }

    public function deleteAccessRecordsForGallery() : bool
    {
        $query = 'DELETE FROM rep_robj_xmg_faccess WHERE gallery_obj_id = ' . $this->ilDB->quote($this->getGalleryId(), 'integer');

        $this->ilDB->manipulate($query);

        $this->setAccessed(array());

        return true;
    }

    public function deleteAccessRecordsForFile($file_id) : bool
    {

        $query = 'DELETE FROM rep_robj_xmg_faccess WHERE gallery_obj_id = ' . $this->ilDB->quote($this->getGalleryId(), 'integer') . ' AND file_id = ' . $this->ilDB->quote($file_id, 'integer');

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

    public static function deleteAllAccessRecordsByUserId(int $user_id) : bool
    {
        global $DIC;

        $ilDB = $DIC->database();

        $query = 'DELETE FROM rep_robj_xmg_faccess WHERE user_id = ' . $ilDB->quote($user_id, 'integer');

        $ilDB->manipulate($query);

        return true;
    }

    /**
     * Returns array of users who visited all LP-relevant files
     *
     * @return array
     */
    public function getLpCompleted() : array
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
    public function getGalleryId() : int
    {
        return $this->gallery_id;
    }

    /**
     * @param int $gallery_id
     */
    private function setGalleryId(int $gallery_id)
    {
        $this->gallery_id = $gallery_id;
    }

    /**
     * @return array
     */
    public function getAccessed() : array
    {
        return $this->accessed_files;
    }

    /**
     * @param array $accessed_files
     */
    private function setAccessed(array $accessed_files)
    {
        $this->accessed_files = $accessed_files;
    }
}
