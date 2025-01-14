<?php
require_once __DIR__.'/../php_file_get_contents_curl/file_get_contents_curl.php';
require_once __DIR__.'/../php_url/url.php';

final class WebsiteMeta
{
    // Edited from: https://stackoverflow.com/a/52218834
    static function get( $url, $specificTags = 0 ): array
    {
        $chromeUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

        $doc = new DOMDocument();
        $doc_contents = FileGetContentsCurl::get(url: $url, user_agent: $chromeUserAgent);
        @$doc -> loadHTML($doc_contents);
        $response['url'] = FileGetContentsCurl::getFinalUrl();
        $response['title'] = $doc->getElementsByTagName('title') -> item(0) -> nodeValue;
        $response['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

        foreach ($doc -> getElementsByTagName('link') as $m)
        {
            $tagName = $m -> getAttribute('rel');
            if ($m->getAttribute('href')) {
                $tagHref = $m -> getAttribute('href');
                if ($tagName !== '' && $tagHref !== '')
                {
                    $response['link'][$tagName] = $tagHref;
                }
            }
        }

        foreach ($doc -> getElementsByTagName('meta') as $m)
        {
            $tagName = $m -> getAttribute('name') ?: $m -> getAttribute('property');
            if (!in_array($tagName, ['viewport'])) {
                $tagContent = $m -> getAttribute('content');
                if ($tagName !== '' && $tagContent !== '')
                {
                    $response['meta'][$tagName] = $tagContent;
                }
            }
        }

        if ( isset( $response['link']['search'] ) )
        {
            $response['search'] = simplexml_load_string
            (
                data: FileGetContentsCurl::get
                (
                    url: URL::relative2Absolute
                    (
                        sourceUrl: $response['url'],
                        requestUrl: $response['link']['search']
                    )
                )
            );
        }

        if ( isset( $response['link']['manifest'] ) )
        {
            $response['manifest'] = json_decode
            (
                FileGetContentsCurl::get
                (
                    url: URL::relative2Absolute
                    (
                        sourceUrl: $response['url'],
                        requestUrl: $response['link']['manifest']
                    )
                )
            );
        }

        return $specificTags? array_intersect_key( $response, array_flip($specificTags) ) : $response;
    }
}