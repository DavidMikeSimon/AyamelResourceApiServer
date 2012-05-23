<?php

namespace AC\GetID3Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ayamel\FilesystemBundle\Filesystem\FilesystemInterface;

class AnalyzeCommand extends ContainerAwareCommand {
	
    protected function configure() {
        $this
            ->setName('getid3:analyze')
            ->setDescription('Return stats on a file by using the getid3 library to analyze the file.')
            ->setDefinition(array(
                new InputArgument('address', InputArgument::REQUIRED, 'Absolute path to file to analyze with getid3.')
            ))
		;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filePath = $input->getArgument('path');
        $stats = $this->getContainer()->get('getid3')->analyze($filePath);
        
        if(!$stats) {
            throw new \RuntimeException(sprintf("Could not analyze file %s", $filePath));
        }
        
        var_dump($stats);
	}

}
