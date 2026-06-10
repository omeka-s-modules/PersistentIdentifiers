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

    public function __construct(Settings $settings, HttpClient $client)
    {
        $this->settings = $settings;
        $this->pidPrefix = $this->settings->get('datacite_prefix');
        $this->pidUsername = $this->settings->get('datacite_username');
        $this->pidPassword = $this->settings->get('datacite_password');
        $this->pidTitle = $this->settings->get('datacite_title_property');
        $this->pidCreators = $this->settings->get('datacite_creators_property');
        $this->pidPublisher = $this->settings->get('datacite_publisher_property');
        $this->pidPublicationYear = $this->settings->get('datacite_publicationYear_property');
        $this->pidResourceType = $this->settings->get('datacite_resourceTypeGeneral_property');
        $this->pidSubject = $this->settings->get('datacite_subject_property');
        $this->pidDescription = $this->settings->get('datacite_description_property');
        $this->pidLanguage = $this->settings->get('datacite_language_property');
        $this->pidVersion = $this->settings->get('datacite_version_property');
        $this->pidRights = $this->settings->get('datacite_rights_property');
        $this->pidSize = $this->settings->get('datacite_size_property');
        $this->pidFormat = $this->settings->get('datacite_format_property');
        $this->client = $client;
    }

    public function getLabel()
    {
        return 'DataCite'; // @translate
    }

    public function mint($targetURI, $itemRepresentation)
    {
        $attributes = $this->buildAttributes($targetURI, $itemRepresentation);
        if (!$attributes) {
            return;
        }

        $dataciteJson = json_encode(['data' => ['type' => 'dois', 'attributes' => $attributes]]);

        $request = $this->client
            ->setUri('https://api.datacite.org/dois')
            ->setMethod('POST')
            ->setAuth($this->pidUsername, $this->pidPassword)
            ->setRawBody($dataciteJson);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: application/json');
        $response = $request->send();
        if (!$response->isSuccess()) {
            return;
        }
        $data = json_decode($response->getBody(), true);
        return $data['data']['id'];
    }

    public function update($existingPID, $targetURI, $itemRepresentation)
    {
        $attributes = $this->buildAttributes($targetURI, $itemRepresentation);
        if (!$attributes) {
            return;
        }

        $dataciteJson = json_encode(['data' => ['type' => 'dois', 'attributes' => $attributes]]);

        $request = $this->client
            ->setUri('https://api.datacite.org/dois/' . $existingPID)
            ->setMethod('PUT')
            ->setAuth($this->pidUsername, $this->pidPassword)
            ->setRawBody($dataciteJson);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: application/json');
        $response = $request->send();
        $request->resetParameters();
        if (!$response->isSuccess()) {
            return;
        }
        $data = json_decode($response->getBody(), true);
        return $data['data']['id'];
    }

    private function buildAttributes($targetURI, $itemRepresentation)
    {
        // Required fields
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

        if (!isset($this->pidPrefix, $pidCreators, $pidTitles, $publisher, $publicationYear, $type)) {
            return null;
        }

        $attributes = [
            'event' => 'publish',
            'prefix' => $this->pidPrefix,
            'creators' => $pidCreators,
            'titles' => $pidTitles,
            'publisher' => $publisher,
            'publicationYear' => $publicationYear,
            'types' => ['resourceTypeGeneral' => $type],
            'url' => $targetURI,
        ];

        // Optional fields — only included when configured and the item has values
        if ($this->pidSubject) {
            $vals = $itemRepresentation->value($this->pidSubject, ['all' => true]);
            if ($vals) {
                $attributes['subjects'] = array_map(fn($v) => ['subject' => $v->value()], $vals);
            }
        }

        if ($this->pidDescription) {
            $vals = $itemRepresentation->value($this->pidDescription, ['all' => true]);
            if ($vals) {
                $attributes['descriptions'] = array_map(
                    fn($v) => ['description' => $v->value(), 'descriptionType' => 'Abstract'],
                    $vals
                );
            }
        }

        if ($this->pidLanguage && ($val = $itemRepresentation->value($this->pidLanguage))) {
            $attributes['language'] = $val->value();
        }

        if ($this->pidVersion && ($val = $itemRepresentation->value($this->pidVersion))) {
            $attributes['version'] = $val->value();
        }

        if ($this->pidRights) {
            $vals = $itemRepresentation->value($this->pidRights, ['all' => true]);
            if ($vals) {
                $attributes['rightsList'] = array_map(function ($v) {
                    $right = ['rights' => $v->value()];
                    if ($v->uri()) {
                        $right['rightsUri'] = $v->uri();
                    }
                    return $right;
                }, $vals);
            }
        }

        if ($this->pidSize) {
            $vals = $itemRepresentation->value($this->pidSize, ['all' => true]);
            if ($vals) {
                $attributes['sizes'] = array_map(fn($v) => $v->value(), $vals);
            }
        }

        if ($this->pidFormat) {
            $vals = $itemRepresentation->value($this->pidFormat, ['all' => true]);
            if ($vals) {
                $attributes['formats'] = array_map(fn($v) => $v->value(), $vals);
            }
        }

        return $attributes;
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
                    'url' => 'https://www.datacite.org/invalid.html',
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
            // Normalise dot notation (e.g. 'dc.identifier') to Omeka S term notation
            // (e.g. 'dc:identifier'). Values() keys are always '{prefix}:{localName}'.
            if (strpos($field, ':') === false && strpos($field, '.') !== false) {
                $field = preg_replace('/\./', ':', $field, 1);
            }
            if (array_key_exists($field, $itemRepresentation->values())) {
                $values = $itemRepresentation->value($field, ['all' => true]);
                foreach ($values as $value) {
                    // Check both the text value (literal) and URI field,
                    // since PIDs may be stored as either value type.
                    $candidate = (string) $value ?: (string) $value->uri();
                    if (strpos($candidate, $this->pidPrefix) !== false) {
                        return trim($candidate);
                    }
                }
            }
        }
        return;
    }
}
