<?php
namespace PersistentIdentifiers\Form;

use Laminas\Form\Form;

class EZIDForm extends Form
{
    public function init()
    {
        // EZID configuration section
        $this->add([
            'type' => 'fieldset',
            'name' => 'ezid-configuration',
            'options' => [
                'label' => 'EZID Configuration', // @translate
            ],
            'attributes' => [
                'id' => 'ezid-configuration',
                'class' => 'pid-configuration inactive',
            ],
        ]);

        $ezidFieldset = $this->get('ezid-configuration');

        $ezidFieldset->add([
            'name' => 'ezid_shoulder',
            'type' => 'text',
            'options' => [
                'label' => 'NAAN & Shoulder Namespace', // @translate
                'info' => '<a target="_blank" href="https://ezid.cdlib.org/learn/id_basics">Name Assigning Authority Number (NAAN) and shoulder value</a> for your organization. Example: ark:/12345/k4.', // @translate
                'escape_info' => false,
            ],
            'attributes' => [
                'id' => 'ezid-shoulder',
                'required' => true,
            ],
        ]);

        $ezidFieldset->add([
            'name' => 'ezid_username',
            'type' => 'text',
            'options' => [
                'label' => 'EZID Username', // @translate
                'info' => 'Ensure user has permission to create and update identifiers for above namespace.', // @translate
            ],
            'attributes' => [
                'id' => 'ezid-username',
                'required' => true,
            ],
        ]);
        
        $ezidFieldset->add([
            'name' => 'ezid_password',
            'type' => 'password',
            'options' => [
                'label' => 'EZID Password', // @translate
                'info' => 'Ensure user has permission to create and update identifiers for above namespace.', // @translate
            ],
            'attributes' => [
                'id' => 'ezid-password',
                'required' => true,
            ],
        ]);
    }
}
