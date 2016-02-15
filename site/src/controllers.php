<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Jenssegers\Agent\Agent as Agent;
use Laracasts\Flash\FlashServiceProvider as Flash;

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

    echo '<h2>Users:</h2>';

    foreach ($db->users->find() as $key => $value) {
        var_dump($key, $value);
    }

    var_dump('<br><br>');
    
    echo '<h2>Pharmacies:</h2>';

    foreach ($db->pharmacies->find() as $key => $value) {
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
    $app['twig']->addGlobal('isadmin', $user && !empty($user['isadmin']));
    $app['twig']->addGlobal('ismanager', $user && !empty($user['ismanager']));

}, Application::EARLY_EVENT);

// @route landing page
$app->match('/', function (Request $request) use ($app) {
    $pharmacies = $app['db']->pharmacies->find();
    $pharmacies = iterator_to_array($pharmacies);

    $perPage = 6*3;
    $page = $request->get('page', 1);
    $pages = ceil(count($pharmacies) / $perPage);
    $offset = ($page - 1) * $perPage;
    $pharmacies = array_slice($pharmacies, $offset, $perPage);

    return $app['twig']->render('landing/index.html.twig', array(
        'pharmacies' => $pharmacies,
        'page' => $page,
        'pages' => $pages,
    ));
})
->bind('landing');

// @route GET /faq
$app->get('/faq', function () use ($app) {
    return $app['twig']->render('faq/index.html.twig');
});

// @route GET /disclaimer
$app->get('/disclaimer', function () use ($app) {
    return $app['twig']->render('disclaimer/index.html.twig');
});

$app->get('/pharmacy/add', function () use ($app) {
    $response = $app['twig']->render('pharmacy-add/index.html.twig', array(
        'alert' => $app['session']->get('alert'),
        'alert_message' => $app['session']->get('alert-message'),
    ));

    $app['session']->remove('alert');
    $app['session']->remove('alert-message');

    return $response;
});

$app->post('/pharmacy/add', function (Request $request) use ($app) {
    $name = $request->get('name');
    $params = $request->request->all();

    $pharmacy = $app['db']->pharmacies->findOne(['name' => $name]);
    if ($pharmacy) {
        $app['session']->set('alert', 'danger');
        $app['session']->set('alert-message', 'Pharmacy with such name already registered.');

        return $app->redirect('/pharmacy/add');
    }

    $app['db']->pharmacies->insert($params);

    $app['session']->set('alert', 'success');
    $app['session']->set('alert-message', 'Well done! You have successfully add a new pharmacy.');

    return $app->redirect('/pharmacy/add');
});

$app->get('/pharmacy/manage', function () use ($app) {
    $user = $app['twig']->getGlobals()['user'];

    $pharmacies = [];
    foreach ($app['db']->pharmacies->find() as $pharmacy) {
        if (@$pharmacy['owner'] === $user['username']) {
            $pharmacies[] = $pharmacy;
        }
    }

    return $app['twig']->render('pharmacy-manage/index.html.twig', [
        'pharmacies' => $pharmacies,
    ]);
});

// @route GET /profile
$app->get('/profile', function () use ($app) {
    $agent = new Agent;

    if ($agent->is('Chrome') && rand(0,100)>90) {
        return new Response('', 500);
    }

    return $app['twig']->render('profile/index.html.twig');
});

$app->get('/logout', function () use ($app) {
    $app['session']->remove('username');

    return $app->redirect('/login');
});

// @route GET /login
$app->get('/login', function (Request $request) use ($app) {
    $user = $app['twig']->getGlobals()['user'];
    
    if ($user) {
        return $app->redirect('/profile');
    }

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
    $agent = new Agent;

    $username = $request->get('username');
    $password = $request->get('password');

    $ok = true;
    $user = $app['db']->users->findOne(['username' => $username]);

    if ($ok && !$user) {
        $ok = false;
        $app['session']->set('alert-message', 'Requested user has been not found.');
    }

    // check password sometimes on Chrome
    if ($ok &&
        (($agent->is('Chrome') && rand(0,100)<90) || $agent->is('Firefox'))
    ) {
        if ($user['password'] !== $password) {
            $ok = false;
            $app['session']->set('alert-message', 'Your password is wrong');
        }
    }

    // check password only in Firefox
    if ($ok && $agent->is('Firefox')) {
        if ($user['password'] !== $password) {
            $ok = false;
            $app['session']->set('alert-message', 'Your password is wrong');
        }
    }

    if (!$ok) {
        $app['session']->set('alert', 'danger');

        return $app->redirect('/login?username='.$username);
    }

    $user['signup_last_date'] = date('Y-m-d H:i:s');
    $app['db']->users->findAndModify(['username' => $username], $user);

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

    $params['signup_date'] = date('Y-m-d H:i:s');
    $params['signup_last_date'] = date('Y-m-d H:i:s');
    $app['db']->users->insert($params);

    $app['session']->set('alert', 'success');
    $app['session']->set('alert-message', 'Well done! You have successfully just registered.');

    return $app->redirect('/login?username='.$params['username']);
});

// @route GET /forgot
$app->get('/forgot', function (Request $request) use ($app) {
    $response = $app['twig']->render('forgot/step1.index.html.twig', array(
        'username' => $request->get('username'),
        'alert' => $app['session']->get('alert'),
        'alert_message' => $app['session']->get('alert-message'),
    ));

    $app['session']->remove('alert');
    $app['session']->remove('alert-message');

    return $response;
});

// @route POST /forgot
$app->post('/forgot', function (Request $request) use ($app) {
    $username = $request->get('username');
    $user = $app['db']->users->findOne(['username' => $username]);

    if (!$user) {
        $app['session']->set('alert', 'danger');
        $app['session']->set('alert-message', 'Requested user has been not found.');

        return $app->redirect('/forgot?username='.$username);
    }

    return $app->redirect('/forgot/step2?username='.$username);
});

$app->get('/forgot/step2', function (Request $request) use ($app) {
    $username = $request->get('username');
    $user = $app['db']->users->findOne(['username' => $username]);

    if (!$user) {
        $app['session']->set('alert', 'danger');
        $app['session']->set('alert-message', 'Requested user has been not found.');

        return $app->redirect('/forgot?username='.$username);
    }

    $response = $app['twig']->render('forgot/step2.index.html.twig', array(
        'username' => $request->get('username'),
        'alert' => $app['session']->get('alert'),
        'alert_message' => $app['session']->get('alert-message'),
    ));

    $app['session']->remove('alert');
    $app['session']->remove('alert-message');

    return $response;
});

// @route POST /forgot/step2
$app->post('/forgot/step2', function (Request $request) use ($app) {
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





$app->match('/pharmacy/{id}', function ($id) use ($app) {
    $pharmacy = $app['db']->pharmacies->findOne(['_id' => new MongoId($id)]);

    if (!$pharmacy) {
        return $app->redirect('/');
    }

    return $app['twig']->render('pharmacy-details/index.html.twig', [
        'pharmacy' => $pharmacy,
    ]);
});

return $app;