<?php

namespace App\Command;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use App\Repository\PropertyRepository;
use App\Entity\Property;

class PropertiesListing extends Command
{
    private $filename;
    private $file;
    private $date;

    /**
    *   php bin/console MaSuperAgence:listing
        Exemple avec date: php bin/console MaSuperAgence:listing 2021-01-01 2021-01-19 --env=prod
    */
    protected function configure()
    {
        $this->setName('MaSuperAgence:listing')
            ->setDescription('Cron qui génère un listing des biens')
            ->addArgument(
                'debut',
                \Symfony\Component\Console\Input\InputArgument::OPTIONAL,
                'argument permettant de savoir la date de debut'
            )
            ->addArgument(
                'fin',
                \Symfony\Component\Console\Input\InputArgument::OPTIONAL,
                'argument permettant de savoir la date de fin'
            );
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
        $this->printLn('#### Lancement du cron MaSuperAgence:listing ####'.PHP_EOL);

        $date_Debut = $input->getArgument('debut');
        $date_Fin   = $input->getArgument('fin');

        $yesteday = new \DateTime();
        $yesteday->modify('-1 day');

        if (!isset($date_Debut) && !isset($date_Fin)) {
            $date_Debut = 'yesterday';
            $date_Fin   = 'yesterday';
            $isCronMode = true;
        } else {
            $isCronMode = false;
            $yesteday   = new \DateTime($date_Debut);
        }

        $this->date =   new \DateTime($date_Debut);
        $dateFin    =   new \DateTime($date_Fin);

        if (!$this->createFile()) {
            die('Impossible d\'ouvrir le fichier '.$this->filename);
        } else {
            $this->printLn('Fichier : creation du fichier excel OK');
        }

        $this->appendFile(
            'Surface',
            'Pieces',
            'Chambres',
            'Etages',
            'Chauffage',
            'Ville',
            'Code Postal',
            'Prix'
        );

        $this->printLn('Date : '.$this->date->format('Y-m-d').PHP_EOL);
        $date=$this->date;

        $properties = $this->repository->createQueryBuilder('p')
                    ->select('p')
                    ->orderBy('p.price', 'DESC')
                    ->getQuery()->getResult();

        foreach ($properties as $property) {
            $surface = $property->getSurface();
            $rooms = $property->getRooms();
            $bedrooms = $property->getBedrooms();
            $floor = $property->getFloor();
            $heat = $property->getHeatType();
            $city = $property->getCity();
            $postal_code = $property->getPostalCode();
            $price = $property->getFormattedPrice();

            $this->appendFile(
                $surface,
                $rooms,
                $bedrooms,
                $floor,
                $heat,
                $city,
                $postal_code,
                $price
            );
        }

        $this->closeFile();
        $this->printLn('#### Fin du cron MaSuperAgence:listing ####');
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

    /********************************************************
    * This function fills the export file with entries.
    *********************************************************/
    private function appendFile($champs1, $champs2, $champs3, $champs4, $champs5, $champs6, $champs7, $champs8)
    {
        try {
            fwrite($this->file, "$champs1;$champs2;$champs3;$champs4;$champs5;$champs6;$champs7;$champs8 \n");
        } catch (\Exception $ex) {
            echo $ex->getMessage().PHP_EOL;
        }
    }

    private function closeFile(): void
    {
        fclose($this->file);
    }

    protected function printLn($line): void
    {
        if ($this->_output->isVerbose()) {
            $this->_output->writeln($line);
        }
    }
}
