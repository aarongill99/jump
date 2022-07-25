<?php
/**
 *      ██ ██    ██ ███    ███ ██████
 *      ██ ██    ██ ████  ████ ██   ██
 *      ██ ██    ██ ██ ████ ██ ██████
 * ██   ██ ██    ██ ██  ██  ██ ██
 *  █████   ██████  ██      ██ ██
 *
 * @author Dale Davies <dale@daledavies.co.uk>
 * @copyright Copyright (c) 2022, Dale Davies
 * @license MIT
 */

namespace Jump\Pages;

use \Jump\Exceptions\TagNotFoundException;

class TagPage extends AbstractPage {

    protected function render_header(): string {
        $template = $this->mustache->loadTemplate('header');
        $this->tagname = $this->routeparams['tag'];
        $title = 'Tag: '.$this->tagname;
        $csrfsection = $this->session->getSection('csrf');
        $unsplashdata = $this->cache->load('unsplash');
        $showsearch = $this->config->parse_bool($this->config->get('showsearch', false));
        $checkstatus = $this->config->parse_bool($this->config->get('checkstatus', false));
        $templatecontext = [
            'csrftoken' => $csrfsection->get('token'),
            'greeting' => $this->tagname,
            'noindex' => $this->config->parse_bool($this->config->get('noindex')),
            'title' => $title,
            'owmapikey' => !!$this->config->get('owmapikey', false),
            'metrictemp' => $this->config->parse_bool($this->config->get('metrictemp')),
            'ampmclock' => $this->config->parse_bool($this->config->get('ampmclock', false)),
            'unsplash' => !!$this->config->get('unsplashapikey', false),
            'unsplashcolor' => $unsplashdata?->color,
            'wwwurl' => $this->config->get_wwwurl(),
            'checkstatus' => $checkstatus,
        ];
        if ($showsearch || $checkstatus) {
            $templatecontext['sitesjson'] = json_encode((new \Jump\Sites($this->config, $this->cache))->get_sites_for_frontend());
            if ($showsearch) {
                $templatecontext['searchengines'] = json_encode((new \Jump\SearchEngines($this->config, $this->cache))->get_search_engines());
            }
        }
        return $template->render($templatecontext);
    }

    protected function render_content(): string {
        $cachekey = isset($this->tagname) ? 'tag:'.$this->tagname : null;
        return $this->cache->load(cachename: 'templates/sites', key: $cachekey, callback: function() {
            $sites = new \Jump\Sites(config: $this->config, cache: $this->cache);
            try {
                $taggedsites = $sites->get_sites_by_tag($this->tagname);
            }
            catch (TagNotFoundException) {
                (new ErrorPage($this->cache, $this->config, 404, 'There are no sites with this tag.'))->init();
            }
            $template = $this->mustache->loadTemplate('sites');
            return $template->render([
                'hassites' => !empty($taggedsites),
                'sites' => $taggedsites,
                'altlayout' => $this->config->parse_bool($this->config->get('altlayout', false)),
                'wwwurl' => $this->config->get_wwwurl(),
            ]);
        });
    }

    protected function render_footer(): string {
        return $this->cache->load(cachename: 'templates/sites', key: 'footer', callback: function() {
            $sites = new \Jump\Sites(config: $this->config, cache: $this->cache);
            $tags = $sites->get_tags_for_template();
            $template = $this->mustache->loadTemplate('footer');
            return $template->render([
                'hastags' => !empty($tags),
                'tags' => $tags,
                'showclock' => $this->config->parse_bool($this->config->get('showclock')),
                'showsearch' => $this->config->parse_bool($this->config->get('showsearch', false)),
                'wwwurl' => $this->config->get_wwwurl(),
            ]);
        });
    }

}
