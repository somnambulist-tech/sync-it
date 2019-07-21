<?php

declare(strict_types=1);

namespace SyncIt\Models;

use Somnambulist\Domain\Entities\AbstractEnumeration;

/**
 * Class SymlinkMode
 *
 * @package    SyncIt\Models
 * @subpackage SyncIt\Models\SymlinkMode
 *
 * @method static SymlinkMode IGNORE()
 * @method static SymlinkMode PORTABLE()
 * @method static SymlinkMode POSIX_RAW()
 */
final class SymlinkMode extends AbstractEnumeration
{

    const IGNORE    = 'ignore';
    const PORTABLE  = 'portable';
    const POSIX_RAW = 'posix-raw';

}
