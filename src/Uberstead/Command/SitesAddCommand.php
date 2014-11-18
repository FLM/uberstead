<?php
namespace Uberstead\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;

class SitesAddCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('sites:add')
            ->setDescription('Add a new site config')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->getSiteManager()->addSite($input, $output, $this->getHelper('question'));
        $this->getContainer()->getProvisionService()->reload($input, $output, $this);
        $this->getContainer()->getProvisionService()->provision($input, $output, $this);
    }
}
