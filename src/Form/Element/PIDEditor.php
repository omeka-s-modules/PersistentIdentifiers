<?php
namespace PersistentIdentifiers\Form\Element;

use Laminas\Form\Element;
use Laminas\Http\Client;
use Laminas\Stdlib\Parameters;
use Omeka\Api\Manager;

class PIDEditor extends Element
{        

    protected $client;
    
    protected $api;
    
    // Add PID to database
    public function storePID($pid, $itemID)
    {        
        $json = [
            'o:item' => ['o:id' => $itemID],
            'pid' => $pid,
        ];
        
        // See if PID record already exists
        $response = $this->api->search('pid_items', ['item_id' => $itemID]);
        $content = $response->getContent();
        if (empty($content)) {     
            // Create new PID record in DB       
            $response = $this->api->create('pid_items', $json);            
        } else {
            // Update PID record in DB
            $PIDrecord = $content[0];        
            $response = $this->api->update('pid_items', $PIDrecord->id(), $json);
        }        
    }
    
    // Delete PID from database
    public function deletePID($itemID)
    {        
        // Ensure PID record already exists
        $response = $this->api->search('pid_items', ['item_id' => $itemID]);
        $content = $response->getContent();
        if (empty($content)) {     
            return 'No PID record found in database!';           
        } else {
            // Delete PID record in DB
            $PIDrecord = $content[0];    
            $this->api->delete('pid_items', $PIDrecord->id());
        }        
    }
    
    public function extractPID($item)
    {        
        // TODO: Look for PID values in designated metadata fields 
        // store & update target via PID API if found
        // RegEx to look for specific syntax, or just take everything
        // and reject if HTTP error?
    }
    
    public function getPIDService($services)
    {
        $pidSelector = $services->get('PersistentIdentifiers\PIDSelectorManager');
        $pidSelectedService = $services->get('Omeka\Settings')->get('pid_service');
        return $pidSelector->get($pidSelectedService);
    }
    
    public function mintPID($services, $pidTarget, $itemID)
    {   
        $pidService = $this->getPIDService($services);

        // TODO: End session after item save
        $sessionCookie = $pidService->connect($this->pidUsername, $this->pidPassword);
        if (!$sessionCookie) {
            $this->setValue('');
            return;
        }

        $newPID = $pidService->mint($sessionCookie, $this->pidShoulder, $pidTarget);
        
        if (!$newPID) {
            $this->setValue('');
        } else {
            $this->setValue($newPID);
            
            // Save to DB
            $this->storePID($newPID, $itemID);        
        }
    }
    
    public function removePID($services, $toRemovePID, $itemID)
    {   
        $pidService = $this->getPIDService($services);

        // TODO: End session after item save
        $sessionCookie = $pidService->connect($this->pidUsername, $this->pidPassword);
        if (!$sessionCookie) {
            $this->setValue('');
            return;
        }

        $deletedPID = $pidService->delete($sessionCookie, $toRemovePID);

        if (!$deletedPID) {
            $this->setValue('');
        } else {            
            $this->setValue('success');
            
            // Delete from DB
            $this->deletePID($itemID);
        }
    }
    
    public function setClient(Client $client)
    {
        $this->client = $client;
    }
    
    public function setApi(Manager $api)
    {
        $this->api = $api;
    }
    
    public function setPidUsername($id)
    {
        $this->pidUsername = $id;
    }

    public function setPidPassword($password)
    {
        $this->pidPassword = $password;
    }

    public function setPidShoulder($uri)
    {
        $this->pidShoulder = $uri;
    }
    
    public function setPidEditAPI($editAPI)
    {
        $this->pidEditAPI = $editAPI;
    }
    
    public function setPidAuthAPI($authAPI)
    {
        $this->pidAuthAPI = $authAPI;
    }
    
    public function setPidUpdateAPI($updateAPI)
    {
        $this->pidUpdateAPI = $updateAPI;
    }
    
    public function setPidTargetPrefix($targetPrefix)
    {
        $this->pidTargetPrefix = $targetPrefix;
    }
}