<?php
namespace PersistentIdentifiers\Form;

use Laminas\Form\Form;
use Omeka\Form\Element\PropertySelect;
use Omeka\Settings\Settings;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;

class DataCiteForm extends Form
{
    use EventManagerAwareTrait;

    /**
     * @var Settings
     */
    protected $settings;

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
                'id' => 'datacite-password',
                'required' => true,
            ],
        ]);

        // Required metadata section
        $this->add([
            'type' => 'fieldset',
            'name' => 'required-metadata',
            'options' => [
                'label' => 'DataCite required metadata', // @translate
            ],
        ]);
        $metadataFieldset = $this->get('required-metadata');
        $metadataFieldset->add([
            'name' => 'datacite_creators_property_term',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Creators property', // @translate
                'info' => 'Local metadata field value to assign to DataCite creators property', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'datacite-creators-property-term',
                'required' => false,
                'value' => $this->settings->get('datacite_creators_property_term'),
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        // $addEvent = new Event('form.add_elements', $this);
        // $this->getEventManager()->triggerEvent($addEvent);
        //
        // $inputFilter = $this->getInputFilter();
        // $inputFilter->add([
        //     'name' => 'datacite_creators_property_term',
        //     'required' => false,
        //     'allow_empty' => true,
        // ]);
        // $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        // $this->getEventManager()->triggerEvent($event);
    }

    /**
     * @param Settings $settings
     */
    public function setSettings(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return Settings
     */
    public function getSettings()
    {
        return $this->settings;
    }
}
