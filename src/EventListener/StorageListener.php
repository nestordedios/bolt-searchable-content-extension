<?php

// see: https://github.com/BoltTranslate/Translate/blob/master/src/EventListener/StorageListener.php

namespace Bolt\Extension\TwoKings\SearchableContent\EventListener;

use Bolt\Config as BoltConfig;
use Bolt\Events\HydrationEvent;
use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Extension\TwoKings\SearchableContent\Service\SearchableContentService;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Field\Collection\RepeatingFieldCollection;
use Bolt\Storage\Query\Query;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class StorageListener implements EventSubscriberInterface
{
    /** @var SearchableContentService $service */
    private $service;

    /**
     *
     */
    public function __construct(
        BoltConfig $boltConfig,
        SearchableContentService $service
    ) {
        $this->boltConfig = $boltConfig;
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            StorageEvents::PRE_SAVE => [
                ['preSave', 0],
            ],
        ];
    }

    /**
     * StorageEvents::PRE_SAVE event callback.
     *
     * @param StorageEvent $event
     */
    public function preSave(StorageEvent $event)
    {
        /** @var Content $record */
        $record = $event->getContent();
        $content = new Content();

        // Note: This check is needed for when logging in, then a preSave is fired too.
        if ($record instanceof $content) {
            $this->service->makeSearchable($record);
        }
    }
}
