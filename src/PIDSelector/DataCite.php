<?php
namespace PersistentIdentifiers\PIDSelector;

use Laminas\Http\Client as HttpClient;
use Omeka\Settings\Settings as Settings;
use Laminas\Stdlib\Parameters;

/**
 * Use DataCite service to mint/update DOI identifiers
 */
class DataCite implements PIDSelectorInterface
{
    /**
     * @var Settings
     */
    protected $settings;

    /**
     * @var HttpClient
     */
    protected $client;

    public function __construct(Settings $settings, HttpClient $client) {
        $this->settings = $settings;
        $this->pidUsername = $this->settings->get('datacite_username');
        $this->pidPassword = $this->settings->get('datacite_password');
        $this->pidShoulder = $this->settings->get('datacite_shoulder');
        $this->client = $client;
    }
    
    public function getLabel()
    {
        return 'DataCite'; // @translate
    }

    public function mint($targetURI)
    {

    }

    public function update($existingPID, $targetURI)
    {

    }

    public function delete($pidToDelete)
    {

    }

    public function extract($existingFields, $itemRepresentation)
    {

    }
}
