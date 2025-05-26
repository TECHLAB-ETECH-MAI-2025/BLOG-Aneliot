<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }
    public function findForDataTable(int $start, int $length, ?string $search, string $orderColumn, string $orderDir): array
{
    $qb = $this->createQueryBuilder('a')
        ->leftJoin('a.categories', 'c');

    if ($search) {
        $qb->andWhere('LOWER(a.title) LIKE :search OR LOWER(c.title) LIKE :search')
            ->setParameter('search', '%' . strtolower($search) . '%');
    }

    // Allowed columns for sorting;
    $allowedFields = ['a.id', 'a.title', 'a.createdAt']; // only real Article fields
    $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

     if ($orderColumn === 'commentsCount') {
        $qb->addSelect('COUNT(com.id) AS HIDDEN commentsCount')
           ->leftJoin('a.comments', 'com')
           ->groupBy( 'a.id')
           ->orderBy('commentsCount', $orderDir);
    } elseif ($orderColumn === 'likesCount') {
        $qb->addSelect('COUNT(l.id) AS HIDDEN likesCount')
           ->leftJoin('a.articleLikes', 'l')
           ->groupBy('a.id')
           ->orderBy('likesCount', $orderDir);
    } elseif ($orderColumn === 'categories') {
        $qb->addSelect('COUNT(c.id) AS HIDDEN categoriesCount')
            ->groupBy('a.id')
            ->orderBy('categoriesCount', $orderDir);
    }
     elseif (in_array($orderColumn, $allowedFields, true)) {
        $qb->orderBy( $orderColumn, $orderDir);
        $qb->select('DISTINCT a');

    } else {
        $qb->orderBy( 'a.createdAt',$orderDir);
        $qb->select('DISTINCT a');

    }

    $qb->setFirstResult($start)
       ->setMaxResults($length);

    $totalCount = $this->createQueryBuilder('a')
        ->select('COUNT(a.id)')
        ->getQuery()
        ->getSingleScalarResult();

    $filteredCountQb = $this->createQueryBuilder(alias: 'a')
        ->leftJoin('a.categories', 'c');

    if ($search) {
        $filteredCountQb
            ->andWhere('LOWER(a.title) LIKE :search OR LOWER(c.title) LIKE :search')
            ->setParameter('search', '%' . strtolower($search) . '%');

    }

    $filteredCount = $filteredCountQb
        ->select('COUNT(DISTINCT a.id)')
        ->getQuery()
        ->getSingleScalarResult();

    return [
        'data' => $qb->getQuery()->getResult(),
        'totalCount' => $totalCount,
        'filteredCount' => $filteredCount,
    ];
}



	/**
	 * Recherche des articles par titre
	 */
	public function searchByTitle(string $query, int $limit = 10): array
	{
		return $this->createQueryBuilder('a')
			->leftJoin('a.categories', 'c')
			->addSelect('c')
			->where('a.title LIKE :query')
			->setParameter('query', '%' . $query . '%')
			->orderBy('a.createdAt', 'DESC')
			->setMaxResults($limit)
			->getQuery()
			->getResult();
	}

    //    /**
    //     * @return Article[] Returns an array of Article objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Article
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

