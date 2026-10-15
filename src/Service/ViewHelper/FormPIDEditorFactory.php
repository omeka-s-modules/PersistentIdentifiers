<?php
namespace PersistentIdentifiers\Service\ViewHelper;

use PersistentIdentifiers\View\Helper\FormPIDEditor;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class FormPIDEditorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new FormPIDEditor($services->get('FormElementManager'));
    }
}
