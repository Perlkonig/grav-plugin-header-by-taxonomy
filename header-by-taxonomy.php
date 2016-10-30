<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Page\Header;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class HeaderByTaxonomyPlugin
 * @package Grav\Plugin
 */
class HeaderByTaxonomyPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        // Enable the main event we are interested in
        $this->enable([
            'onPageProcessed' => ['onPageProcessed', 1000000]
        ]);
    }

    /**
     * Do some work for this event, full details of events can be found
     * on the learn site: http://learn.getgrav.org/plugins/event-hooks
     *
     * @param Event $e
     */
    public function onPageProcessed(Event $e)
    {
        // Get the page and its taxonomy
        //$page = $this->grav['page'];
        $page = $e['page'];
        $tax = $page->taxonomy();
        $header = $page->header();
        //dump($header);
        $header = new \Grav\Common\Page\Header((array) $header);

        // Get the list of sets
        $sets = $this->grav['config']->get('plugins.header-by-taxonomy.sets');

        // Go over each set and determine whether it applies to this page
        foreach ($sets as $set) {
            $result = false;
            $overwrite = false;
            if (! is_null($this->grav['config']->get('plugins.header-by-taxonomy.overwrite'))) {
                $overwrite = $this->grav['config']->get('plugins.header-by-taxonomy.overwrite');
            }
            if (isset($set['overwrite'])) {
                $overwrite = $set['overwrite'];
            }
            $individuals = array();
            $combo = 'or';
            if (isset($set['combinator'])) {
                $combo = strtolower($set['combinator']);
            }
            foreach ($set['criteria'] as $c) {
                $combinator = 'or';
                if (isset($c['combinator'])) {
                    $combinator = strtolower($c['combinator']);
                }
                switch ($combinator) {
                    case 'and':
                        array_push($individuals, $this->evalAnd($c['taxonomy'], $c['values'], $tax));
                        break;
                    default:
                        array_push($individuals, $this->evalOr($c['taxonomy'], $c['values'], $tax));
                }
                // Check for short circuit
                if ($combo === 'and') {
                    if (array_sum($individuals) != count($individuals)) {
                        break;
                    }
                } else {
                    if (array_sum($individuals) > 0) {
                        break;
                    }
                }
            }

            // Now evaluate whether the header tags are to be applied
            $result = false;
            switch ($combo) {
                case 'and':
                    $result = (array_sum($individuals) === count($individuals));
                    break;
                default: // OR
                    $result = (array_sum($individuals) > 0);
            }

            if ($result) {
                //dump($page->metadata());
                //dump("Matches!");
                foreach ($set['header'] as $key => $value) {
                    if (! is_null($header->get($key))) {
                    }
                    if ($overwrite) {
                            $header->set($key, $value);
                    } else {
                        if (is_null($header->get($key))) {
                            $header->set($key, $value);
                        }
                    }
                }
                // Save the results!
                $page->header($header->items);
                //dump($page->header());

                // Regenerate the metadata
                $metadata = $this->metadata($header->items);
                $page->metadata($metadata);
            }
        }
    }

    private function evalAnd($ctax, $cvalues, $ptax) {
        // Does that taxonomy even exist
        if (! array_key_exists($ctax, $ptax)) {
            return 0;
        }
        // Do all the values exist
        foreach ($cvalues as $value) {
            if (! in_array($value, $ptax[$ctax])) {
                return 0;
            }
        }
        return 1;
    }

    private function evalOr($ctax, $cvalues, $ptax) {
        // Does that taxonomy even exist
        if (! array_key_exists($ctax, $ptax)) {
            return 0;
        }
        // Do any of the values exist
        foreach ($cvalues as $value) {
            if (in_array($value, $ptax[$ctax])) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * Function to merge page metadata tags and build an array of Metadata objects
     * that can then be rendered in the page.
     *
     * @param  array $var an Array of metadata values to set
     *
     * @return array      an Array of metadata values for the page
     */
    public function metadata($header)
    {
        $header_tag_http_equivs = ['content-type', 'default-style', 'refresh', 'x-ua-compatible'];
        $metadata = [];
        // Set the Generator tag
        $metadata['generator'] = 'GravCMS';
        // Get initial metadata for the page
        $metadata = array_merge($metadata, Grav::instance()['config']->get('site.metadata'));
        if (isset($header['metadata'])) {
            // Merge any site.metadata settings in with page metadata
            $metadata = array_merge($metadata, $header['metadata']);
        }
        // Build an array of meta objects..
        foreach ((array)$metadata as $key => $value) {
            // If this is a property type metadata: "og", "twitter", "facebook" etc
            // Backward compatibility for nested arrays in metas
            if (is_array($value)) {
                foreach ($value as $property => $prop_value) {
                    $prop_key                  = $key . ":" . $property;
                    $metadata[$prop_key] = ['name' => $prop_key, 'property' => $prop_key, 'content' => htmlspecialchars($prop_value, ENT_QUOTES, 'UTF-8')];
                }
            } else {
                // If it this is a standard meta data type
                if ($value) {
                    if (in_array($key, $header_tag_http_equivs)) {
                        $metadata[$key] = ['http_equiv' => $key, 'content' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8')];
                    } elseif ($key == 'charset') {
                        $metadata[$key] = ['charset' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8')];
                    } else {
                        // if it's a social metadata with separator, render as property
                        $separator    = strpos($key, ':');
                        $hasSeparator = $separator && $separator < strlen($key) - 1;
                        $entry        = ['name' => $key, 'content' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8')];
                        if ($hasSeparator) {
                            $entry['property'] = $key;
                        }
                        $metadata[$key] = $entry;
                    }
                }
            }
        }
        return $metadata;
    }
}
