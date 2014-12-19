<?php

namespace Rswork\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Rswork\Silex\Combine\Combine;

class CombineServiceProvider implements ServiceProviderInterface, ControllerProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */
    public function register( Application $app )
    {
        $app['combine.default_options'] = array(
            'cache' => false,
            'base_path' => sys_get_temp_dir() .DIRECTORY_SEPARATOR. 'combine',
            'cache_path' => sys_get_temp_dir() .DIRECTORY_SEPARATOR. 'combine' .DIRECTORY_SEPARATOR. 'cache',
            'css_path' => 'css',
            'js_path' => 'js',
            'mount_to' => '/',
            'match_url' => '/combine',
            'bind_to' => 'rswork.combine',
            'expires' => 604800, // default is one week
        );

        $app['combine.cache'] = $app['combine.default_options']['cache'];
        $app['combine.base_path'] = $app['combine.default_options']['base_path'];
        $app['combine.cache_path'] = $app['combine.default_options']['cache_path'];
        $app['combine.css_path'] = $app['combine.default_options']['css_path'];
        $app['combine.js_path'] = $app['combine.default_options']['js_path'];
        $app['combine.match_url'] = $app['combine.default_options']['match_url'];
        $app['combine.mount_to'] = $app['combine.default_options']['mount_to'];
        $app['combine.bind_to'] = $app['combine.default_options']['bind_to'];
        $app['combine.expires'] = $app['combine.default_options']['expires'];

        $app['combine'] = $app->share(function() use ($app) {
            $options = array(
                'cache' => $app['combine.cache'],
                'base_path' => $app['combine.base_path'],
                'cache_path' => $app['combine.cache_path'],
                'css_path' => $app['combine.css_path'],
                'js_path' => $app['combine.js_path'],
            );

            return new Combine( $options );
        });
    }

    public function boot( Application $app )
    {
        if( false !== $app['combine.base_path'] AND file_exists( $app['combine.base_path'] ) ) {
            $app->mount($app['combine.mount_to'], $this);
        }
    }

    public function connect( Application $app )
    {
        $combine = $app['controllers_factory'];

        $combine
            ->match(
                $app['combine.match_url'],
                function( Request $request, Application $app ){
                    $files = '';

                    if( $request->query->has('files') ) {
                        $files .= $request->query->get('files');
                    }

                    if( $request->query->has('f') ) {
                        if( $files != '' ) {
                            $files .= ','.$request->query->get('f');
                        } else {
                            $files .= $request->query->get('f');
                        }
                    }

                    $base = '';
                    if( $request->query->has('b') ) {
                        $base .= $request->query->get('b');
                    } elseif( $request->query->has('base') ) {
                        $base .= $request->query->get('base');
                    }

                    $base = rtrim( $base, '\/\\' );

                    $info = $app['combine']->getInfo( $files, $base );

                    if( $info === false ) {
                        return $app->abort(404);
                    }

                    $expires = new \DateTime();

                    $expires->setTimestamp( $expires->getTimestamp() + abs($app['combine.expires']) );

                    $file = $app['combine']->combine( $files, $base );

                    $response = new BinaryFileResponse($file['cache_file']);
                    $response->setEtag( $file['etag'] );
                    $response->setLastModified( new \DateTime( date( 'Y-m-d H:i:s', $file['lastmodified']) ) );
                    $response->setPublic();
                    $response->setExpires($expires);
                    $response->isNotModified($request);

                    if( $info['type'] == 'js' ) {
                        $response
                            ->headers
                            ->set('Content-Type', 'application/javascript')
                        ;
                    }elseif ($info['type'] == 'css'){
                        $response
                            ->headers
                            ->set('Content-Type', 'text/css')
                        ;
                    }

                    return $response;
                }
            )
            ->bind( $app['combine.bind_to'] )
        ;

        return $combine;
    }
}
