<?php
namespace PersistentIdentifiers\Service\PIDSelector;

use PersistentIdentifiers\PIDSelector\EZID;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class EZIDFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new EZID($services->get('Omeka\HttpClient'));
    }
}