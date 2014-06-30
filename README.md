##OpenSSO Authenication for Laravel 4

This is a provider for adding a OpenSSO driver to your authentication system in Laravel 4.1

## Installation 

To install this package through composer, edit your project's `composer.json` file to require `maen/opensso`.

	"require": {
		"laravel/framework": "4.1.*",
		"maen/opensso": "dev-master"
	},
	"minimum-stability" : "dev"
	
Next, update Composer from the terminal:

    composer update
    
##Configuration
You will need to add a opensso configuartion file to `app/config/` called `opensso.php` and set out in the following way with the correct information for your OpenSSO installation


```php
return array(
	"serverAddress" => "https://sso.mysite.com/",
	"uri" 			=> "myuri",
	"cookiepath"	=> "/",
	"cookiedomain"	=> ".mysite.com",
	"cookiename"	=> "mycookiename",
);
```
Also make sure in `auth/config/auth.php` the driver is set to `opensso`.

Finally add the OpenSSO servicer provider into `auth/config/app.php` as follows

    'Maen\Opensso\OpenssoServiceProvider'
##Usage
Now your Auth driver is using OpenSSO you will be able to use the Laravel `Auth` class to authenication users.

###Examples

```php
//Authenicating using the OpenSSO TokenID from a cookie
Auth::attempt();
	
//Authenicating using user input
$input = Input::only('username', 'password');
Auth::attempt($input);

//Retriving the OpenSSO attributes of a logged in user
$user = Auth::user();
```
