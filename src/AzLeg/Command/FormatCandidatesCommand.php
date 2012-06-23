<?php

namespace AzLeg\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FormatCandidatesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('format:candidates')
            ->setDescription('Imports a collection of books')
            ->setDefinition(array(
                // new InputArgument('path', InputArgument::REQUIRED, 'path to look for books'),
                new InputOption('source', null, InputOption::VALUE_REQUIRED, 'Location of the Candidates CSV', 'http://www.azsos.gov/election/2012/Primary/candidates.csv')
            ))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = trim(file_get_contents($input->getOption('source')));
        $candidates = array();
        $header = null;

        foreach (preg_split('/\r\n/', $source) as $line) {
            $row = str_getcsv($line);
            if (!$header) {
                $header = array_map('strtolower', $row);
            } else {
                $candidates[] = array_combine($header, $row);
            }
        }


        $districts = array();

        foreach ($candidates as $candidate) {
            if (preg_match('/State (Senator|Representative) - District No. (\d+)/', $candidate['officename'], $match)) {
                $district   = $match[2];
                $officeType = strtolower($match[1]);

                if (!isset($districts[$district])) {
                    $districts[$district] = array(
                        'district' => $district,
                        'senator' => array(),
                        'representative' => array()
                    );
                }

                $districts[$district][$officeType][] = $candidate;
            }
        }

        $output->writeln(json_encode($districts, JSON_PRETTY_PRINT));
    }
}