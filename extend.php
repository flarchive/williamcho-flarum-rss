<?php

use Flarum\Extend;
use WilliamCho\Rss\Controllers\RssFeedController;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Routes('forum'))
        ->get('/rss', 'williamcho-rss', RssFeedController::class),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Settings())
        ->default('williamcho-rss.max_items', 500)
        ->default('williamcho-rss.summary_length', 500),
];
