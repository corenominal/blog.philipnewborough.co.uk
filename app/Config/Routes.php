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

// Admin post editor routes
$routes->get('/admin/posts/create', 'Admin\Posts::create');
$routes->post('/admin/posts/store', 'Admin\Posts::store');
$routes->post('/admin/posts/preview', 'Admin\Posts::preview');
// Featured image upload/remove endpoints
$routes->post('/admin/posts/upload_featured_image', 'Admin\Posts::upload_featured_image');
$routes->post('/admin/posts/remove_featured_image', 'Admin\Posts::remove_featured_image');
// List existing featured images for the post editor media library
$routes->get('/admin/posts/list_featured_images', 'Admin\Posts::list_featured_images');
// Body image upload (Images tab in post editor)
$routes->post('/admin/posts/upload_body_image', 'Admin\Posts::upload_body_image');
// Video upload/remove endpoints
$routes->post('/admin/posts/upload_video', 'Admin\Posts::upload_video');
$routes->post('/admin/posts/remove_video', 'Admin\Posts::remove_video');
$routes->get('/admin/posts/(:num)/edit', 'Admin\Posts::edit/$1');
$routes->post('/admin/posts/(:num)/update', 'Admin\Posts::update/$1');

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
