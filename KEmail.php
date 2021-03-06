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
     * @var bool enable_queue Enable the queuing mechanism
    */
    public $enable_queue=false;

    /**
     * @var string queue_table_name name of table in database to hold the queue
    */
    public $queue_table_name='kemail_queue';

    /**
     *@var bool Automatically check for and create the kemail_queue table in your database
    */
    public $autocreate_db_table=true;

    /**
     *@var string sender name of the sender for user friendly display of names in mail-clients
    */
    public $sender='';
    
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
     * @var CDbConnection Holds the CDbConnection to be used for queues
    */
    private $connection;

    /**
     * @var last_error Hold string of last error
    */
    private $last_error = '';

    /**
     * Imports required libraries and sets configuration
     *
     * @access public
    */
    public function init()
    {
        $this->current_directory = dirname(__FILE__).DIRECTORY_SEPARATOR;
        include($this->current_directory.'includes'.DIRECTORY_SEPARATOR.'smtp.php');
        include($this->current_directory.'includes'.DIRECTORY_SEPARATOR.'basic_sasl_client.php');
        include($this->current_directory.'includes'.DIRECTORY_SEPARATOR.'cram_md5_sasl_client.php');
        include($this->current_directory.'includes'.DIRECTORY_SEPARATOR.'digest_sasl_client.php');
        include($this->current_directory.'includes'.DIRECTORY_SEPARATOR.'login_sasl_client.php');
        include($this->current_directory.'includes'.DIRECTORY_SEPARATOR.'ntlm_sasl_client.php');
        include($this->current_directory.'includes'.DIRECTORY_SEPARATOR.'plain_sasl_client.php');
        include($this->current_directory.'includes'.DIRECTORY_SEPARATOR.'sasl.php');
        
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
                include($this->current_directory.'includes'.DIRECTORY_SEPARATOR.'getmxrr.php');
            }
        }
        if($this->needToCreateTable()) $this->autoCreateTable();
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
        
        $success = $this->smtp_object->SendMessage(
            $from,
            $to_f,
            array_merge(array(
                    "From: $this->sender <$from>",
                    "To: $to_h",
                    "Subject: ".$subject,
                    "Date: ".strftime("%a, %d %b %Y %H:%M:%S %Z")
            ),$additional_headers),
            $body);

        if($success){
            $this->last_error = '';
            return true;
        } else {
            $this->last_error = $this->smtp_object->error;
            return false;
        }
    }

    /**
     * Queues an email to be sent via smtp library
     *
     * @param string $from From email address
     * @param array|string $to To email address(es)
     * @param string $subject Subject of email
     * @param string $body Body of the email
     * @param array $additional_headers More headers to include in the email
     * @param integer $priority Number 0-9 denoting a priority, 9 being the highest
     *
     * @return bool Whether or not the email was queued
     *
     * @throws Exception when $enable_queue is false
     * @throws Exception when no database is defined
     * @throws Exception when $to is not an array or string
    */
    public function queue($from,$to,$subject,$body,$additional_headers=array(),$priority=5)
    {
        if(!$this->enable_queue) throw new Exception('Queue is not enabled for kemail');
        
        $connection = $this->getConnection();
        if(!$connection) throw new Exception('Database connection not found');
        if(!is_string($to) and !is_array($to)) throw new Exception('$to can only be a string or an array');

        $to = json_encode($to);
        $additional_headers = json_encode($additional_headers);

        $insertSql = 'INSERT INTO `'.$this->queue_table_name.'` (`priority`,`from`,`to`,`subject`,`body`,`additional_headers`)
            VALUES (:priority,:from,:to,:subject,:body,:additional_headers)';
        $command = $connection->createCommand($insertSql);
        $command->bindParam(':priority',$priority,PDO::PARAM_STR);
        $command->bindParam(':from',$from,PDO::PARAM_STR);
        $command->bindParam(':to',$to,PDO::PARAM_STR);
        $command->bindParam(':subject',$subject,PDO::PARAM_STR);
        $command->bindParam(':body',$body,PDO::PARAM_STR);
        $command->bindParam(':additional_headers',$additional_headers,PDO::PARAM_STR);
        $success = $command->execute();
        if($success){
            $this->last_error = '';
            return true;
        } else {
            $this->last_error = 'Failed to insert email to queue.';
            return false;
        }

    }

    /**
     * Sends emails from queue
     *
     * @param integer How many emails to process from the queue. 0 means unlimited
     * @param bool Whether or not to include priority in queue selection
     *
     * @return bool **TODO** always returns true for now.
     *
     * @throws Exception when no database is defined
    */
    public function processQueue($limit=0,$ignorePriority=false)
    {
        $connection = $this->getConnection();
        if(!$connection) throw new Exception('Database connection not found');

        $selectSql = 'SELECT * FROM `'.$this->queue_table_name.'`';
        if(!$ignorePriority) $selectSql .= " ORDER BY `priority` DESC, `time` ASC";
        else $selectSql .= " ORDER BY `time` ASC";
        if($limit) $selectSql .= ' LIMIT 0, '.$limit;

        $command = $connection->createCommand($selectSql);
        $data = $command->query();

        $toBeDeleted = array();
        $had_error = false;
        $error_string = '';
        foreach($data as $email){
            if ($this->send($email['from'],json_decode($email['to'],true),$email['subject'],$email['body'],json_decode($email['additional_headers'],true))) {
                $toBeDeleted[] = $email['id'];
            } else {
                $had_error = true;
                $error_string .= $this->smtp_object->error.PHP_EOL;
            }
        }

        $this->last_error = trim($error_string);

        if(count($toBeDeleted))
        {
            $deleteSql = 'DELETE FROM `'.$this->queue_table_name.'` WHERE `id` IN ('.implode(', ', $toBeDeleted).')';
            $command = $connection->createCommand($deleteSql);
            $command->execute();
        }

        return $had_error;
    }

    /**
     * Returns total count of emails in the queue
     * 
     * @return int Total count of items in the queue
     *
     * @throws Exception when no database is defined
    */
    public function queueSize()
    {
        $connection = $this->getConnection();
        if(!$connection) throw new Exception('Database connection not found');

        $countSql = 'SELECT COUNT(*) FROM `'.$this->queue_table_name.'`';

        $command = $connection->createCommand($countSql);
        $count = $command->queryScalar();

        return $count;
    }

    /**
     * Set the CDbConnection to be used for the queue
     *
     * @param CDbConnection The connection to use for the queue
     *
     * @throws Exception if $connection is not a CDbConnection
    */
    public function setConnection($connection)
    {
        if(!is_a($connection,'CDbConnection')) throw Exception('$connection is not of type CDbConnection');
        $this->connection = $connection;
    }

    public function lastError(){
        return $this->smtp_object->error;
    }

    private function needToCreateTable()
    {
        if(!$this->enable_queue && !$this->autocreate_db_table) return false;
        $connection = $this->getConnection();
        if(!$connection) throw new Exception('Database connection not found');

        $checkSql = 'show tables like "'.$this->queue_table_name.'"';
        $command = $connection->createCommand($checkSql);
        return $command->queryScalar()!==$this->queue_table_name;
    }

    private function autoCreateTable()
    {
        $connection = $this->getConnection();
        if(!$connection) throw new Exception('Database connection not found');

        $createSQL = 'CREATE TABLE IF NOT EXISTS `'.$this->queue_table_name.'` (
              `id` int(15) NOT NULL AUTO_INCREMENT,
              `priority` int(1) NOT NULL DEFAULT \'5\',
              `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              `from` varchar(500) NOT NULL,
              `to` varchar(500) NOT NULL,
              `subject` varchar(500) NOT NULL,
              `body` longtext NOT NULL,
              `additional_headers` longtext,
              PRIMARY KEY (`id`),
              KEY `priority` (`priority`),
              KEY `time` (`time`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
        $command = $connection->createCommand($createSQL);
        $command->execute();
    }

    private function getConnection()
    {
        if(!$this->connection) $connection = Yii::app()->db;
        else $connection = $this->connection;
        if(!$connection) throw new Exception('Database connection not found');
        return $connection;
    }
}

?>
