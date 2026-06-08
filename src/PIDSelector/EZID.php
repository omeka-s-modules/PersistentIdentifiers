<?php
namespace PersistentIdentifiers\PIDSelector;

use Laminas\Http\Client as HttpClient;
use Omeka\Settings\Settings as Settings;
use Laminas\Stdlib\Parameters;

/**
 * Use EZID service to mint/update ARK identifiers
 */
class EZID implements PIDSelectorInterface
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
        $this->pidUsername = $this->settings->get('ezid_username');
        $this->pidPassword = $this->settings->get('ezid_password');
        $this->pidShoulder = $this->settings->get('ezid_shoulder');
        $this->client = $client;
    }

    public function getLabel()
    {
        return 'EZID'; // @translate
    }

    public function mint($targetURI, $itemRepresentation)
    {
        // Build organization-specific mint URL
        $shoulder = 'https://ezid.cdlib.org/shoulder/' . $this->pidShoulder;
        // append EZID required prefix to pid target
        $target = '_target: ' . $targetURI;

        $request = $this->client
            ->setUri($shoulder)
            ->setMethod('POST')
            ->setAuth($this->pidUsername, $this->pidPassword)
            ->setRawBody($target);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: text/plain');
        $response = $request->send();
        if (!$response->isSuccess()) {
            return;
        } else {
            $newPID = str_replace('success: ', '', $response->getBody());
            return $newPID;
        }
    }

    public function update($existingPID, $targetURI, $itemRepresentation)
    {
        // Build organization-specific update URL
        $shoulder = 'https://ezid.cdlib.org/id/' . $existingPID;
        // append EZID required prefix to pid target
        $target = '_target: ' . $targetURI;

        // Update target via PID API
        // If PID not found, new PID will be created
        $request = $this->client
            ->setUri($shoulder)
            ->setMethod('PUT')
            ->setAuth($this->pidUsername, $this->pidPassword)
            ->setRawBody($target);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: text/plain');
        $request->getRequest()->setQuery(new Parameters(['update_if_exists' => 'yes']));
        $response = $request->send();
        // Clear parameters for batch minting/editing
        $request->resetParameters();
        if (!$response->isSuccess()) {
            return;
        } else {
            $updatedPID = str_replace('success: ', '', $response->getBody());
            return $updatedPID;
        }
    }

    public function delete($pidToDelete)
    {
        // Build organization-specific delete URL
        $shoulder = 'https://ezid.cdlib.org/id/' . $pidToDelete;
        // Set EZID required prefix with empty value
        $target = '_target:';

        // Remove target via PID API
        // EZIDs cannot be deleted, only metadata (i.e. target) can be removed
        $request = $this->client
            ->setUri($shoulder)
            ->setMethod('POST')
            ->setAuth($this->pidUsername, $this->pidPassword)
            ->setRawBody($target);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: text/plain');
        $response = $request->send();
        if (!$response->isSuccess()) {
            return;
        } else {
            $deletedPID = str_replace('success: ', '', $response->getBody());
            return $deletedPID;
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
                    if (strpos($candidate, $this->pidShoulder) !== false) {
                        return trim($candidate);
                    }
                }
            }
        }
        return;
    }
}
