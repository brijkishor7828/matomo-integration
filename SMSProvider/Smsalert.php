<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Smsalert\SMSProvider;

use Piwik\Http;
use Piwik\Plugins\MobileMessaging\APIException;
use Piwik\Plugins\MobileMessaging\SMSProvider;
use Psr\Log\LoggerInterface;

/**
 * Add Smsalert to SMS providers
 */
class Smsalert extends \Piwik\Plugins\MobileMessaging\SMSProvider
{

    const API_URL = 'https://www.smsalert.co.in/api/push.json';
	const API_URL_CREDIT = 'https://www.smsalert.co.in/api/creditstatus.json';
    const SOCKET_TIMEOUT = 15;

    /**
     * @var LoggerInterface
     */
    private $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getId()
    {
        return 'Smsalert';
    }

    public function getDescription()
    {
        return 'You can use <a target="_blank" rel="noreferrer noopener" href="https://www.smsalert.co.in"><img src="plugins/SmsalertMessaging/images/smsalert.png"/></a> to send SMS Reports from Piwik.<br/>
			<ul>
			<li> First, <a target="_blank" rel="noreferrer noopener" href="https://www.smsalert.co.in/#register/">get an Account at Smsalert</a> (Signup is free!)
			</li><li> Enter your Smsalert credentials on this page. </li>
			</ul>
			<br/>About Smsalert: <ul>
			<li>Smsalert provides fast and reliable high quality worldwide SMS delivery, over 900 networks in every corner of the globe.
			</li><li>Cost per SMS message depends on the target country and starts from 0.30INR.
			</li><li>Most countries and networks are supported but we suggest you check the latest position on their supported networks list <a href="https://www.smsalert.co.in/#pricing" target="_blank" rel="noreferrer noopener">here</a>.
			</li><li>For sending an SMS, you need so-called Smsalert credits, which are purchased in advance. The Smsalert credits do not expire. 
			</li>
			</ul>
			';
    }

    public function getCredentialFields()
    {
    	return array(
    			array(
    					'type'  => 'text',
    					'name'  => 'username',
    					'title' => 'Username'
    			),
    			array(
    					'type'  => 'text',
    					'name'  => 'password',
    					'title' => 'General_Password'
    			),
				array(
    					'type'  => 'text',
    					'name'  => 'senderid',
    					'title' => 'Senderid'
    			),
    	);
    }

    public function verifyCredential($credential)
    {
        $this->getCreditLeft($credential);

        return true;

    }

    public function sendSMS($credential, $smsText, $phoneNumber, $from)
    {
        $parameters = array(
            'user' => $credential['username'],
            'pwd' => $credential['password'],
            'sender' => $credential['senderid'],
            'mobileno' => $phoneNumber,
            'text' => $smsText,
        );
        $url = self::API_URL . '?' . http_build_query($parameters, '', '&');
        $timeout = self::SOCKET_TIMEOUT;
        $result = Http::sendHttpRequestBy(
            Http::getTransportMethod(),
            $url,
            $timeout,
            $getExtendedInfo = true
        );
		$result = @json_decode($result, true);

        if (!$result || $result['status'] != 'success') {
            throw new APIException(
                'Smsalert API returned the following error message : ' . $result['description']
            );
        }

        return $result;
    }

    public function getCreditLeft($credential)
    {
        $parameters = array(
            'user' => $credential['username'],
            'pwd' => $credential['password'],
                   );
        $url = self::API_URL_CREDIT . '?' . http_build_query($parameters, '', '&');
        $timeout = self::SOCKET_TIMEOUT;
        $result = Http::sendHttpRequestBy(
            Http::getTransportMethod(),
            $url,
            $timeout,
            $getExtendedInfo = true
        );
		$result = @json_decode($result, true);

        if (!$result || $result['status'] != 'success') {
            throw new APIException(
                'Smsalert API returned the following error message : ' . $result['description']
            );
        }
		$data =''; 
        foreach($result['description']['routes'] as $route)
		{
			$data.=$route['display_name'].' : '.$route['credits'].' ';
           
		}
		return $data;
    }
}
