<?php

declare(strict_types=1);

namespace SyncIt\Commands;

use InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use SyncIt\Commands\Behaviours\ListConfiguredTasks;
use SyncIt\Commands\Behaviours\RunWrappedProcess;
use SyncIt\Models\SyncTask;

/**
 * Class MonitorCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\MonitorCommand
 */
class MonitorCommand extends BaseCommand
{

    use ListConfiguredTasks;
    use RunWrappedProcess;

    protected function configure()
    {
        $this
            ->setName('monitor')
            ->setDescription('Monitor the chosen task via mutagen monitor')
            ->addArgument('label', InputArgument::OPTIONAL, 'The task (label) to monitor')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List available tasks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('list')) {
            $table = $this->buildTaskTableHelper($output);
            $table->render();

            return 0;
        }

        $this->getMutagen()->assertDaemonIsRunning($input, $output, false);

        $tasks = $this->getConfig()->getTasks();
        $label = $input->getArgument('label');

        if (!$label) {
            /** @var QuestionHelper $helper */
            $helper   = $this->getHelper('question');
            $question = new ChoiceQuestion('Which task would you like to monitor? ', $tasks->keys()->toArray());

            $label = $helper->ask($input, $output, $question);
        }

        $this->getMutagen()->getSessions()->map($tasks);

        /** @var SyncTask $task */
        if (null === $task = $tasks->get($label)) {
            throw new InvalidArgumentException(sprintf('Task with label "%s" not found in current project', $label));
        }
        if (!$task->isRunning()) {
            throw new InvalidArgumentException(sprintf('The task "%s" is not running', $label));
        }

        $output->writeln('Starting monitor, use <info>Ctrl+C</info> to stop');

        /*
         * monitor provides updated output, so stream it straight back out.
         */
        passthru('mutagen monitor ' . $task->getSession()->getId());

        return 0;
    }
}
