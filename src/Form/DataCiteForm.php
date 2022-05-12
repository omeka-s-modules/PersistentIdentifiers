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
        // DataCite configuration section
        $this->add([
            'type' => 'fieldset',
            'name' => 'datacite-configuration',
            'options' => [
                'label' => 'DataCite Configuration', // @translate
            ],
            'attributes' => [
                'id' => 'datacite-configuration',
                'class' => 'pid-configuration inactive',
            ],
        ]);

        $dataciteFieldset = $this->get('datacite-configuration');

        $dataciteFieldset->add([
            'name' => 'datacite_prefix',
            'type' => 'text',
            'options' => [
                'label' => 'Repository DOI Prefix', // @translate
                'info' => 'The <a target="_blank" href="https://support.datacite.org/docs/doi-basics#prefix">DOI prefix</a> associated with your DataCite repository. Example: 10.82157', // @translate
                'escape_info' => false,
            ],
            'attributes' => [
                'id' => 'datacite-prefix',
                'required' => true,
            ],
        ]);

        $dataciteFieldset->add([
            'name' => 'datacite_username',
            'type' => 'text',
            'options' => [
                'label' => 'DataCite Repository ID', // @translate
                'info' => 'Unique identifier assigned to your DataCite repository. Example: XQZU.RBBDXB', // @translate
            ],
            'attributes' => [
                'id' => 'datacite-username',
                'required' => true,
            ],
        ]);

        $dataciteFieldset->add([
            'name' => 'datacite_password',
            'type' => 'password',
            'options' => [
                'label' => 'DataCite Password', // @translate
                'info' => 'Password associated with DataCite repository (note that this is different from your DataCite Member password).', // @translate
            ],
            'attributes' => [
                'id' => 'datacite-password',
                'required' => true,
            ],
        ]);

        // DataCite Required metadata section
        $this->add([
            'type' => 'fieldset',
            'name' => 'datacite-required-metadata',
            'options' => [
                'label' => 'DataCite Required metadata', // @translate
            ],
            'attributes' => [
                'id' => 'datacite-required-metadata',
                'class' => 'pid-configuration inactive',
            ],
        ]);

        $dataciteMetadataFieldset = $this->get('datacite-required-metadata');

        $dataciteMetadataFieldset->add([
            'name' => 'datacite_title_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Title property', // @translate
                'info' => 'Local metadata field value to assign to required DataCite title property.', // @translate
                'empty_option' => '',
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'datacite-title-property',
                'required' => true,
                'value' => $this->settings->get('datacite_title_property'),
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        $dataciteMetadataFieldset->add([
            'name' => 'datacite_creators_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Creators property', // @translate
                'info' => 'Local metadata field value to assign to required DataCite creators property.', // @translate
                'empty_option' => '',
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'datacite-creators-property',
                'required' => true,
                'value' => $this->settings->get('datacite_creators_property'),
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        $dataciteMetadataFieldset->add([
            'name' => 'datacite_publisher_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Publisher property', // @translate
                'info' => 'Local metadata field value to assign to required DataCite publisher property.', // @translate
                'empty_option' => '',
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'datacite-publisher-property',
                'required' => true,
                'value' => $this->settings->get('datacite_publisher_property'),
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        $dataciteMetadataFieldset->add([
            'name' => 'datacite_publicationYear_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Publication Year property', // @translate
                'info' => 'Local metadata field value to assign to required DataCite publicationYear property. Must be in YYYY format.', // @translate
                'empty_option' => '',
                'term_as_value' => true,
            ],
            'attributes' => [
                'id' => 'datacite-publicationYear-property',
                'required' => true,
                'value' => $this->settings->get('datacite_publicationYear_property'),
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        $dataciteMetadataFieldset->add([
            'name' => 'datacite_resourceTypeGeneral_property',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Resource Type General property', // @translate
                'info' => 'Local metadata field value to assign to required DataCite resourceTypeGeneral property. Must match a <a target="_blank" href="https://support.datacite.org/docs/datacite-metadata-schema-v44-mandatory-properties#101-resourcetypegeneral">controlled resourceTypeGeneral vocabulary</a> value exactly.', // @translate
                'empty_option' => '',
                'term_as_value' => true,
                'escape_info' => false,
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
