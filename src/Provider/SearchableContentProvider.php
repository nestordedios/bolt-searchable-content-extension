<?php

namespace Bolt\Extension\TwoKings\SearchableContent\Provider;

use Bolt\Extension\TwoKings\SearchableContent\Service\SearchableContentService;
use Silex\Application;
use Silex\ServiceProviderInterface;


/**
 * Searchable Content Service Provider
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class SearchableContentProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {

        $app['searchablecontent.service'] = $app->share(
            function(Application $app){
                return new SearchableContentService(
                    $app['config'],
                    $app['searchablecontent.config'],
                    $app['storage'],
                    $app['logger.system']
                );
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }

}
