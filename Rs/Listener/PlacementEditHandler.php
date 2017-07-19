<?php
namespace Rs\Listener;

use Tk\Event\Subscriber;

/**
 * Class StartupHandler
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementEditHandler implements Subscriber
{


    /**
     * Check the user has access to this controller
     *
     * @param \Tk\Event\ControllerEvent $event
     */
    public function onController(\Tk\Event\ControllerEvent $event)
    {
        $plugin = \Rs\Plugin::getInstance();

        $controller = $event->getController();
        if ($controller instanceof \App\Controller\Placement\Edit) {
            vd('doDefault()');
            $controller->getForm()->addField(new \Tk\Form\Field\Html('this is some shite'));
        }

    }

    /**
     * Check the user has access to this controller
     *
     * @param \Tk\Event\ControllerResultEvent $event
     */
    public function onView(\Tk\Event\ControllerResultEvent $event)
    {
        $plugin = \Rs\Plugin::getInstance();

        $controller = $event->getController();
        if ($controller instanceof \App\Controller\Placement\Edit) {
            vd('onView()');
            $controller->getForm()->addField(new \Tk\Form\Field\Html('this is some shite'));
        }

    }


    /**
     * Check the user has access to this controller
     *
     * @param \Tk\Event\Event $event
     */
    public function onControllerShow(\Tk\Event\Event $event)
    {
        $plugin = \Rs\Plugin::getInstance();

        $controller = $event->get('controller');
        if ($controller instanceof \App\Controller\Placement\Edit) {
            vd('show()');
        }

    }


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\Kernel\KernelEvents::CONTROLLER => array('onController', 0),
            \Tk\Kernel\KernelEvents::VIEW => array('onView', 0),
            \App\AppEvents::SHOW => array('onControllerShow', 0)
        );
    }
    
}