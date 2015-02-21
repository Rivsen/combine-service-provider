<?php

namespace Rswork\Silex\Extension;

use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Twig extension for the bundle.
 */
class Combine extends \Twig_Extension
{

    private $app;

    public function __construct(\Pimple $app)
    {
        $this->app = $app;
    }

    /**
     * Getter.
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'combine_url' => new \Twig_Function_Method($this, 'getCombineUrl'),
        );
    }

    /**
     * Getter.
     *
     * @return string
     */
    public function getCombineUrl()
    {
        $combine = $this->app['combine'];

        $args = func_get_args();

        if( isset( $args[0] ) ) {
            $files = $args[0];
        } else {
            $files = '';
        }

        if( is_array( $files ) ) {
            $files = implode( $files, ',' );
        }

        if( isset( $args[1] ) ) {
            $base = $args[1];
        } else {
            $base = '';
        }


        return $this
            ->app['url_generator']
            ->generate(
                $this->app['combine.bind_to'],
                array(
                    'f'=>$files,
                    'b'=>$base,
                ),
                UrlGenerator::ABSOLUTE_URL
            )
        ;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'combine_extension';
    }
}
