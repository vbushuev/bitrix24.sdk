<?php
namespace vsb\bitrix24;
use vsb\Exception as vExceptions;
class Service{
	protected $options = [
		/**
		 * client_id приложения
		 */
		'CLIENT_ID' => 'local.566973a3ec7410.58002193',
		/**
		 * client_secret приложения
		 */
		'CLIENT_SECRET' => '802232f8073927f1b07b812c4b9a4b3f',
		/**
		 * относительный путь приложения на сервере
		 */
		'PATH' => '/bitrix24/',
		/**
		 * полный адрес к приложения
		 */
		'REDIRECT_URI' => 'http://bitrix24.portal.bs2/bitrix24',
		/**
		 * scope приложения
		 */
		'SCOPE' => 'crm,log,user',

		/**
		 * протокол, по которому работаем. должен быть https
		 */
		'PROTOCOL' => "https",

		'EVENT_HANDLER' => 'http://bitrix24.portal.bs2/bitrix24/event',

		'domain' => 'oookbrenessans.bitrix24.ru',

		'authenticated' => false,

		''
	];
	private static $service;
	/**
	 * Getting singleton
	 */
	/**
	 * Close constrruct method for signletone
	 */
	private function __construct(){
		// clear auth session
		if(isset($_REQUEST["clear"]) || $_SERVER["REQUEST_METHOD"] == "POST"){
			unset($_SESSION["query_data"]);
		}
		if($_SERVER["REQUEST_METHOD"] == "POST"){//  get code
			if(!empty($_POST["portal"])){
				$this->options['domain'] = $_POST["portal"];
				$params = array(
					"response_type" => "code",
					"client_id" =>$this->options['CLIENT_ID'],
					"redirect_uri" =>$this->options['REDIRECT_URI'],
				);
				$path = "/oauth/authorize/";

				redirect(PROTOCOL."://".$this->options['domain'].$path."?".http_build_$this->query($params));
			}
		//  /get code
		}

		if(isset($_REQUEST["code"])){
		//  get access_token
			$code = $_REQUEST["code"];
			$this->options['domain'] = $_REQUEST["domain"];
			$member_id = $_REQUEST["member_id"];

			$params = array(
				"grant_type" => "authorization_code",
				"client_id" =>$this->options['CLIENT_ID'],
				"client_secret" =>$this->options['CLIENT_SECRET'],
				"redirect_uri" =>$this->options['REDIRECT_URI'],
				"scope" =>$this->options['SCOPE'],
				"code" => $code,
			);
			$path = "/oauth/token/";

			$query_data = $this->query("GET",$this->options['PROTOCOL']."://".$this->options['domain'].$path, $params);

			if(isset($query_data["access_token"]))
			{
				$_SESSION["query_data"] = $query_data;
				$_SESSION["query_data"]["ts"] = time();

				self::redirect($this->options['PATH']);
				die();
			}
			else
			{
				throw new vException("Произошла ошибка авторизации! ".join($query_data, ', '));
			}
		//  /get access_token
		}
		elseif(isset($_REQUEST["refresh"])){
		//  refresh auth
			$params = array(
				"grant_type" => "refresh_token",
				"client_id" =>$this->options['CLIENT_ID'],
				"client_secret" =>$this->options['CLIENT_SECRET'],
				"redirect_uri" =>$this->options['REDIRECT_URI'],
				"scope" =>$this->options['SCOPE'],
				"refresh_token" => $_SESSION["query_data"]["refresh_token"],
			);

			$path = "/oauth/token/";

			$query_data = $this->query("GET",$this->options['PROTOCOL']."://".$_SESSION["query_data"]["domain"].$path, $params);

			if(isset($query_data["access_token"]))
			{
				$_SESSION["query_data"] = $query_data;
				$_SESSION["query_data"]["ts"] = time();

				self::redirect($this->options['PATH']);
				die();
			}
			else
			{
				$error = "Произошла ошибка авторизации! ".print_r($query_data);
			}
		//  /refresh auth
		}

	}
	public static function GetService($o=[]){
		if (null !== self::$service) {
            return self::$service;
        }
		self::$service = new Bitrix24;
		return self::$service;
	}
	/**
	 * Производит перенаправление пользователя на заданный адрес
	 *
	 * @param string $url адрес
	 */
	public static function redirect($url){
		Header("HTTP 302 Found");
		Header("Location: ".$url);
		die();
	}
	/**
	 * Совершает запрос с заданными данными по заданному адресу. В ответ ожидается JSON
	 *
	 * @param string $method GET|POST
	 * @param string $url адрес
	 * @param array|null $data POST-данные
	 *
	 * @return array
	 */
	public function $this->query($method, $url, $data = null){
		$query_data = "";
		$curlOptions = array(
			CURLOPT_RETURNTRANSFER => true
		);

		if($method == "POST")
		{
			$curlOptions[CURLOPT_POST] = true;
			$curlOptions[CURLOPT_POSTFIELDS] = http_build_$this->query($data);
		}
		elseif(!empty($data))
		{
			$url .= strpos($url, "?") > 0 ? "&" : "?";
			$url .= http_build_$this->query($data);
		}

		$curl = curl_init($url);
		curl_setopt_array($curl, $curlOptions);
		$result = curl_exec($curl);

		return json_decode($result, 1);
	}

	/**
	 * Вызов метода REST.
	 *
	 * @param string $this->options['domain'] портал
	 * @param string $method вызываемый метод
	 * @param array $params параметры вызова метода
	 *
	 * @return array
	 */
	public function $this->call($method, $params)
	{
		return $this->query("POST",$this->options['PROTOCOL']."://".$this->options['domain']."/rest/".$method, $params);
	}
}

$error = "";


require_once(dirname(__FILE__)."/include/footer.php");
?>
