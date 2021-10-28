<?php
namespace PersistentIdentifiers\PIDSelector;

use Omeka\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = PIDSelectorInterface::class;

    public function get($name, array $options = null)
    {
        return parent::get($name, $options);
    }
}