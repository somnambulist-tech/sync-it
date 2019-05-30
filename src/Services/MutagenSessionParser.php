<?php

declare(strict_types=1);

namespace SyncIt\Services;

use Somnambulist\Collection\Collection;
use Symfony\Component\Process\Process;
use SyncIt\Models\MutagenSession;
use SyncIt\Models\Sessions;

/**
 * Class MutagenSessionParser
 *
 * Note: this only exists until the full label support is stable and finalised.
 *
 * A very basic parser that converts the standard (not long) output from
 * `mutagen list` to a set of session objects that can be mapped to SyncTasks.
 * The output from mutagen list as of 0.8.3 is:
 *
 * --------------------------------------------------------------------------------
 * Session: 0ab49b70-d390-4702-a6cc-23f7d7060450
 * Alpha:
 *         URL: /some/path/here
 *         Connection state: Connected
 * Beta:
 *         URL: docker://container_name_1/app
 *         DOCKER_HOST=tcp://localhost:2375
 *         DOCKER_TLS_VERIFY=
 *         DOCKER_CERT_PATH=
 * Connection state: Connected
 * Status: Watching for changes
 * --------------------------------------------------------------------------------
 *
 * This is split by lines and then accumulated into an array before being
 * converted to a MutagenSession in the Sessions object. SyncTasks are mapped
 * based on the source and target. These should (in theory) be unique,
 * however if a task maps to more than one session, it will raise an
 * Exception to avoid issues.
 *
 * @package    SyncIt\Services
 * @subpackage SyncIt\Services\MutagenSessionParser
 */
class MutagenSessionParser
{

    /**
     * @return Sessions
     */
    public function sessions(): Sessions
    {
        $sessions = new Collection();
        $proc     = new Process(['mutagen', 'list']);
        $proc->run();

        if (!$proc->isSuccessful()) {
            return new Sessions($sessions);
        }

        $data       = explode("\n", $proc->getOutput());
        $parsedData = [];
        $counter    = 0;
        $mode       = '';

        if (count($data) < 7) {
            return new Sessions($sessions);
        }

        foreach ($data as $line) {
            if (false !== strpos($line, '---')) {
                $counter++;
                continue;
            }

            if (false !== stripos($line, 'session')) {
                $parsedData[$counter]['id'] = trim(str_ireplace('session:', '', $line));
                continue;
            }
            if (false !== stripos($line, 'alpha')) {
                $mode = 'source';
                continue;
            }
            if (false !== stripos($line, 'beta')) {
                $mode = 'target';
                continue;
            }

            if (false !== stripos($line, 'url')) {
                $parsedData[$counter][$mode] = trim(str_ireplace('url:', '', $line));
                continue;
            }
            if (false !== stripos($line, 'connection state')) {
                $parsedData[$counter]['conn'] = trim(str_ireplace('connection state:', '', $line));
                continue;
            }
            if (false !== stripos($line, 'status')) {
                $parsedData[$counter]['status'] = trim(str_ireplace('status:', '', $line));
                continue;
            }
        }

        foreach ($parsedData as $row) {
            $sessions->add(
                new MutagenSession(
                    $row['id'],
                    $row['source'],
                    $row['target'],
                    $row['conn'] ?? null,
                    $row['status'] ?? null
                )
            );
        }

        return new Sessions($sessions);
    }
}
