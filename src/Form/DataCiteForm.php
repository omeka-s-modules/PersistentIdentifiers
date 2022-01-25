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
            'name' => 'datacite_title_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Title property', // @translate
                'info' => 'Local metadata field value to assign to required DataCite title property', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'datacite-title-property',
                'required' => true,
                'value' => $this->settings->get('datacite_title_property'),
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'datacite_creators_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Creators property', // @translate
                'info' => 'Local metadata field value to assign to required DataCite creators property', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'datacite-creators-property',
                'required' => true,
                'value' => $this->settings->get('datacite_creators_property'),
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'datacite_publisher_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Publisher property', // @translate
                'info' => 'Local metadata field value to assign to required DataCite publisher property', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'datacite-publisher-property',
                'required' => true,
                'value' => $this->settings->get('datacite_publisher_property'),
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'datacite_publicationYear_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Publication Year property', // @translate
                'info' => 'Local metadata field value to assign to required DataCite publicationYear property', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'datacite-publicationYear-property',
                'required' => true,
                'value' => $this->settings->get('datacite_publicationYear_property'),
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'datacite_resourceTypeGeneral_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Resource Type General property', // @translate
                'info' => 'Local metadata field value to assign to required DataCite resourceTypeGeneral property', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'datacite-resourceTypeGeneral-property',
                'required' => true,
                'value' => $this->settings->get('datacite_resourceTypeGeneral_property'),
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);
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
