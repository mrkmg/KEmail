KEmail
======

Yii Application Component to interact with smtp servers without relying on PHP's mail or PEAR's Mail. Simply a wrapper for [http://www.phpclasses.org/package/14-PHP-Sends-e-mail-messages-via-SMTP-protocol.html](http://www.phpclasses.org/package/14-PHP-Sends-e-mail-messages-via-SMTP-protocol.html)

## To Install ##
1. Copy KEmail into approot/protected/extenstions

2. Under "import", add:

	application.extensions.KEmail.KEmail

3. Under "components", add:

	'email'=>array(  
		'class'=>'KEmail',  
		'host_name'=>'smtp_server',  //Hostname or IP of smtp server  
	),

## Basic Usage ##

1. Single recipient  
	Yii::app()->email->send('from@email.address','to@email.address','Subject','Body');

2. Multiple recipients    
	$to = array(  
	'user1@email.address',  
	'user2@emai.address',  
	);  
	Yii::app()->email->send('from@email.address',$to,'Subject','Body');

## Configuration ##

The following is an outline of all the avaible options, and their default options

    host_name="localhost"  		Host name of smtp server
    host_port=25				Port of smtp server
    ssl=false					Force SSL
    http_proxy_host_name=''		Set to enable using an http proxy to access smtp server
    http_proxy_host_port=3128	Port of http proxy
    socks_host_name=''			Set to enable using a socks proxy
    socks_host_port=1080		Port of socks proxy
    socks_version='5'			Version of socks proxy
    start_tls=false				Force `start_tls`
    localhost='localhost'		Given hostname of client
    direct_delivery=false		Skip smtp server and delevier directly to recipients smtp
    timeout=10					Time in seconds to timeout for all smtp connections
    data_timeout=0				Time in seconds to timeout for data transfer to SMTP server, if 0 uses timeout
    debug=false					Output Debug information to browser
    html_debug=true				Format Debug information as html
    pop3_auth_host=''			Set to authenticate to a pop3 server
    user=""						Username for smtp authentication
    realm=""					Realm for smtp authentication
    password=""					Password for smtp authentication
    workstation=""				Workstation for smtp authentication
    authentication_mechanism=""	Force a specific smtp authentication mechanism ('LOGIN','PLAIN','CRAM-MD5','NTLM')

## Advanced Usage ##

You can also customize headers

For example, to send an HTML email, do the following

	$headers = array(
		'MIME-Version: 1.0',
		'Content-type: text/html; charset=iso-8859-1'
	);
	Yii::app()->email->send('from@email.address','to@email.address','Subject','<html><head><title>Subject</title></head><body>BODY</body></html>',$headers);


## TODO/Upcoming Features ##

* Add mail queue
    * Allow for use of a variety of storage methods, flatfile, sqlite, mysql.
    * Implement yiic access to process mail queue
    * Implement function call to process mail queue
* Error detection and reporting
