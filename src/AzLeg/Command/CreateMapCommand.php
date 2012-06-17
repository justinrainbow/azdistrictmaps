<?php

namespace AzLeg\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateMapCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('create')
            ->setDescription('Imports a collection of books')
            ->setDefinition(array(
                // new InputArgument('path', InputArgument::REQUIRED, 'path to look for books'),
            ))
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootDir = $this->getProjectDirectory();
        $app = $this->getSilexApplication();

        $fh = fopen($rootDir . '/candidates.csv', 'r');
        $candidates = array();
        $header = null;

        while (($row = fgetcsv($fh))) {
            if (!$header) {
                $header = array_map('strtolower', $row);
            } else {
                $candidates[] = array_combine($header, $row);
            }
        }

        fclose($fh);



        $districts = array();

        foreach ($candidates as $candidate) {
            if (preg_match('/State (Senator|Representative) - District No. (\d+)/', $candidate['officename'], $match)) {
                $district   = $match[2];
                $officeType = strtolower($match[1]);

                if (!isset($districts[$district])) {
                    $districts[$district] = array(
                        'senator' => array(),
                        'representative' => array()
                    );
                }

                $districts[$district][$officeType][] = $candidate;
            }
        }



        $dom = new \DOMDocument();
        $dom->loadXML(file_get_contents($rootDir . '/input.kml'));

        $kml = simplexml_import_dom($dom);

        function extractData($str)
        {
            if (preg_match_all('~<tr><td>([^<]+)</td><td>([^<]+)</td></tr>~i', $str, $matches)) {
                return array_combine($matches[1], $matches[2]);
            }

            return array();
        }

        foreach ($kml->Document->Folder->Placemark as $placemark) {
            $info = extractData((string) $placemark->description);

            if (!count($info)) {
                continue;
            }

            if (!isset($districts[(int) $info['District']])) {
                continue;
            }

            $district = $districts[(int) $info['District']];

            $description = $app['twig']->render('description.html.twig', array(
                'district' => $district,
                'info' => $info
            ));

            $placemark->description = htmlentities($description);
        }



        $kml->asXML($rootDir . '/doc.kml');


    }
}