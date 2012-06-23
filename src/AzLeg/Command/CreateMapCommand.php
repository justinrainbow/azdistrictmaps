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
                new InputArgument('data', InputArgument::IS_ARRAY, 'List of JSON files'),
                new InputOption('kmz', null, InputOption::VALUE_REQUIRED, 'Location of the original KMZ', 'http://www.azredistricting.org/Maps/Final-Maps/Legislative/Maps/Final_Legislative_districts.kmz'),
            ))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootDir = $this->getProjectDirectory();
        $app = $this->getSilexApplication();

        $districts = array();

        foreach ($input->getArgument('data') as $file) {
            $data = json_decode(file_get_contents($file), true);

            foreach ($data as $record) {
                if (!isset($districts[$record['district']])) {
                    $districts[$record['district']] = $record;
                } else {
                    $districts[$record['district']] = array_merge($districts[$record['district']], $record);
                }
            }
        }

        $tempName = tempnam(sys_get_temp_dir(), 'kmz-');
        file_put_contents($tempName, file_get_contents($input->getOption('kmz')));

        $zip = new \ZipArchive();
        if ($zip->open($tempName) !== true) {
            $output->writeln('<error>Unable to open downloaded KMZ file.</error>');
            unlink($tempName);

            return;
        }

        $kml = simplexml_load_string($zip->getFromName('doc.kml'));

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

            $placemark->description = $description;
        }



        $kml->asXML($rootDir . '/doc.kml');

        unlink($tempName);
    }
}