<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\ODM\MongoDB\Mapping\Annotations\PreUpdate;
use Doctrine\ODM\MongoDB\Mapping\Annotations\PrePersist;
use Symfony\Component\HttpFoundation\File\File;
use AppBundle\Document\Exif;
use AppBundle\Document\Thumbnail;


/**
 * @MongoDB\Document
 * @Vich\Uploadable
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\PhotoRepository") @HasLifecycleCallbacks
 */
class Photo
{

  const image_path = "../var/cache/upload/";

  /**
    * @MongoDB\Id(strategy="AUTO")
    */
   protected $id;

   /**
    * @MongoDB\File
    * @Vich\UploadableField(mapping="attachement_image", fileNameProperty="imageName", size="imageSize")
    * @Assert\Image(
    *   maxSizeMessage="Le fichier est trop lourd {{ size }} {{ suffix }}. Le maximum est de {{ limit }} {{ suffix }}.",
    *   mimeTypesMessage="Le fichier n'est pas une image."
    * )
    *
    */
   protected $imageFile;

   /**
    * @MongoDB\Field(type="string")
    *
    */
   protected $imageName;

   /**
    * @MongoDB\Field(type="string")
    *
    */
   protected $base64;

   /**
    * @MongoDB\Field(type="string")
    *
    */
   protected $ext;

   /**
    * @MongoDB\Field(type="int")
    *
    */
   protected $imageSize;

   /**
    * @MongoDB\Field(type="date")
    *
    */
   protected $updatedAt;


   /** @MongoDB\EmbedOne(targetDocument="GeoJson") */
   public $localisation;

    /** @MongoDB\EmbedOne(targetDocument="Exif") */
    protected $exif;

   /** @MongoDB\ReferenceOne(targetDocument="Thumbnail") */
    protected $thumbnail;

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
     * @MongoDB\PrePersist()
     * @MongoDB\PreUpdate()
     */
    public function preSave() {
        $this->updatedAt = new \DateTime();
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
        unlink(realpath(self::image_path.$this->getImageName()));
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

    /**
     * Enregistre les données exif
     */
    public function storeExif($file)
    {
        $exif = @exif_read_data($file);
        ($exif) ? $this->setExif($exif) : $this->setExif([]);
    }

    public function fixOrientation($file)
    {
        $rotation = $this->getExif()->getRotation();
        switch ($rotation) {
            case Exif::ROT_180:
                $file = imagerotate($file, 180, 0);
                break;
            case Exif::ROT_M90:
                $file = imagerotate($file, -90, 0);
                break;
            case Exif::ROT_90:
                $file = imagerotate($file, 90, 0);
                break;
        }

        return $file;
    }

    public function convertBase64($file){
        $this->setBase64(base64_encode(file_get_contents($file)));
    }

    /**
     * On fait les opération sur le fichier,
     * puis on le converti et on le supprime
     *
     * @param int $resizeWidth La nouvelle largeur
     */
    public function operate($resizeWidth = 0)
    {
        $file = self::image_path . $this->getImageName();
        $this->setExt(mime_content_type($file));

        $file = realpath($file);

        $this->storeExif($file);
        $this->extractGeolocFromFile($file);
        $this->convertBase64($file);
    }

    public function getBase64Src(){
        return 'data: '.$this->getExt().';base64,'.$this->getBase64();
    }

    private function resize_image($file, $w, $h)
    {
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

            $this->fixOrientation($dst);

            $resultImg = imagejpeg($dst, $file);
        }
        if($this->isPng()){
            $src = imagecreatefrompng($file);
            $dst = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            $this->fixOrientation($dst);

            $resultImg = imagepng($dst, $file);
        }
        if($this->isGif()){
            $src = imagecreatefromgif($file);
            $dst = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            $this->fixOrientation($dst);

            $resultImg = imagegif($dst, $file);
        }

        return $resultImg;
    }

    /**
     * Set localisation
     *
     * @param AppBundle\Document\GeoJson $localisation
     * @return $this
     */
    public function setLocalisation(\AppBundle\Document\GeoJson $localisation)
    {
        $this->localisation = $localisation;
        return $this;
    }

    /**
     * Get localisation
     *
     * @return AppBundle\Document\GeoJson $localisation
     */
    public function getLocalisation()
    {
        return $this->localisation;
    }

    public function setLatLon($lat,$lon){

        $localisation = new GeoJson();
        $localisation->setType("Point");

        $coordinates = new Coordinates();
        $coordinates->setX($lon);
        $coordinates->setY($lat);
        $localisation->setCoordinates($coordinates);

        $this->setLocalisation($localisation);
    }

    public function getLon(){
        return $this->getLocalisation()->getCoordinates()->getX();
    }
    public function getLat(){
        return $this->getLocalisation()->getCoordinates()->getY();
    }

    public function extractGeolocFromFile($filename){
        $location  = $this->get_image_location($filename);
        if(!$location){
            return;
        }
        $latitude = $location["lat"];
        $this->setLatLon($location["lat"],$location["lon"]);
    }

    public function get_image_location($file) {
        if (!is_file($file)) {

            return false;
        }
        try {
            $info = exif_read_data($file);
        } catch (\Exception $e) {

            return false;
        }
        if ($info !== false) {

            return false;
        }
        $direction = array('N', 'S', 'E', 'W');
        if (!isset($info['GPSLatitude'], $info['GPSLongitude'], $info['GPSLatitudeRef'], $info['GPSLongitudeRef']) || !in_array($info['GPSLatitudeRef'], $direction) || !in_array($info['GPSLongitudeRef'], $direction)) {

            return false;
        }

        $lat_degrees_a = explode('/',$info['GPSLatitude'][0]);
        $lat_minutes_a = explode('/',$info['GPSLatitude'][1]);
        $lat_seconds_a = explode('/',$info['GPSLatitude'][2]);
        $lng_degrees_a = explode('/',$info['GPSLongitude'][0]);
        $lng_minutes_a = explode('/',$info['GPSLongitude'][1]);
        $lng_seconds_a = explode('/',$info['GPSLongitude'][2]);

        $lat_degrees = $lat_degrees_a[0] / $lat_degrees_a[1];
        $lat_minutes = $lat_minutes_a[0] / $lat_minutes_a[1];
        $lat_seconds = $lat_seconds_a[0] / $lat_seconds_a[1];
        $lng_degrees = $lng_degrees_a[0] / $lng_degrees_a[1];
        $lng_minutes = $lng_minutes_a[0] / $lng_minutes_a[1];
        $lng_seconds = $lng_seconds_a[0] / $lng_seconds_a[1];

        $lat = (float) $lat_degrees + ((($lat_minutes * 60) + ($lat_seconds)) / 3600);
        $lng = (float) $lng_degrees + ((($lng_minutes * 60) + ($lng_seconds)) / 3600);
        $lat = number_format($lat, 7);
        $lng = number_format($lng, 7);

        //If the latitude is South, make it negative.
        //If the longitude is west, make it negative
        $lat = $info['GPSLatitudeRef'] == 'S' ? $lat * -1 : $lat;
        $lng = $info['GPSLongitudeRef'] == 'W' ? $lng * -1 : $lng;

        return array(
            'lat' => $lat,
            'lng' => $lng
        );
    }

    /**
     * On enregistre les données Exif
     *
     * @param array $exif Les données exif
     * @return $this
     */
    public function setExif(array $exif)
    {
        $this->exif = new Exif($exif);
    }

    /**
     * Retourne les données Exif
     *
     * @return Exif Les données Exif
     */
    public function getExif()
    {
        return $this->exif;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setThumbnail(Thumbnail $thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }
}
