<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Attachement
 *
 * @author mathurin
 */

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;


/**
 * @MongoDB\Document
 * @Vich\Uploadable
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\PhotoRepository")
 */
class Photo
{

  /**
    * @MongoDB\Id(strategy="AUTO")
    */
   protected $id;

   /**
    * @MongoDB\File
    * @Vich\UploadableField(mapping="attachement_image", fileNameProperty="imageName", size="imageSize")
    *
    */
   protected $imageFile;

   /**
    * @MongoDB\String
    *
    */
   protected $imageName;

   /**
    * @MongoDB\String
    *
    */
   protected $base64;

   /**
    * @MongoDB\string
    *
    */
   protected $ext;

   /**
    * @MongoDB\Int
    *
    */
   protected $imageSize;

   /**
    * @MongoDB\Date
    *
    */
   protected $updatedAt;

   /**
    * @MongoDB\String
    *
    */
   protected $titre;

   /**
    * @MongoDB\String
    *
    */
   protected $originalName;

   /**
    * @MongoDB\ReferenceOne(targetDocument="Ascenseur", inversedBy="photos", simple=true)
    */
   protected $ascenseur;


    /**
     * Set updatedAt
     *
     * @param date $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return date $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set titre
     *
     * @param string $titre
     * @return $this
     */
    public function setTitre($titre)
    {
        $this->titre = $titre;
        return $this;
    }

    /**
     * Get titre
     *
     * @return string $titre
     */
    public function getTitre()
    {
        return $this->titre;
    }

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }



    /**
     * Set imageName
     *
     * @param string $imageName
     * @return $this
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;
        return $this;
    }

    /**
     * Get imageName
     *
     * @return string $imageName
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * Set imageSize
     *
     * @param int $imageSize
     * @return $this
     */
    public function setImageSize($imageSize)
    {
        $this->imageSize = $imageSize;
        return $this;
    }

    /**
     * Get imageSize
     *
     * @return int $imageSize
     */
    public function getImageSize()
    {
        return $this->imageSize;
    }

    /**
     * Set imageFile
     *
     * @param file $imageFile
     * @return $this
     */
    public function setImageFile($imageFile = null)
    {
      $this->imageFile = $imageFile;

      if ($imageFile && !$this->getBase64()) {
          $this->updatedAt = new \DateTime('now');
      }
      if($imageFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile && $imageFile->getClientOriginalName()){
         $this->setOriginalName($imageFile->getClientOriginalName());
      }
    }

    /**
     * Get imageFile
     *
     * @return file $imageFile
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }



    public function isImage(){
      return preg_match('/(\.jpg|\.jpeg|\.gif|\.png)$/i',$this->getImageName());
    }

    public function isJpg(){
      return preg_match('/(\.jpg|\.jpeg)$/i',$this->getImageName());
    }

    public function isPng(){
      return preg_match('/(\.png)$/i',$this->getImageName());
    }

    public function isGif(){
      return preg_match('/(\.gif)$/i',$this->getImageName());
    }


    /**
     * Set originalName
     *
     * @param string $originalName
     * @return $this
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;
        return $this;
    }

    /**
     * Get originalName
     *
     * @return string $originalName
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    public function removeFile(){
        unlink(realpath('../web/documents/'.$this->getImageName()));
    }

    /**
     * Set base64
     *
     * @param string $base64
     * @return $this
     */
    public function setBase64($base64)
    {
        $this->base64 = $base64;
        return $this;
    }

    /**
     * Get base64
     *
     * @return string $base64
     */
    public function getBase64()
    {
        return $this->base64;
    }

    /**
     * Set ext
     *
     * @param string $ext
     * @return $this
     */
    public function setExt($ext)
    {
        $this->ext = $ext;
        return $this;
    }

    /**
     * Get ext
     *
     * @return string $ext
     */
    public function getExt()
    {
        return $this->ext;
    }

    public function convertBase64AndRemove($resizeWidth = false){
        $file = '../web/documents/'.$this->getImageName();
        $this->setExt(mime_content_type($file));
        if($resizeWidth){
            list($width, $height, $type, $attr) = getimagesize(realpath($file));
            $attent_width = $resizeWidth;
            $attent_height = ($attent_width * $height) / $attent_width;
            $dstImg = $this->resize_image(realpath($file), $attent_width, $attent_height);
            $this->setBase64(base64_encode(file_get_contents(realpath($file))));
        }else{
            $this->setBase64(base64_encode(file_get_contents(realpath($file))));
        }
        $this->removeFile();
    }

    public function getBase64Src(){
        return 'data: '.$this->getExt().';base64,'.$this->getBase64();
    }

    private function resize_image($file, $w, $h) {
        list($width, $height) = getimagesize($file);
        $r = $width / $height;
        if ($w/$h > $r) {
            $newwidth = $h*$r;
            $newheight = $h;
        } else {
            $newheight = $w/$r;
            $newwidth = $w;
        }

        $resultImg = null;
        if($this->isJpg()){
            $src = imagecreatefromjpeg($file);
            $dst = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            $resultImg = imagejpeg($dst,realpath($file));
        }
        if($this->isPng()){
            $src = imagecreatefrompng($file);
            $dst = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            $resultImg = imagepng($dst,realpath($file));
        }
        if($this->isGif()){
            $src = imagecreatefromgif($file);
            $dst = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            $resultImg = imagegif($dst,realpath($file));
        }

        return $resultImg;
    }

    public static function cmpUpdateAt($a, $b) {
        if ($a->getUpdatedAt() == $b->getUpdatedAt()) {
                return "0";
            } else {
                return ($b->getUpdatedAt() > $a->getUpdatedAt())? "+1" : "-1";
            }
    }

    /**
     * Set ascenseur
     *
     * @param AppBundle\Document\Ascenseur $ascenseur
     * @return self
     */
    public function setAscenseur(\AppBundle\Document\Ascenseur $ascenseur)
    {
        $this->ascenseur = $ascenseur;
        return $this;
    }

    /**
     * Get ascenseur
     *
     * @return AppBundle\Document\Ascenseur $ascenseur
     */
    public function getAscenseur()
    {
        return $this->ascenseur;
    }
}
