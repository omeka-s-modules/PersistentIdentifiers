<?php
namespace PersistentIdentifiers\View\Helper;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

class FormPIDEditor extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        $resource = $element->getValue();
        return $view->partial('persistent-identifiers/common/pid-edit-form', [
            'itemID' => $resource->id(),
            'itemAPIURL' => $resource->apiUrl()
        ]);
    }
}