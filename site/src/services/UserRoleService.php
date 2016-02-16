<?php
namespace app\services;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserRoleService {
    const ROLE_ADMIN = 'admin';
    
    public function __construct(Application $app) {
        $this->app = $app;
    }

    public function validateRole($role) {
        $user = $this->app['user.service']->getAuthenticatedUser();
        
        if ($user) {
            var_dump(1);die;
        }

        return $this->app->redirect('/login');
    }

    public function getValidateRoleCallback($role) {
        return function () use ($role) {
            return true;
            // return $this->validateRole($role);
        };
    }

    public function getAuthenticatedRoleCallback() {
        return function () {
            return true;
            // return $this->validateRole($role);
        };
    }
}