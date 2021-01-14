<?php

namespace App\Service;

abstract class XmlWriterAbstract
{
    /**
     *
     * @var \DOMDocument
     */
    protected $domDocument;

    /**
     *
     * @var string
     */
    protected $fileName;
    
    /**
     *
     * @var string
     */
    protected $xsdFile;

    public function __construct()
    {
        $this->domDocument = new \DOMDocument('1.0', 'UTF-8');
        $this->domDocument->formatOutput = false;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function setXsdFile($xsdFile)
    {
        $this->xsdFile = $xsdFile;
    }

    public function save($destination)
    {
        if (isset($this->fileName)) {
            $this->fileName = $this->checkAndGetArchiveName($destination, $this->fileName);
            $this->domDocument->save($destination.$this->fileName); //Sauvegarde le XML dans un fichier ainsi dans le répertoire désiré
        } else {
            throw new \Exception('Impossible de sauvegarder car il n\'y a pas de nom de fichier');
        }
    }

    public function validate()
    {
        if (isset($this->xsdFile) && file_exists($this->fileName)) {
            $tempDom = new \DOMDocument();
            $tempDom->load($this->fileName); //Charge du XML depuis un fichier

            if (!$tempDom->schemaValidate($this->xsdFile)) {
                $errors = libxml_get_errors();
                throw new \Exception('xml généré non valide : '.$this->domDocument->saveXml(), $errors, $this->fileName);
            }
        }
        libxml_clear_errors();
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function saveXml() 
    {
        return $this->domDocument->saveXML($this->domDocument->documentElement); //Sauvegarde l'arbre interne XML dans une chaîne de caractères et retournera l'élément correspondant à l'erreur
    }

    public function eraseDocument()
    {
        while ($this->domDocument->hasChildNodes()) {
            $this->domDocument->removeChild($this->domDocument->firstChild);
        }
    }

    public function checkAndGetArchiveName($destination, $fileName)
    {
        $i = 1;
        while (file_exists($destination.$fileName)) {
            $fileName = $i."_".$fileName;
            $i++;
        }

        return $fileName;
    }

    abstract public function setData($data);
}
