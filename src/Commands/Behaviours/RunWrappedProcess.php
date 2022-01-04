<?php declare(strict_types=1);

namespace SyncIt\Commands\Behaviours;

use Somnambulist\Components\Collection\MutableCollection as Collection;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Trait RunWrappedProcess
 *
 * @package    SyncIt\Commands\Behaviours
 * @subpackage SyncIt\Commands\Behaviours\RunWrappedProcess
 *
 * @method Helper getHelper(string $name)
 */
trait RunWrappedProcess
{

    protected function runProcessViaHelper(OutputInterface $output, Collection $command): Process
    {
        /** @var ProcessHelper $helper */
        $helper = $this->getHelper('process');
        $proc   = new Process($command->toArray());

        $helper->run($output, $proc);

        return $proc;
    }
}
