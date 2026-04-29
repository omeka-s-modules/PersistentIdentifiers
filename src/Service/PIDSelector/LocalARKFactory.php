<?php
namespace PersistentIdentifiers\Service\PIDSelector;

use PersistentIdentifiers\PIDSelector\LocalARK;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class LocalARKFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        return new LocalARK($settings);
    }
}
