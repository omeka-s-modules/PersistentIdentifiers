<?php
namespace PersistentIdentifiers\Controller;

use PersistentIdentifiers\Form\ConfigForm;
use PersistentIdentifiers\Form\EZIDForm;
use PersistentIdentifiers\Form\DataCiteForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
use Omeka\Api\Exception as ApiException;
use Omeka\Settings\Settings;
use Omeka\Stdlib\Message;

class IndexController extends AbstractActionController
{
    /**
     * @var Settings
     */
    protected $settings;
    
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    public function __construct(Settings $settings, ServiceLocatorInterface $services)
    {
        $this->settings = $settings;
        $this->services = $services;
        $this->api = $this->services->get('Omeka\ApiManager');
    }
    
    public function indexAction()
    {
        $view = new ViewModel;
        $form = $this->getForm(ConfigForm::class);
        $form->setData([
            'pid_assign_all' => $this->settings->get('pid_assign_all'),
            'existing_pid_fields' => $this->settings->get('existing_pid_fields'),
            'pid_service' => $this->settings->get('pid_service'),
        ]);
        $view->setVariable('form', $form);
        
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $this->settings->set('pid_assign_all', $formData['pid_assign_all']);
                $this->settings->set('existing_pid_fields', $formData['existing_pid_fields']);
                $this->settings->set('pid_service', $formData['pid_service']);
            }
        }

        return $view;
    }

    public function EzidConfigurationAction()
    {
        $view = new ViewModel;
        $form = $this->getForm(EZIDForm::class);
        $form->setData([
            'ezid_shoulder' => $this->settings->get('ezid_shoulder'),
            'ezid_username' => $this->settings->get('ezid_username'),
            'ezid_password' => $this->settings->get('ezid_password'),
        ]);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();

                $this->settings->set('ezid_shoulder', $formData['ezid_shoulder']);
                $this->settings->set('ezid_username', $formData['ezid_username']);
                $this->settings->set('ezid_password', $formData['ezid_password']);
            }
        }

        return $view;
    }
    
    public function DataciteConfigurationAction()
    {
        $view = new ViewModel;
        $form = $this->getForm(DataCiteForm::class);
        $form->setData([
            'datacite_prefix' => $this->settings->get('datacite_prefix'),
            'datacite_username' => $this->settings->get('datacite_username'),
            'datacite_password' => $this->settings->get('datacite_password'),
        ]);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();

                $this->settings->set('datacite_prefix', $formData['datacite_prefix']);
                $this->settings->set('datacite_username', $formData['datacite_username']);
                $this->settings->set('datacite_password', $formData['datacite_password']);
                $this->settings->set('datacite_title_property', $formData['required-metadata']['datacite_title_property']);
                $this->settings->set('datacite_creators_property', $formData['required-metadata']['datacite_creators_property']);
                $this->settings->set('datacite_publisher_property', $formData['required-metadata']['datacite_publisher_property']);
                $this->settings->set('datacite_publicationYear_property', $formData['required-metadata']['datacite_publicationYear_property']);
                $this->settings->set('datacite_resourceTypeGeneral_property', $formData['required-metadata']['datacite_resourceTypeGeneral_property']);
            }
        }

        return $view;
    }

    // Mint and/or remove PID (called via Ajax from pid-form)
    public function pidEditAction()
    {        
        $response = $this->getResponse();
        $target = isset($_POST['target']) ? $_POST['target'] : null;
        $itemID = isset($_POST['itemID']) ? $_POST['itemID'] : null;

        $pidSelector = $this->services->get('PersistentIdentifiers\PIDSelectorManager');
        $pidSelectedService = $this->settings->get('pid_service');
        $pidService = $pidSelector->get($pidSelectedService);
        
        if (isset($_POST['toRemovePID'])) {
            $deletedPID = $this->removePID($pidService, $_POST['toRemovePID'], $itemID);
            return $response->setContent($deletedPID);
        } else {
            // Mint and store new PID
            $mintedPID = $this->mintPID($pidService, $target, $itemID);
            return $response->setContent($mintedPID);
        }
    }

    public function itemLandingPageAction()
    {
        $view = new ViewModel;

        // Retrieve PID for display on item page
        $PIDresponse = $this->api()->search('pid_items', ['item_id' => $this->params('id')]);
        $PIDcontent = $PIDresponse->getContent();
        if (!empty($PIDcontent)) {
            $PIDrecord = $PIDcontent[0];
            $view->setVariable('pid', $PIDrecord->getPID());
        }

        // Display 'tombstone' message if item not found
        try {
            $response = $this->api()->read('items', $this->params('id'));
        } catch (ApiException\NotFoundException $e) {
            $view->setVariable('missingID', $this->params('id'));
            return $view;
        }

        $item = $response->getContent();
        $view->setVariable('item', $item);

        return $view;
    }

    // Mint (create) PID via PID Service API and store in DB
    public function mintPID($pidService, $pidTarget, $itemID)
    {
        // Get Item Representation to access metadata as needed
        $response = $this->api()->read('items', $itemID);
        $itemRepresentation = $response->getContent();

        // If PIDs in existing fields, attempt to extract
        if ($this->settings->get('existing_pid_fields')) {
            $existingFields = $this->settings->get('existing_pid_fields');
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
        if (empty($addPID)) {
            return null;
        } else {
            // Save to DB
            $this->storePID($addPID, $itemID);

            return $addPID;
        }
    }

    // Attempt to remove PID/target URI from PID Service and delete from DB
    public function removePID($pidService, $toRemovePID, $itemID)
    {
        $deletedPID = $pidService->delete($toRemovePID);

        // Delete from DB
        $this->deletePID($itemID);
        $this->messenger()->addSuccess('PID removed');
        return 'success';
    }

    // Add PID to Omeka database
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

    // Delete PID from Omeka database
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
}
