<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app->match('/phpinfo', function () use ($app) {
    phpinfo();
    die;
});

$app->match('/debug', function () use ($app) {
    $db = $app['mongo']['default']->playground;

    echo '<pre>';

    echo '<h2>Dbs:</h2>';
    var_dump($app['mongo']['default']->listDBs());

    echo '<h2>Collections (playground db):</h2>';
    var_dump($db->listCollections());

    foreach ($db->listCollections() as $collection) {
        $collectionName = $collection->getName();
        var_dump($collectionName);
    }
    var_dump('<br><br>');

    foreach ($db->users->find() as $key => $value) {
        var_dump($key, $value);
    }

    die;
});

$app->before(function (Request $request, Application $app) {
    $app['twig']->addGlobal('lipsum', new joshtronic\LoremIpsum());

    $user = null;
    if ($username = $app['session']->get('username')) {
        $user = $app['db']->users->findOne(['username' => $username]);
    }
    $app['twig']->addGlobal('user', $user);

}, Application::EARLY_EVENT);

$app->get('/faq', function () use ($app) {
    return $app['twig']->render('faq/index.html.twig');
});

$app->get('/profile', function () use ($app) {
    return $app['twig']->render('profile/index.html.twig', array(
        // 
    ));
});

$app->get('/logout', function () use ($app) {
    $app['session']->remove('username');

    return $app->redirect('/login');
});

// @route GET /login
$app->get('/login', function (Request $request) use ($app) {
    $response = $app['twig']->render('login/index.html.twig', array(
        'username' => $request->get('username'),
        'alert' => $app['session']->get('alert'),
        'alert_message' => $app['session']->get('alert-message'),
    ));

    $app['session']->remove('alert');
    $app['session']->remove('alert-message');

    return $response;
});

// @route POST /login
$app->post('/login', function (Request $request) use ($app) {
    $username = $request->get('username');
    $user = $app['db']->users->findOne(['username' => $username]);

    if (!$user) {
        $app['session']->set('alert', 'danger');
        $app['session']->set('alert-message', 'Requested user has been not found.');

        return $app->redirect('/login?username='.$username);
    }

    $app['session']->set('username', $username);

    return $app->redirect('/');
});

$app->get('/signup', function () use ($app) {
    $response = $app['twig']->render('signup/index.html.twig', array(
        'alert' => $app['session']->get('alert'),
        'alert_message' => $app['session']->get('alert-message'),
    ));

    $app['session']->remove('alert');
    $app['session']->remove('alert-message');

    return $response;
});

$app->post('/signup', function (Request $request) use ($app) {
    $username = $request->get('username');
    $params = $request->request->all();

    $user = $app['db']->users->findOne(['username' => $username]);
    if ($user) {
        $app['session']->set('alert', 'danger');
        $app['session']->set('alert-message', 'User with such username already registered.');

        return $app->redirect('/signup?username='.$username);
    }

    $app['db']->users->insert($params);

    $app['session']->set('alert', 'success');
    $app['session']->set('alert-message', 'Well done! You have successfully just registered.');

    return $app->redirect('/login?username='.$params['username']);
});

$app->get('/forgot', function (Request $request) use ($app) {
    $response = $app['twig']->render('forgot/index.html.twig', array(
        'username' => $request->get('username'),
        'alert' => $app['session']->get('alert'),
        'alert_message' => $app['session']->get('alert-message'),
    ));

    $app['session']->remove('alert');
    $app['session']->remove('alert-message');

    return $response;
});

$app->post('/forgot', function (Request $request) use ($app) {
    $username = $request->get('username');
    $user = $app['db']->users->findOne(['username' => $username]);

    if (!$user) {
        $app['session']->set('alert', 'danger');
        $app['session']->set('alert-message', 'Requested user has been not found.');

        return $app->redirect('/forgot?username='.$username);
    }

    return $app->redirect('/forgot-step2?username='.$username);
});

$app->get('/forgot-step2', function (Request $request) use ($app) {
    $username = $request->get('username');
    $user = $app['db']->users->findOne(['username' => $username]);

    if (!$user) {
        $app['session']->set('alert', 'danger');
        $app['session']->set('alert-message', 'Requested user has been not found.');

        return $app->redirect('/forgot?username='.$username);
    }

    $response = $app['twig']->render('forgot-step2/index.html.twig', array(
        'username' => $request->get('username'),
        'alert' => $app['session']->get('alert'),
        'alert_message' => $app['session']->get('alert-message'),
    ));

    $app['session']->remove('alert');
    $app['session']->remove('alert-message');

    return $response;
});

$app->post('/forgot-step2', function (Request $request) use ($app) {
    $username = $request->get('username');
    $user = $app['db']->users->findOne(['username' => $username]);

    if (!$user) {
        $app['session']->set('alert', 'danger');
        $app['session']->set('alert-message', 'Requested user has been not found.');

        return $app->redirect('/forgot?username='.$username);
    }

    $user['password'] = $request->get('password');
    $app['db']->users->findAndModify(['username' => $username], $user);

    $app['session']->set('alert', 'success');
    $app['session']->set('alert-message', 'Your password has been changed.');

    return $app->redirect('/login?username='.$username);
});

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