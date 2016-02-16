<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Jenssegers\Agent\Agent as Agent;
use Laracasts\Flash\FlashServiceProvider as Flash;
use app\services\UserRoleService as UserRoleService;

//
$app->before(function (Request $request, Application $app) {
    //
    $app['twig']->addGlobal('lipsum', new joshtronic\LoremIpsum());

    if ($app['user.service']->isAuthenticated()) {
        $user = $app['user.service']->getAuthenticatedUser();

        $app['twig']->addGlobal('user', $user);
        $app['twig']->addGlobal('isadmin', $user && !empty($user['isadmin']));
        $app['twig']->addGlobal('ismanager', $user && !empty($user['ismanager']));
    }

}, Application::EARLY_EVENT);

// @route GET /phpinfo
$app->get('/phpinfo', function () use ($app) {
    ob_start();
    phpinfo();
    $phpinfo = ob_get_contents();
    ob_get_clean();

    return new Response($phpinfo, 201);
})
// ->after($app['userRole.service']->getValidateRoleCallback(UserRoleService::ROLE_ADMIN))
;

// @route GET /debug
$app->match('/debug', function () use ($app) {
    return $app['twig']->render('debug/index.html.twig', [
        'db' => $app['mongo']['default'],
    ]);
})
// ->after($app['userRole.service']->getValidateRoleCallback(UserRoleService::ROLE_ADMIN))
;

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
        'orders' => $app['session']->get('orders'),
        'page' => $page,
        'pages' => $pages,
    ));
})
->bind('landing')
// ->after($app['userRole.service']->getAuthenticatedRoleCallback())
;

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
    $user = @$app['twig']->getGlobals()['user'];

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
    $user = $app['user.service']->getAuthenticatedUser();
    
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

$app->get('/pharmacy/{id}', function ($id) use ($app) {
    $pharmacy = $app['db']->pharmacies->findOne(['_id' => new MongoId($id)]);

    if (!$pharmacy) {
        return $app->redirect('/');
    }

    $response = $app['twig']->render('pharmacy-details/index.html.twig', array(
        'pharmacy' => $pharmacy,
        'alert' => $app['session']->get('alert'),
        'alert_message' => $app['session']->get('alert-message'),
    ));

    $app['session']->remove('alert');
    $app['session']->remove('alert-message');

    return $response;
});

$app->post('/pharmacy/{id}', function (Request $request, $id) use ($app) {
    $params = $request->request->all();

    $pharmacySearchConditions = ['_id' => new MongoId($id)];
    $pharmacy = $app['db']->pharmacies->findOne($pharmacySearchConditions);

    if (!$pharmacy) {
        return $app->redirect('/');
    }

    $pharmacy = array_merge($pharmacy, $params);
    $app['db']->pharmacies->findAndModify($pharmacySearchConditions, $pharmacy);

    return $app->redirect('/pharmacy/'.$id);
});

$app->post('/pharmacy/{id}/products/add', function (Request $request, $id) use ($app) {
    $params = $request->request->all();

    $pharmacySearchConditions = ['_id' => new MongoId($id)];
    $pharmacy = $app['db']->pharmacies->findOne($pharmacySearchConditions);

    if (!$pharmacy) {
        return $app->redirect('/');
    }

    if (!isset($pharmacy['products'])) {
        $pharmacy['products'] = array();
    }

    $params['id'] = time() + rand(0,99999);
    $pharmacy['products'][] = $params;

    $app['db']->pharmacies->findAndModify($pharmacySearchConditions, $pharmacy);

    return $app->redirect('/pharmacy/'.$id);
});

$app->get('/pharmacy/{pharmacyId}/products/{productId}/order', function (Request $request, $pharmacyId, $productId) use ($app) {
    $pharmacySearchConditions = ['_id' => new MongoId($pharmacyId)];
    $pharmacy = $app['db']->pharmacies->findOne($pharmacySearchConditions);

    if (!$pharmacy) {
        return $app->redirect('/');
    }

    foreach ($pharmacy['products'] as &$product) {
        if (@$product['id'] == $productId) {
            $product['count'] += 10;
            break;
        }
    }

    $app['db']->pharmacies->findAndModify($pharmacySearchConditions, $pharmacy);

    return $app->redirect('/pharmacy/'.$pharmacyId);
});

$app->get('/pharmacy/{pharmacyId}/products/{productId}/buy', function (Request $request, $pharmacyId, $productId) use ($app) {
    $pharmacySearchConditions = ['_id' => new MongoId($pharmacyId)];
    $pharmacy = $app['db']->pharmacies->findOne($pharmacySearchConditions);

    if (!$pharmacy) {
        return $app->redirect('/');
    }

    $productToBuy = null;

    foreach ($pharmacy['products'] as $product) {
        if (@$product['id'] == $productId) {
            $productToBuy = $product;
            break;
        }
    }

    if ($productToBuy) {
        $app['session']->set('alert', 'success');
        $app['session']->set('alert-message', 'Well done! You have just buy the "'.$productToBuy['name'].'" product.');        

        $productToBuy['count'] -= 1;
        $productToBuy['last_buy'] = date('Y-m-d H:i:s');

        foreach ($pharmacy['products'] as &$product) {
            if (@$product['id'] == $productId) {
                $product = $productToBuy;
                break;
            }
        }

        $app['db']->pharmacies->findAndModify($pharmacySearchConditions, $pharmacy);

        $orders = $app['session']->get('orders');
        if (empty($orders)) {
            $orders = array();
        }
        $orders[] = array(
            'pharmacyId' => $pharmacyId,
            'productId' => $productId,
            'product' => $productToBuy,
        );

        $app['session']->set('orders', $orders);
    }

    return $app->redirect('/pharmacy/'.$pharmacyId);
});


// @route GET /users/add
$app->get('/users/add', function (Request $request) use ($app) {
    $isAdmin = @$app['twig']->getGlobals()['isadmin'];
    if (!$isAdmin) {
        return $app->redirect('/');
    }

    $response = $app['twig']->render('users-add/index.html.twig', array(
        'username' => $request->get('username'),
        'alert' => $app['session']->get('alert'),
        'alert_message' => $app['session']->get('alert-message'),
    ));

    $app['session']->remove('alert');
    $app['session']->remove('alert-message');

    return $response;
});

// @route POST /users/add
$app->post('/users/add', function (Request $request) use ($app) {
    $isAdmin = @$app['twig']->getGlobals()['isadmin'];
    if (!$isAdmin) {
        return $app->redirect('/');
    }

    $params = $request->request->all();
    $username = $request->get('username');
    $user = $app['db']->users->findOne(['username' => $username]);

    if ($user) {
        $app['session']->set('alert', 'danger');
        $app['session']->set('alert-message', 'Requested user already registered.');

        return $app->redirect('/users/add?username='.$username);
    }

    $app['db']->users->insert($params);

    $app['session']->set('alert', 'success');
    $app['session']->set('alert-message', 'Well done! You have successfully add a new user.');

    return $app->redirect('/users/add');
});

// @route GET /users/manage
$app->get('/users/manage', function (Request $request) use ($app) {
    $users = $app['db']->users->find();
    $users = iterator_to_array($users);

    return $app['twig']->render('users-manage/index.html.twig', array(
        'users' => $users,
    ));
});

// @route GET /users/remove
$app->get('/users/remove/{id}', function (Request $request, $id) use ($app) {
    $user = $app['db']->users->findOne(['_id' => new MongoId($id)]);

    if ($user['isadmin']) {
        return $app->redirect('/users/manage');
    }

    $a = $app['db']->users->remove(['_id' => new MongoId($id)]);
    return $app->redirect('/users/manage');
});

// @route GET /users/add-manager-role
$app->get('/users/add-manager-role/{id}', function (Request $request, $id) use ($app) {
    $user = $app['db']->users->findOne(['_id' => new MongoId($id)]);

    if ($user['isadmin']) {
        return $app->redirect('/users/manage');
    }

    $user['ismanager'] = true;
    
    $app['db']->users->findAndModify(['username' => $user['username']], $user);

    return $app->redirect('/users/manage');
});

// @route GET /users/remove-manager-role
$app->get('/users/remove-manager-role/{id}', function (Request $request, $id) use ($app) {
    $user = $app['db']->users->findOne(['_id' => new MongoId($id)]);
    unset($user['ismanager']);
    
    $app['db']->users->findAndModify(['username' => $user['username']], $user);

    return $app->redirect('/users/manage');
});

return $app;