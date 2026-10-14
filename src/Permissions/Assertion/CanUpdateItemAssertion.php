<?php
namespace PersistentIdentifiers\Permissions\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Omeka\Entity\Item;

/**
 * Allow a PID record to be changed only by a user who may update its item.
 */
class CanUpdateItemAssertion implements AssertionInterface
{
    public function assert(Acl $acl, ?RoleInterface $role = null,
        ?ResourceInterface $resource = null, $privilege = null
    ) {
        if (!$role || !$resource || !method_exists($resource, 'getItem')) {
            return false;
        }
        $item = $resource->getItem();
        return $item instanceof Item && $acl->isAllowed($role, $item, 'update');
    }
}
