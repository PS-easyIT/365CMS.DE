<?php
declare(strict_types=1);

return [
    'route_groups' => [
        'public' => [
            '/',
            '/contact',
            '/search',
            '/blog',
        ],
        'auth' => [
            '/login',
            '/register',
        ],
        'member' => [
            '/member/dashboard',
            '/member/privacy',
            '/member/media',
        ],
        'admin' => [
            '/admin',
            '/admin/diagnose',
            '/admin/comments',
            '/admin/toc',
            '/admin/hub-sites',
            '/admin/site-tables',
            '/admin/users/new',
            '/admin/groups',
        ],
        'error' => [
            '/this-route-should-404',
        ],
    ],
    'historical_retests' => [
        '/member/dashboard',
        '/admin',
        '/member/privacy',
        '/member/media',
        '/login',
        '/admin/comments',
        '/admin/toc',
        '/admin/hub-sites',
        '/admin/site-tables',
        '/admin/users/new',
        '/admin/groups',
        '/blog',
    ],
    'required_workflow_markers' => [
        'Phase 6a: Beta-Smoke nach Deployment',
        'php tests/release-smoke/run.php',
        '/member/dashboard',
        '/admin/diagnose',
        '/this-route-should-404',
    ],
];
