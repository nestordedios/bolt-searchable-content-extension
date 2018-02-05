<?php

namespace Bolt\Extension\TwoKings\SearchableContent;

use Bolt\Extension\SimpleExtension;
use Bolt\Extension\TwoKings\SearchableContent\Config\Config;
use Bolt\Extension\TwoKings\SearchableContent\Controller\SearchableContentController;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class SearchableContentExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [
            '/extensions/searchablecontent' => new SearchableContentController(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceProviders()
    {
        return [
            $this,
            new Provider\SearchableContentProvider()
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $app['searchablecontent.config'] = $app->share(function () {
            return new Config($this->getConfig());
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function subscribe(EventDispatcherInterface $dispatcher)
    {
        $app = $this->getContainer();
        $dispatcher->addSubscriber(
            new EventListener\StorageListener(
                $app['config'],
                $app['searchablecontent.service']
            )
        );
    }

}
