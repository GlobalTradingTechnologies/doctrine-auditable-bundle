<?php
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAdapterBundle\Entity;

use DateTimeInterface;
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
    protected $id;

    /**
     * Date (time when change was registered in the system)
     *
     * @var DateTimeInterface
     *
     * @ORM\Column(type="datetime", name="created_ts", columnDefinition="TIMESTAMP NULL DEFAULT NULL")
     */
    protected $createdTs;

    /**
     * Username
     *
     * @var string
     *
     * @ORM\Column(type="string", name="username")
     */
    protected $username;

    /**
     * Entity class name
     *
     * @var string
     *
     * @ORM\Column(name="entity_class", type="string", length=255)
     */
    protected $entityClass;

    /**
     * Entity ID
     *
     * @var string
     *
     * @ORM\Column(name="entity_id", type="string", length=255)
     */
    protected $entityId;

    /**
     * ChangeSet comment
     *
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $comment;

    /**
     * Group constructor.
     *
     * @param DateTimeInterface $createdTs   Crated timestamp
     * @param string            $username    User name
     * @param string            $entityClass Entity class name
     * @param string            $entityId    Entity ID
     * @param string            $comment     ChangeSet comment
     */
    public function __construct(DateTimeInterface $createdTs, $username, $entityClass, $entityId, $comment)
    {
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get created timestamp
     *
     * @return DateTimeInterface
     */
    public function getCreatedTs()
    {
        return $this->createdTs;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get entity class name
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Get entity ID
     *
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityClass;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }
}
