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
     * Does the PID service allow for session cookies/single login?
     *
     * @return bool
     */
    public function isSessionable();

    /**
     * Connect to PID service API and return access token
     *
     * @param string $username
     * @param string $password
     * @return string
     */
    public function connect($username, $password);
    
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
     * Process a batch PID mint (create) request.
     *
     * @param string $sessionCookie
     * @param string $pidShoulder
     * @param string $targetURI
     * @return string
     */
    public function batchMint($sessionCookie, $pidShoulder, $targetURI);

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
     * Process a batch PID delete request.
     *
     * @param string $sessionCookie
     * @param string $pidToDelete
     * @return string
     */
    public function batchDelete($sessionCookie, $pidToDelete);

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
