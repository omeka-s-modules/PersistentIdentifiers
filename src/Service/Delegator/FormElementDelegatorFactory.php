<?php
namespace PersistentIdentifiers\Service\Delegator;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;

class FormElementDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, ?array $options = null
    ) {
        $formElement = $callback();
        $formElement->addClass(
            \PersistentIdentifiers\Form\Element\PIDEditor::class,
            'PIDEditor'
        );
        return $formElement;
    }
}
