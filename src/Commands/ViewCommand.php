<?php declare(strict_types=1);

namespace SyncIt\Commands;

use InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SyncIt\Commands\Behaviours\ListConfiguredTasks;
use SyncIt\Commands\Behaviours\RunWrappedProcess;
use SyncIt\Models\SyncTask;

/**
 * Class ViewCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\ViewCommand
 */
class ViewCommand extends BaseCommand
{

    use ListConfiguredTasks;
    use RunWrappedProcess;

    protected function configure()
    {
        $this
            ->setName('view')
            ->setDescription('View a tasks configuration including current status')
            ->addArgument('label', InputArgument::OPTIONAL, 'The task label(s) to inspect')
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

        $tasks = $this->getConfig()->getTasks();
        $label = $input->getArgument('label');

        if (!$label) {
            $label = $this->tools()->choose('View the details of which task? ', $tasks->keys()->toArray());
        }

        $this->getMutagen()->getSessions()->map($tasks);

        /** @var SyncTask $task */
        if (null === $task = $tasks->get($label)) {
            throw new InvalidArgumentException(sprintf('Task with label "%s" not found in current project', $label));
        }

        $output->writeln('');

        $summary = new Table($output);
        $summary
            ->setHeaderTitle(
                sprintf(
                    'Sync-It -- Status for <fg=blue;bg=white;options=bold>%s</> -- Mutagen (v%s)',
                    $task->getLabel(),
                    $this->getMutagen()->getVersion()
                )
            )
            ->setHeaders(['', 'Value'])
            ->setColumnWidth(0, 20)
            ->setColumnWidth(1, 60)
            ->setColumnMaxWidth(1, 60)
            ->addRow(['<comment>Task (label)</comment>', $task->getLabel()])
            ->addRow(['<comment>Source (alpha)</comment>', $task->getSource()])
            ->addRow(['<comment>Target (beta)</comment>', $task->getTarget()])
            ->addRow(['<comment>Using Common</comment>', $task->shouldUseCommon() ? 'Yes' : 'No'])
            ->addRow(['<comment>Groups</comment>', $task->getGroups()->implode(', ')])
            ->addRow(['<comment>Running</comment>', $task->isRunning() ? '<info>Yes</info>' : '<warn>No</>'])
            ->addRow(['<comment>Session</comment>', $task->getSession() ? $task->getSession()->getId() : '--'])
        ;
        $summary->render();

        $options = new Table($output);
        $options
            ->setHeaderTitle('Options')
            ->setHeaders(['Option', 'Value'])
            ->setColumnWidth(0, 30)
            ->setColumnMaxWidth(0, 30)
            ->setColumnWidth(1, 50)
            ->setColumnMaxWidth(1, 50)
            ->addRows($task->getOptions()->map(function ($value, $key) { return [$key, $value];})->toArray())
        ;
        $options->render();

        $ignore = new Table($output);
        $ignore
            ->setHeaderTitle('Ignore Rules')
            ->setHeaders(['Rule'])
            ->setColumnWidth(0, 83)
            ->setColumnMaxWidth(0, 83)
            ->addRows($task->getIgnore()->map(function ($value, $key) { return [$value];})->toArray())
        ;
        $ignore->render();

        $output->writeln('');
        if ($task->getSession()) {
            $output->writeln(sprintf('Run: <comment>debug %s</comment> for debug data', $task->getSession()->getId()));
        }
        $output->writeln('');

        return 0;
    }
}
