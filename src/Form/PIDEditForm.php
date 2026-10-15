<?php
namespace PersistentIdentifiers\Form;

use Laminas\Form\Form;

/**
 * Form for minting/removing an item's PID via Ajax.
 *
 * Has no elements of its own; Omeka's form initializer adds the CSRF element
 * that the pid-edit endpoint validates.
 */
class PIDEditForm extends Form
{
    public function init()
    {
    }
}
