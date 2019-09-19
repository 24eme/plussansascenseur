<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Lib\AdresseDataGouvApi;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\ODM\MongoDB\Mapping\Annotations\PreUpdate;
use Doctrine\ODM\MongoDB\Mapping\Annotations\PrePersist;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\AscenseurRepository") @HasLifecycleCallbacks
 */
class Ascenseur {

    const STATUT_ENPANNE = "EN_PANNE";
    const STATUT_FONCTIONNEL = "FONCTIONNEL";

    /**
      * @MongoDB\Id(strategy="AUTO")
      */
     protected $id;


     /** @MongoDB\EmbedOne(targetDocument="GeoJson") */
    protected $localisation;

     /**
     *  @MongoDB\ReferenceMany(targetDocument="Photo")
     */
    protected $photos;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $adresse;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $codePostal;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $commune;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $emplacement;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $reference;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $constructeurNom;

    /**
     * @MongoDB\Field(type="int")
     *
     */
    protected $etageMin;

    /**
     * @MongoDB\Field(type="int")
     *
     */
    protected $etageMax;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $telephoneDepannage;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $statut;

    /**
     * @MongoDB\Field(type="date")
     *
     */
    protected $dateStatut;

    /**
     *  @MongoDB\EmbedMany(targetDocument="Evenement")
     *
     */
    protected $historique;

    /**
     *  @MongoDB\Field(type="date")
     *
     */
    protected $dateConstruction;

    /**
     *  @MongoDB\Field(type="date")
     *
     */
    protected $dateRenovation;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $syndicNom;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $syndicContact;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $bailleurNom;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $bailleurContact;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $ascensoristeNom;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $ascensoristeContact;

    /**
     * @MongoDB\Field(type="date")
     *
     */
    protected $updatedAt;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Signalement", mappedBy="ascenseur")
     */
     protected $signalements;


    /**
     * @MongoDB\Field(type="int")
     *
     */
    protected $nombreFollowers;

    public function __construct()
    {
        $this->photos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setStatut(self::STATUT_ENPANNE);
        $this->localisation = new GeoJson();
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
     * Add photo
     *
     * @param AppBundle\Document\Photo $photo
     */
    public function addPhoto(\AppBundle\Document\Photo $photo)
    {
        $this->photos[] = $photo;
    }

    /**
     * Remove photo
     *
     * @param AppBundle\Document\Photo $photo
     */
    public function removePhoto(\AppBundle\Document\Photo $photo)
    {
        $this->photos->removeElement($photo);
    }

    public function getPhotosId()
    {
        $ids = array();

        foreach($this->photos->getMongoData() as $item) {

            $ids[] = $item['$id'];
        }

        return $ids;
    }

    public function getFirstPhotoId() {
        foreach($this->getPhotosId() as $id) {

            return $id;
        }

        return null;
    }

    /**
     * Set localisation
     *
     * @param AppBundle\Document\GeoJson $localisation
     * @return self
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

    public function setAdresse($adresse){
        $this->adresse = $adresse;

        return $this;
    }

    public function getAdresse() {

        return $this->adresse;
    }

    public function setCodePostal($codePostal){
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getCodePostal() {

        return $this->codePostal;
    }

    public function setCommune($commune){
        $this->commune = $commune;

        return $this;
    }

    public function getCommune() {

        return $this->commune;
    }

    public function getAdresseLibelle() {
        if ($this->getAdresse() || $this->getCommune() || $this->getCodePostal()) {
            return $this->getAdresse().' '.$this->getCodePostal().' '.$this->getCommune();
        }
        if ($localisation = $this->getLocalisation()) {
            if ($coordinates = $localisation->getCoordinates()) {
                return AdresseDataGouvApi::getAddrByCoordinates($coordinates->getLibelle())['label'];
            }
        }
        return null;
    }

    public function setEmplacement($emplacement){
        $this->emplacement = $emplacement;

        return $this;
    }

    public function getEmplacement() {

        return $this->emplacement;
    }

    public function setReference($reference){
        $this->reference = $reference;

        return $this;
    }

    public function getReference() {

        return $this->reference;
    }

    public function setConstructeurNom($constructeurNom){
        $this->constructeurNom = $constructeurNom;

        return $this;
    }

    public function getConstructeurNom() {

        return $this->constructeurNom;
    }

    public function setEtageMin($etageMin){
        $this->etageMin = $etageMin;

        return $this;
    }

    public function getEtageMin() {

        return $this->etageMin;
    }

    public function setEtageMax($etageMax){
        $this->etageMax = $etageMax;

        return $this;
    }

    public function getEtageMax() {

        return $this->etageMax;
    }

    public function setTelephoneDepannage($telephoneDepannage){
        $this->telephoneDepannage = $telephoneDepannage;

        return $this;
    }

    public function getTelephoneDepannage() {

        return $this->telephoneDepannage;
    }

    /**
     * Set statut
     *
     * @param string $statut
     * @return $this
     */
    public function setStatut($statut)
    {
        $this->statut = $statut;
        return $this;
    }

    /**
     * Get statut
     *
     * @return string $statut
     */
    public function getStatut()
    {
        return $this->statut;
    }

    /**
     * Get historique
     *
     * @return \Doctrine\Common\Collections\Collection $historique
     */
    public function getHistorique()
    {
        return array_reverse($this->historique->toArray());
    }

    public function addEvenement($date, $infos, $auteur) {
        $evenement = new Evenement();
        $evenement->setDate($date);
        $evenement->setCommentaire($infos);
        $evenement->setAuteur($auteur);

        $this->historique[] = $evenement;

        $this->setNombreFollowers($this->getSignalements()->count());

        return $evenement;
    }

    /**
     * Add historique
     *
     * @param AppBundle\Document\Evenement $historique
     */
    public function addHistorique(\AppBundle\Document\Evenement $historique)
    {
        $this->historique[] = $historique;
    }

    /**
     * Remove historique
     *
     * @param AppBundle\Document\Evenement $historique
     */
    public function removeHistorique(\AppBundle\Document\Evenement $historique)
    {
        $this->historique->removeElement($historique);
    }

    /**
     * Set dateConstruction
     *
     * @param date $dateConstruction
     * @return $this
     */
    public function setDateConstruction($dateConstruction)
    {
        $this->dateConstruction = $dateConstruction;
        return $this;
    }

    /**
     * Get dateConstruction
     *
     * @return date $dateConstruction
     */
    public function getDateConstruction()
    {
        return $this->dateConstruction;
    }

    /**
     * Set dateRenovation
     *
     * @param date $dateRenovation
     * @return $this
     */
    public function setDateRenovation($dateRenovation)
    {
        $this->dateRenovation = $dateRenovation;
        return $this;
    }

    /**
     * Get dateRenovation
     *
     * @return date $dateRenovation
     */
    public function getDateRenovation()
    {
        return $this->dateRenovation;
    }

    /**
     * Set syndicNom
     *
     * @param string $syndicNom
     * @return $this
     */
    public function setSyndicNom($syndicNom)
    {
        $this->syndicNom = $syndicNom;
        return $this;
    }

    /**
     * Get syndicNom
     *
     * @return string $syndicNom
     */
    public function getSyndicNom()
    {
        return $this->syndicNom;
    }

    /**
     * Set syndicContact
     *
     * @param string $syndicContact
     * @return $this
     */
    public function setSyndicContact($syndicContact)
    {
        $this->syndicContact = $syndicContact;
        return $this;
    }

    /**
     * Get syndicContact
     *
     * @return string $syndicContact
     */
    public function getSyndicContact()
    {
        return $this->syndicContact;
    }

    /**
     * Set bailleurNom
     *
     * @param string $bailleurNom
     * @return $this
     */
    public function setBailleurNom($bailleurNom)
    {
        $this->bailleurNom = $bailleurNom;
        return $this;
    }

    /**
     * Get bailleurNom
     *
     * @return string $bailleurNom
     */
    public function getBailleurNom()
    {
        return $this->bailleurNom;
    }

    /**
     * Set bailleurContact
     *
     * @param string $bailleurContact
     * @return $this
     */
    public function setBailleurContact($bailleurContact)
    {
        $this->bailleurContact = $bailleurContact;
        return $this;
    }

    /**
     * Get bailleurContact
     *
     * @return string $bailleurContact
     */
    public function getBailleurContact()
    {
        return $this->bailleurContact;
    }

    /**
     * Set ascensoristeNom
     *
     * @param string $ascensoristeNom
     * @return $this
     */
    public function setAscensoristeNom($ascensoristeNom)
    {
        $this->ascensoristeNom = $ascensoristeNom;
        return $this;
    }

    /**
     * Get ascensoristeNom
     *
     * @return string $ascensoristeNom
     */
    public function getAscensoristeNom()
    {
        return $this->ascensoristeNom;
    }

    /**
     * Set ascensoristeContact
     *
     * @param string $ascensoristeContact
     * @return $this
     */
    public function setAscensoristeContact($ascensoristeContact)
    {
        $this->ascensoristeContact = $ascensoristeContact;
        return $this;
    }

    /**
     * Get ascensoristeContact
     *
     * @return string $ascensoristeContact
     */
    public function getAscensoristeContact()
    {
        return $this->ascensoristeContact;
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
     * @MongoDB\PrePersist()
     * @MongoDB\PreUpdate()
     */
    public function preSave() {
        $this->updatedAt = new \DateTime();
    }

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
     * Set dateStatut
     *
     * @param date $dateStatut
     * @return $this
     */
    public function setDateStatut($dateStatut)
    {
        $this->dateStatut = $dateStatut;
        return $this;
    }

    /**
     * Get dateStatut
     *
     * @return date $dateStatut
     */
    public function getDateStatut()
    {
        return $this->dateStatut;
    }

    /**
     * Set nombreFollowers
     *
     * @param int $nombreFollowers
     * @return $this
     */
    public function setNombreFollowers($nombreFollowers)
    {
        $this->nombreFollowers = $nombreFollowers;
        return $this;
    }

    /**
     * Get nombreFollowers
     *
     * @return int $nombreFollowers
     */
    public function getNombreFollowers()
    {
        return $this->nombreFollowers;
    }

    /**
     * Get signalements
     *
     * @return \Doctrine\Common\Collections\Collection $signalements
     */
    public function getSignalements()
    {
        if(is_null($this->signalements)) {

            return new \Doctrine\Common\Collections\ArrayCollection();
        }
        return $this->signalements;
    }
}
