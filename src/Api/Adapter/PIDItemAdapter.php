<?php
namespace PersistentIdentifiers\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class PIDItemAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'PersistentIdentifiers\Entity\PIDItem';
    }

    public function getResourceName()
    {
        return 'pid_items';
    }

    public function getRepresentationClass()
    {
        return 'PersistentIdentifiers\Api\Representation\PIDItemRepresentation';
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['pid'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.pid',
                $this->createNamedParameter($qb, $query['pid']))
            );
        }

        if (isset($query['item_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.item',
                $this->createNamedParameter($qb, $query['item_id']))
            );
        }
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        if (isset($data['o:item']['o:id'])) {
            $item = $this->getAdapter('items')->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }
        if (isset($data['pid'])) {
            $entity->setPID($data['pid']);
        }

        // The current user must be able to update the item this PID is bound to.
        if ($entity->getItem()) {
            $this->authorize($entity->getItem(), 'update');
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getItem()) {
            $errorStore->addError('o:item', 'A PID must be attached to an item.'); // @translate
        }
        if (!$entity->getPID()) {
            $errorStore->addError('pid', 'A PID must have a value.'); // @translate
        }
    }
}
