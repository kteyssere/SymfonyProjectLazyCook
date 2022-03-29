<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Recipe|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recipe|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recipe[]    findAll()
 * @method Recipe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Recipe $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Recipe $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return Recipe[] Returns an array of Recipe objects
     */
    public function findRecipeByName(string $value)
    {
        return $this->createQueryBuilder('r')
            ->orWhere('r.name LIKE :val')
            ->orWhere('r.ingredients LIKE :val')
            ->setParameter('val', $value . '%')
            ->orderBy('r.id', 'ASC')

            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Recipe[] Returns an array of Recipe objects
     */
    public function findRecipeByDiff(string $value)
    {
        return $this->createQueryBuilder('r')
            ->orWhere('r.difficulty LIKE :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')

            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Recipe[] Returns an array of Recipe objects
     */
    public function findRecipeByTime(string $value)
    {
        return $this->createQueryBuilder('r')
            ->orWhere('r.preparationTime LIKE :val')
            ->setParameter('val', $value)
            ->orderBy('r.preparationTime', 'ASC')

            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Recipe[] Returns an array of Recipe objects
     */
    public function findRecipeByNbPerson($value)
    {
        return $this->createQueryBuilder('r')
            ->orWhere('r.numberOfPerson LIKE :val')
            ->setParameter('val', $value)
            ->orderBy('r.numberOfPerson', 'ASC')

            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Recipe[] Returns an array of Recipe objects
     */
    public function findRecipeByCategory($value)
    {
        return $this->createQueryBuilder('r')
            ->orWhere('r.category LIKE :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')

            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Recipe[] Returns an array of Recipe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Recipe
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
