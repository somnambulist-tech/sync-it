<?php

declare(strict_types=1);

namespace SyncIt\Commands;

use Somnambulist\Collection\Collection;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use SyncIt\Commands\Behaviours\ListConfiguredTasks;
use SyncIt\Commands\Behaviours\RunWrappedProcess;
use SyncIt\Models\SyncTask;

/**
 * Class StopCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\StopCommand
 */
class StopCommand extends BaseCommand
{

    use ListConfiguredTasks;
    use RunWrappedProcess;

    protected function configure()
    {
        $this
            ->setName('stop')
            ->setDescription('Stops all configured mutagen sync tasks')
            ->addOption('label', 'l', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'The task label(s) to stop (mutagen >0.9.0)', [])
            ->addOption('list', null, InputOption::VALUE_NONE, 'List available tasks')
            ->setHelp(<<<'HELP'
Stop a specified, or all, sync tasks as defined in the current projects config
file.

To stop all tasks run:

  <info>php %command.full_name%</info>

To stop an individual task run:

  <info>php %command.full_name% --label=task</info>

To stop multiple tasks use multiple --label calls:

  <info>php %command.full_name% --label=task1 --label=task2</info>

List the available tasks:

  <info>php %command.full_name% --list</info>

If a task will not stop, turn on debugging to get the output from the
call to mutagen:

  <info>php %command.full_name% --label=task -vvv</info>

HELP
            )
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

        $tasks  = $this->getMutagen()->getSessions()->map($this->getConfig()->getTasks());
        $labels = (array)$input->getOption('label');

        if (count($labels) < 1) {
            $labels = $tasks->keys()->toArray();
        }
        if (!count($input->getOption('label'))) {
            if (!is_array($labels = $this->promptForLabelsToStop($input, $output, $tasks, $labels))) {
                return $labels;
            }
        }

        $this->stopSelectedTasks($output, $tasks, $labels);

        return 0;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Collection      $tasks
     * @param array           $labels
     *
     * @return array|int
     */
    private function promptForLabelsToStop(InputInterface $input, OutputInterface $output, Collection $tasks, array $labels)
    {
        /** @var QuestionHelper $helper */
        $helper   = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Which task would you like to stop? ',
            $tasks->keys()->add('All')->add( 'All & Daemon')->toArray()
        );

        $label = strtolower((string)$helper->ask($input, $output, $question));

        if ('all & daemon' === $label) {
            $output->writeln('Stopping all tasks and the mutagen daemon...');
            if ($this->getMutagen()->stop()) {
                $output->writeln('<fg=black;bg=green> STOP </> stopped all sessions and daemon successfully');

                return 0;
            }

            $output->writeln('<fg=white;bg=red> ERR </> failed to stop processes! Check mutagen status');
            return 1;

        } elseif ('all' !== $label) {
            $labels = [$label];
        }

        return $labels;
    }

    /**
     * @param OutputInterface $output
     * @param Collection      $tasks
     * @param array|string[]  $labels
     */
    private function stopSelectedTasks(OutputInterface $output, Collection $tasks, array $labels): void
    {
        $output->writeln(sprintf('Stopping <info>%s</info> sync tasks', count($labels)));

        $tasks->only($labels)->each(function (SyncTask $task) use ($output) {
            if ($task->isRunning()) {
                return $this->stopTask($output, $task);
            }

            $output->writeln(sprintf('<fg=white;bg=blue> STOP </> task <fg=yellow>"%s"</> is not running', $task->getLabel()));

            return true;
        });
    }

    /**
     * @param OutputInterface $output
     * @param SyncTask        $task
     *
     * @return bool
     */
    private function stopTask(OutputInterface $output, SyncTask $task): bool
    {
        $command = new Collection(['mutagen', 'terminate']);

        if ($this->getMutagen()->hasLabels()) {
            $command->add(sprintf('--label-selector=%s', $task->getLabel()));
        } else {
            $command->add($task->getSession()->getId());
        }

        $proc = $this->runProcessViaHelper($output, $command);

        if ($proc->isSuccessful()) {
            $output->writeln(
                sprintf('<fg=black;bg=green> STOP </> stopped session for <fg=yellow>"%s"</> successfully', $task->getLabel())
            );
        } else {
            $output->writeln(
                sprintf('<error> ERR </error> failed to start session for <fg=yellow>"%s"</>; check options', $task->getLabel())
            );
        }

        return true;
    }
}
