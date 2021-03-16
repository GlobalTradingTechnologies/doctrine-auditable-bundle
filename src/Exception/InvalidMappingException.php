<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Exception;

use Exception;

/**
 * InvalidMappingException
 *
 * Triggered when mapping user argument is not valid or incomplete.
 */
class InvalidMappingException extends Exception implements DoctrineAuditableExceptionInterface
{
}
