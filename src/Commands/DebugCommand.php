<?php declare(strict_types=1);

namespace SyncIt\Commands;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use SyncIt\Commands\Behaviours\ListConfiguredTasks;
use SyncIt\Commands\Behaviours\RunWrappedProcess;
use SyncIt\Models\SyncTask;

/**
 * Class DebugCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\DebugCommand
 */
class DebugCommand extends BaseCommand
{
    use ListConfiguredTasks;
    use RunWrappedProcess;

    protected function configure(): void
    {
        $this
            ->setName('debug')
            ->setDescription('Fetches the raw output from mutagen to aid debugging a failing task')
            ->addArgument('label', InputArgument::OPTIONAL, 'The task label(s) to inspect')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List available tasks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('list')) {
            $table = $this->buildTaskTableHelper($output);
            $table->render();

            return 0;
        }

        $tasks = $this->getConfig()->getTasks();
        $label = $input->getArgument('label');

        if (!$label) {
            $label = $this->tools()->choose('Which task do you want to debug? ', $tasks->keys()->toArray());
        }

        $this->getMutagen()->getSessions()->map($tasks);

        /** @var SyncTask $task */
        if (null === $task = $tasks->get($label)) {
            throw new InvalidArgumentException(sprintf('Task with label "%s" not found in current project', $label));
        }

        $this->tools()->info('fetching debug data for <info>%s</info>', $label);
        $proc = Process::fromShellCommandline(sprintf('mutagen sync list -l %s', $task->getSession()->getId()));
        $proc->run();

        $output->writeln($proc->getOutput());

        return 0;
    }
}
