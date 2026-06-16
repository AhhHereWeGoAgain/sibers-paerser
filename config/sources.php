<?php

return [
    'ngs' => [
        'name' => 'NGS Latest News',
        'type' => 'html',
        'url' => 'https://ngs.ru/text/',
        'base_url' => 'https://ngs.ru',
        'is_active' => true,

        'request' => [
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0',
            'headers' => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
            ],
            'referer' => 'https://ngs.ru/text/',
            'use_cookie_jar' => false,
        ],

        'list' => [
            'item' => [
                'selector_type' => 'class',
                'selector' => 'wrap_RL97A',
            ],

            'fields' => [
                'title' => [
                    'type' => 'text',
                    'selector_type' => 'class',
                    'selector' => 'header_RL97A',
                    'attribute' => 'text',
                    'required' => true,
                ],

                'detail_url' => [
                    'type' => 'link',
                    'purpose' => 'detail_page',
                    'selector_type' => 'class',
                    'selector' => 'header_RL97A',
                    'attribute' => 'href',
                    'resolve_url' => true,
                    'required' => true,
                ],

                'summary' => [
                    'type' => 'text',
                    'selector_type' => 'class',
                    'selector' => 'subtitle_RL97A',
                    'attribute' => 'text',
                    'required' => false,
                ],

                'published_at' => [
                    'type' => 'date',
                    'selector_type' => 'class',
                    'selector' => 'text_FqVl7',
                    'attribute' => 'text',
                    'required' => false,
                ],

                'image' => [
                    'type' => 'image',
                    'selector_type' => 'tag',
                    'selector' => 'img',
                    'attribute' => 'src',
                    'resolve_url' => true,
                    'required' => false,
                ],
            ],
        ],

        'detail' => [
            'enabled' => true,
            'url_from_field' => 'detail_url',

            'fields' => [
                'title' => [
                    'type' => 'text',
                    'selector_type' => 'tag',
                    'selector' => 'h1',
                    'attribute' => 'text',
                    'required' => true,
                ],

                'content' => [
                    'type' => 'text',
                    'selector_type' => 'class',
                    'selector' => 'uiArticleBlockText_J821F',
                    'attribute' => 'text',
                    'multiple' => true,
                    'separator' => "\n\n",
                    'required' => false,
                ],
            ],
        ],
        'pagination' => [
            'enabled' => true,

            'url_template' => 'https://ngs.ru/text/page-{page}/',
            'start_page' => 1,

            'app_items_per_page' => 10,
            'max_source_pages_per_request' => 3,

            'auto_calibrate' => true,
            'calibration_ttl' => 3600,
        ],
    ],
    'lenta' => [
        'name' => 'Lenta.ru Latest News',
        'type' => 'html',
        'url' => 'https://lenta.ru/parts/news/',
        'base_url' => 'https://lenta.ru',
        'is_active' => true,

        'request' => [
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0',
            'headers' => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
            ],
            'referer' => 'https://lenta.ru/parts/news/',
            'use_cookie_jar' => false,
        ],

        'debug' => [
        ],

        'pagination' => [
            'enabled' => true,
            'url_template' => 'https://lenta.ru/parts/news/{page}/',
            'start_page' => 1,

            'app_items_per_page' => 10,
            'max_source_pages_per_request' => 3,

            'auto_calibrate' => true,
            'calibration_ttl' => 3600,
        ],

        'list' => [
            'item' => [
                'selector_type' => 'class',
                'selector' => 'parts-page__item',
            ],

            'fields' => [
                'title' => [
                    'type' => 'text',
                    'selector_type' => 'class',
                    'selector' => 'card-full-news__title',
                    'attribute' => 'text',
                    'required' => true,
                ],

                'detail_url' => [
                    'type' => 'link',
                    'purpose' => 'detail_page',
                    'selector_type' => 'class',
                    'selector' => 'card-full-news',
                    'attribute' => 'href',
                    'resolve_url' => true,
                    'required' => true,
                ],

                'published_at' => [
                    'type' => 'date',
                    'selector_type' => 'class',
                    'selector' => 'card-full-news__date',
                    'attribute' => 'text',
                    'required' => false,
                ],

                'category' => [
                    'type' => 'text',
                    'selector_type' => 'class',
                    'selector' => 'card-full-news__rubric',
                    'attribute' => 'text',
                    'required' => false,
                ],
            ],
        ],

        'detail' => [
            'enabled' => true,

            'fields' => [
                'title' => [
                    'type' => 'text',
                    'selector_type' => 'tag',
                    'selector' => 'h1',
                    'attribute' => 'text',
                    'required' => true,
                ],

                'summary' => [
                    'type' => 'text',
                    'selector_type' => 'tag',
                    'selector' => 'h2',
                    'attribute' => 'text',
                    'required' => false,
                ],

                'content' => [
                    'type' => 'text',
                    'selector_type' => 'tag',
                    'selector' => 'p',
                    'attribute' => 'text',
                    'multiple' => true,
                    'separator' => "\n\n",
                    'required' => false,
                ],

                'image' => [
                    'type' => 'image',
                    'selector_type' => 'tag',
                    'selector' => 'img',
                    'attribute' => 'src',
                    'resolve_url' => true,
                    'required' => false,
                ],
            ],
        ],
    ],
];