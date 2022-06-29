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
        $this->pidPrefix = $this->settings->get('datacite_prefix');
        $this->pidUsername = $this->settings->get('datacite_username');
        $this->pidPassword = $this->settings->get('datacite_password');
        $this->pidTitle = $this->settings->get('datacite_title_property');
        $this->pidCreators = $this->settings->get('datacite_creators_property');
        $this->pidPublisher = $this->settings->get('datacite_publisher_property');
        $this->pidPublicationYear = $this->settings->get('datacite_publicationYear_property');
        $this->pidResourceType = $this->settings->get('datacite_resourceTypeGeneral_property');
        $this->client = $client;
    }
    
    public function getLabel()
    {
        return 'DataCite'; // @translate
    }

    public function mint($targetURI, $itemRepresentation)
    {
        // Supply organization-specific mint URL
        $shoulder = 'https://api.datacite.org/dois';

        // Handle multiple values for creator & title fields
        $creators = $itemRepresentation->value($this->pidCreators, ['all' => true]);
        foreach ($creators as $creator) {
            $pidCreators[] = ['name' => $creator->value()];
        }
        $titles = $itemRepresentation->value($this->pidTitle, ['all' => true]);
        foreach ($titles as $title) {
            $pidTitles[] = ['title' => $title->value()];
        }
        $publisher = $itemRepresentation->value($this->pidPublisher) ? $itemRepresentation->value($this->pidPublisher)->value() : null;
        $publicationYear = $itemRepresentation->value($this->pidPublicationYear) ? $itemRepresentation->value($this->pidPublicationYear)->value() : null;
        $type = $itemRepresentation->value($this->pidResourceType) ? $itemRepresentation->value($this->pidResourceType)->value() : null;

        // If any required metadata is missing, don't mint
        if (!isset($this->pidPrefix, $pidCreators, $pidTitles, $publisher, $publicationYear, $type)) {
            return;
        }

        // Build JSON data with DataCite prefix, required metadata & target URI
        $dataciteArray = [
            'data' => [
                'type' => 'dois',
                'attributes' => [
                    'event' => 'publish',
                    'prefix' => $this->pidPrefix,
                    'creators' => $pidCreators,
                    'titles' => $pidTitles,
                    'publisher' => $publisher,
                    'publicationYear' => $publicationYear,
                    'types' => [
                        'resourceTypeGeneral' => $type,
                    ],
                    'url' => $targetURI
                ],
            ],
        ];
        $dataciteJson = json_encode($dataciteArray);

        // Send mint request
        $request = $this->client
            ->setUri($shoulder)
            ->setMethod('POST')
            ->setAuth($this->pidUsername, $this->pidPassword)
            ->setRawBody($dataciteJson);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: application/json');
        $response = $request->send();
        if (!$response->isSuccess()) {
            return;
        } else {
            $data = json_decode($response->getBody(), true);
            return $data['data']['id'];
        }
    }

    public function update($existingPID, $targetURI, $itemRepresentation)
    {
        // Build organization-specific update URL
        $shoulder = 'https://api.datacite.org/dois/' . $existingPID;

        // Handle multiple values for creator & title fields
        $creators = $itemRepresentation->value($this->pidCreators, ['all' => true]);
        foreach ($creators as $creator) {
            $pidCreators[] = ['name' => $creator->value()];
        }
        $titles = $itemRepresentation->value($this->pidTitle, ['all' => true]);
        foreach ($titles as $title) {
            $pidTitles[] = ['title' => $title->value()];
        }
        $publisher = $itemRepresentation->value($this->pidPublisher) ? $itemRepresentation->value($this->pidPublisher)->value() : null;
        $publicationYear = $itemRepresentation->value($this->pidPublicationYear) ? $itemRepresentation->value($this->pidPublicationYear)->value() : null;
        $type = $itemRepresentation->value($this->pidResourceType) ? $itemRepresentation->value($this->pidResourceType)->value() : null;

        // If any required metadata is missing, don't mint
        if (!isset($this->pidPrefix, $pidCreators, $pidTitles, $publisher, $publicationYear, $type)) {
            return;
        }

        // Build JSON data with DataCite prefix, required metadata & target URI
        $dataciteArray = [
            'data' => [
                'type' => 'dois',
                'attributes' => [
                    'event' => 'publish',
                    'prefix' => $this->pidPrefix,
                    'creators' => $pidCreators,
                    'titles' => $pidTitles,
                    'publisher' => $publisher,
                    'publicationYear' => $publicationYear,
                    'types' => [
                        'resourceTypeGeneral' => $type,
                    ],
                    'url' => $targetURI
                ],
            ],
        ];
        $dataciteJson = json_encode($dataciteArray);

        // Send update request
        $request = $this->client
            ->setUri($shoulder)
            ->setMethod('PUT')
            ->setAuth($this->pidUsername, $this->pidPassword)
            ->setRawBody($dataciteJson);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: application/json');
        $response = $request->send();
        // Clear parameters for batch minting/editing
        $request->resetParameters();
        if (!$response->isSuccess()) {
            return;
        } else {
            $data = json_decode($response->getBody(), true);
            return $data['data']['id'];
        }
    }

    public function delete($pidToDelete)
    {
        // Build organization-specific delete URL
        $shoulder = 'https://api.datacite.org/dois/' . $pidToDelete;

        // Update JSON data with hide event and DataCite tombstone URL
        $dataciteArray = [
            'data' => [
                'attributes' => [
                    'event' => 'hide',
                    'url' => 'https://www.datacite.org/invalid.html'
                ],
            ],
        ];
        $dataciteJson = json_encode($dataciteArray);

        // Send removal update request
        // DOIs cannot be deleted, only indexing state and metadata can be changed
        $request = $this->client
            ->setUri($shoulder)
            ->setMethod('PUT')
            ->setAuth($this->pidUsername, $this->pidPassword)
            ->setRawBody($dataciteJson);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: application/json');
        $response = $request->send();
        if (!$response->isSuccess()) {
            return;
        } else {
            $data = json_decode($response->getBody(), true);
            return $data['data']['id'];
        }
    }

    public function extract($existingFields, $itemRepresentation)
    {
        foreach (explode(',', $existingFields) as $field) {
            $field = trim($field);
            // Match input PID fields to existing resource metadata fields
            if (array_key_exists($field, $itemRepresentation->values())) {
                $values = $itemRepresentation->value($field, ['all' => true]);
                foreach ($values as $value) {
                    // Find PID values by checking for institution's EZID shoulder within value
                    // Return first match
                    if (strpos($value, $this->pidPrefix) !== false) {
                        return trim($value);
                    }
                }
            }
        }
        return;
    }
}
