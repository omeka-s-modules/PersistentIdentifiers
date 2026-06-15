<?php
namespace PersistentIdentifiers\Form;

use Laminas\Form\Form;
use Omeka\Form\Element\PropertySelect;

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
                    'localark' => 'Local ARK',
                ],
            ],
            'attributes' => [
                'id' => 'pid_service',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'pid_assign_all',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Assign PIDs to new items', // @translate
                'info' => 'Mint and assign PIDs to all newly created or imported items.', // @translate
            ],
            'attributes' => [
                'id' => 'assign-all',
            ],
        ]);

        $this->add([
            'name' => 'existing_pid_fields',
            'type' => 'text',
            'options' => [
                'label' => 'Fields with existing PIDs', // @translate
                'info' => 'List of Omeka S property terms (e.g. dcterms:identifier), separated by commas, that may contain PID values. If found, existing PID will be assigned instead of minting a new PID. Order matters: the first PID found from listed fields will be assigned to item.', // @translate
            ],
            'attributes' => [
                'id' => 'assign-existing',
            ],
        ]);

        $this->add([
            'name' => 'pid_store_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Optional field to store PIDs', // @translate
                'info' => 'If set, PID will also be written to this metadata property within the item.', // @translate
                'empty_option' => '',
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'pid-store-property',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);
    }
}
