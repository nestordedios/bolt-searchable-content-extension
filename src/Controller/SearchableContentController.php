<?php

namespace Bolt\Extension\TwoKings\SearchableContent\Controller;

use Bolt\Controller\Base;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class SearchableContentController extends Base
{
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $ctr)
    {
        $ctr
            ->match('/searchable', [$this, 'searchable'])
            ->before([$this, 'before'])
            ->bind('searchablecontent.searchable')
        ;

        return $ctr;
    }

    /**
     * Check if the current user is logged in.
     *
     * @param Request     $request
     * @param Application $app
     */
    public function before(Request $request, Application $app)
    {
        $token = $app['session']->get('authentication', false);

        if (! $token) {
            return $this->redirectToRoute('dashboard');
        }
    }

    /**
     *
     *
     * @param Application $app
     * @param Request     $request
     */
    public function searchable(Request $request, Application $app)
    {
        $message = $app['searchablecontent.service']->makeAllRecordsSearchable();

        return $message;
    }

}
