<?php

namespace App\Repository;

use App\Entity\MetaEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MetaEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method MetaEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method MetaEntity[]    findAll()
 * @method MetaEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MetaEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetaEntity::class);
    }
}
