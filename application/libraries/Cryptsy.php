<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
* CodeIgniter Cryptsy
*
* A CodeIgniter library to interact with Cryptsy API
*
* @package CodeIgniter
* @category Libraries
* @author Antoan Stoykov
* @version v0.1
* @link https://github.com/stoykov/codeigniter-cryptsy
* @link http://alienshaped.com/
* @license http://www.opensource.org/licenses/mit-license.html
*/

class Cryptsy {
	/**
	* CI
	*
	* CodeIgniter instance
	* @var object
	*/
	private $_ci;
	
	/**
	* pubkey
	*
	* Cryptsy Public Key
	* @var string
	*/
	private $_pubkey;
	
	/**
	* privkey
	*
	* Cryptsy Private Key
	* @var string
	*/
	private $_privkey;

	public function __construct()
	{
		$this->_ci =& get_instance();
		$this->_ci->load->config('cryptsy');
		
		$this->_pubkey = $this->_ci->config->item("cryptsy_public_key");
		$this->_privkey = $this->_ci->config->item("cryptsy_private_key");
	}
	
	public function general_request($method, $data = null)
	{
		return $this->_make_query($method, $data);
	}
	
	/**
	* create_order
	*
	* Creates an order at a specific market
	*
	* @param market integer
	* @param type string
	* @param quantity float
	* @param price decimal
	*
	* @return object
	*/
	public function create_order($market, $type, $quantity, $price)
	{
		return $this->_make_query("createorder", array(
			"marketid" => $market,
			"ordertype" => $type,
			"quantity" => $quantity,
			"price" => $price
		));
	}
	
	/**
	* cancel_order
	*
	* Cancels an order
	*
	* @param id integer
	*
	* @return object
	*/
	public function cancel_order($id)
	{
		return $this->_make_query("cancelorder", array(
			"orderid" => $id
		));
	}
	
	/**
	* calculate_fees
	*
	* Calculates fees for selling/buying a given amount
	*
	* @param type string
	* @param quantity float
	* @param price decimal
	*
	* @return object
	*/
	public function calculate_fees($type, $quantity, $price)
	{
		return $this->_make_query("calculatefees", array(
			"ordertype" => $type,
			"quantity" => $quantity,
			"price" => $price
		));
	}
	
	private function _make_query($method, array $req = array())
	{
        $req['method'] = $method;
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1];
       
        // generate the POST data string
        $post_data = http_build_query($req, '', '&');

        $sign = hash_hmac("sha512", $post_data, $this->_privkey);
 
        // generate the extra headers
        $headers = array(
                'Sign: '.$sign,
                'Key: '.$this->_pubkey,
        );
 
        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Cryptsy API PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
        }
        curl_setopt($ch, CURLOPT_URL, 'https://api.cryptsy.com/api');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
 
        $res = curl_exec($ch);

        if ($res === false)
			throw new Exception('Could not get reply: '.curl_error($ch));
			
        $dec = json_decode($res);
		
        if (!$dec)
			throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
			
        return $dec;
	}
}