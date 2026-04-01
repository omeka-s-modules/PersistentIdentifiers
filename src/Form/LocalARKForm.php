<?php
namespace PersistentIdentifiers\Form;

use Laminas\Form\Form;

class LocalARKForm extends Form
{
    public function init()
    {
        // Local ARK configuration section
        $this->add([
            'type' => 'fieldset',
            'name' => 'local-ark-configuration',
            'options' => [
                'label' => 'Local ARK Configuration', // @translate
            ],
            'attributes' => [
                'id' => 'local-ark-configuration',
                'class' => 'pid-configuration inactive',
            ],
        ]);

        $fieldset = $this->get('local-ark-configuration');

        $fieldset->add([
            'name' => 'local_ark_naan',
            'type' => 'text',
            'options' => [
                'label' => 'NAAN', // @translate
                'info' => 'Your organization\'s <a target="_blank" href="https://arks.org/about/ark-naans-and-systems/">Name Assigning Authority Number (NAAN)</a> as assigned by the ARK Alliance. Example: 12345.', // @translate
                'escape_info' => false,
            ],
            'attributes' => [
                'id' => 'local-ark-naan',
                'required' => true,
            ],
        ]);

        $fieldset->add([
            'name' => 'local_ark_shoulder',
            'type' => 'text',
            'options' => [
                'label' => 'Shoulder', // @translate
                'info' => 'Optional two character betanumeric prefix appended after the NAAN. Useful for sub-dividing ARKs by project or unit (for example). Generated automatically if left blank.', // @translate
                'escape_info' => false,
            ],
            'attributes' => [
                'id' => 'local-ark-shoulder',
                'required' => false,
            ],
        ]);
    }
}
