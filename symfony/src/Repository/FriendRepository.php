<?php

namespace App\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class FriendRepository extends DocumentRepository
{
    public function getFilteredFriends($filters, $tag)
    {
        $qb = $this->createQueryBuilder('f');
        foreach ($filters as $k => $v) {
            $qb->field($k)->equals($v);
        }

        if (!is_null($tag)) {
            $qb->field('tags')->includesReferenceTo($tag);
        }

        return $qb->getQuery()->execute();
    }
}
