<?php
declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

//Establecemos los settings
$container->set('dbSettings', function () {
    return [
        "dbHost" => "localhost",
        "dbUser" => "root",
        "dbPwd" => "",
        "dbName" => "todoApp"
    ];
});

$container->set('db', function (ContainerInterface $c) {
    $config = $c->get('dbSettings');
    $db = new PDO('mysql:host=' . $config['dbHost'] . ';dbname=' . $config['dbName'] . '', $config['dbUser'], $config['dbPwd']);
    $db->exec("SET NAMES UTF-8");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
});

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->post('/registrarUsuario', function ($request, $response) {
        $db = $this->get('db');
        $postResponse = $request->getParsedBody();

        $token = md5(uniqid(strval(rand()), true));
        $nombre = filter_var($postResponse['nombre'], FILTER_SANITIZE_STRING);
        $usuario = filter_var($postResponse['usuario'], FILTER_SANITIZE_STRING);
        $email = filter_var($postResponse['email'], FILTER_SANITIZE_EMAIL);
        $clave = filter_var($postResponse['clave'], FILTER_SANITIZE_STRING);
        $encryptedClave = crypt($clave, 'Sc2dgcK39Zspr7nUKdnV28xfwFUaP2Sx4vnVxHzhyRwzzujVyx');
            
        $arrayResponse = [
            "status" => '',
            "response" => '',
            "checkSendEmail" => '',
            "data" => array()
        ];

        try {
            
            //Comprobar si el usuario existe
            $queryExisteUsuario = "SELECT usuario FROM usuarios WHERE usuario = '$usuario'";
            $resultadoExisteUser = $db->prepare($queryExisteUsuario);
            $resultadoExisteUser->execute();

            //Comprobar si el correo existe
            $queryExisteEmail = "SELECT email FROM usuarios WHERE email = '$email'";
            $resultadoExisteEmail = $db->prepare($queryExisteEmail);
            $resultadoExisteEmail->execute();
            
            if($resultadoExisteUser->rowCount() === 1 && $resultadoExisteEmail->rowCount() === 1){
                $arrayResponse['response'] = 'Oh vaya! Parece que el email y usuario ya existen...';
                $arrayResponse['status'] = 0;
            }else if($resultadoExisteEmail->rowCount() === 1){
                $arrayResponse['response'] = 'Ups! El email ya existe...';
                $arrayResponse['status'] = 0;
            }else if ($resultadoExisteUser->rowCount() === 1){
                $arrayResponse['response'] = 'Ups! El usuario ya existe...';
                $arrayResponse['status'] = 0;
            }
            else{
                $array = [
                    "token" => $token,
                    "nombre" => $nombre,
                    "usuario" => $usuario,
                    "email" => $email,
                    "clave" => $encryptedClave
                ];
        
                $query = "INSERT INTO usuarios (token, nombre, usuario, email, clave) VALUES (?, ?, ?, ?, ?)";
                $resultado = $db->prepare($query);
                $resultado->bindparam(1, $array['token']);
                $resultado->bindparam(2, $array['nombre']);
                $resultado->bindparam(3, $array['usuario']);
                $resultado->bindparam(4, $array['email']);
                $resultado->bindparam(5, $array['clave']);
                $resultado->execute();

                if($resultado->rowCount() === 1 ){
                    $headers  = "From: HUBBET ADMIN <joaquinromeroesteve@gmail.com>\n";
                    $headers .= "Reply-To: joaquinromeroesteve@gmail.com\n";
                    $headers .= "Return-Path: joaquinromeroesteve@gmail.com\n";
                    $headers .= "MIME-Version: 1.0\n";
                    $headers .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
                    $headers .= "X-Priority: 1 (Highest)\n";
                    $headers .= "X-MSMail-Priority: High\n";
                    $headers .= "Importance: High\n";
                    $body = '<h1>Haz click en el botón para activar tu cuenta</h1>'; /* file_get_contents( __DIR__ . "/template.html"); */
                    $body .= "<a href='http://localhost:4200/activateAccount/" . $token . "'>Activar Cuenta</a>";
                    $arrayResponse['checkSendEmail'] = mail($email,"Bienvenido a Hubbet", $body, $headers);
                    $arrayResponse['status'] = 1;
                    $arrayResponse['response'] = 'Usuario registrado satisfactoriamente!';
                    $arrayResponse['data'] = $array;
                }else{
                    $arrayResponse['status'] = 0;
                    $arrayResponse['response'] = 'Vaya! Ha ocurrido un problema al crear tu usuario!';
                }
            }

        } catch (Exception $e) {
            $arrayResponse['status'] = 'error';
            $arrayResponse['response'] = $e->getMessage();
        }
        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    $app->post('/activateAccount', function ($request, $response) {

        $db = $this->get('db');
        $postResponse = $request->getParsedBody();
        $token = filter_var($postResponse['token'], FILTER_SANITIZE_STRING);

        $arrayResponse = [
            "status" => '',
            "response" => '',
            "checkSendEmail" => '',
            "data" => array()
        ];

        try{

            $queryToken = "SELECT usuario, token, nombre, email, verificado FROM usuarios WHERE token = ? && verificado = 0";
            $resultadoToken = $db->prepare($queryToken);
            $resultadoToken->bindparam(1, $token);
            $resultadoToken->execute();

            if($resultadoToken->rowCount() === 1){
                $queryVerificar = "UPDATE usuarios SET verificado = 1 WHERE token = ?";
                $resultadoVerificar = $db->prepare($queryVerificar);
                $resultadoVerificar->bindparam(1, $token);
                $resultadoVerificar->execute();
                $arrayResponse['status'] = 1;
                $arrayResponse['data'] = $resultadoToken->fetch(PDO::FETCH_ASSOC);
                
            }else{
                $arrayResponse['status'] = 0;
                $arrayResponse['response'] = 'El usuario ya está verificado!';
            }

            }catch (Exception $e) {
                $arrayResponse['status'] = 0;
                $arrayResponse['response'] = $e->getMessage();
            }
        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

//Validar el login del usuario
$app->post('/validarLogin', function ($request, $response) {

    $db = $this->get('db');
    $postResponse = $request->getParsedBody();
    $email = filter_var($postResponse['email'], FILTER_SANITIZE_STRING);
    $clave = filter_var($postResponse['clave'], FILTER_SANITIZE_STRING);
    $encryptedClave = crypt($clave, 'Sc2dgcK39Zspr7nUKdnV28xfwFUaP2Sx4vnVxHzhyRwzzujVyx');

    $arrayResponse = [
        "status" => '',
        "response" => '',
        "checkSendEmail" => '',
        "data" => array()
    ];

    try{
        //Comprobamos que exista y esté verificado
        $queryExisteUsuario = "SELECT usuario, email  FROM usuarios WHERE (usuario = '$email' || email = '$email') && verificado = 1";
        $resultadoExisteUser = $db->prepare($queryExisteUsuario);
        $resultadoExisteUser->execute();

        if($resultadoExisteUser->rowCount() === 1){

            //Verrificamos login
            $queryLogin = "SELECT usuario, token, nombre, email, verificado FROM usuarios WHERE ((email = ? || usuario = ?) && clave = ?)  && verificado = 1";
            $resultadoLogin = $db->prepare($queryLogin);
            $resultadoLogin->bindparam(1, $email);
            $resultadoLogin->bindparam(2, $email);
            $resultadoLogin->bindparam(3, $encryptedClave);
            $resultadoLogin->execute();

        if($resultadoLogin->rowCount() === 1){

            //Si el login es correcto devolvemos los datos
            $arrayResponse['status'] = 1;
            $arrayResponse['data'] = $resultadoLogin->fetch(PDO::FETCH_ASSOC);
            
        }else{
            $arrayResponse['status'] = 0;
            $arrayResponse['response'] = 'Ha ocurrido un error al iniciar sesión!';
        }

        }else{
            $arrayResponse['status'] = 0;
            $arrayResponse['response'] = 'La cuenta no existe o no está verificada!';
        }

        }catch (Exception $e) {
            $arrayResponse['status'] = 0;
            $arrayResponse['response'] = $e->getMessage();
        }
    $response->getBody()->write(json_encode($arrayResponse));
    return $response;
});

//Verificar que el usuario es el que dice ser en todo momento
$app->post('/isLogged', function ($request, $response) {

    $db = $this->get('db');
    $postResponse = $request->getParsedBody();
    $postData = json_decode($postResponse['userData']);

    //Creamos las variables
   
    $usuario = $postData->usuario;
    $token = $postData->token;
    $nombre = $postData->nombre;
    $email = $postData->email;
    $verificado = $postData->verificado;

    $arrayResponse = [
        "status" => '',
        "response" =>  ''
    ];

    try{
        //Comprobamos que los datos que nos pasan coinciden con los de la BD
        $queryValidaUser = "SELECT * FROM usuarios WHERE usuario = ? AND token = ? AND nombre = ? AND email = ? AND verificado = ?";
        $resultadoValidaUser = $db->prepare($queryValidaUser);
        $resultadoValidaUser->bindParam(1, $usuario);
        $resultadoValidaUser->bindParam(2, $token);
        $resultadoValidaUser->bindParam(3, $nombre);
        $resultadoValidaUser->bindParam(4, $email);
        $resultadoValidaUser->bindParam(5, $verificado);
        $resultadoValidaUser->execute();

        if($resultadoValidaUser->rowCount() === 1){
            $arrayResponse['status'] = 1;
            $arrayResponse['response'] = true;
        }else{
            $arrayResponse['status'] = 0;
            $arrayResponse['response'] = false;
        }

    }catch (Exception $e) {
        $arrayResponse['status'] = 0;
        $arrayResponse['response'] = false;
    }
    $response->getBody()->write(json_encode($arrayResponse));
    return $response;
});

};
