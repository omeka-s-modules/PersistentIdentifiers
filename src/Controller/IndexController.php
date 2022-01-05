<?php
namespace PersistentIdentifiers\Controller;

use PersistentIdentifiers\Form\ConfigForm;
use PersistentIdentifiers\Form\EZIDForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;
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
        $this->pidUsername = $this->settings->get('pid_username');
        $this->pidPassword = $this->settings->get('pid_password');
        $this->pidShoulder = $this->settings->get('pid_shoulder');
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
            'pid_shoulder' => $this->settings->get('pid_shoulder'),
            'ezid_username' => $this->settings->get('pid_username'),
            'ezid_password' => $this->settings->get('pid_password'),
        ]);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();

                $this->settings->set('pid_shoulder', $formData['pid_shoulder']);
                $this->settings->set('pid_username', $formData['ezid_username']);
                $this->settings->set('pid_password', $formData['ezid_password']);
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

    // Mint (create) PID via PID Service API and store in DB
    public function mintPID($pidService, $pidTarget, $itemID)
    {
        // If PIDs in existing fields, attempt to extract
        if ($this->settings->get('existing_pid_fields')) {
            $existingFields = $this->settings->get('existing_pid_fields');
            $response = $this->api()->read('items', $itemID);
            $itemRepresentation = $response->getContent();
            $existingPID = $pidService->extract($this->pidShoulder, $existingFields, $itemRepresentation);
            if ($existingPID) {
                // Attempt to update PID service with Omeka resource URI
                $addPID = $pidService->update($this->pidUsername, $this->pidPassword, $existingPID, $pidTarget);
            } else if (empty($extractOnly)) {
                // If no existing PID found and PID element checked, mint new PID
                $addPID = $pidService->mint($this->pidUsername, $this->pidPassword, $this->pidShoulder, $pidTarget);
            }
        } else {
            // Mint new PID
            $addPID = $pidService->mint($this->pidUsername, $this->pidPassword, $this->pidShoulder, $pidTarget);
        }

        if (!$addPID) {
            return null;
        } else {
            // Save to DB
            $this->storePID($addPID, $itemID);

            return $addPID;
        }
    }

    // Remove PID via PID Service API and delete from DB
    public function removePID($pidService, $toRemovePID, $itemID)
    {
        $deletedPID = $pidService->delete($this->pidUsername, $this->pidPassword, $toRemovePID);

        if (!$deletedPID) {
            return null;
        } else {
            // Delete from DB
            $this->deletePID($itemID);
            $this->messenger()->addSuccess('PID removed');
            return 'success';
        }
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

    public function extractPID($item)
    {
        // TODO: Look for PID values in designated metadata fields
        // store & update target via PID API if found
        // RegEx to look for specific syntax, or just take everything
        // and reject if HTTP error?
    }
}
