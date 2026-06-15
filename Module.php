<?php
namespace PersistentIdentifiers;

use Omeka\Module\AbstractModule;
use Omeka\Entity\Item;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use PersistentIdentifiers\Form\Element as ModuleElement;
use Laminas\Mvc\MvcEvent;
use Laminas\EventManager\Event;

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
        $acl->allow(null, 'PersistentIdentifiers\Controller\Index', 'ark-landing-page');
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
                // Pass item resource to PID form for PID target
                $pid->setValue($view->resource);
                // Check and disable checkbox when pid_assign_all is on
                if ($view->setting('pid_assign_all')) {
                    $pid->setAttribute('checked', true);
                    $pid->setAttribute('disabled', true);
                }
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

                if ($adapter->getResourceName() !== 'items') {
                    return;
                }

                $pidChecked = !empty($requestContent['o:pid']['o:id']);
                $assignAll = !empty($settings->get('pid_assign_all'));
                $hasExistingFields = !empty($settings->get('existing_pid_fields'));

                // Skip if pid_assign_all unchecked and no existing PID fields set
                if (!$pidChecked && !$assignAll && !$hasExistingFields) {
                    return;
                }

                // Extract existing PIDs but don't mint new ones if pid_assign_all unchecked
                $extractOnly = (!$pidChecked && !$assignAll);

                $this->mintPID($itemRepresentation, $extractOnly);
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
                $action = $data['batch_pid_action'] ?? '';
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

        // If pid_store_property set, re-inject into record during hydration
        // Since 'Mint PID' button is done via JS and invisible to initial form load
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            function (Event $event) {
                $services = $this->getServiceLocator();
                $settings = $services->get('Omeka\Settings');
                $storeProperty = $settings->get('pid_store_property');
                if (!$storeProperty) {
                    return;
                }

                $request = $event->getParam('request');
                if ($request->getOperation() !== 'update') {
                    return;
                }

                $item = $event->getParam('entity');
                $api = $services->get('Omeka\ApiManager');
                $pidResults = $api->search('pid_items', ['item_id' => $item->getId()])->getContent();
                if (empty($pidResults)) {
                    return;
                }
                $pid = $pidResults[0]->getPID();

                [$prefix, $localName] = explode(':', $storeProperty, 2);
                $vocabs = $api->search('vocabularies', ['prefix' => $prefix])->getContent();
                if (empty($vocabs)) {
                    return;
                }
                $properties = $api->search('properties', [
                    'vocabulary_id' => $vocabs[0]->id(),
                    'local_name' => $localName,
                ])->getContent();
                if (empty($properties)) {
                    return;
                }
                $propertyId = $properties[0]->id();

                foreach ($item->getValues() as $existing) {
                    if ($existing->getProperty()->getId() === $propertyId
                        && $existing->getValue() === $pid
                    ) {
                        return;
                    }
                }

                $em = $services->get('Omeka\EntityManager');
                $propertyEntity = $em->find('Omeka\Entity\Property', $propertyId);
                if (!$propertyEntity) {
                    return;
                }

                $value = new \Omeka\Entity\Value();
                $value->setType('literal');
                $value->setResource($item);
                $value->setProperty($propertyEntity);
                $value->setValue($pid);
                $value->setIsPublic(true);
                $em->persist($value);
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
            } elseif (empty($extractOnly)) {
                // If no existing PID found and PID element checked, mint new PID
                $addPID = $pidService->mint($pidTarget, $itemRepresentation);
            }
        } elseif (empty($extractOnly)) {
            // Mint new PID (skip if we were only asked to extract)
            $addPID = $pidService->mint($pidTarget, $itemRepresentation);
        }

        if (!$addPID) {
            return;
        }

        // Save to DB
        $api->create('pid_items', [
            'o:item' => ['o:id' => $itemID],
            'pid' => $addPID,
        ]);

        // Write to metadata property if configured
        $storeProperty = $settings->get('pid_store_property');
        if ($storeProperty) {
            // Avoid duplicates
            foreach ($itemRepresentation->value($storeProperty, ['all' => true]) as $value) {
                if ((string) $value === $addPID) {
                    return;
                }
            }

            [$prefix, $localName] = explode(':', $storeProperty, 2);
            $vocabs = $api->search('vocabularies', ['prefix' => $prefix])->getContent();
            if (!empty($vocabs)) {
                $properties = $api->search('properties', [
                    'vocabulary_id' => $vocabs[0]->id(),
                    'local_name' => $localName,
                ])->getContent();
                if (!empty($properties)) {
                    $api->update('items', $itemID, [
                        $storeProperty => [[
                            'type' => 'literal',
                            'property_id' => $properties[0]->id(),
                            '@value' => $addPID,
                        ]],
                    ], [], ['isPartial' => true, 'collectionAction' => 'append']);
                }
            }
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
