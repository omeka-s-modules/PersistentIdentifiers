<?php
namespace PersistentIdentifiers;

use Omeka\Module\AbstractModule;
use Omeka\Entity\Item;
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

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        // Allow all users to access item pages
        $acl->allow(
            null,
            ['PersistentIdentifiers\Api\Adapter\PIDItemAdapter',
             'PersistentIdentifiers\Entity\PidItem',
            ]
        );
        // Allow all visitors to view PID generic item landing page.
        $acl->allow(null, 'PersistentIdentifiers\Controller\Index', 'item-landing-page');
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec('CREATE TABLE pid_item (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, pid VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_C025A89B126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;');
        $connection->exec('ALTER TABLE pid_item ADD CONSTRAINT FK_C025A89B126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
    }
    
    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec('ALTER TABLE pid_item DROP FOREIGN KEY FK_C025A89B126F525E');
        $connection->exec('DROP TABLE pid_item');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        
        // Add PID element to item edit form
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.advanced',
            function (Event $event) {
                $view = $event->getTarget();
                $view->form->add([
                            'name' => 'o:pid[o:id]',
                            'type' => ModuleElement\PIDEditor::class,
                            'options' => [
                                'label' => 'Persistent Identifier', // @translate
                                'info' => 'Mint & assign PID from chosen service. (Note: PID is immediately assigned to item)', // @translate
                            ],
                        ]);
                $pid = $view->form->get('o:pid[o:id]');
                // Pass item resource to PID form for PID target
                $pid->setValue($view->resource);
                echo $view->formRow($pid);
            }
        );

        // Add PID checkbox to new item form
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.form.advanced',
            function (Event $event) {
                $view = $event->getTarget();
                $view->form->add([
                            'name' => 'o:pid[o:id]',
                            'type' => 'checkbox',
                            'options' => [
                                'label' => 'Assign Persistent Identifier', // @translate
                                'info' => 'Mint & assign PID from chosen service.', // @translate
                            ],
                        ]);
                $pid = $view->form->get('o:pid[o:id]');
                // Disable checkbox and automatically assign PID if specified in settings
                if ($view->setting('pid_assign_all')) {
                    $pid->setAttribute('value', true);
                    $pid->setAttribute('disabled', true);
                }
                // Pass item resource to PID form for PID target
                $pid->setValue($view->resource);
                echo $view->formRow($pid);
            }
        );

        // Mint PID for newly created item
        $sharedEventManager->attach(
            '*',
            'api.create.post',
            function (Event $event) {
                $settings = $this->getServiceLocator()->get('Omeka\Settings');

                $requestContent = $event->getParam('request')->getContent();
                $addObject = $event->getParam('response')->getContent();
                $adapter = $event->getTarget();
                $itemRepresentation = $adapter->getRepresentation($addObject);

                // If PID element and pid_assign_all unchecked, only attempt extraction (no new minting)
                $extractOnly = (empty($requestContent['o:pid']['o:id']) && empty($settings->get('pid_assign_all'))) ? true : false;

                // If PID element or pid_assign_all checked,
                // mint/update and store PID
                if ((!empty($requestContent['o:pid']['o:id'])
                    || !empty($settings->get('pid_assign_all')))
                    && $adapter->getResourceName() == 'items') {
                        $this->mintPID($itemRepresentation, $extractOnly);
                }
            }
        );

        // Add PID to item display sidebar
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.sidebar',
            function (Event $event) {
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
        );

        // Add PID action radio buttons to the resource batch update form.
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_elements',
            function (Event $event) {
                $form = $event->getTarget();
                $resourceType = $form->getOption('resource_type');
                if ('item' !== $resourceType) {
                    // This is not an item batch update form.
                    return;
                }
                $form->add([
                            'name' => 'batch_pid_action',
                            'type' => 'radio',
                            'options' => [
                                'label' => 'Persistent Identifiers', // @translate
                                'info' => 'Mint & assign PID to any item that does not already have one, or remove any existing PIDs.', // @translate
                                'value_options' => [
                                    'mint' => 'Mint PIDs', // @translate
                                    'remove' => 'Remove PIDs', // @translate
                                    '' => '[No action]', // @translate
                                ],
                            ],
                            'attributes' => [
                                'value' => '',
                            ],
                        ]);
            }
        );

        // Don't require PID action value to the resource batch update form.
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_input_filters',
            function (Event $event) {
                $inputFilter = $event->getParam('inputFilter');
                $inputFilter->add([
                    'name' => 'batch_pid_action',
                    'required' => false,
                ]);
            }
        );

        // Authorize 'batch_pid_action' when preprocessing batch update data.
        // This signals to mint or delete PID while updating each item.
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.preprocess_batch_update',
            function (Event $event) {
                $adapter = $event->getTarget();
                $data = $event->getParam('data');
                $rawData = $event->getParam('request')->getContent();
                if (isset($rawData['batch_pid_action'])
                    && in_array($rawData['batch_pid_action'], ['mint', 'remove'])
                ) {
                    $data['batch_pid_action'] = $rawData['batch_pid_action'];
                }
                $event->setParam('data', $data);
            }
        );
        
        // After hydrating, mint or delete PID for item according to 'batch_pid_action'.
        // When minting, skip items with existing PID. When deleting, skip items with no PID.
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            function (Event $event) {
                $item = $event->getParam('entity');
                $data = $event->getParam('request')->getContent();
                $action = isset($data['batch_pid_action']) ? $data['batch_pid_action'] : '';
                $adapter = $event->getTarget();
                $itemRepresentation = $adapter->getRepresentation($item);

                $api = $this->getServiceLocator()->get('Omeka\ApiManager');
                $response = $api->search('pid_items', ['item_id' => $item->getId()]) ?: '';
                $PIDcontent = $response->getContent();

                // If mint action selected and no PID exists, mint and store PID
                if (('mint' === $action) && empty($PIDcontent)) {
                    $this->mintPID($itemRepresentation);
                }

                // If remove action selected and PID exists, remove and delete
                if (('remove' === $action) && !empty($PIDcontent)) {
                    $itemPID = $PIDcontent[0]->getPID();
                    $this->removePID($itemRepresentation, $itemPID);
                }
            }
        );
    }

    public function mintPID($itemRepresentation, $extractOnly = false)
    {

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $api = $services->get('Omeka\ApiManager');

        // Set selected PID service
        $pidSelector = $services->get('PersistentIdentifiers\PIDSelectorManager');
        $pidSelectedService = $settings->get('pid_service');
        $pidService = $pidSelector->get($pidSelectedService);

        $pidTarget = $itemRepresentation->apiUrl();
        $itemID = $itemRepresentation->id();

        // If PIDs in existing fields, attempt to extract
        if ($settings->get('existing_pid_fields')) {
            $existingFields = $settings->get('existing_pid_fields');
            $existingPID = $pidService->extract($existingFields, $itemRepresentation);
            if ($existingPID) {
                // Attempt to update PID service with Omeka resource URI
                $addPID = $pidService->update($existingPID, $pidTarget, $itemRepresentation);
            } else if (empty($extractOnly)) {
                // If no existing PID found and PID element checked, mint new PID
                $addPID = $pidService->mint($pidTarget, $itemRepresentation);
            }
        } else {
            // Mint new PID
            $addPID = $pidService->mint($pidTarget, $itemRepresentation);
        }

        if (!$addPID) {
            return;
        } else {
            // Save to DB
            $json = [
                'o:item' => ['o:id' => $itemID],
                'pid' => $addPID,
            ];

            $api->create('pid_items', $json);
        }
    }

    public function removePID($itemRepresentation, $itemPID)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $api = $services->get('Omeka\ApiManager');

        // Set selected PID service
        $pidSelector = $services->get('PersistentIdentifiers\PIDSelectorManager');
        $pidSelectedService = $settings->get('pid_service');
        $pidService = $pidSelector->get($pidSelectedService);

        $itemID = $itemRepresentation->id();

        // Attempt to remove PID/target URI from PID Service
        $deletedPID = $pidService->delete($itemPID);

        // Ensure PID record exists
        $response = $api->search('pid_items', ['item_id' => $itemID]);
        $content = $response->getContent();
        if (empty($content)) {
            return;
        } else {
            // Delete PID record in DB
            $PIDrecord = $content[0];
            $api->delete('pid_items', $PIDrecord->id());
        }
    }
}
