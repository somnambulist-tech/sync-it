<?php declare(strict_types=1);

namespace SyncIt\Models;

use Somnambulist\Components\Domain\Entities\AbstractEnumeration;

/**
 * Class CopyMode
 *
 * @package    SyncIt\Models
 * @subpackage SyncIt\Models\CopyMode
 *
 * @method static CopyMode TWO_WAY_SAFE()
 * @method static CopyMode TWO_WAY_RESOLVED()
 * @method static CopyMode ONE_WAY_SAFE()
 * @method static CopyMode ONE_WAY_REPLICA()
 */
final class CopyMode extends AbstractEnumeration
{
    const TWO_WAY_SAFE     = 'two-way-safe';
    const TWO_WAY_RESOLVED = 'two-way-resolved';
    const ONE_WAY_SAFE     = 'one-way-safe';
    const ONE_WAY_REPLICA  = 'one-way-replica';
}
