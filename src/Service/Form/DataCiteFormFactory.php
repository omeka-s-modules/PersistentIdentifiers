<?php
namespace PersistentIdentifiers\Service\Form;

use PersistentIdentifiers\Form\DataCiteForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class DataCiteFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $form = new DataCiteForm;
        return $form;
    }
}
