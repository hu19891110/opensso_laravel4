<?php namespace Maen\Opensso;

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserProviderInterface;
use Exception;
use Config;

class OpenssoUserProvider implements UserProviderInterface{
	
	/**
     * $serverAddress - server address to OpenSSO REST API passed to the constructor 
     *
     * @var string
     */
    private $serverAddress = null;
	
	/**
     * $uri - uri used for OpenSSO authenicate operation passed to the constructor 
     *
     * @var string
     */
    protected $uri = null;
	
	/**
     * $cookiePath - the path set for the OpenSSO cookie.
     *
     * @var string
     */
    protected $cookiePath = null;

    /**
     * $cookieDomain - the domain set for the OpenSSO cookie.
     *
     * @var string
     */
    protected $cookieDomain = null;
	
	/**
     * $cookieName - the name for the OpenSSO cookie.
     *
     * @var string
     */
    protected $cookieName = null;
	
	
	/**
     * $openssoUser - attributes object for OpenssoUser.
     *
     * @var string
     */
	private $openssoUser = null;
	
	/**
     * $tokenid - tokenid of OpenSSO cookie 
     *
     * @var string
     */
    protected $tokenId = null;
	
	/**
     * Creates a new LdapUserProvider and connect to Ldap
     *
     * @param array $config
     * @return void
     */
    public function __construct($config)
    {
        $this->serverAddress = $config['serverAddress'];
		$this->uri 			 = $config['uri'];
		$this->cookiePath 	 = $config['cookiepath'];
		$this->cookieDomain  = $config['cookiedomain'];
		$this->cookieName  	 = $config['cookiename'];
		
		$this->getTokenIdFromCookie();
    }
	
	/**
	 * Retrieve a user by their unique identifier.
	 *
	 * @param  mixed  $identifier
	 * @return \Maen\Opensso\OpenssoUser|null
	 */
	public function retrieveById($identifier)
    {
		$this->tokenId = $identifier;
		
		if($this->isTokenValid($this->tokenId)) {
			
			$user = $this->setAttributes($this->tokenId);
			
			return new \Maen\Opensso\OpenssoUser($user);
		}
    }
	
	/**
	 * Retrieve a user by the given credentials.
	 *
	 * @param  array  $credentials
	 * @return \Maen\Opensso\OpenssoUser|null
	 */
	public function retrieveByCredentials(array $credentials = array())
	{
		
		//Signing in using token id from Open SSO cookie
		if(empty($credentials)){
			if($this->isTokenValid($this->tokenId)) {
				
				$user = $this->setAttributes($this->tokenId);
				
				return new \Maen\Opensso\OpenssoUser($user);
				
			}
			else{
				return null;
			}
		}
		
		$ch = curl_init(); 
		curl_setopt ($ch, CURLOPT_POST, true); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'username=' . $credentials['username'] .
											 '&password=' . $credentials['password'] .
											 '&uri=' . $this->uri);
		curl_setopt($ch, CURLOPT_URL, $this->serverAddress.'opensso/identity/authenticate'); 
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		
		if($output === false){
			throw new Exception('Curl error: ' . curl_error($ch));
			curl_close($ch);
		}
		else{
			$tokenId = str_replace('token.id=','',$output); 
			$tokenId = substr($tokenId,0,-1);
			
			$this->tokenId = $tokenId;
			
			$user = $this->setAttributes($tokenId);
			
			curl_close($ch);
			
			setrawcookie($this->cookieName, $this->tokenId, 0, $this->cookiePath, $this->cookieDomain);
			
			return new \Maen\Opensso\OpenssoUser($user);
		}
		 
	}
	
	/**
	 * Validate a user against the given credentials.
	 *
	 * @param  \Illuminate\Auth\UserInterface  $user
	 * @param  array  $credentials
	 * @return bool
	 */
	public function validateCredentials(\Illuminate\Auth\UserInterface $user, array $credentials)
	{
		$userId = $user->getAuthIdentifier();

		return true;
	}
	
	/**
	 * Retrieve a user by by their unique identifier and "remember me" token.
	 *
	 * @param  mixed  $identifier
	 * @param  string  $token
	 * @return \Maen\Opensso\OpenssoUser|null
	 */
	public function retrieveByToken($identifier, $token)
	{
   		//
	}
	
	/**
	 * Update the "remember me" token for the given user in storage.
	 *
	 * @param  \Illuminate\Auth\UserInterface  $user
	 * @param  string  $token
	 * @return void
	 */
	public function updateRememberToken(\Illuminate\Auth\UserInterface $user, $token)
	{
		//
	} 
	
	 /**
     * Validate OpenSSO token
     *
     * @param  string $tokenid
     * @return bool
     */
    protected function isTokenValid($tokenid)
    {
		
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $this->serverAddress . 'opensso/identity/isTokenValid?tokenid=' . $tokenid); 
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);  
		curl_close($ch);
		
		$result = substr($output,0,-1);
		
		if ($result == 'boolean=true') {
			return true;
		}
		else {
			return false;
		}

    }
	
	/**
     * Sets a user attributes recieved from the OpenSSO REST API to be used by
     * this adapter.
     *
     * @param  string $tokenId The string for OpenSSO REST API token id
     * @return array
     */
    protected function setAttributes($tokenId)
    {
		
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $this->serverAddress.'opensso/identity/attributes?' . $this->cookieName . '=' . $tokenId); 
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);  
		curl_close($ch);
		$output = explode(chr(10), $output);
		
		$attributesArray = array();
		foreach ($output as $key => $value) {
			
			$tokenPattern = "/^userdetails.token.id=/";
			$namePattern  = "/^userdetails.attribute.name=/";
			$valuePattern = "/^userdetails.attribute.value=/";
			
			$tokenMatch = preg_match($tokenPattern, $value);
			$nameMatch  = preg_match($namePattern, $value);
			
			if($tokenMatch) {
				$attrKey   = 'tokenid';
				$attrValue = preg_replace($tokenPattern,'',$value);	
			}
			else if ($nameMatch) {
				$attrKey   = preg_replace($namePattern,'',$value);
				$attrValue = preg_replace($valuePattern,'',$output[$key+1]);
			}
			
			$attributesArray[$attrKey] = $attrValue;
		}
		
        return $attributesArray;
        
    }
	
	/**
     * Set the token id from the user's Open SSO cookie
     *
     * @return OpenssoUserProvider Provides a fluent interface
     */
	protected function getTokenIdFromCookie(){
		
		if(isset($_COOKIE[$this->cookieName])){
			$this->tokenId = $_COOKIE[$this->cookieName];
		}
		
		return $this;
		
	}
	
}