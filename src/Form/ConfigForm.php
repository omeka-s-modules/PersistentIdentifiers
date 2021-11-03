<?php
namespace PersistentIdentifiers\Form;

use Omeka\Form\Element\ItemSetSelect;
use Laminas\Form\Form;

class ConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'pid_service',
            'type' => 'radio',
            'options' => [
                'label' => 'PID Service',
                'value_options' => [
                    'ezid' => 'EZID (ARKs)',
                    'datacite' => 'DataCite (DOIs)',
                ],
            ],
            'attributes' => [
                'id' => 'pid_service',
                'required' => true,
            ],
        ]);
        
        $this->add([
            'name' => 'assign_all',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Assign PIDs to new items', // @translate
                'info' => 'Mint and assign Persistent Identifiers (PIDs) to all newly created or imported items.', // @translate
            ],
            'attributes' => [
                'id' => 'assign-all',
            ],
        ]);

        $this->add([
            'name' => 'assign_existing',
            'type' => 'text',
            'options' => [
                'label' => 'Fields with existing PIDs', // @translate
                'info' => 'List of fields (such as dc.identifier), separated by commas, that may contain existing PID values. If found during import or PID mint, existing PID will be assigned to item.', // @translate
            ],
            'attributes' => [
                'id' => 'assign-existing',
            ],
        ]);
    }
}
