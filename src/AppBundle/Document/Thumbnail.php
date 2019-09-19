<?php

namespace AppBundle\Document;

use Symfony\Component\HttpFoundation\File\File;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/** @MongoDB\Document */
class Thumbnail
{
    const size = 200;
    const path = __DIR__.'/../../../var/thumbnails/';

    /** @MongoDB\Id */
    private $id;

    /** @MongoDB\Field */
    private $name;

    /** @MongoDB\File */
    private $file;

    /** @MongoDB\NotSaved(type="date") */
    private $uploadDate;

    /** @MongoDB\NotSaved(type="int") */
    private $length;

    /** @MongoDB\Field */
    private $chunkSize;

    /** @MongoDB\NotSaved(type="string") */
    private $md5;

    public function __construct(File $file)
    {
        $this->file = $file;
        $this->name = $file->getFileName();
        copy($this->file, self::path.$this->name);
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function thumbnalize($size = self::size)
    {
        list($width, $height, $type) = getimagesize($this->file);
        $r = $width / $height;

        $newwidth = $size;
        $newheight = $size / $r;

        $resultImg = null;

        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($this->file);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($this->file);
                break;
        }

        $dst = imagecreatetruecolor($newwidth, $newheight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        imagejpeg(
            $dst,
            self::path.$this->file->getFileName()
        );
        imagedestroy($dst);
        imagedestroy($src);

        $this->setFile(self::path.$this->name);
    }
}
