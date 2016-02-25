<?php
namespace app\services;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserRoleService {
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    
    public function __construct(Application $app) {
        $this->app = $app;
    }

    public function validateUserRole($user, $role) {
        $hasRole = false;
        
        if ($user) {
            $hasRole = in_array($role, $user['roles']);
        }

        return $hasRole;
    }

    public function validateRole($role) {
        $user = $this->app['user.service']->getAuthenticatedUser();
        $hasRole = $this->validateUserRole($user, $role);

        return $hasRole;
    }

    public function getValidateRoleCallback($role) {
        return function () use ($role) {
            if (false == $this->validateRole($role)) {
                return $this->app->redirect('/');
            }
        };
    }

    public function getAuthenticatedRoleCallback() {
        return function () {
            if (false == $this->app['user.service']->isAuthenticated()) {
                return $this->app->redirect('/login');
            }
        };
    }
}