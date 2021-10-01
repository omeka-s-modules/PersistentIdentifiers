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
    
    public function mintPID($pidTarget, $itemID)
    {   
        // TODO: End session after item save
        $sessionCookie = $this->startPIDSession();
        $shoulder = $this->pidEditAPI . $this->pidShoulder;
        // append prefix to pid target, if any
        $target = isset($this->pidTargetPrefix) ? $this->pidTargetPrefix . $pidTarget : $pidTarget;

        $request = $this->client
            ->setUri($shoulder)
            ->setMethod('POST')
            ->setRawBody($target);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: text/plain')
                                            ->addHeaderLine('Cookie: ' . $sessionCookie);
        $response = $request->send();
        if (!$response->isSuccess()) {
            $this->setValue("Could not connect to PID Service, check settings. " . $response->getBody()); // @translate
        } else {
            // TODO: make more generic
            $newPID = str_replace('success: ', '', $response->getBody());
            
            $this->setValue($newPID);
            
            // Save to DB
            $this->storePID($newPID, $itemID);
            
        }
    }
    
    public function removePID($pidTarget, $toRemovePID, $itemID)
    {   
        // TODO: End session after item save
        $sessionCookie = $this->startPIDSession();
        $shoulder = $this->pidUpdateAPI . $toRemovePID;
        // append prefix to (empty) pid target, if any
        $target = isset($this->pidTargetPrefix) ? $this->pidTargetPrefix . $pidTarget : $pidTarget;
        
        $request = $this->client
            ->setUri($shoulder)
            ->setMethod('PUT')
            ->setRawBody($target);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: text/plain')
                                            ->addHeaderLine('Cookie: ' . $sessionCookie);
        // $request->getRequest()->getQuery()->setQuery('update_if_exists=yes');
        $request->getRequest()->setQuery(new Parameters(['update_if_exists' => 'yes']));
        $response = $request->send();
        if (!$response->isSuccess()) {
            $this->setValue("Could not connect to PID Service, check settings. " . $response->getBody()); // @translate
        } else {            
            // Delete from DB
            $this->deletePID($itemID);
        }
    }
    
    // Logs in to PID API and returns access token
    public function startPIDSession()
    {
        $uri = $this->pidAuthAPI;
        $username = $this->pidUsername;
        $password = $this->pidPassword;
        $request = $this->client
            ->setUri($uri)
            ->setMethod('GET')
            ->setAuth($username, $password);
        $response = $request->send();
        if (!$response->isSuccess()) {
            return;
        } else {            
            // Retrieve sessionID cookie
            $cookieString = $response->getHeaders()->toArray()['Set-Cookie'][0];
            $cookieArray = explode(';', $cookieString);
            $sessionID = $cookieArray[0];
            
            return $sessionID;
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