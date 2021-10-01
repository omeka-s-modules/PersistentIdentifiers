<?php
namespace PersistentIdentifiers\Service\Controller;

use PersistentIdentifiers\Controller\IndexController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');

        $indexController = new IndexController($settings, $services);
        return $indexController;
    }
}