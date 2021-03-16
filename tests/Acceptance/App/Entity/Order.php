<?php

declare(strict_types=1);

/*
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAuditableBundle\Acceptance\App\Entity;

use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Annotation as Auditable;

/**
 * Class Order
 *
 * @ORM\Entity()
 * @ORM\Table("orders")
 * @Auditable\Entity()
 */
class Order
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column()
     * @Auditable\Property()
     */
    private string $customer;

    /**
     * @ORM\Column()
     * @Auditable\Property()
     */
    private int $totalItems;

    /**
     * @ORM\Column(type="datetime")
     * @Auditable\Property()
     */
    private DateTime $postedTs;

    /**
     * @ORM\Column(type="datetimetz_immutable", nullable=true)
     * @Auditable\Property()
     */
    private ?DateTimeImmutable $executedTs = null;

    public function __construct(string $customer, int $totalItems)
    {
        $this->customer   = $customer;
        $this->totalItems = $totalItems;
        $this->postedTs   = new DateTime();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCustomer(): string
    {
        return $this->customer;
    }

    /**
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * @param string $customer
     */
    public function setCustomer(string $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @param int $totalItems
     */
    public function setTotalItems(int $totalItems): void
    {
        $this->totalItems = $totalItems;
    }

    /**
     * @return DateTime
     */
    public function getPostedTs(): DateTime
    {
        return $this->postedTs;
    }

    /**
     * @param DateTime $postedTs
     */
    public function setPostedTs(DateTime $postedTs): void
    {
        $this->postedTs = $postedTs;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getExecutedTs(): ?DateTimeImmutable
    {
        return $this->executedTs;
    }

    /**
     * @param DateTimeImmutable|null $executedTs
     */
    public function setExecutedTs(?DateTimeImmutable $executedTs): void
    {
        $this->executedTs = $executedTs;
    }
}
