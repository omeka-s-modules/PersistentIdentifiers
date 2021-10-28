<?php
namespace PersistentIdentifiers\PIDSelector;

use Laminas\Http\Client as HttpClient;
use Laminas\Stdlib\Parameters;

/**
 * Use EZID service to mint/update ARK identifiers
 */
class EZID implements PIDSelectorInterface
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
        return 'EZID'; // @translate
    }

    public function connect($username, $password)
    {
        $pidConnectAPI = 'https://ezid.cdlib.org/login';

        $request = $this->client
            ->setUri($pidConnectAPI)
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

    public function mint($sessionCookie, $pidShoulder, $targetURI)
    {
        // Build organization-specific mint URL
        $shoulder = 'https://ezid.cdlib.org/shoulder/' . $pidShoulder;
        // append EZID required prefix to pid target
        $target = '_target: ' . $targetURI;

        $request = $this->client
            ->setUri($shoulder)
            ->setMethod('POST')
            ->setRawBody($target);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: text/plain')
                                            ->addHeaderLine('Cookie: ' . $sessionCookie);
        $response = $request->send();
        if (!$response->isSuccess()) {
            return;
        } else {
            $newPID = str_replace('success: ', '', $response->getBody());
            return $newPID;
        }
    }

    /**
     * Process a PID delete request.
     *
     * @param string $sessionCookie
     * @param string $pidToDelete
     * @return string
     */
    public function delete($sessionCookie, $pidToDelete){
        
        // Build organization-specific delete URL
        $shoulder = 'https://ezid.cdlib.org/id/' . $pidToDelete;
        // Set EZID required prefix with empty value
        $target = '_target:';
        
        // Remove target via PID API
        $request = $this->client
            ->setUri($shoulder)
            ->setMethod('PUT')
            ->setRawBody($target);
        $request->getRequest()->getHeaders()->addHeaderLine('Content-type: text/plain')
                                            ->addHeaderLine('Cookie: ' . $sessionCookie);
        $request->getRequest()->setQuery(new Parameters(['update_if_exists' => 'yes']));
        $response = $request->send();
        if (!$response->isSuccess()) {
            return;
        } else {
            $deletedPID = str_replace('success: ', '', $response->getBody());
            return $deletedPID;
        }
    }
}