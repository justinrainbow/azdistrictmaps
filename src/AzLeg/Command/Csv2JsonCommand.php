<?php

namespace AzLeg\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Csv2JsonCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('csv2json')
            ->setDescription('Imports a collection of books')
            ->setDefinition(array(
                new InputArgument('file', InputArgument::REQUIRED, 'path to look for books'),
            ))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fh = fopen($input->getArgument('file'), 'r');
        $data = array();
        $header = null;

        while (($row = fgetcsv($fh))) {
            if (!$header) {
                $header = array_map('strtolower', $row);
            } else {
                $data[] = array_combine($header, $row);
            }
        }

        fclose($fh);

        echo json_encode($data);
    }
}