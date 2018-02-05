<?php

namespace Bolt\Extension\TwoKings\SearchableContent\Service;

use Bolt\Config as BoltConfig;
use Bolt\Extension\TwoKings\SearchableContent\Config\Config;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Storage\Collection;
use Bolt\Storage\Entity;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Repository\ContentRepository;
use Monolog\Logger;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Helper to make content better searchable by putting repeater/block content
 * inside a separate textarea for indexing purposes.
 *
 * There's multiple ways to do this, in this service I used to approached this
 * from the configuration in `contenttypes.yml` and look for three types of
 * fields: `text`, `html`, and `textarea`. This did not work properly, because
 * you want more control as you don't want to index all text fields.
 *
 * A better approach is to check per value for whether it needs to be
 * indexed, and how (e.g. looking up a resource and its contents).
 *
 * See `searchablecontent.twokings.yml` for an explicit configuration of which fields to
 * index.
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class SearchableContentService
{
    /** @var BoltConfig $boltConfig */
    private $boltConfig;

    /** @var EntityManager $storage */
    private $storage;

    /** @var Logger $logger */
    private $logger;

    /** @var array $searchableFieldTypes */
    private $searchableFieldTypes;

    /** @var array $config */
    private $config;

    /**
     * Constructs a new SearchableContentService instance.
     *
     * @param EntityManager       $storage
     */
    public function __construct(
        BoltConfig    $boltConfig,
        Config        $config,
        EntityManager $storage,
        Logger        $logger
    ) {
        $this->boltConfig           = $boltConfig;
        $this->storage              = $storage;
        $this->logger               = $logger;
        // old method
        $this->searchableFieldTypes = [
            'text',
            'html',
            'textarea',
        ];
        // new method
        $this->config = $config->get('searchable');
    }

    /**
     * Make all records searchable
     */
    public function makeAllRecordsSearchable()
    {
        $time_start = microtime(true);

        $contenttypes = array_keys($this->config);

        $success = [];
        $totals  = [];
        $errors  = [];

        foreach ($contenttypes as $contenttype) {
            $repo = $this->storage->getRepository($contenttype);
            $records = $repo->findAll();
            $success[$contenttype] = 0;
            $totals[$contenttype]  = count($records);

            foreach ($records as $record) {
                $this->makeSearchable($record);

                try {
                    $repo->save($record);
                    $success[$contenttype]++;
                }
                catch (\Exception $e) {
                    $message = sprintf("Exception: attempting to save [%s/%s]: %s",
                        $contenttype,
                        $record->get('id'),
                        $e->getMessage()
                    );
                    $errors[] = $message;
                    $this->logger->info(
                        $message,
                        ['event' => 'extension']
                    );
                }

                /*
                $this->logger->info(
                    sprintf("[Searchable] %s/%s -- %s",
                        $contenttype,
                        $record->get('id'),
                        $record->get('search')
                    ),
                    ['event' => 'extension']
                );
                //*/
            }
        }

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $stringTotals = [];
        foreach ($totals as $ct => $total) {
            $stringTotals[] = "{$success[$ct]}/$total  $ct";
        }

        $message = sprintf("Made [%s] searchable in %s seconds.",
            implode(', ', $stringTotals),
            number_format($time, 2)
        );

        $this->logger->info(
            $message,
            ['event' => 'extension']
        );

        return "<p>$message</p>" . implode('<br/>', $errors);
    }

    /**
     * Make a single record searchable by concatenating the text values in
     * repeaters/blocks and set that in a search field.
     *
     * @param Content $record
     */
    public function makeSearchable(Content $record)
    {
        $values = $record->serialize();

        if ($values['id']) {
            $result = $this->handleContent($record);
            $result = str_replace([ '&shy;', '&nbsp;' ], [ '', ' ' ], $result);
            $result = html_entity_decode(strip_tags($result));

            $record->set('search', $result);
        }
    }

    /**
     * For a given record, concatenante all _searchable_ content within repeaters
     * and blocks and return that as a string.
     *
     * @param Content $record
     * @param string
     */
    private function handleContent(Content $record)
    {
        $contenttype = $record['contenttype']['slug'];
        $fields      = $this->boltConfig->get("contenttypes/$contenttype/fields");
        $result      = '';

        foreach ($fields as $key => $field) {
            $fieldType   = $field['type'];
            switch ($fieldType) {
                case 'repeater':
                    $fieldConfig = isset($this->config[$contenttype][$key]) ? $this->config[$contenttype][$key] : [];
                    $fieldValue  = $record['values'][$key];
                    $result .= ' ' . $this->handleRepeater($fieldConfig, $fieldValue);
                    break;
                case 'block':
                    $fieldConfig = isset($this->config[$contenttype][$key]) ? $this->config[$contenttype][$key] : [];
                    $fieldValue  = $record['values'][$key];
                    $result .= ' ' . $this->handleBlock($fieldConfig, $fieldValue);
                    break;
            }
        }

        return trim($result);
    }

    /**
     * For a given value and its configuration, concatenate its contents and
     * return that as a string.
     *
     * @param array $fieldConfig
     * @param array|RepeatingFieldCollection $values
     */
    private function handleRepeater($fieldConfig, $values)
    {
        if (empty($values) || empty($fieldConfig)) {
            return '';
        }

        $result = '';

        foreach ($values as $value) {
            // $values is an array (POST)
            if (is_array($value)) {
                foreach ($value as $key => $realValue) {
                    if (in_array($key, $fieldConfig)) {
                        $result .= ' ' . $realValue;
                    }
                }
            }
            // $values is a RepeatingFieldCollection
            elseif (get_class($value) == 'Bolt\Storage\Field\Collection\LazyFieldCollection') {
                foreach ($value as $realValue) {
                    $fieldName  = $realValue['fieldname'];
                    $fieldValue = $realValue['value'];
                    if (in_array($fieldName, $fieldConfig)) {
                        $result .= ' ' . $fieldValue;
                    }
                }
            }
        }

        return trim($result);
    }

    /**
     * For a given value and its configuration, concatenate its contents and
     * return that as a string.
     *
     * @param array $fieldConfig
     * @param array|RepeatingFieldCollection $values
     */
    private function handleBlock($fieldConfig, $values)
    {
        if (empty($values) || empty($fieldConfig)) {
            return '';
        }

        $result = '';

        foreach ($values as $value) {
            // $values is an array (POST)
            if (is_array($value)) {
                foreach ($value as $blockName => $blockValues) {
                    foreach ($blockValues as $key => $realValue ) {
                        if (isset($fieldConfig[ $blockName ])
                            && in_array($key, $fieldConfig[ $blockName ])) {
                            $result .= ' ' . $realValue;
                        }
                    }
                }
            }
            // $values is a RepeatingFieldCollection
            elseif (get_class($value) == 'Bolt\Storage\Field\Collection\LazyFieldCollection') {
                foreach ($value as $realValue) {
                    if (isset($fieldConfig[ $realValue['block'] ])
                        && in_array($realValue['fieldname'], $fieldConfig[ $realValue['block'] ])) {
                        $result .= ' ' . $realValue['value'];
                    }
                }
            }
        }
        return trim($result);
    }

}
