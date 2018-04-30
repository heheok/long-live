<?php
/**
 * Created by PhpStorm.
 * User: harun.akgun
 * Date: 25.02.2016
 * Time: 14:06
 */

namespace LongLiveBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCheckCommand extends ContainerAwareCommand {
    protected function configure()
    {
        $this
            ->setName('status:check')
            ->setDescription('Main command to be called with cron. If "all" option is not set, you have to supply a ruleName to run.')
            ->addArgument(
                'ruleName',
                InputArgument::OPTIONAL,
                'Which rule you want to run?'
            )
            ->addOption(
                'add',
                'a',
                InputOption::VALUE_NONE,
                'Add A new rule with step by step wizard.'
            )
            ->addOption(
                'remove',
                'r',
                InputOption::VALUE_NONE,
                'Remove a rule by name'
            )
            ->addOption(
                'all',
                'A',
                InputOption::VALUE_NONE,
                'If set, all rules will be run one after another.'
            )
            ->addOption(
                'list',
                'l',
                InputOption::VALUE_NONE,
                'Lists all the rules.'
            )
            ->addOption(
                'generate',
                'g',
                InputOption::VALUE_NONE,
                'Generate test data for every entry on rule database <error>(do not use in production)</error>'
            )
            ->addOption(
                'info',
                'i',
                InputOption::VALUE_NONE,
                'Gives information about the bundle.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $container = $this->getContainer();
        $statusService = $container->get('longlive.statuscheck');
        $helper = $this->getHelper('question');
        $ruleName = $input->getArgument('ruleName');

        if ($input->getOption('add')) {

            $statusService->addRule($input,$output,$helper);
            die();
        }
        if ($input->getOption('remove')){
            $statusService->removeRule($input,$output,$ruleName,$helper);
            die();
        }
        if ($input->getOption('info')){
            $statusService->getInfo($output);
            die();
        }
        if ( $input->getOption('list')) {
            $statusService->getList($output);
            die();
        }
        if ($input->getOption('all') ) {
            $statusService->runAll($output);
            die();
        }
        if ($input->getOption('generate')){
            $statusService->generateData($output);
            die();
        }


        if ($ruleName) {
            $statusService->runCheck($output,$ruleName);
            die();
        } else {
            $output->writeln("You have to supply a ruleID in order to run this command.");
            die();
        }
    }

}