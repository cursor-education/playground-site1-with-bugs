<?php
namespace app\services;
use Silex\Application;

class UserService {
    
    public function __construct(Application $app) {
        $this->app = $app;
    }

    public function isAuthenticated() {
        return null !== $this->getAuthenticatedUser();
    }

    public function getAuthenticatedUser() {
        $username = $this->app['session']->get('username');
        $userRecord = $this->app['db']->users->findOne(['xusername' => $username]);

        return $userRecord;
    }
}