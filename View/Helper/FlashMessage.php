<?php
/**
 * Ooba_View_Helper_FlashMessage
 *
 * @category   Ooba
 * @package    View
 * @subpackage Helper
 * @copyright  (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 *
 */
class Ooba_View_Helper_FlashMessage extends Zend_View_Helper_Abstract
{
    /**
     * Default messenger instance
     *
     * @var FlashMessenger
     */
    protected $_defaultMessenger;

    /**
     * Setup the ooba flash messenger
     * 
     * @param  string $namespace (Optional) The namespace from which to retrieve the message
     * @return Ooba_View_Helper_FlashMessage
     */
    public function flashMessage($namespace = 'default')
    {
        $this->_defaultMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')
                ->setNamespace($namespace);
        return $this;
    }

    /**
     * Add a message
     *
     * @param  string $message Message
     * @return void
     */
    public function addMessage($message)
    {
        $this->_defaultMessenger->addMessage($message);
    }

    /**
     * Get a message for the specified namespace
     *
     * @return string
     */
    public function getMessage()
    {
        $messages = $this->_defaultMessenger->getCurrentMessages();

        return $messages[0];
    }

    /**
     * Get a message for the specified namespace
     *
     * @return boolean
     */
    public function hasMessage()
    {
        $messages = $this->_defaultMessenger->getCurrentMessages();

        if (count($messages) > 0) {
            return true;
        }

        return false;
    }
    
    /**
     * Clear the messages for the current namespace
     * 
     * @return boolean
     */
    public function clearMessages()
    {
        return $this->_defaultMessenger->clearCurrentMessages();
    }
}