<?php



class KEmail extends CApplicationComponent
{
    public $host_name="localhost";
    public $host_port=25;
    public $ssl=false;
    public $http_proxy_host_name='';
    public $http_proxy_host_port=3128;
    public $socks_host_name='';
    public $socks_host_port=1080;
    public $socks_version='5';
    public $start_tls=false;
    public $localhost='localhost';
    public $direct_delivery=false;
    public $timeout=10;
    public $data_timeout=0;
    public $debug=false;
    public $html_debug=true;
    public $pop3_auth_host='';
    public $user="";
    public $realm="";
    public $password="";
    public $workstation="";
    public $authentication_mechanism="";
    
    private $current_directory;
    private $smtp_object;
    
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