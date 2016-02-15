<?php
// use app\models\UserModel as UserModel;

$app['db'] = $app->share(function () use ($app) {
    return $app['mongo']['default']->playground;
});

// $app['user.model'] = $app->share(function () use ($app) {
//     return new UserModel($app);
// });