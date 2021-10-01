<?php
namespace PersistentIdentifiers\Service\Form;

use PersistentIdentifiers\Form\ConfigForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $form = new ConfigForm;
        return $form;
    }
}
