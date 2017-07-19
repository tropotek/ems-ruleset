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
class CategoryClassHandler implements Subscriber
{


    /**
     * Check the user has access to this controller
     *
     * @param \Tk\Event\Event $event
     */
    public function onGetCategoryClass(\Tk\Event\Event $event)
    {
        $plugin = \Rs\Plugin::getInstance();
        /** @var \App\Db\Company $company */
        $company = $event->get('company');
        /** @var \App\Db\Profile $profile */
        $profile = $event->get('profile');

        $catList = \App\Db\CompanyCategoryMap::create()->findFiltered(array(
            'profileId' => $profile->getId(),
            'companyId' => $company->getId()
        ));

        $profilePluginData = \Tk\Db\Data::create($plugin->getName() . '.course.profile', $profile->getId());
        $script = $profilePluginData->get('plugin.company.get.class');
        if ($profilePluginData->get('plugin.active') && $script != null) {
            $calcClass = eval($script);
            if ($calcClass) {
                $event->set('class', $calcClass);
            }
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
            \App\AppEvents::COMPANY_GET_CATEGORY_CLASS => array('onGetCategoryClass', 0)
        );
    }
    
}