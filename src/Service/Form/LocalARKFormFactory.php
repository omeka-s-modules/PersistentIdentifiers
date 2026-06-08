<?php
namespace PersistentIdentifiers\Service\Form;

use PersistentIdentifiers\Form\LocalARKForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class LocalARKFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $form = new LocalARKForm;
        return $form;
    }
}
