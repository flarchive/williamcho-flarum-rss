<?php

namespace WilliamCho\Rss\Controllers;

use Flarum\Discussion\Discussion;
use Flarum\Formatter\Formattable;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Guest;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RssFeedController implements RequestHandlerInterface
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
        protected SlugManager $slugManager
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $maxItems = max(1, (int) $this->settings->get('williamcho-rss.max_items', 500));
        $summaryLength = max(0, (int) $this->settings->get('williamcho-rss.summary_length', 500));

        $discussions = Discussion::whereVisibleTo(new Guest())
            ->whereNotNull('first_post_id')
            ->with('firstPost')
            ->orderByDesc('created_at')
            ->take($maxItems)
            ->get();

        $forumTitle = $this->settings->get('forum_title', 'Forum');
        $forumUrl = $this->url->to('forum')->base();

        $xml = new \DOMDocument('1.0', 'UTF-8');

        $rss = $xml->appendChild($xml->createElement('rss'));
        $rss->setAttribute('version', '2.0');

        $channel = $rss->appendChild($xml->createElement('channel'));
        $this->appendTextElement($xml, $channel, 'title', $forumTitle);
        $this->appendTextElement($xml, $channel, 'link', $forumUrl);
        $this->appendTextElement($xml, $channel, 'description', $forumTitle);
        $this->appendTextElement($xml, $channel, 'lastBuildDate', (new \DateTime())->format(\DateTime::RSS));

        foreach ($discussions as $discussion) {
            $item = $channel->appendChild($xml->createElement('item'));

            $link = $this->url->to('forum')->route('discussion', [
                'id' => $this->slugManager->forResource(Discussion::class)->toSlug($discussion),
            ]);

            $this->appendTextElement($xml, $item, 'title', $discussion->title);
            $this->appendTextElement($xml, $item, 'link', $link);

            $guid = $this->appendTextElement($xml, $item, 'guid', $link);
            $guid->setAttribute('isPermaLink', 'true');

            $this->appendTextElement(
                $xml,
                $item,
                'pubDate',
                $discussion->created_at->format(\DateTime::RSS)
            );

            $this->appendTextElement(
                $xml,
                $item,
                'description',
                $this->summarize($discussion->firstPost, $summaryLength, $request)
            );
        }

        $response = new Response('php://memory');
        $response->getBody()->write($xml->saveXML());

        return $response->withHeader('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    protected function appendTextElement(\DOMDocument $xml, \DOMElement $parent, string $name, string $value): \DOMElement
    {
        $element = $parent->appendChild($xml->createElement($name));
        $element->appendChild($xml->createTextNode($value));

        return $element;
    }

    protected function summarize(?Post $post, int $summaryLength, ServerRequestInterface $request): string
    {
        if (! $post instanceof Formattable) {
            return '';
        }

        $html = $post->formatContent($request);
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return mb_substr($text, 0, $summaryLength);
    }
}
