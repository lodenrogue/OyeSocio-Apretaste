<?php

class OyeSocio extends Service
{
	/**
	 * Function executed when the service is called
	 * 
	 * @param Request
	 * @return Response
	 * */
	public function _main(Request $request)
	{
        $output = file_get_contents("http://192.168.1.116:8080/oyesocio/api/signin?email={$request->email}");
        //$output = file_get_contents("http://192.168.1.116:8080/oyesocio/api/signin?email=test@test.com");
        $outputJson = json_decode($output);
        $outputData = $outputJson->data;

		$response = new Response();
		if($outputJson->type == "SIGNUP"){
			$responseContent = array(
				"output" => $outputData
			);
			$response->setResponseSubject("Necesitamos saber su nombre");
			$response->createFromTemplate("signup.tpl", $responseContent);
		}
		else {
			$response = $this->_perfil($request);
		}
		return $response;
	}

	public function _registrarse(Request $request){
		$fullname = explode(" ", $request->query);
		$firstName = $fullname[0];
		$lastName = $fullname[1];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://192.168.1.116:8080/oyesocio/api/register");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "firstName={$firstName}&lastName={$lastName}&email={$request->email}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_exec ($ch);
		curl_close ($ch);

		return $this->_perfil($request);
	}

	public function _perfil(Request $request){
		$output = file_get_contents("http://192.168.1.116:8080/oyesocio/api/profile?email={$request->email}");
		print_r($output);

		$response = new Response();
		$response->setResponseSubject("Su perfil");
		$response->createFromText($output);
		return $response;
	}

	public function _publicar(Request $request){

	}
}
