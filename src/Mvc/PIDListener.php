<?php
namespace PersistentIdentifiers\Mvc;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;

class PIDListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'redirectToItem'], 0);
    }

    public function redirectToItem(MvcEvent $event)
    {
        if (!$this->isValidRoute($event)) {
            return;
        }
        if (!$this->isValidRequest($event)) {
            return;
        }

        // Set route to generic PID item landing page
        $url = $event->getRouter()->assemble(
            ['id' => $event->getRouteMatch()->getParam('id')],
            ['name' => 'PIDitem']
        );
        $response = $event->getResponse();
        
        // Set Location in headers and use HTTP 302 to trigger redirect
        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode(302);
        $response->sendHeaders();
        return $response;
    }

    // Ensure initial route is for an item with id via API
    protected function isValidRoute(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        $matchedRouteName = $routeMatch->getMatchedRouteName();
        if ('api/default' !== $matchedRouteName) {
            return false;
        }
        $resource = $routeMatch->getParam('resource');
        if ('items' !== $resource) {
            return false;
        }
        $id = $routeMatch->getParam('id');
        if (null === $id) {
            return false;
        }
        return true;
    }

    // Ensure request headers accept text/html (i.e. request is from browser).
    protected function isValidRequest(MvcEvent $event)
    {
        $request = $event->getRequest();
        $acceptHeader = $request->getHeaders()->get('Accept')->getFieldValue();
        if (strpos($acceptHeader, 'text/html') === false) {
            return false;
        }
        return true;
    }
}
