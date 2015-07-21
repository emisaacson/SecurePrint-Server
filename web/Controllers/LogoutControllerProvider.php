<?php
namespace PrintApp\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use PrintApp\UserManagement\User;

class LogoutControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        
        /**
         * Perform a logout for the user.
         */
        $controllers->get('/', function (Request $request) use ($app) {
            $Username = $request->query->get('username');
            $Token = $request->query->get('token');
            $Domain = $request->query->get('domain');
            
            $User = User::TryGetUserByUsername($Username, $Domain, $app);
            if ($User !== NULL) {
                $User->Load();
                if ($User->Token === $Token) {
                    $User->Delete();
                }
                return $app->redirect('/logout/clear');
            }

            return $app->redirect('/logout/close');
        });

        /**
         * Some URLs just for signaling with the WebView in the Android
         * App. TODO: stop this and user a normal API call instead.
         */
        $controllers->get('/clear', function () {
            return '';
        });
        
        $controllers->get('/close', function () {
            return '';
        });

        return $controllers;
    }
}