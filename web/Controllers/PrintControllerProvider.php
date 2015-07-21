<?php
namespace PrintApp\Controllers;

require 'ipp/CupsPrintIPP.php';

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use PrintApp\UserManagement\User;

/**
 * Application endpoints related to printing.
 */
class PrintControllerProvider implements ControllerProviderInterface
{
    
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];
        
        /**
         * Gets all pending print jobs for the current user.
         */
        $controllers->get('/jobs', function (Request $request) use ($app) {
            $Username = User::GetUsernameFromRequest($request);
            /* @var $cups CupsPrintIPP */
            $cups = new \CupsPrintIPP();
            if (!empty($app['config']) && !empty($app['config']['cups']) && !empty($app['config']['cups']['host'])) {
                $cups->setHost($app['config']['cups']['host']);
            }
            else {
                throw new \Exception("Cups is not configured.");
            }
            if (!empty($app['config']) && !empty($app['config']['cups']) && !empty($app['config']['cups']['catchall_printer'])) {
                $cups->setPrinterURI($app['config']['cups']['catchall_printer']);
            }
            else {
                throw new \Exception("Printer is not configured.");
            }
            
            $cups->setUserName($Username); // setting user name for server
            //$cups->debug_level = 3; // Debugging very verbose
            //$cups->setLog('/tmp/printipp','file',3); // logging very verbose
            
            if (($error = $cups->getJobs(true)) === "successfull-ok") {
                $jobs_attributes = [];
                for ($count = 0; !empty($cups->jobs_attributes->{"job_". $count}) && is_object($cups->jobs_attributes->{"job_". $count}); $count++) {
                    $jobs_attributes["job_". $count] = $cups->jobs_attributes->{"job_". $count};
                }
                if (!empty($jobs_attributes)) {
                    return $app->json($jobs_attributes);
                }
                else {
                    return $app->json(new \stdClass());
                }
            }
            return $app->json($error, 500);
            
        })->before('PrintApp\Controllers\Shared::EnsureAuthenticatedGETJSON');
        
        /**
         * Gets a list of all known beacons and the printers they are near.
         */
        $controllers->get('/beaconMap', function (Request $request) use ($app) {
            $all_beacons = $app['db']->fetchAll("SELECT * FROM vw_beacons_printers");
            
            if (!empty($all_beacons)) {           
                $beacon_map = [];
                foreach ($all_beacons as $beacon) {
                    $beacon_map[$beacon["beacon_identifier"]] = $beacon['printer_name'];
                }
                return $app->json($beacon_map);
            }
            else {
                return $app->json([]);
            }
        })->before('PrintApp\Controllers\Shared::EnsureAuthenticatedGETJSON');
        
        /**
         * Release all print jobs for the current user to the printer provided in
         * the POST data
         */
        $controllers->post('/releaseAll', function (Request $request) use ($app) {
            $Username = User::GetUsernameFromRequest($request);
            $Printer = $request->request->get('printer');
            
            if (empty($Printer)) {
                throw new \Exception("Must provider a printer to print to.");
            }
            
            /* @var $cups CupsPrintIPP */
            $cups = new \CupsPrintIPP();
            if (!empty($app['config']) && !empty($app['config']['cups']) && !empty($app['config']['cups']['host'])) {
                $cups->setHost($app['config']['cups']['host']);
            }
            else {
                throw new \Exception("Cups is not configured.");
            }
            if (!empty($app['config']) && !empty($app['config']['cups']) && !empty($app['config']['cups']['catchall_printer'])) {
                $cups->setPrinterURI($app['config']['cups']['catchall_printer']);
            }
            else {
                throw new \Exception("Printer is not configured.");
            }
            
            $cups->setUserName($Username);
            $cups->debug_level = 3; // Debugging very verbose
            $cups->setLog('/tmp/printipp','file',3); // logging very verbose
            
            if (($error = $cups->getJobs(false)) === "successfull-ok") {
                $jobs_attributes = [];
                for ($count = 0; !empty($cups->jobs_attributes->{"job_". $count}) && is_object($cups->jobs_attributes->{"job_". $count}); $count++) {
                    $jobs_attributes["job_". $count] = $cups->jobs_attributes->{"job_". $count};
                }
                foreach ($jobs_attributes as $v) {
                    $job_uri = $v->job_uri->_value0;
                    $job_id = $v->job_id->_value0;
                    exec("/usr/sbin/lpmove ". escapeshellarg($job_id) ." ". escapeshellarg($Printer));
                    if (!empty($job_uri) && ($releaseJobsError = $cups->releaseJob($job_uri)) !== "successfull-ok") {
                        return $app->json($releaseJobsError, 500);
                    }
                }

                return $app->json([
                    "isSuccessful" => true,
                ]);
            }
            return $app->json($error, 500);
        })->before('PrintApp\Controllers\Shared::EnsureAuthenticatedGETJSON');

        return $controllers;
    }
}
