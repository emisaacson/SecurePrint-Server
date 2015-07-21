<?php

namespace PrintApp\Controllers;

use Silex\Application;
use PrintApp\UserManagement\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shared utility class for all controllers
 */
class Shared {
    /**
     * Middleware to ensure only authenticated requests produce output
     * for an HTTP GET request. The domain, token, and username must appear
     * in the request query string. The requestor should expect JSON payload
     * in the output
     * 
     * @param Request $request
     * @param Application $app
     * @return object Application response
     */
    public static function EnsureAuthenticatedGETJSON(Request $request, Application $app) {
        $Username = $request->query->get('username');
        $Domain = $request->query->get('domain');
        $Token = $request->query->get('token');
        
        $User = User::TryGetUserByUsername($Username, $Domain, $app);
        if ($User !== null) {
            $User->Load();
            if ($User->Token !== $Token) {
                return $app->json(['IsAuthenticated' => false], 403);
            }
        }
        else {
            return $app->json(['IsAuthenticated' => false], 403);
        }
    }
}
