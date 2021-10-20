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
            'Omeka\Form\ResourceForm',
            'form.add_elements',
            [$this, 'addPIDElement']
        );
        
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.before',
            [$this, 'handleViewFormBefore']
        );
        
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.form.before',
            [$this, 'handleViewFormBefore']
        );
        
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.sidebar',
            [$this, 'handleShowItemSidebar']
        );
    }
    
    public function addPIDElement($event)
    {
        $form = $event->getTarget();
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $form->add([
                    'name' => 'o:pid[o:id]',
                    'type' => ModuleElement\PIDEditor::class,
                    'options' => [
                        'label' => 'Persistent Identifier', // @translate
                        'info' => 'Mint & assign PID from chosen service. (Note: PID is immediately assigned to item)', // @translate
                    ],
                ]);        
    }
    
    public function handleViewFormBefore(Event $event)
    {
        $view = $event->getTarget();
        echo $view->partial('persistent-identifiers/common/resource-fields');
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
