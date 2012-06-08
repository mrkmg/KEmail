<?php

/**
 * KEmail is an Yii Application Component that provides direct smtp interaction
 *
 * KEmail wraps http://www.phpclasses.org/package/14-PHP-Sends-e-mail-messages-via-SMTP-protocol.html to
 * provide a simple way to send email's to specific smtp servers from within php only. Does not require
 * php's mail() or any other library.
 *
 * @package KEmail
*/
class KEmail extends CApplicationComponent
{
    /**
     * @var string Host name of smtp server
    */
    public $host_name="localhost";
    
    /**
     * @var integer Port of smtp server
    */
    public $host_port=25;
    
    /**
     * @var bool Force SSL
    */
    public $ssl=false;
    
    /**
     * @var string Set to enable using an http proxy to access smtp server
    */
    public $http_proxy_host_name='';
    
    /**
     * @var integer Port of http proxy
    */
    public $http_proxy_host_port=3128;
    
    /**
     * @var string Set to enable using a socks proxy
    */
    public $socks_host_name='';
    
    /**
     * @var integer Port of socks proxy
    */
    public $socks_host_port=1080;
    
    /**
     * @var integer Version of socks proxy
    */
    public $socks_version='5';
    
    /**
     * @var bool Force `start_tls`
    */
    public $start_tls=false;
    
    /**
     * @var string Given hostname of client
    */
    public $localhost='localhost';
    
    /**
     * @var bool Skip smtp server and delevier directly to recipients smtp
    */
    public $direct_delivery=false;
    
    /**
     * @var integer Time in seconds to timeout for all smtp connections
    */
    public $timeout=10;
    
    /**
     * @var integer Time in seconds to timeout for data transfer to SMTP server, if 0 uses timeout
    */
    public $data_timeout=0;
    
    /**
     * @var bool Output Debug information to browser
    */
    public $debug=false;
    
    /**
     * @var bool Format Debug information as html, caution as this does not care when it is called, it will immediatly output
    */
    public $html_debug=true;
    
    /**
     * @var string Set to authenticate to a pop3 server
    */
    public $pop3_auth_host='';
    
    /**
     * @var string Username for smtp authentication
    */
    public $user="";
    
    /**
     * @var string Realm for smtp authentication
    */
    public $realm="";
    
    /**
     * @var string Password for smtp authentication
    */
    public $password="";
    
    /**
     * @var string Workstation for smtp authentication
    */
    public $workstation="";
    
    /**
     * @var string Force a specific smtp authentication mechanism ('LOGIN','PLAIN','CRAM-MD5','NTLM')
    */
    public $authentication_mechanism="";
    
    /**
     * @var string current directory of this script file, used to include required libraries
     * @access private
    */
    private $current_directory;
    
    /**
     * @var smtp_class Holder for library class
    */
    private $smtp_object;
    
    
    /**
     * Imports required libraries and sets configuration
     *
     * @access public
    */
    public function init()
    {
        $this->current_directory = dirname(__FILE__).DIRECTORY_SEPARATOR;
        include($this->current_directory.'includes/smtp.php');
	include($this->current_directory.'includes/basic_sasl_client.php');
	include($this->current_directory.'includes/cram_md5_sasl_client.php');
	include($this->current_directory.'includes/digest_sasl_client.php');
	include($this->current_directory.'includes/login_sasl_client.php');
	include($this->current_directory.'includes/ntlm_sasl_client.php');
	include($this->current_directory.'includes/plain_sasl_client.php');
	include($this->current_directory.'includes/sasl.php');
	
        $this->smtp_object = new smtp_class;
        $this->smtp_object->host_name =             $this->host_name;
	$this->smtp_object->host_port =             $this->host_port;
	$this->smtp_object->ssl =                   $this->ssl;

	$this->smtp_object->http_proxy_host_name =  $this->http_proxy_host_name;
	$this->smtp_object->http_proxy_host_port =  $this->http_proxy_host_port;

	$this->smtp_object->socks_host_name =       $this->socks_host_name;
	$this->smtp_object->socks_host_port =       $this->socks_host_port;
	$this->smtp_object->socks_version =         $this->socks_version;

	$this->smtp_object->start_tls =             $this->start_tls;
	$this->smtp_object->localhost =             $this->localhost;
	$this->smtp_object->direct_delivery =       $this->direct_delivery;
	$this->smtp_object->timeout =               $this->timeout;
	$this->smtp_object->data_timeout =          $this->data_timeout;

	$this->smtp_object->debug =                 $this->debug;
	$this->smtp_object->html_debug =            $this->html_debug;
	$this->smtp_object->pop3_auth_host =        $this->pop3_auth_host;
	$this->smtp_object->user =                  $this->user;
	$this->smtp_object->realm =                 $this->realm;
	$this->smtp_object->password =              $this->password;
	$this->smtp_object->workstation =           $this->workstation;
	$this->smtp_object->authentication_mechanism = $this->authentication_mechanism;
        
        if($this->direct_delivery)
        {
            if(!function_exists("GetMXRR"))
            {
                $_NAMESERVERS=array();
                include($this->current_directory."includes/getmxrr.php");
            }
        }
    }
    
    /**
     * Sends an email via smtp library
     *
     * @param string $from From email address
     * @param array|string $to To email address(es)
     * @param string $subject Subject of email
     * @param string $body Body of the email
     * @param array $additional_headers More headers to include in the email
     *
     * @return bool Whether or not the email was sent
     *
     * @throws Exception when $to is not an array or string
    */
    public function send($from,$to,$subject,$body,$additional_headers=array())
    {
        $to_f = array();
        $to_h = '';
        if(is_array($to))
        {
            $to_f = $to;
            $to_h = implode(', ',$to);
        }
        elseif(is_string($to))
        {
            $to_f = array($to);
            $to_h = $to;
        }
        else
        {
            throw new Exception('$to can only be a string or an array');
        }
        
        return $this->smtp_object->SendMessage(
            $from,
            $to_f,
            array_merge(array(
                    "From: $from",
                    "To: $to_h",
                    "Subject: ".$subject,
                    "Date: ".strftime("%a, %d %b %Y %H:%M:%S %Z")
            ),$additional_headers),
            $body);
    }
}

?>