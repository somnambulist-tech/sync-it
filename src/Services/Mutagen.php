<?php

declare(strict_types=1);

namespace SyncIt\Services;

use Symfony\Component\Process\Process;
use SyncIt\Models\Sessions;

/**
 * Class Mutagen
 *
 * Helper class to aggregate assorted calls / info about the installed
 * mutagen process.
 *
 * @package    SyncIt\Services
 * @subpackage SyncIt\Services\Mutagen
 */
class Mutagen
{

    /**
     * @var string
     */
    private $version;

    public function isRunning(): bool
    {
        exec('pgrep mutagen', $pids);

        return !empty($pids);
    }

    public function assertDaemonIsRunning(): void
    {
        if (!$this->isRunning()) {
            throw new \RuntimeException('The mutagen daemon is not running, run: "mutagen daemon start"');
        }
    }

    public function getVersion(): string
    {
        if (!$this->version) {
            $proc = new Process(['mutagen', 'version']);
            $proc->run();

            $this->version = trim($proc->getOutput());
        }

        return $this->version;
    }

    public function hasLabels(): bool
    {
        return version_compare($this->getVersion(), '0.9.0', '>=');
    }

    public function getSessions(): Sessions
    {
        return (new MutagenSessionParser())->sessions();
    }
}
