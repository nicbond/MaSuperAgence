<?php

namespace App\Command;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;

class SplitXML extends Command
{
    /**
     * surcharge de la configuration pour ce cron
     * php bin/console MaSuperAgence:split -vvv
     */
    protected function configure()
    {
        $this
            ->setName('MaSuperAgence:split')
            ->setDescription('Cron permettant de spitter des xml');
    }

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        parent::__construct();
    }

    /**
     * exécution du cron
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_output = $output;
        $this->printLn(''.PHP_EOL);
        $this->printLn('#### Lancement du cron splitXML ####'.PHP_EOL);
        libxml_use_internal_errors(true); //Désactive le rapport d'erreur libxml et les stock pour lecture ultérieure

        $this->printLn('-- process split --');
        $dateToday = new \DateTime();
        $treatmentDate = $dateToday->format('Ymd');

        $oldFile = $this->params->get('listing');
        $directory = $oldFile.'XML/old/'.$treatmentDate; //Ce répertoire servira à classer le xml original
        $this->checkAndCreateDirectory($directory);
        $directoryTreatment = $this->params->get('listing').'XML/'.$treatmentDate.'/'; //Ce répertoire servira à traitrer/ranger les XML splittés

        $file = $this->params->get('split');
        $basename = basename($file);
        $search = 'pacs.008.001.02';

        $content = file_get_contents($file);

        if ($content) {
            if (strpos($content, 'xsd:'.$search)!==false) {
                $value = preg_split('/\<\?xml version="1.0" encoding="UTF-8"\?\>/', $content, -1, PREG_SPLIT_NO_EMPTY);

                $i = 0;
                $done = 0;

                if (count($value) > 1) {
                    $this->checkAndCreateDirectory($directoryTreatment);

                    foreach ($value as $split) {
                        $i = 1;
                        if ($split) {
                            $doc = new \DOMDocument('1.0', 'UTF-8');
                            $doc->formatOutput = false;
                            $doc->loadXML($split);

                            $filename = $this->checkAndGetArchiveName($directoryTreatment, $file);

                            if ($doc->save($filename)) {
                                $done++;
                            }
                        }
                    }

                    if ($done == count($value)) {
                        $return = true;
                    } else {
                        $return = false;
                    }
                }
            } else {
                throw new \Exception('impossible de récupérer le contenu du fichier xml suivant : '.$file);
            }
        }

        $this->printLn($done.' fichiers ont ete splittes');
        rename($file, $directory.'/'.$basename);
        $this->printLn('Copie du xlm original dans le repertoire old'.PHP_EOL);
        $this->printLn('#### Fin du cron splitXML ####');
        return 0;
    }

    protected function printLn($line): void
    {
        if ($this->_output->isVerbose()) {
            $this->_output->writeln($line);
        }
    }

    private function checkAndCreateDirectory($directory): void
    {
        if (file_exists($directory)) {
            $this->printLn('Le repertoire existe deja');
        } else {
            if (!mkdir($directory, 0777, true)) {
                die('Echec lors de la creation des repertoires...');
            } else {
                $this->printLn('Creation du repertoire OK');
            }
        }
    }

    private function checkAndGetArchiveName($directory, $filename): string
    {
        $i = 1;

        $basename = basename($filename);
        $filename = $directory.$basename; //Rajout du répertoire ou l'on souhaite enregistrer le ficher plus tard

        while (file_exists($filename)) {
            $filename = explode('.xml', $filename);
            $arrayToString = implode("", $filename);
            $basenameFilename = basename($arrayToString);

            $filename = $directory.$basenameFilename."_".$i.".xml";
            $i++;
        }

        return $filename;
    }
}
