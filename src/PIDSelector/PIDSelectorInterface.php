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
     * @param string $targetURI
     * @param ItemRepresentation $itemRepresentation
     * @return string
     */
    public function mint($targetURI, $itemRepresentation);
    
    /**
     * Process a single PID update request.
     *
     * @param string $existingPID
     * @param string $targetURI
     * @param ItemRepresentation $itemRepresentation
     * @return string
     */
    public function update($existingPID, $targetURI, $itemRepresentation);

    /**
     * Process a single PID delete request.
     *
     * @param string $pidToDelete
     * @return string
     */
    public function delete($pidToDelete);

    /**
     * Extract PID value from designated metadata field(s)
     * and test for validity.
     *
     * @param array $existingFields
     * @param ItemRepresentation $itemRepresentation
     * @return string
     */
    public function extract($existingFields, $itemRepresentation);
}
