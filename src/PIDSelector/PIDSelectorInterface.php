<?php
namespace PersistentIdentifiers\PIDSelector;

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
     * Connect to PID service API and return access token
     *
     * @param string $username
     * @param string $password
     * @return string
     */
    public function connect($username, $password);
    
    /**
     * Process a PID mint (create) request.
     *
     * @param string $sessionCookie
     * @param string $pidShoulder     
     * @param string $targetURI
     * @return string
     */
    public function mint($sessionCookie, $pidShoulder, $targetURI);

    /**
     * Process a PID delete request.
     *
     * @param string $sessionCookie
     * @param string $pidToDelete
     * @return string
     */
    public function delete($sessionCookie, $pidToDelete);
}