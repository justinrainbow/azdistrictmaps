<?php

namespace AzLeg\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateDistrictDataCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('create:districts')
            ->setDescription('Imports a collection of books')
            ->setDefinition(array(
                new InputArgument('output', InputArgument::OPTIONAL, 'Save to this file'),
                new InputOption('kmz', null, InputOption::VALUE_REQUIRED, 'Location of the original KMZ', 'http://www.azredistricting.org/Maps/Final-Maps/Legislative/Maps/Final_Legislative_districts.kmz'),
            ))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootDir = $this->getProjectDirectory();
        $app = $this->getSilexApplication();

        $districts = array();

        $tempName = tempnam(sys_get_temp_dir(), 'kmz-');
        file_put_contents($tempName, file_get_contents($input->getOption('kmz')));

        $zip = new \ZipArchive();
        if ($zip->open($tempName) !== true) {
            $output->writeln('<error>Unable to open downloaded KMZ file.</error>');
            unlink($tempName);

            return;
        }

        $kml = simplexml_load_string($zip->getFromName('doc.kml'));

        foreach ($kml->Document->Folder->Placemark as $placemark) {
            if (array() !== $data = $this->convert($placemark)) {
                $districts[] = $data;
            }
        }

        unlink($tempName);

        $out = json_encode($districts, JSON_PRETTY_PRINT);

        if ($file = $input->getArgument('output')) {
            file_put_contents($file, $out);
        } else {
            $output->writeln($out);
        }
    }

    private function convert(\SimpleXMLElement $element)
    {
        $info = $this->extractData((string) $element->description);

        if (!count($info)) {
            return array();
        }

        $data = array(
            'district' => (int) $info['District'],
            'coordinates' => preg_split('~\s+~', (string) $element->Polygon->outerBoundaryIs->LinearRing->coordinates)
        );

        return $data;
    }

    private function extractData($str)
    {
        if (preg_match_all('~<tr><td>([^<]+)</td><td>([^<]+)</td></tr>~i', $str, $matches)) {
            return array_combine($matches[1], $matches[2]);
        }

        return array();
    }
}