<?php
namespace PersistentIdentifiers\Form;

use Omeka\Form\Element\ItemSetSelect;
use Laminas\Form\Form;

class EZIDForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'pid_shoulder',
            'type' => 'text',
            'options' => [
                'label' => 'NAAN & Shoulder Namespace', // @translate
                'info' => '<a target="_blank" href="https://ezid.cdlib.org/learn/id_basics">Name Assigning Authority Number (NAAN) and shoulder value</a> for your organization. Example: ark:/12345/k4.', // @translate
                'escape_info' => false,
            ],
            'attributes' => [
                'id' => 'pid-shoulder',
                'required' => true,
            ],
        ]);

        $this->add([
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
        
        $this->add([
            // TODO: show password as 'dots'
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
