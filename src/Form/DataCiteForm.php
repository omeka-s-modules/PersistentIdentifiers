<?php
namespace PersistentIdentifiers\Form;

use Omeka\Form\Element\ItemSetSelect;
use Laminas\Form\Form;

class DataCiteForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'datacite_shoulder',
            'type' => 'text',
            'options' => [
                'label' => 'NAAN & Shoulder Namespace', // @translate
                'info' => '<a target="_blank" href="https://ezid.cdlib.org/learn/id_basics">Name Assigning Authority Number (NAAN) and shoulder value</a> for your organization. Example: ark:/12345/k4.', // @translate
                'escape_info' => false,
            ],
            'attributes' => [
                'id' => 'datacite-shoulder',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'datacite_username',
            'type' => 'text',
            'options' => [
                'label' => 'DataCite Username', // @translate
                'info' => 'Ensure user has permission to create and update identifiers for above namespace.', // @translate
            ],
            'attributes' => [
                'id' => 'datacite-username',
                'required' => true,
            ],
        ]);
        
        $this->add([
            // TODO: show password as 'dots'
            'name' => 'datacite_password',
            'type' => 'password',
            'options' => [
                'label' => 'DataCite Password', // @translate
                'info' => 'Ensure user has permission to create and update identifiers for above namespace.', // @translate
            ],
            'attributes' => [
                'id' => 'ezid-password',
                'required' => true,
            ],
        ]);
    }
}
