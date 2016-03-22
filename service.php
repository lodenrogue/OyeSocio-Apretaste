<?php

class OyeSocio extends Service
{
	/**
	 * Function executed when the service is called
	 * 
	 * @param Request
	 * @return Response
	 * */
	public function _main(Request $request){
        $output = file_get_contents("http://192.168.1.116:8080/oyesocio/service/signin?email={$request->email}");
        //$output = file_get_contents("http://192.168.1.116:8080/oyesocio/service/signin?email=test@test.com");

		$response = new Response();
		
		if($output == "SIGNUP"){
			$response->setResponseSubject("Necesitamos saber su nombre");
			$response->createFromTemplate("signup.tpl", []);
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
		curl_setopt($ch, CURLOPT_URL,"http://192.168.1.116:8080/oyesocio/service/register");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "firstName={$firstName}&lastName={$lastName}&email={$request->email}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_exec ($ch);
		curl_close ($ch);

		return $this->_perfil($request);
	}

	public function _perfil(Request $request, $userId){
		$output = null;

		// Check if a user id is passed in the query
		if(is_numeric($request->query)){
			$userId = $request->query;
		}

		// if we have a user id then return the target user's profile
		// Otherwise return the querying user's profile
		if($userId != null){
			$output = file_get_contents("http://192.168.1.116:8080/oyesocio/service/profile/{$userId}?viewerEmail={$request->email}");
		}
		else {
			$output = file_get_contents("http://192.168.1.116:8080/oyesocio/service/profile?viewerEmail={$request->email}&targetEmail={$request->email}");
		}

		if($output == "ERROR: NO USER FOUND WITH EMAIL {$request->email}") {
			return $this->_main($request);
		}
		else if($output == "ERROR: NO USER FOUND WITH ID {$userId}"){
			$response = new Response();
			$response->setResponseSubject("El perfil no existe");
			$response->createFromText("Ese perfil no existe en el systema");
			return $response;
		}
		else {
			$response = new Response();
			$response->setResponseSubject("Perfil");
			$response->createFromText($output);
			return $response;
		}
	}

	public function _publicar(Request $request){
		$message = $request->query;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://192.168.1.116:8080/oyesocio/service/publish");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "userEmail={$request->email}&content={$message}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$serverOutput = curl_exec ($ch);
		curl_close ($ch);

		if($serverOutput == "ERROR: NO USER FOUND"){
			return $this->_main($request);
		}
		else {
			return $this->_perfil($request);
		}
	}

	public function _gustar_publicacion(Request $request){
		$postId = $request->query;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://192.168.1.116:8080/oyesocio/service/like/post/{$postId}");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "userEmail={$request->email}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$serverOutput = curl_exec ($ch);
		curl_close ($ch);

		if($serverOutput == "ERROR: USER ALREADY LIKED THIS"){
			$response = new Response();
			$response->setResponseSubject("Ya le habia gustado");
			$response->createFromText("Ya le habia gustado esta publicacion. Solo puede gustarle una vez.");
			return $response;

		}
		else if($serverOutput == "ERROR: POST NOT FOUND"){
			$response = new Response();
			$response->setResponseSubject("La publicacion no existe");
			$response->createFromText("La publicacion no fue gustada porque no existe en el systema");
			return $response;
		}
		else if($serverOutput == "ERROR: NO USER FOUND"){
			return $this->_main($request);
		}
		else {
			$response = new Response();
			$response->setResponseSubject("Mucho Gusto");
			$response->createFromText("Usted ha gustado esta publicacion");
			return $response;
		}
	}

	public function _eliminar_publicacion(Request $request){
		$postId = $request->query;

		$output = null;
		if(is_numeric($postId)){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,"http://192.168.1.116:8080/oyesocio/service/delete-post/{$postId}?userEmail={$request->email}");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec ($ch);
			curl_close ($ch);
		}

		if($output == "DONE"){
			$response = new Response();
			$response->setResponseSubject("Publicacion Eliminada");
			$response->createFromText("La publicacion ha sido borrada del systema");
			return $response;
		}
		else if($output == "ERROR: USER IS NOT OWNER"){
			$response = new Response();
			$response->setResponseSubject("No tiene permiso");
			$response->createFromText("La publicacion no fue borrada porque usted no tiene permiso para hacer eso");
			return $response;
		}
		else if($output == "ERROR: POST NOT FOUND"){
			$response = new Response();
			$response->setResponseSubject("La publicacion no existe");
			$response->createFromText("La publicacion no fue borrada porque no existe en el systema");
			return $response;
		}
		else {
			return $this->_main($request);
		}
	}

	public function _responder(Request $request){
		$reply = explode(" ", $request->query, 2);
		$postId = $reply[0];
		$content = $reply[1];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://192.168.1.116:8080/oyesocio/service/reply");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "postId={$postId}&userEmail={$request->email}&content={$content}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$serverOutput = curl_exec ($ch);
		curl_close ($ch);

		if($serverOutput == "ERROR: NO USER FOUND"){
			return $this->_main($request);
		}
		else if($serOutput == "ERROR: NO POST FOUND"){
			$response = new Response();
			$response->setResponseSubject("La publicacion no existe");
			$response->createFromText("La repuesta no fue submitida porque la publicacion no existe en el systema");
			return $response;
		}
		else {
			$outputJson = json_decode($serverOutput);
			return $this->_perfil($request, $outputJson->userId);
		}
	}

	public function _gustar_repuesta(Request $request){
		$commentId = $request->query;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://192.168.1.116:8080/oyesocio/service/like/comment/{$commentId}");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "userEmail={$request->email}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$serverOutput = curl_exec ($ch);
		curl_close ($ch);

		if($serverOutput == "ERROR: USER ALREADY LIKED THIS"){
			$response = new Response();
			$response->setResponseSubject("Ya le habia gustado");
			$response->createFromText("Ya le habia gustado esta repuesta. Solo puede gustarle una vez.");
			return $response;

		}
		else if($serverOutput == "ERROR: COMMENT NOT FOUND"){
			$response = new Response();
			$response->setResponseSubject("La repuesta no existe");
			$response->createFromText("La repuesta no fue gustada porque no existe en el systema");
			return $response;
		}
		else if($serverOutput == "ERROR: NO USER FOUND"){
			return $this->_main($request);
		}
		else {
			$response = new Response();
			$response->setResponseSubject("Mucho Gusto");
			$response->createFromText("Usted ha gustado esta repuesta");
			return $response;
		}
	}

	public function _eliminar_repuesta(Request $request){
		$commentId = $request->query;

		$output = null;
		if(is_numeric($commentId)){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,"http://192.168.1.116:8080/oyesocio/service/delete-comment/{$commentId}?userEmail={$request->email}");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec ($ch);
			curl_close ($ch);
		}

		if($output == "DONE"){
			$response = new Response();
			$response->setResponseSubject("Repuesta Eliminada");
			$response->createFromText("La repuesta ha sido borrada del systema");
			return $response;
		}
		else if($output == "ERROR: USER IS NOT OWNER"){
			$response = new Response();
			$response->setResponseSubject("No tiene permiso");
			$response->createFromText("La repuesta no fue borrada porque usted no tiene permiso para hacer eso");
			return $response;
		}
		else if($output == "ERROR: COMMENT NOT FOUND"){
			$response = new Response();
			$response->setResponseSubject("La repuesta no existe");
			$response->createFromText("La repuesta no fue borrada porque no existe en el systema");
			return $response;
		}
		else {
			return $this->_main($request);
		}
	}
}
