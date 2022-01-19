<?php
namespace PersistentIdentifiers\PIDSelector;

use Laminas\Http\Client as HttpClient;
use Laminas\Stdlib\Parameters;

/**
 * Use DataCite service to mint/update DOI identifiers
 */
class DataCite implements PIDSelectorInterface
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

    public function __construct(HttpClient $httpClient) {
        $this->client = $httpClient;
    }
    
    public function getLabel()
    {
        return 'DataCite'; // @translate
    }

    public function mint($username, $password, $pidShoulder, $targetURI)
    {

    }

    public function update($username, $password, $existingPID, $targetURI)
    {

    }

    public function delete($username, $password, $pidToDelete)
    {

    }

    public function extract($pidShoulder, $existingFields, $itemRepresentation)
    {

    }
}
