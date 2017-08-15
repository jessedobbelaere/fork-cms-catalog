<?php

namespace Backend\Modules\Catalog\Domain\Order;

use Backend\Modules\Catalog\Domain\Order\Exception\OrderNotFound;
use Backend\Modules\ContentBlocks\Domain\ContentBlock\Exception\ContentBlockNotFound;
use Common\Locale;
use Doctrine\ORM\EntityRepository;

class OrderRepository extends EntityRepository
{
    public function add(Order $order): void
    {
        // We don't flush here, see http://disq.us/p/okjc6b
        $this->getEntityManager()->persist($order);
    }

    public function findOneByIdAndLocale(?int $id, Locale $locale): ?Order
    {
        if ($id === null) {
            throw ContentBlockNotFound::forEmptyId();
        }

        /** @var Order $order */
        $order = $this->findOneBy(['id' => $id, 'locale' => $locale]);

        if ($order === null) {
            throw OrderNotFound::forId($id);
        }

        return $order;
    }

    public function removeByIdAndLocale($id, Locale $locale): void
    {
        // We don't flush here, see http://disq.us/p/okjc6b
        array_map(
            function (Order $order) {
                $this->getEntityManager()->remove($order);
            },
            (array) $this->findBy(['id' => $id, 'locale' => $locale])
        );
    }

    public function getStatusCount(int $status): int
    {
        $query_builder = $this->createQueryBuilder('i');

        return $query_builder->select('COUNT(i.id) as order_count')
                             ->andWhere('i.status = :status')
                             ->setParameter('status', $status)
                             ->getQuery()
                             ->getSingleScalarResult();
    }
}
