<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app->match('/debug', function () use ($app) {
    $db = $app['mongo']['default']->playground;

    echo '<pre>';

    foreach ($db->users->find() as $key => $value) {
        var_dump($key, $value);
    }

    die;
});

$app->match('/all', function () use ($app) {
    return $app['twig']->render('all/index.html.twig');
});



$app->match('/forgot', function () use ($app) {
    return $app['twig']->render('forgot/index.html.twig');
});

$app->get('/login', function (Request $request) use ($app) {
    return $app['twig']->render('login/index.html.twig', array(
        'username' => $request->get('username'),
        'error' => $request->get('error'),
    ));
});

$app->post('/login', function (Request $request) use ($app) {
    $username = $request->get('username');
    $user = $app['db']->users->findOne(['username' => $username]);

    if (!$user) {
        return $app->redirect('/login?username='.$username.'&error=login');
    }

    return $app->redirect('/');
});

$app->get('/signup', function () use ($app) {
    return $app['twig']->render('signup/index.html.twig');
});

$app->post('/signup', function (Request $request) use ($app) {
    $params = $request->request->all();

    // $db = $app['mongo']['default']->playground;
    $app['db']->users->insert($params);

    return $app->redirect('/login?username='.$params['username']);
});

$app->before(function (Request $request, Application $app) {
    $app['twig']->addGlobal('lipsum', new joshtronic\LoremIpsum());
}, Application::EARLY_EVENT);

// @route landing page
$app->match('/', function () use ($app) {
    return $app['twig']->render('landing/index.html.twig', array(
    ));
})
->bind('landing');

$app->match('/apteka/{id}', function () use ($app) {
    return $app['twig']->render('details/index.html.twig');
});

//
$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    return $app['twig']->render('error/index.html.twig', array(
        'code' => $code,
        'message' => $e->getMessage(),
    ));
});

return $app;