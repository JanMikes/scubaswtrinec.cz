<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Admin lists show these records `ORDER BY order DESC`; new rows get `order = id * 10`
 * (same rule as the 2014 app), so the newest record lands on top until it is moved.
 */
interface OrderableInterface
{
    public function getId(): ?int;

    public function getOrder(): ?int;

    public function setOrder(?int $order): void;
}
