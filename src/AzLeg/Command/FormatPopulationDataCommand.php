<?php

namespace AzLeg\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FormatPopulationDataCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('format:population-data')
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
        $data = file_get_contents('php://stdin');

        $records = json_decode($data, true);
        $formatted = array();

        foreach ($records as $row) {
            $district   = intval($row['District']);
            $population = intval(str_replace(',', '', $row['Population']));
            $votingAgePopulation = intval(str_replace(',', '', $row['Voting Age Pop.']));
            $deviation = array(
                'total'      => intval(str_replace(',', '', $row['Deviation from Ideal Population'])),
                'percentage' => floatval($row['Deviation from Ideal Population (%)']),
            );

            $data = array(
                'district' => $district,
                'deviation' => $deviation,
                'population' => array(
                    'total' => $population,
                    'segments' => array(),
                ),
                'votingPopulation' => array(
                    'total' => $votingAgePopulation,
                    'segments' => array()
                )
            );

            unset($row['District'], $row['Population'], $row['Voting Age Pop.'], $row['Deviation from Ideal Population'], $row['Deviation from Ideal Population (%)']);

            $segmentNames  = array_chunk(array_keys($row), 2);
            $segmentValues = array_chunk($row, 2);

            foreach ($segmentNames as $names) {
                $segment = array_shift($segmentValues);
                $name    = trim(strtr($names[0], array(
                    'Non Hispanic (NH) '   => '',
                    'Non-Hispanic (NH) '   => '',
                    'Hispanic Population'  => 'Hispanic',
                    'Multi-Race and Other' => 'Other',
                )));
                $name    = preg_replace('~^NH ~', '', $name);

                $stats = array(
                    'total'      => intval(str_replace(',', '', $segment[0])),
                    'percentage' => floatval($segment[1]),
                );

                if (preg_match('~Voting Age Pop~i', $name)) {
                    $name = trim(strtr($name, array(
                        'Voting Age Pop.' => '',
                    )));

                    $name = strtolower(strtr($name, array(
                        ' ' => '-'
                    )));

                    $data['votingPopulation']['segments'][$name] = $stats;
                } else {
                    $name = strtolower(strtr($name, array(
                        ' ' => '-'
                    )));

                    $data['population']['segments'][$name] = $stats;
                }
            }

            $formatted[$data['district']] = $data;
        }

        $output->writeln(json_encode($formatted, JSON_PRETTY_PRINT));
    }
}