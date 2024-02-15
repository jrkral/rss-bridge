<?php

class AO3Bridge extends BridgeAbstract
{
    const NAME = 'AO3';
    const URI = 'https://archiveofourown.org/';
    const CACHE_TIMEOUT = 1800;
    const DESCRIPTION = 'Returns works or chapters from Archive of Our Own';
    const MAINTAINER = 'Obsidienne';
    const PARAMETERS = [
        'List' => [
            'url' => [
                'name' => 'url',
                'required' => true,
                // Example: F/F tag, complete works only
                'exampleValue' => 'https://archiveofourown.org/works?work_search[complete]=T&tag_id=F*s*F',
            ],
            'range' => [
                'name' => 'Chapter Content',
                'title' => 'Chapter(s) to include in each work\'s feed entry',
                'defaultValue' => 'none',
                'type' => 'list',
                'values' => [
                    'None' => null,
                    'First' => 'first',
                    'Latest' => 'last',
                    'Entire work' => 'all',
                ],
            ],
        ],
        'Bookmarks' => [
            'user' => [
                'name' => 'user',
                'required' => true,
                // Example: Nyaaru's bookmarks
                'exampleValue' => 'Nyaaru',
            ],
        ],
        'Work' => [
            'id' => [
                'name' => 'id',
                'required' => true,
                // Example: latest chapters from A Better Past by LysSerris
                'exampleValue' => '18181853',
            ],
        ]
    ];
    private $title;

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'Bookmarks':
                $user = $this->getInput('user');
                $this->title = $user;
                $url = self::URI
                    . '/users/' . $user
                    . '/bookmarks?bookmark_search[sort_column]=bookmarkable_date';
                $this->collectList($url);
                break;
            case 'List':
                $this->collectList($this->getInput('url'));
                break;
            case 'Work':
                $this->collectWork($this->getInput('id'));
                break;
        }
    }

    /**
     * Feed for lists of works (e.g. recent works, search results, filtered tags,
     * bookmarks, series, collections).
     */
    private function collectList($url)
    {
        $this->url = $url;
        $httpClient = RssBridge::getHttpClient();

        $version = 'v0.0.1';
        $agent = ['useragent' => "rss-bridge $version (https://github.com/RSS-Bridge/rss-bridge)"];
        $response = $httpClient->request($url, $agent);

        $html = \str_get_html($response->getBody());
        $html = defaultLinkTo($html, self::URI);

        // Get list title. Will include page range + count in some cases
        $heading = ($html->find('#main > h2', 0));
        if ($heading->find('a.tag')) {
            $heading = $heading->find('a.tag', 0);
        }
        $this->title = $heading->plaintext;

        foreach ($html->find('.index.group > li') as $element) {
            $item = [];

            $title = $element->find('div h4 a', 0);
            if (!isset($title)) {
                continue; // discard deleted works
            }
            $item['title'] = $title->plaintext;
            $item['content'] = $element;
            $item['uri'] = $title->href;

            $strdate = $element->find('div p.datetime', 0)->plaintext;
            $item['timestamp'] = strtotime($strdate);

            $chapters = $element->find('dl dd.chapters', 0);
            // bookmarked series and external works do not have a chapters count
            $chapters = (isset($chapters) ? $chapters->plaintext : 0);
            $item['uid'] = $item['uri'] . "/$strdate/$chapters";

            // Fetch workskin of desired chapter(s) in list
            if ($this->getInput('range')) {
                $url = $item['uri'];
                switch ($this->getInput('range')) {
                    case ('all'):
                        $url .= '?view_full_work=true';
                        break;
                    case ('first'):
                        break;
                    case ('last'):
                        // only way to get this is using the navigate page unfortunately
                        $url .= '/navigate';
                        $response = $httpClient->request($url, $agent);
                        $html = \str_get_html($response->getBody());
                        $html = defaultLinkTo($html, self::URI);
                        $url = $html->find('ol.index.group > li > a', -1)->href;
                        break;
                }
                $response = $httpClient->request($url, $agent);
                $html = \str_get_html($response->getBody());
                $html = defaultLinkTo($html, self::URI);
                $item['content'] .= $html->find('#workskin', 0);
            }

            // Use predictability of download links to generate enclosures
            $wid = explode('/', $item['uri'])[4];
            foreach (['azw3', 'epub', 'mobi', 'pdf', 'html'] as $ext) {
                $item['enclosures'][] = 'https://archiveofourown.org/downloads/' . $wid . '/work.' . $ext;
            }

            $this->items[] = $item;
        }
    }

    /**
     * Feed for recent chapters of a specific work.
     */
    private function collectWork($id)
    {
        $url = self::URI . "/works/$id";
        $this->url = $url;
        $httpClient = RssBridge::getHttpClient();

        $version = 'v0.0.1';
        $agent = ['useragent' => "rss-bridge $version (https://github.com/RSS-Bridge/rss-bridge)"];

        $response = $httpClient->request($url . '/navigate', $agent);
        $html = \str_get_html($response->getBody());
        $html = defaultLinkTo($html, self::URI);

        $response = $httpClient->request($url . '?view_full_work=true', $agent);
        $workhtml = \str_get_html($response->getBody());
        $workhtml = defaultLinkTo($workhtml, self::URI);

        $this->title = $html->find('h2 a', 0)->plaintext;

        $nav = $html->find('ol.index.group > li');
        for ($i = 0; $i < count($nav); $i++) {
            $item = [];

            $element = $nav[$i];
            $item['title'] = $element->find('a', 0)->plaintext;
            $item['content'] = $workhtml->find('#chapter-' . ($i + 1), 0);
            $item['uri'] = $element->find('a', 0)->href;

            $strdate = $element->find('span.datetime', 0)->plaintext;
            $strdate = str_replace('(', '', $strdate);
            $strdate = str_replace(')', '', $strdate);
            $item['timestamp'] = strtotime($strdate);

            $item['uid'] = $item['uri'] . "/$strdate";

            $this->items[] = $item;
        }

        $this->items = array_reverse($this->items);
    }

    public function getName()
    {
        $name = parent::getName() . " $this->queriedContext";
        if (isset($this->title)) {
            $name .= " - $this->title";
        }
        return $name;
    }

    public function getIcon()
    {
        return self::URI . '/favicon.ico';
    }

    public function getURI()
    {
        $uri = parent::getURI();
        if (isset($this->url)) {
            $uri = $this->url;
        }
        return $uri;
    }
}
