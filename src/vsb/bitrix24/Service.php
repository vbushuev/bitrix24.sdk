<?php
namespace vsb\bitrix24;
use vsb\Exception as vExceptions;
class Service{
	protected $options = [
		/**
		 * client_id приложения
		 */
		'CLIENT_ID' => 'app.00000000000000.00000000',
		/**
		 * client_secret приложения
		 */
		'CLIENT_SECRET' => '00000000000000000000000000000000',
		/**
		 * относительный путь приложения на сервере
		 */
		'PATH' => '/your_path/',
		/**
		 * полный адрес к приложения
		 */
		'REDIRECT_URI' => 'http://your.host/your_path/',
		/**
		 * scope приложения
		 */
		'SCOPE' => 'crm,log,user',

		/**
		 * протокол, по которому работаем. должен быть https
		 */
		'PROTOCOL' => "https",

		'EVENT_HANDLER' => 'http://your.host/your_path/'
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

		if(!isset($_SESSION["query_data"])){
		// *
			if($error)
			{
				echo '<b>'.$error.'</b>';
			}
		?>
		<?

		// *

		}
		else {
		// *

			if(time() > $_SESSION["query_data"]["ts"] + $_SESSION["query_data"]["expires_in"])
			{
				echo "<b>Авторизационные данные истекли</b>";
			}
			else
			{
				echo "Авторизационные данные истекут через ".($_SESSION["query_data"]["ts"] + $_SESSION["query_data"]["expires_in"] - time())." секунд";
			}
		?>

		<ul>
			<li><a href="<?=PATH?>?test=user.current">Информация о пользователе</a>
			<li><a href="<?=PATH?>?test=user.update">Загрузить новую аватарку пользователя</a>
			<li><a href="<?=PATH?>?test=log.blogpost.add">Опубликовать запись в Живой Ленте</a>
			<li><a href="<?=PATH?>?test=event.bind">Установить обработчик события</a>
		</ul>

		<a href="<?=PATH?>?refresh=1">Обновить данные авторизации</a><br />
		<a href="<?=PATH?>?clear=1">Очистить данные авторизации</a><br />

		<?
			$test = isset($_REQUEST["test"]) ? $_REQUEST["test"] : "";
			switch($test)
			{
				case 'user.current': // test: user info

					$data = $this->call($_SESSION["query_data"]["domain"], "user.current", array(
						"auth" => $_SESSION["query_data"]["access_token"])
					);

				break;

				case 'user.update': // test batch&files

					$fileContent = file_get_contents(dirname(__FILE__)."/images/MM35_PG189a.jpg");

					$batch = array(
						'user' => 'user.current',
						'user_update' => 'user.update?'
							.http_build_$this->query(array(
								'ID' => '$result[user][ID]',
								'PERSONAL_PHOTO' => array(
									'avatar.jpg',
									base64_encode($fileContent)
								)
							))
					);

					$data = $this->call($_SESSION["query_data"]["domain"], "batch", array(
						"auth" => $_SESSION["query_data"]["access_token"],
						"cmd" => $batch,
					));

				break;

				case 'event.bind': // bind event handler

					$data = $this->call($_SESSION["query_data"]["domain"], "event.bind", array(
						"auth" => $_SESSION["query_data"]["access_token"],
						"EVENT" => "ONCRMLEADADD",
						"HANDLER" =>$this->options['EVENT_HANDLER'],
					));

				break;

				case 'log.blogpost.add': // add livefeed entry

					$fileContent = file_get_contents(dirname(__FILE__)."/images/MM35_PG189a.jpg");

					$data = $this->call($_SESSION["query_data"]["domain"], "log.blogpost.add", array(
		 				"auth" => $_SESSION["query_data"]["access_token"],
						"POST_TITLE" => "Hello world!",
						"POST_MESSAGE" => "Goodbye, cruel world :-(",
						"FILES" => array(
							array(
								'minotaur.jpg',
								base64_encode($fileContent)
							)
						),

		 			));

		 		break;


				default:

					$data = $_SESSION["query_data"];

				break;
			}

			echo '<pre>'; var_export($data); echo '</pre>';

			// *
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
		return $this->$this->query("POST",$this->options['PROTOCOL']."://".$this->options['domain']."/rest/".$method, $params);
	}
}

$error = "";


require_once(dirname(__FILE__)."/include/footer.php");
?>
