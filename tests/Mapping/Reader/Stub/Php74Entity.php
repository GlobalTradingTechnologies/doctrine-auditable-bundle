<?php

declare(strict_types=1);

/*
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\Stub;

use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Annotation as Auditable;

/**
 * Class Php74Entity
 *
 * @Auditable\Entity()
 */
final class Php74Entity extends AbstractEntity
{
    /**
     * @Auditable\Property
     */
    public $auditableProperty;

    public $notAuditableProperty;
}
