<?php namespace Maen\Opensso;

use Illuminate\Auth\Guard;
use Illuminate\Support\ServiceProvider;
use Exception;

class OpenssoServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		
		$this->package('maen/opensso');
		\Auth::extend('opensso', function($app) {
			
			if(!$app['config']['opensso']){
				 throw new Exception('OpenSSO config not found. Check if app/config/opensso.php exists.');
			}
			
			$config = $app['config']['opensso'];
			
 			return new Guard(new OpenssoUserProvider($config), $app['session.store']);
			
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

}
