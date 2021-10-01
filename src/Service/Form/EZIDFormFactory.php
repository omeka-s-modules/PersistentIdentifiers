<?php
namespace PersistentIdentifiers\Service\Form;

use PersistentIdentifiers\Form\EZIDForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class EZIDFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $form = new EZIDForm;
        return $form;
    }
}
