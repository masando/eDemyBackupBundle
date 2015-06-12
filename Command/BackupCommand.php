<?php
namespace eDemy\BackupBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Process\Process;

class BackupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('edemy:backup')
            ->setDescription('backup mysql and files')
            //->addArgument(
            //    'cmd',
            //    InputArgument::REQUIRED,
            //    'Command Name?'
            //)
            ->addOption(
               'dir',
               null,
               InputOption::VALUE_REQUIRED,
               'output directory to save the backup'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $dbName = $this->getContainer()->getParameter('database_name');
        $date = date('Y-m-d-H-i-s');
        $this->directory = "app/backups/backup_" . $date;
        $this->sqlFile = $this->directory . '/' . 'backup_' . $dbName . '_' . $date . '.sql';
        $this->tarFile = 'app/backups/' . 'backup_' . $dbName . '_' . $date . '.tar.gz';

        $p =  new Process('mkdir -p ' . $this->directory);
        if(!$p->run()) {
            $time = new \DateTime();
            if($this->mysqldump($output)) {
                $this->output->writeln("<info>Dumped in $this->sqlFile in ". $time->diff($time = new \DateTime())->format('%s seconds').'</info>');
                $this->output->writeln('<info>MISSION ACCOMPLISHED</info>');
            } else {
                $this->output->writeln('<error>Nasty error happened :\'-(</error>');
                if ($this->failingProcess instanceOf Process) {
                    $this->output->writeln('<error>%s</error>', $this->failingProcess->getErrorOutput());
                }
            }
            $this->copy();
            $this->archive();
            $p =  new Process('rm -r ' . $this->directory);
            $p->run();
        }
    }

    protected function mysqldump(OutputInterface $output)
    {
        $dbName = $this->getContainer()->getParameter('database_name');
        $dbPort = $this->getContainer()->getParameter('database_port');
        if($dbPort == null) $dbPort = 3306;
        $dbUser = $this->getContainer()->getParameter('database_user');
        $dbPwd = $this->getContainer()->getParameter('database_password');
        $cmd = sprintf('mysqldump -h 127.0.0.1 -u %s --password=%s -P %s %s > %s', $dbUser, $dbPwd, $dbPort, $dbName, $this->sqlFile);
        //die(var_dump($cmd));
        $mysqldump =  new Process($cmd);
        $mysqldump->run();
        if ($mysqldump->isSuccessful()) {
            $output->writeln(sprintf('<info>Database %s dumped succesfully</info>', $dbName));
            return true;
        }
        $this->failingProcess = $mysqldump;
        return false;
    }

    protected function copy()
    {
        $p =  new Process('mkdir -p ' . $this->directory . '/web/images');
        $p->run();
        $p =  new Process('cp -r web/images ' . $this->directory . '/web');
        $p->run();
        if ($p->isSuccessful()) {
            $this->output->writeln('<info>Files copied</info>');
            return true;
        }
        $this->failingProcess = $p;
        return false;
    }

    protected function archive()
    {
        $p =  new Process('tar cfz ' . $this->tarFile . ' ' . $this->directory);
        $p->run();
        if ($p->isSuccessful()) {
            $this->output->writeln(sprintf('<info>Backup %s dumped succesfully</info>', $this->tarFile));
            return true;
        }
        $this->failingProcess = $p;
        return false;
    }
}
