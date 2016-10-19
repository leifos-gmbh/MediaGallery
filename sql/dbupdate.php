<#1>
<?php
if (!$ilDB->tableExists('rep_robj_xmg_filedata'))
{
	$fields = array (
	'xmg_id'    => array(
		'type' => 'integer',
		'length'  => 4,
		'notnull' => true,
		'default' => 0),
	'media_id'   => array(
		'type' => 'text',
		'notnull' => false,
		'length' => 255,
		'fixed' => false,
		'default' => NULL),
	'topic'   => array(
		'type' => 'text',
		'notnull' => false,
		'length' => 255,
		'fixed' => false,
		'default' => NULL),
	'title'   => array(
		'type' => 'text',
		'notnull' => false,
		'length' => 255,
		'fixed' => false,
		'default' => NULL),
	'description'   => array(
		'type' => 'text',
		'notnull' => false,
		'length' => 255,
		'fixed' => false,
		'default' => NULL),
	'filename'   => array(
		'type' => 'text',
		'notnull' => true,
		'length' => 255,
		'fixed' => false)
	);
	$ilDB->createTable('rep_robj_xmg_filedata', $fields);
	$ilDB->addIndex("rep_robj_xmg_filedata", array("xmg_id"), "i1");
	$ilDB->addIndex("rep_robj_xmg_filedata", array("filename"), "i2");
}
?>
<#2>
<?php

include_once './Services/Administration/classes/class.ilSetting.php';
$setting = new ilSetting("xmg");
$setting->set('ext_img', 'jpg,jpeg,tif,tiff,png,gif,bmp');
$setting->set('ext_vid', 'mov,avi,m4v,mp4,flv');
$setting->set('ext_aud', 'mp3,wav,ogg,m4a');
$setting->set('sort', 'entry');

?>
<#3>
<?php

if(!$ilDB->tableColumnExists('rep_robj_xmg_filedata', 'custom'))
{
	$ilDB->addTableColumn("rep_robj_xmg_filedata",	"custom",
		array(
			"type" => "float",
			"notnull" => true,
			"default" => 0)
	);
}

?>
<#4>
<?php
if (!$ilDB->tableExists('rep_robj_xmg_object'))
{
	$fields = array (
	'obj_fi'    => array(
		'type' => 'integer',
		'length'  => 4,
		'notnull' => true,
		'default' => 0),
	'sortorder'   => array(
		'type' => 'text',
		'notnull' => false,
		'length' => 255,
		'fixed' => false,
		'default' => NULL),
	'show_title'    => array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 0)
	);
	$ilDB->createTable('rep_robj_xmg_object', $fields);
	$ilDB->addIndex("rep_robj_xmg_object", array("obj_fi"), "i1");
}
?>
<#5>
<?php

if(!$ilDB->tableColumnExists('rep_robj_xmg_filedata', 'mtype'))
{
	$ilDB->addTableColumn("rep_robj_xmg_filedata",	"mtype",
		array(
			'type' => 'text',
			'notnull' => false,
			'length' => 255,
			'fixed' => false,
			'default' => NULL)
	);
}

?>
<#6>
<?php

if(!$ilDB->tableColumnExists('rep_robj_xmg_filedata', 'width'))
{
	$ilDB->addTableColumn("rep_robj_xmg_filedata",	"width",
		array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0)
	);
	$ilDB->addTableColumn("rep_robj_xmg_filedata",	"height",
		array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0)
	);
}

?>
<#7>
<?php
if (!$ilDB->tableExists('rep_robj_xmg_downloads'))
{
	$fields = array (
	'xmg_id'    => array(
		'type' => 'integer',
		'length'  => 4,
		'notnull' => true,
		'default' => 0),
	'filename'   => array(
		'type' => 'text',
		'notnull' => true,
		'length' => 255,
		'fixed' => false)
	);
	$ilDB->createTable('rep_robj_xmg_downloads', $fields);
	$ilDB->addIndex("rep_robj_xmg_downloads", array("xmg_id"), "i1");
}
?>
<#8>
<?php
if(!$ilDB->tableColumnExists('rep_robj_xmg_object', 'download'))
{
	$ilDB->addTableColumn("rep_robj_xmg_object",	"download",
		array(
			'type' => 'integer',
			'length'  => 2,
			'notnull' => true,
			'default' => 0)
	);
}

?>
<#9>
<?php
if(!$ilDB->tableColumnExists('rep_robj_xmg_filedata', 'pwidth'))
{
	$ilDB->addTableColumn("rep_robj_xmg_filedata",	"pwidth",
		array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0)
	);
	$ilDB->addTableColumn("rep_robj_xmg_filedata",	"pheight",
		array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0)
	);
}

?>
<#10>
<?php
if(!$ilDB->tableColumnExists('rep_robj_xmg_filedata', 'pfilename'))
{
	$ilDB->addTableColumn("rep_robj_xmg_filedata",	"pfilename",
		array(
			'type' => 'text',
			'notnull' => false,
			'length' => 255,
			'fixed' => false)
	);
}

?>
<#11>
<?php
if(!$ilDB->tableColumnExists('rep_robj_xmg_object', 'theme'))
{
	$ilDB->addTableColumn("rep_robj_xmg_object",	"theme",
		array(
			'type' => 'text',
			'notnull' => false,
			'length' => 255,
			'fixed' => false,
			'default' => NULL)
	);
}

?>
<#12>
<?php

include_once './Services/Administration/classes/class.ilSetting.php';
$setting = new ilSetting("xmg");
$setting->set('max_upload', '100');

?>
<#13>
<?php
$downloads = array();

if ($ilDB->tableExists('rep_robj_xmg_downloads') && !$ilDB->tableColumnExists('rep_robj_xmg_downloads', 'id') )
{
	$res = $ilDB->query("SELECT * FROM rep_robj_xmg_downloads");

	while($row = $ilDB->fetchAssoc($res))
	{
		$downloads[$row["xmg_id"]][$row["filename"]] = $row;
	}

	$ilDB->manipulate("DROP TABLE rep_robj_xmg_downloads");
}

if (!$ilDB->tableExists('rep_robj_xmg_downloads'))
{
	$fields = array (
		'id'		=> array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0),
		'xmg_id'    => array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0),
		'download_flag'=> array(
			'type' => 'integer',
			'length'  => 2,
			'notnull' => true,
			'default' => 0),
		'filename'   => array(
			'type' => 'text',
			'notnull' => true,
			'length' => 255,
			'fixed' => false)
	);
	$ilDB->createTable('rep_robj_xmg_downloads', $fields);
	$ilDB->addPrimaryKey('rep_robj_xmg_downloads', array('id'));
	$ilDB->addIndex("rep_robj_xmg_downloads", array("xmg_id"), "i1");
	$ilDB->createSequence('rep_robj_xmg_downloads');
}

if(count($downloads) > 0)
{
	foreach($downloads as $xmg_id => $data)
	{
		foreach($data as $download)
		{
			$id = $ilDB->nextId('rep_robj_xmg_downloads');

			$arr = array(
				"id" => array("integer", $id),
				"xmg_id" => array("integer", $xmg_id),
				"download_flag" => array("integer", 1),
				"filename" => array("text", $download["filename"])
			);

			$ilDB->insert("rep_robj_xmg_downloads", $arr);
		}
	}
}
?>
<#14>
<?php
$media_dir = ilUtil::getWebspaceDir(). "/mediagallery/";

$objects = array();

if(file_exists($media_dir))
{
	$objects = scandir($media_dir);
}

if(count($objects) > 0 && $ilDB->tableExists('rep_robj_xmg_downloads') && $ilDB->tableColumnExists('rep_robj_xmg_downloads', 'id'))
{
	$res = $ilDB->query("SELECT * FROM rep_robj_xmg_downloads");

	while($row = $ilDB->fetchAssoc($res))
	{
		$downloads[$row["xmg_id"]][$row["filename"]] = $row;
	}

	foreach((array) $objects as $obj_id)
	{
		if($obj_id === '.' || $obj_id === '..') {continue;}
		$downloads_path = $media_dir.$obj_id.'/media/downloads/';
		if(file_exists($downloads_path))
		{
			$downloads = scandir($downloads_path);

			foreach($downloads as $filename)
			{
				if($filename === '.' || $filename === '..') {continue;}

				$ext = pathinfo($downloads_path.$filename, PATHINFO_EXTENSION);

				if($ext == ".zip" || $ext == "zip" && !isset($downloads[$obj_id][$filename]))
				{
					$id = $ilDB->nextId('rep_robj_xmg_downloads');

					$arr = array(
						"id" => array("integer", $id),
						"xmg_id" => array("integer", $obj_id),
						"download_flag" => array("integer", 0),
						"filename" => array("text", $filename)
					);

					$ilDB->insert("rep_robj_xmg_downloads", $arr);
				}
			}
		}
	}
}
?>
<#15>
<?php
$file_data = array();

if ($ilDB->tableExists('rep_robj_xmg_filedata') && !$ilDB->tableColumnExists('rep_robj_xmg_filedata', 'id') )
{
	$res = $ilDB->query("SELECT * FROM rep_robj_xmg_filedata");

	while($row = $ilDB->fetchAssoc($res))
	{
		$file_data[$row["xmg_id"]][$row["filename"]] = $row;
	}
	$ilDB->manipulate("DROP TABLE rep_robj_xmg_filedata");
}

if (!$ilDB->tableExists('rep_robj_xmg_filedata'))
{
	$fields = array (
		'id'		=> array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0),
		'xmg_id'    => array(
			'type' => 'integer',
			'length'  => 4,
			'notnull' => true,
			'default' => 0),
		'media_id'   => array(
			'type' => 'text',
			'notnull' => false,
			'length' => 255,
			'fixed' => false,
			'default' => NULL),
		'topic'   => array(
			'type' => 'text',
			'notnull' => false,
			'length' => 255,
			'fixed' => false,
			'default' => NULL),
		'title'   => array(
			'type' => 'text',
			'notnull' => false,
			'length' => 255,
			'fixed' => false,
			'default' => NULL),
		'description'   => array(
			'type' => 'text',
			'notnull' => false,
			'length' => 255,
			'fixed' => false,
			'default' => NULL),
		'filename'   => array(
			'type' => 'text',
			'notnull' => true,
			'length' => 255,
			'fixed' => false),
		"custom"	=> array(
			"type" => "integer",
			'length'  => 4,
			"notnull" => true,
			"default" => 0),
		'pfilename'   => array(
			'type' => 'text',
			'notnull' => false,
			'length' => 255,
			'fixed' => false)
	);
	$ilDB->createTable('rep_robj_xmg_filedata', $fields);
	$ilDB->addPrimaryKey('rep_robj_xmg_filedata', array('id'));
	$ilDB->addIndex("rep_robj_xmg_filedata", array("xmg_id"), "i1");
	$ilDB->createSequence('rep_robj_xmg_filedata');
}

if(count($file_data) > 0)
{
	foreach($file_data as $object => $files)
	{
		foreach($files as $filename => $file)
		{
			$id = $ilDB->nextId('rep_robj_xmg_filedata');

			$file_data[$object][$filename]["id"] = $id;

			$arr = array(
				"id" => array("integer", $id),
				"xmg_id" => array("integer", $file["xmg_id"]),
				"media_id" => array("integer",$file["media_id"]),
				"topic" => array("text", $file["topic"]),
				"title" => array("text", $file["title"]),
				"description" => array("text", $file["description"]),
				"filename" => array("text", $file["filename"]),
				"custom" => array("integer",$file["custom"]),
				"pfilename" => array("text", $file["pfilename"])
			);

			$ilDB->insert("rep_robj_xmg_filedata",$arr);
		}
	}
}
?>
<#16>
<?php
$media_dir = ilUtil::getWebspaceDir(). "/mediagallery/";

if($ilDB->tableColumnExists('rep_robj_xmg_filedata', 'id'))
{
	$structure = array(
		"originals/",
		"thumbs/",
		"small/",
		"medium/",
		"large/"
	);

	$res = $ilDB->query("SELECT * FROM rep_robj_xmg_filedata");

	while($row = $ilDB->fetchAssoc($res))
	{
		foreach($structure as $folder)
		{
			$path = $media_dir. $row["xmg_id"] . '/media/' . $folder;
			$filename = $row['filename'];
			$extension = pathinfo($path. $filename, PATHINFO_EXTENSION);

			if($folder != "originals/" && (substr($filename, -5) == '.tiff' || substr($filename, -4) == '.tif'))
			{
				$extension = "png";
				$filename = str_replace(array(".tiff", ".tif"), ".png", $filename);
			}

			if(file_exists($path. $filename))
			{
				rename($path. $filename,$path . $row['id'] . '.' . $extension);
			}
		}
	}
}
?>
<#17>
<?php
$media_dir = ilUtil::getWebspaceDir(). "/mediagallery/";
if ($ilDB->tableExists('rep_robj_xmg_filedata'))
{
	$res = $ilDB->query("SELECT xmg_id FROM rep_robj_xmg_filedata GROUP BY xmg_id");

	while($row = $ilDB->fetchAssoc($res))
	{
		if(file_exists($media_dir. $row['xmg_id'] . '/'))
		{
			rename($media_dir. $row['xmg_id'] . '/', $media_dir. 'xmg_' .$row['xmg_id'] . '/');
		}

		if(file_exists($media_dir. 'xmg_' .$row['xmg_id'] . '/media/'))
		{
			$structure = array(
				"originals/",
				"thumbs/",
				"small/",
				"medium/",
				"large/",
				"previews/",
				"downloads/"
			);

			foreach($structure as $folder)
			{
				rename($media_dir. 'xmg_' .$row['xmg_id'] . '/media/'. $folder, $media_dir. 'xmg_' .$row['xmg_id'] . '/'. $folder);
			}

			rmdir($media_dir. 'xmg_' .$row['xmg_id'] . '/media/');
		}
	}
}
?>
<#18>
<?php
$media_dir = ilUtil::getWebspaceDir(). "/mediagallery/";

if(file_exists($media_dir))
{
	$sec_dir = ilUtil::getWebspaceDir()."/sec/";

	if(!file_exists($sec_dir))
	{
		mkdir($sec_dir);
	}
	if(!file_exists($sec_dir."ilXmg/"))
	{
		rename($media_dir, $sec_dir."ilXmg/");
	}
}
?>
<#19>
<?php
$query = "DELETE FROM il_wac_secure_path ".
	"WHERE path = ".$ilDB->quote('ilXmg','text');

$res = $ilDB->manipulate($query);

$ilDB->insert('il_wac_secure_path', array(
	"path" 	=> array('text', 'ilXmg'),
	"component_directory" => array('text', realpath('./Customizing/global/plugins/Services/Repository/RepositoryObject/MediaGallery/')),
	"checking_class"	=> array('text', 'ilObjMediaGalleryAccess'),
	"in_sec_folder" => array('integer', 1)
));
?>
<#20>
<?php
if ($ilDB->tableExists('rep_robj_xmg_filedata'))
{
	$res = $ilDB->manipulate("UPDATE rep_robj_xmg_filedata SET media_id = '' WHERE media_id = '0'");
}
?>
<#21>
<?php
if ($ilDB->tableExists('rep_robj_xmg_object'))
{
	$res = $ilDB->manipulate("UPDATE rep_robj_xmg_object SET sortorder = 'filename' WHERE sortorder = 'entry'");
}
?>
<#22>
<?php
include_once './Services/Administration/classes/class.ilSetting.php';
$setting = new ilSetting("xmg");
$setting->delete('sort');
?>
<#23>
<?php
$query = "DELETE FROM il_wac_secure_path ".
	"WHERE path = ".$ilDB->quote('ilXmg','text');

$res = $ilDB->manipulate($query);

$ilDB->insert('il_wac_secure_path', array(
	"path" 	=> array('text', 'ilXmg'),
	"component_directory" => array('text', realpath('./Customizing/global/plugins/Services/Repository/RepositoryObject/MediaGallery/')),
	"checking_class"	=> array('text', 'ilObjMediaGalleryAccess'),
	"in_sec_folder" => array('integer', 1)
));
?>
