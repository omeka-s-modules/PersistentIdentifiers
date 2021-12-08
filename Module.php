<?php
namespace PersistentIdentifiers;

use Omeka\Module\AbstractModule;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Form\Fieldset;
use PersistentIdentifiers\Form\Element as ModuleElement;
use Laminas\Mvc\MvcEvent;
use Laminas\EventManager\Event;
use PersistentIdentifiers\Form\ConfigForm;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec("CREATE TABLE piditem (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, pid VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_C127A48126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;");
        $connection->exec("ALTER TABLE piditem ADD CONSTRAINT FK_C127A48126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;");
    }
    
    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec("ALTER TABLE piditem DROP FOREIGN KEY FK_C127A48126F525E;");
        $connection->exec('DROP TABLE piditem');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.before',
            [$this, 'handleEditFormBefore']
        );
        
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.form.before',
            [$this, 'handleAddFormBefore']
        );

        $sharedEventManager->attach(
            '*',
            'api.create.post',
            [$this, 'handleAddFormAfter']
        );
        
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.sidebar',
            [$this, 'handleShowItemSidebar']
        );
    }
    
    public function handleEditFormBefore(Event $event)
    {
        $view = $event->getTarget();
        echo $view->partial('persistent-identifiers/common/resource-fields-edit');
    }
    
    public function handleAddFormBefore(Event $event)
    {
        $view = $event->getTarget();
        echo $view->partial('persistent-identifiers/common/resource-fields-add');
    }

    public function handleAddFormAfter(Event $event)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $api = $services->get('Omeka\ApiManager');

        $requestContent = $event->getParam('request')->getContent();
        $addObject = $event->getParam('response')->getContent();
        $adapter = $event->getTarget();
        $addObjectRepresentation = $adapter->getRepresentation($addObject);

        // If PID element checked, mint and store new PID
        if (!empty($requestContent['o:pid']['o:id'])) {
            // Set selected PID service
            $pidSelector = $services->get('PersistentIdentifiers\PIDSelectorManager');
            $pidSelectedService = $settings->get('pid_service');
            $pidService = $pidSelector->get($pidSelectedService);

            $pidUsername = $settings->get('pid_username');
            $pidPassword = $settings->get('pid_password');
            $pidShoulder = $settings->get('pid_shoulder');
            $pidTarget = $addObjectRepresentation->apiUrl();
            $itemID = $addObjectRepresentation->id();

            // TODO: End session after item save
            $sessionCookie = $pidService->connect($pidUsername, $pidPassword);
            if (!$sessionCookie) {
                return;
            }

            // Mint and store new PID
            $newPID = $pidService->mint($sessionCookie, $pidShoulder, $pidTarget);

            if (!$newPID) {
                return;
            } else {
                // Save to DB
                $json = [
                    'o:item' => ['o:id' => $itemID],
                    'pid' => $newPID,
                ];

                $response = $api->create('pid_items', $json);
            }
        }
    }
    
    public function handleShowItemSidebar(Event $event)
    {
        $view = $event->getTarget();
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('pid_items', ['item_id' => $view->item->id()]) ?: '';
        $PIDcontent = $response->getContent();
        if (!empty($PIDcontent)) {
            $PIDrecord = $PIDcontent[0];
            echo '<div class="meta-group">';
            echo '<h4>' . $view->translate('Persistent Identifier') . '</h4>';
            echo '<div class="value">' . $PIDrecord->getPID() . '</div>';
            echo '</div>';
        }
    }
}
