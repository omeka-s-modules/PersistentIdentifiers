<?php
namespace PersistentIdentifiers\View\Helper;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PersistentIdentifiers\Form\PIDEditForm;

class FormPIDEditor extends AbstractHelper
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $formElementManager;

    public function __construct(ServiceLocatorInterface $formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        $resource = $element->getValue();
        $form = $this->formElementManager->get(PIDEditForm::class);
        return $view->partial('persistent-identifiers/common/pid-edit-form', [
            'itemID' => $resource->id(),
            'csrfToken' => $form->get('csrf')->getValue(),
        ]);
    }
}
