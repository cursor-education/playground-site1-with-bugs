<?php
use app\services\UserService as UserService;
use app\services\UserRoleService as UserRoleService;

$app['db'] = $app->share(function () use ($app) {
    return $app['mongo']['default']->playground;
});

$app['user.service'] = $app->share(function () use ($app) {
    return new UserService($app);
});

$app['userRole.service'] = $app->share(function () use ($app) {
    return new UserRoleService($app);
});