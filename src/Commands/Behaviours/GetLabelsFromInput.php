<?php declare(strict_types=1);

namespace SyncIt\Commands\Behaviours;

use Somnambulist\Components\Collection\MutableCollection as Collection;
use Symfony\Component\Console\Input\InputInterface;
use SyncIt\Models\SyncTask;
use function count;
use function trim;

/**
 * Trait GetLabelsFromInput
 *
 * @package    SyncIt\Commands\Behaviours
 * @subpackage SyncIt\Commands\Behaviours\GetLabelsFromInput
 */
trait GetLabelsFromInput
{
    private function getLabelsFromInput(InputInterface $input, Collection $tasks): array
    {
        if (!$labels = $input->getOption('label')) {
            if (!$labels = $input->getArgument('label')) {
                return [];
            }
        }

        if (count($labels) === 1) {
            $label = trim($labels[0]);

            if ('all' == $label) {
                $labels = $tasks->keys()->toArray();
            } elseif (!$tasks->keys()->contains($label)) {
                $labels = $tasks
                    ->filter(function (SyncTask $task) use ($label) {
                        return $task->getGroups()->contains($label);
                    })
                    ->keys()
                    ->toArray()
                ;
            }
        }

        return $labels;
    }
}
