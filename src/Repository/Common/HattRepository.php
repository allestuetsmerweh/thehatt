<?php

namespace App\Repository\Common;

use Doctrine\ORM\EntityRepository;

/**
 * @template T of object
 *
 * @extends EntityRepository<T>
 */
class HattRepository extends EntityRepository {
    /** @var class-string<T> */
    protected string $entityClass;
}
