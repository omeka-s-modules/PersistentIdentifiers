<?php
namespace PersistentIdentifiers\Service\PIDSelector;

use PersistentIdentifiers\PIDSelector\Manager;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        return new Manager($services, $config['pid_services']);
    }
}