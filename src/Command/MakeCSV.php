<?php

namespace App\Command;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use App\Repository\PropertyRepository;
use App\Entity\Property;

class MakeCSV extends Command
{
    private $filename;
    private $file;
    private $date;

    /**
    *   php bin/console MaSuperAgence:csv
        Exemple avec date: php bin/console MaSuperAgence:csv --env=prod
    *   Ce cron fait la même chose que PropertiesListing sauf qu'ici j'utilise la fonction fputcsv()
    */
    protected function configure()
    {
        $this->setName('MaSuperAgence:csv')
            ->setDescription('Cron qui génère un fichier CSV');

        $this->columns = array('Surface', 'Pieces', 'Chambres', 'Etages', 'Chauffage', 'Ville', 'Code_Postal', 'Prix');
    }

    public function __construct(ParameterBagInterface $params, PropertyRepository $repository)
    {
        $this->params = $params;
        $this->repository = $repository;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_output = $output;
        $this->printLn(''.PHP_EOL);
        $this->printLn('#### Lancement du cron MaSuperAgence:csv ####'.PHP_EOL);

        $this->date = new \DateTime;
        $this->printLn('Date : '.$this->date->format('Y-m-d'));

        $data = array();

        if (!$this->createFile()) {
            die('Impossible d\'ouvrir le fichier '.$this->filename);
        } else {
            $this->printLn('Fichier : creation du fichier excel OK');
        }

        $properties = $this->repository->createQueryBuilder('p')
                    ->select('p')
                    ->orderBy('p.price', 'DESC')
                    ->getQuery()->getResult();

        $handle = fopen($this->filename, 'a');

        if ($handle) {
            fputcsv($handle, $this->columns, ';');
            foreach ($properties as $property) {
                $data['surface'] = $property->getSurface();
                $data['rooms'] = $property->getRooms();
                $data['bedrooms'] = $property->getBedrooms();
                $data['floor'] = $property->getFloor();
                $data['heat'] = $property->getHeatType();
                $data['city'] = $property->getCity();
                $data['postal_code'] = $property->getPostalCode();
                $data['price'] = $property->getFormattedPrice();
                fputcsv($handle, $data, ';');
            }
        }
        fclose($handle);
        $this->printLn('Sauvegarde du fichier CSV'.PHP_EOL);

        $this->printLn('#### Fin du cron MaSuperAgence:csv ####');
        return 0;
    }

    /********************************************************
    * This function creates the export file.
    *********************************************************/
    private function createFile(): bool
    {
        $dir = $this->params->get('listing');
        $this->filename = $dir.'exports/LISTING-DES-BIENS-'.$this->date->format('Y-m-d').'.csv';
        $fexist = 2;

        while (file_exists($this->filename)) {
            $this->filename = $dir.'exports/LISTING-DES-BIENS-'.$this->date->format('Y-m-d').'-'.$fexist.'.csv';
            $fexist++;
        }
        try {
            $this->file = fopen($this->filename, 'a');
        } catch (\Exception $ex) {
            echo $ex->getMessage().PHP_EOL;
            return false;
        }
        return true;
    }

    protected function printLn($line): void
    {
        if ($this->_output->isVerbose()) {
            $this->_output->writeln($line);
        }
    }
}
