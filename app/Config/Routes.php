<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Search route
$routes->get('search', 'Search::index');

// Post permalink routes
$routes->get('posts/(:segment)', 'Post::show/$1');
$routes->get('posts/(:segment)/json', 'Post::showJson/$1');
$routes->get('posts/(:segment)/markdown', 'Post::showMarkdown/$1');

// Feed routes
$routes->get('feed/rss', 'Feed::rss');

// Tag archive routes
$routes->get('tags/(:segment)', 'Tag::show/$1');

// Admin routes
$routes->get('/admin', 'Admin\Home::index');
$routes->get('/admin/stats', 'Admin\Home::stats');
$routes->get('/admin/posts/datatable', 'Admin\Home::postsDataTable');
$routes->post('/admin/posts/delete', 'Admin\Home::deletePosts');

// API routes
$routes->match(['get', 'options'], '/api/test/ping', 'Api\Test::ping');

// Command line routes
$routes->cli('cli/test/index/(:segment)', 'CLI\Test::index/$1');
$routes->cli('cli/test/count', 'CLI\Test::count');

// Metrics route
$routes->post('/metrics/receive', 'Metrics::receive');

// Logout route
$routes->get('/logout', 'Auth::logout');

// Unauthorised route
$routes->get('/unauthorised', 'Unauthorised::index');

// Custom 404 route
$routes->set404Override('App\Controllers\Errors::show404');

// Debug routes
$routes->get('/debug', 'Debug\Home::index');
$routes->get('/debug/(:segment)', 'Debug\Rerouter::reroute/$1');
$routes->get('/debug/(:segment)/(:segment)', 'Debug\Rerouter::reroute/$1/$2');
