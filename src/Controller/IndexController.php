<?php
namespace PersistentIdentifiers\Controller;

use PersistentIdentifiers\Form\ConfigForm;
use PersistentIdentifiers\Form\EZIDForm;
use PersistentIdentifiers\Form\Element\PIDEditor;
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
        $this->services = $services;
    }
    
    public function indexAction()
    {
        $view = new ViewModel;
        $form = $this->getForm(ConfigForm::class);
        $form->setData([
            'assign_all' => $this->settings->get('assign_all'),
            'assign_existing' => $this->settings->get('assign_existing'),
            'pid_service' => $this->settings->get('pid_service'),
        ]);
        $view->setVariable('form', $form);
        
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $this->settings->set('assign_all', $formData['assign_all']);
                $this->settings->set('assign_existing', $formData['assign_existing']);
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
    
    public function pidEditAction()
    {        
        $response = $this->getResponse();
        $target = isset($_POST['target']) ? $_POST['target'] : null;
        $itemID = isset($_POST['itemID']) ? $_POST['itemID'] : null;
        
        // Build an editor object and either retrieve or generate PID
        // pointing to $target
        $editor = new PIDEditor();
        $editor->setClient($this->services->get('Omeka\HttpClient'));
        $editor->setApi($this->services->get('Omeka\ApiManager'));
        $editor->setPidUsername($this->settings->get('pid_username'));
        $editor->setPidPassword($this->settings->get('pid_password'));
        $editor->setPidShoulder($this->settings->get('pid_shoulder'));
        $editor->setPidEditAPI($this->settings->get('pid_editAPI'));
        $editor->setPidAuthAPI($this->settings->get('pid_authAPI'));
        $editor->setPidUpdateAPI($this->settings->get('pid_updateAPI'));
        
        if (isset($_POST['toRemovePID'])) {
            $editor->removePID($this->services, $_POST['toRemovePID'], $itemID);
            $this->messenger()->addSuccess('PID removed');
        } else {
            // Mint and store new PID
            $editor->mintPID($this->services, $target, $itemID);
        }

        return $response->setContent($editor->getValue());
    }

}
