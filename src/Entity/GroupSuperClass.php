<?php
declare(strict_types=1);
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAuditableBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * ChangeSet group
 *
 * @ORM\MappedSuperclass
 */
class GroupSuperClass
{
    /**
     * ID
     *
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * Date (time when change was registered in the system)
     *
     * @ORM\Column(type="datetime_immutable", name="created_ts", columnDefinition="TIMESTAMP NULL DEFAULT NULL")
     */
    protected DateTimeImmutable $createdTs;

    /**
     * Username
     *
     * @ORM\Column(type="string", name="username", nullable=true)
     */
    protected ?string $username;

    /**
     * Entity class name
     *
     * @var string
     *
     * @ORM\Column(name="entity_class", type="string", length=255)
     */
    protected string $entityClass;

    /**
     * Entity ID
     *
     * @var string
     *
     * @ORM\Column(name="entity_id", type="string", length=255)
     */
    protected string $entityId;

    /**
     * ChangeSet comment
     *
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $comment;

    /**
     * Group constructor.
     *
     * @param DateTimeImmutable $createdTs   Crated timestamp
     * @param string|null       $username    User name
     * @param string            $entityClass Entity class name
     * @param string            $entityId    Entity ID
     * @param string|null       $comment     ChangeSet comment
     */
    public function __construct(
        DateTimeImmutable $createdTs,
        ?string $username,
        string $entityClass,
        string $entityId,
        ?string $comment
    ) {
        $this->createdTs   = $createdTs;
        $this->username    = $username;
        $this->entityClass = $entityClass;
        $this->entityId    = $entityId;
        $this->comment     = $comment;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get created timestamp
     */
    public function getCreatedTs(): DateTimeImmutable
    {
        return $this->createdTs;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Get entity class name
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * Get entity ID
     *
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
}
