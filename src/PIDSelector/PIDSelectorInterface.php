<?php
namespace PersistentIdentifiers\PIDSelector;

use Omeka\Api\Representation\ItemRepresentation;

/**
 * Interface for different PID Services.
 */
interface PIDSelectorInterface
{
    /**
     * Get a human-readable label for this PID Service.
     *
     * @return string
     */
    public function getLabel();
    
    /**
     * Process a single PID mint (create) request.
     *
     * @param string $username
     * @param string $password
     * @param string $pidShoulder
     * @param string $targetURI
     * @return string
     */
    public function mint($username, $password, $pidShoulder, $targetURI);
    
    /**
     * Process a single PID update request.
     *
     * @param string $username
     * @param string $password
     * @param string $existingPID
     * @param string $targetURI
     * @return string
     */
    public function update($username, $password, $existingPID, $targetURI);

    /**
     * Process a single PID delete request.
     *
     * @param string $username
     * @param string $password
     * @param string $pidToDelete
     * @return string
     */
    public function delete($username, $password, $pidToDelete);

    /**
     * Extract PID value from designated metadata field(s)
     * and test for validity.
     *
     * @param string $pidShoulder
     * @param array $existingFields
     * @param ItemRepresentation $itemRepresentation
     * @return string
     */
    public function extract($pidShoulder, $existingFields, $itemRepresentation);
}
