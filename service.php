<?php

// use Goutte\Client; // UNCOMMENT TO USE THE CRAWLER OR DELETE

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
        //$output = file_get_contents("http://192.168.1.116:8080/oyesocio/api/signin?email={$request->email}");
        $output = file_get_contents("http://192.168.1.116:8080/oyesocio/api/signin?email=test@test.com");
        $outputJson = json_decode($output);
        $outputData = $outputJson->data;

		// create a json object to send to the template
		$responseContent = array(
			"output" => $outputData
		);

		// create the response
		$response = new Response();
		if($outputJson->type == "SIGNUP"){
			$response->setResponseSubject("Necesitamos saber su nombre");
			$response->createFromTemplate("signup.tpl", $responseContent);
		}
		else {
			$response->setResponseSubject("Su perfil");
			$response->createFromText($outputData);
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
		$output = curl_exec ($ch);
		curl_close ($ch);

		// create the response
		$response = new Response();
		$response->setResponseSubject("Su perfil");
		$response->createFromText($outputData);
		return $response;
	}
}
