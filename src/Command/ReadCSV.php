<?php

namespace App\Command;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;

class ReadCSV extends Command
{
    private $filename;
    private $file;
    private $date;

    /**
    *   php bin/console MaSuperAgence:ReadCSV
        Exemple avec date: php bin/console MaSuperAgence:ReadCSV --env=prod
    */
    protected function configure()
    {
        $this->setName('MaSuperAgence:ReadCSV')
            ->setDescription('Cron qui lit un fichier CSV');
    }

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_output = $output;
        $this->printLn(''.PHP_EOL);
        $this->printLn('#### Lancement du cron MaSuperAgence:ReadCSV ####'.PHP_EOL);

        $this->date = new \DateTime;
        $this->printLn('Date : '.$this->date->format('Y-m-d'));

        $filepath = $this->params->get('listing').'exports/LISTING-DES-BIENS-2021-01-19.csv';
        $lines = $this->convertToArray($filepath);

        if ($lines) {
            foreach ($lines as $line) {
                $surface = trim($line[0]);
                $rooms = trim($line[1]);
                $bedrooms = trim($line[2]);
                $floor = trim($line[3]);
                $heat = trim($line[4]);
                $city = trim($line[5]);
                $postal_code = trim($line[6]);
                $price = trim($line[7]);
                $this->printLn($surface.' | '.$rooms.' | '.$bedrooms.' | '.$floor.' | '.$heat.' | '.$city.' | '.$postal_code.' | '.$price);
            }
        }
        $this->printLn(''.PHP_EOL);
        $this->printLn('#### Fin du cron MaSuperAgence:csv ####');
        return 0;
    }

    private function convertToArray($filepath): array 
    {
        $lines = array();
        $line = 0;

        if (($handle = fopen($filepath, "r")) !== FALSE) {
            $firstLine = true;
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                $column = count($data);

                if ($firstLine) {
                    $firstLine = false;
                    continue;
                }
                $line++;
                $lines[] = $data;
            }
            $this->printLn('Le CSV comprend '.$column.' colonnes et '.$line.' lignes');
        }
        return $lines;
    }

    protected function printLn($line): void
    {
        if ($this->_output->isVerbose()) {
            $this->_output->writeln($line);
        }
    }
}