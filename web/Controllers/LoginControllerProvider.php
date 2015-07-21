<?php
namespace PrintApp\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use PrintApp\Ldap\LdapAuthentication;
use PrintApp\UserManagement\User;

class LoginControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        /**
         * Display a login form
         */
        $controllers->get('/', function (Request $request) use ($app) {
            $IsError = ($request->query->get('error') === "1");
            
            $x = $app['twig']->render('login.twig', [
                'error' => $IsError,
            ]);
            return $x;

        });
        
        /**
         * Handle login form submissions
         */
        $controllers->post('/', function (Request $request) use ($app) {
            $UsernameInput = User::GetDomainAndUsernameFromInput($request->request->get('username'), $app);
            $Password = $request->request->get('password');
            $DeviceID = $request->query->get('device_id');
            $Username = $UsernameInput['Username'];
            $Domain = $UsernameInput['Domain'];

            $Ldap = new LdapAuthentication($app);
            try {
                $LoginResult = $Ldap->TryToAuthenticate($Domain, $Username, $Password);
            } catch (\Exception $ex) {
                return $app->redirect("/login?error=1&device_id=" . urlencode($DeviceID));
            }
            if (!$LoginResult) {
                return $app->redirect("/login?error=1&device_id=" . urlencode($DeviceID));
            }
            
            /* @var $User User */
            $User = User::TryGetUserByUsername($Username, $Domain, $app);
            if ($User !== NULL) {
                $User->Load();
                $User->ResetToken();
                $User->DeviceID = $DeviceID;
                $User->Save();
            }
            else {
                $User = new User($app);
                $User->DeviceID = $DeviceID;
                $User->Username = $Username;
                $User->Domain = $Domain;
                
                $User->ResetToken();
                $User->Save();
            }
            
            $UserFields = [
                'Domain' => $User->Domain,
                'Username' => $User->Username,
                'Token' => $User->Token,
            ];
            return $app->redirect('/login/close?' . http_build_query($UserFields));
        });

        /**
         * Close the WebView on the Android client. TODO: remove the
         * webview altogether
         */
        $controllers->get('/close', function () {
            return '';
        });

        return $controllers;
    }
}