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
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
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
        $defaultImagen = "";

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

            if ($resultadoExisteUser->rowCount() === 1 && $resultadoExisteEmail->rowCount() === 1) {
                $arrayResponse['response'] = 'Oh vaya! Parece que el email y usuario ya existen...';
                $arrayResponse['status'] = 0;
            } else if ($resultadoExisteEmail->rowCount() === 1) {
                $arrayResponse['response'] = 'Ups! El email ya existe...';
                $arrayResponse['status'] = 0;
            } else if ($resultadoExisteUser->rowCount() === 1) {
                $arrayResponse['response'] = 'Ups! El usuario ya existe...';
                $arrayResponse['status'] = 0;
            } else {
                $array = [
                    "token" => $token,
                    "nombre" => $nombre,
                    "usuario" => $usuario,
                    "email" => $email,
                    "clave" => $encryptedClave,
                    "imagen" => $defaultImagen
                ];

                $query = "INSERT INTO usuarios (token, nombre, usuario, email, clave, imagen) VALUES (?, ?, ?, ?, ?, ?)";
                $resultado = $db->prepare($query);
                $resultado->bindparam(1, $array['token']);
                $resultado->bindparam(2, $array['nombre']);
                $resultado->bindparam(3, $array['usuario']);
                $resultado->bindparam(4, $array['email']);
                $resultado->bindparam(5, $array['clave']);
                $resultado->bindparam(6, $array['imagen']);
                $resultado->execute();

                if ($resultado->rowCount() === 1) {
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
                    $arrayResponse['checkSendEmail'] = mail($email, "Bienvenido a Hubbet", $body, $headers);
                    $arrayResponse['status'] = 1;
                    $arrayResponse['response'] = 'Usuario registrado satisfactoriamente!';
                    $arrayResponse['data'] = $array;
                } else {
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

        try {

            $queryToken = "SELECT usuario, token, nombre, email, verificado, imagen FROM usuarios WHERE token = ? && verificado = 0";
            $resultadoToken = $db->prepare($queryToken);
            $resultadoToken->bindparam(1, $token);
            $resultadoToken->execute();

            if ($resultadoToken->rowCount() === 1) {
                $queryVerificar = "UPDATE usuarios SET verificado = 1 WHERE token = ?";
                $resultadoVerificar = $db->prepare($queryVerificar);
                $resultadoVerificar->bindparam(1, $token);
                $resultadoVerificar->execute();
                $arrayResponse['status'] = 1;
                $arrayResponse['data'] = $resultadoToken->fetch(PDO::FETCH_ASSOC);
            } else {
                $arrayResponse['status'] = 0;
                $arrayResponse['response'] = 'El usuario ya está verificado!';
            }
        } catch (Exception $e) {
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

        try {
            //Comprobamos que exista y esté verificado
            $queryExisteUsuario = "SELECT usuario, email  FROM usuarios WHERE (usuario = '$email' || email = '$email') && verificado = 1";
            $resultadoExisteUser = $db->prepare($queryExisteUsuario);
            $resultadoExisteUser->execute();

            if ($resultadoExisteUser->rowCount() === 1) {

                $queryLogin =  "SELECT usuario, token, usuarios.nombre AS 'nombre', email, verificado, usuarios.imagen, ubicacion, rol, equipos.nombre AS 'equipo', usuarios.descripcion FROM usuarios 
                LEFT JOIN usuarios_equipos ON usuarios.id = usuarios_equipos.id_usuario 
                LEFT JOIN equipos ON usuarios_equipos.id_equipo = equipos.id 
                WHERE ((email = ? || usuario = ?) && clave = ?) && verificado = 1";

                //Verrificamos login
                $resultadoLogin = $db->prepare($queryLogin);
                $resultadoLogin->bindparam(1, $email);
                $resultadoLogin->bindparam(2, $email);
                $resultadoLogin->bindparam(3, $encryptedClave);
                $resultadoLogin->execute();

                if ($resultadoLogin->rowCount() === 1) {

                    //Si el login es correcto devolvemos los datos
                    $arrayResponse['status'] = 1;
                    $arrayResponse['data'] = $resultadoLogin->fetch(PDO::FETCH_ASSOC);
                } else {
                    $arrayResponse['status'] = 0;
                    $arrayResponse['response'] = 'Ha ocurrido un error al iniciar sesión!';
                }
            } else {
                $arrayResponse['status'] = 0;
                $arrayResponse['response'] = 'La cuenta no existe o no está verificada!';
            }
        } catch (Exception $e) {
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

        try {
            //Comprobamos que los datos que nos pasan coinciden con los de la BD
            $queryValidaUser = "SELECT * FROM usuarios WHERE usuario = ? AND token = ? AND nombre = ? AND email = ? AND verificado = ?";
            $resultadoValidaUser = $db->prepare($queryValidaUser);
            $resultadoValidaUser->bindParam(1, $usuario);
            $resultadoValidaUser->bindParam(2, $token);
            $resultadoValidaUser->bindParam(3, $nombre);
            $resultadoValidaUser->bindParam(4, $email);
            $resultadoValidaUser->bindParam(5, $verificado);
            $resultadoValidaUser->execute();

            if ($resultadoValidaUser->rowCount() === 1) {
                $arrayResponse['status'] = 1;
                $arrayResponse['response'] = true;
            } else {
                $arrayResponse['status'] = 0;
                $arrayResponse['response'] = false;
            }
        } catch (Exception $e) {
            $arrayResponse['status'] = 0;
            $arrayResponse['response'] = false;
        }
        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Enviar email para restablecer la contrasña
    $app->post('/resetClave', function ($request, $response) {
        $db = $this->get('db');
        $postResponse = $request->getParsedBody();

        //$token = md5(uniqid(strval(rand()), true));
        $email = filter_var($postResponse['email'], FILTER_SANITIZE_EMAIL);

        $arrayResponse = [
            "status" => '',
            "response" => '',
            "checkSendEmail" => '',
            "data" => ''
        ];

        try {

            //Comprobar si el email existe
            $queryExisteEmail = "SELECT token FROM usuarios WHERE email = '$email'";
            $resultadoExisteEmail = $db->prepare($queryExisteEmail);
            $resultadoExisteEmail->execute();

            if ($resultadoExisteEmail->rowCount() !== 1) {
                $arrayResponse['response'] = 'Ups! El email introducido no existe...';
                $arrayResponse['status'] = 0;
            } else {
                $arrayResponse['data'] = $resultadoExisteEmail->fetch(PDO::FETCH_ASSOC);
                $tokenDate = strtotime("+1 day");
                $token = base64_encode($arrayResponse['data']['token'] . ";" . $tokenDate);
                $arrayResponse['response'] = 'Email envaido correctamente!';
                $arrayResponse['status'] = 1;

                //Si el corrreo existe enviar un enlace junto con su token para restablecer la contraseña
                $headers  = "From: HUBBET ADMIN <joaquinromeroesteve@gmail.com>\n";
                $headers .= "Reply-To: joaquinromeroesteve@gmail.com\n";
                $headers .= "Return-Path: joaquinromeroesteve@gmail.com\n";
                $headers .= "MIME-Version: 1.0\n";
                $headers .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
                $headers .= "X-Priority: 1 (Highest)\n";
                $headers .= "X-MSMail-Priority: High\n";
                $headers .= "Importance: High\n";
                $body = '<h1>Haz click en el botón para cambiar tu contraseña</h1>'; /* file_get_contents( __DIR__ . "/template.html"); */
                $body .= "<a href='http://localhost:4200/reset/" . $token . "'>Cambiar Contraseña</a>";
                $arrayResponse['checkSendEmail'] = mail($email, "Cambio de contraseña", $body, $headers);
            }
        } catch (Exception $e) {
            $arrayResponse['status'] = 'error';
            $arrayResponse['response'] = $e->getMessage();
        }
        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //
    //Cambio de contraseña con el token del usuario, y generar otro token
    $app->post('/verifyTokenReset', function ($request, $response) {
        $db = $this->get('db');
        $postResponse = $request->getParsedBody();
        $tokenCodificado = $postResponse['token'];

        $arrayResponse = [
            "status" => '',
            "response" => '',
            "errorCode" => ''
        ];

        try {
            //Decodificar el token y la fecha
            $arrayToken = explode(';', base64_decode($tokenCodificado));

            if (isset($arrayToken[0]) && isset($arrayToken[1])) {
                $tokenDecodificado = $arrayToken[0];
                $fechaDecodificada = $arrayToken[1];
                $fechaActual = strtotime("now");

                //Verificar que el token existe
                $queryExisteToken = "SELECT token FROM usuarios WHERE token = '$tokenDecodificado'";
                $resultadoExisteToken = $db->prepare($queryExisteToken);
                $resultadoExisteToken->execute();

                if ($resultadoExisteToken->rowCount() === 0) {
                    $arrayResponse['response'] = "Token inválido!";
                    $arrayResponse['errorCode'] = "1000";
                    $arrayResponse['status'] = false;
                } else {

                    //Verificar que el token no haya expirado (que la fecha es correcta)
                    if (date("m/d/Y h:i:s", intval($fechaDecodificada)) < date("m/d/Y h:i:s", $fechaActual)) {
                        $arrayResponse['response'] = "Token inválido!";
                        $arrayResponse['errorCode'] = "1001";
                        $arrayResponse['status'] = false;
                    } else {
                        $arrayResponse['response'] = "Contraseña cambiada correctamente!";
                        $arrayResponse['errorCode'] = "";
                        $arrayResponse['status'] = true;
                    }
                }
            } else {
                $arrayResponse['response'] = "Token inválido!";
                $arrayResponse['errorCode'] = "1002";
                $arrayResponse['status'] = false;
            }
        } catch (Exception $e) {
            $arrayResponse['status'] = false;
            $arrayResponse['errorCode'] = "1003";
            $arrayResponse['response'] = $e->getMessage();
        }
        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Cambio de contraseña con el token del usuario, y generar otro token
    $app->post('/reset', function ($request, $response) {
        $db = $this->get('db');
        $postResponse = $request->getParsedBody();

        $clave = filter_var($postResponse['clave'], FILTER_SANITIZE_STRING);
        $claveConfirm = filter_var($postResponse['claveConfirm'], FILTER_SANITIZE_STRING);
        $tokenCodificado = $postResponse['token'];

        $arrayResponse = [
            "status" => '',
            "response" => '',
            "errorCode" => ''
        ];

        try {
            //Decodificar el token y la fecha
            $arrayToken = explode(';', base64_decode($tokenCodificado));

            if (isset($arrayToken[0]) && isset($arrayToken[1])) {
                $tokenDecodificado = $arrayToken[0];
                $fechaDecodificada = $arrayToken[1];
                $fechaActual = strtotime("now");

                //Verificar que el token existe
                $queryExisteToken = "SELECT id FROM usuarios WHERE token = '$tokenDecodificado'";
                $resultadoExisteToken = $db->prepare($queryExisteToken);
                $resultadoExisteToken->execute();
                $data = $resultadoExisteToken->fetch(PDO::FETCH_ASSOC);
                $id = $data['id'];

                if ($resultadoExisteToken->rowCount() === 0) {
                    $arrayResponse['response'] = "Token inválido!";
                    $arrayResponse['errorCode'] = "1000";
                    $arrayResponse['status'] = false;
                } else {

                    //Verificar que el token no haya expirado (que la fecha es correcta)
                    if (date("m/d/Y h:i:s", intval($fechaDecodificada)) < date("m/d/Y h:i:s", $fechaActual)) {
                        $arrayResponse['response'] = "Token inválido!";
                        $arrayResponse['errorCode'] = "1001";
                        $arrayResponse['status'] = false;
                    } else {
                        if ($clave != $claveConfirm) {
                            $arrayResponse['response'] = "Las contraseñas no coinciden!";
                            $arrayResponse['errorCode'] = "1004";
                            $arrayResponse['status'] = false;
                        } else {
                            $claveFinal = crypt($claveConfirm, 'Sc2dgcK39Zspr7nUKdnV28xfwFUaP2Sx4vnVxHzhyRwzzujVyx');
                            $token = md5(uniqid(strval(rand()), true));
                            $queryChangePassword = "UPDATE usuarios SET clave = '$claveFinal', token = '$token' WHERE token = '$tokenDecodificado' AND id = '$id'";
                            $resultadoQueryPassword = $db->prepare($queryChangePassword);
                            $resultadoQueryPassword->execute();

                            if ($resultadoQueryPassword->rowCount() === 0) {
                                $arrayResponse['response'] = "Lo sentimos, ha ocurrido un error al cambiar la contraseña...";
                                $arrayResponse['errorCode'] = "1005";
                                $arrayResponse['status'] = false;
                            } else {
                                $arrayResponse['response'] = "Contraseña cambiada correctamente!";
                                $arrayResponse['errorCode'] = "";
                                $arrayResponse['status'] = true;
                            }
                        }
                    }
                }
            } else {
                $arrayResponse['response'] = "Token inválido!";
                $arrayResponse['errorCode'] = "1002";
                $arrayResponse['status'] = false;
            }
        } catch (Exception $e) {
            $arrayResponse['status'] = false;
            $arrayResponse['errorCode'] = "1003";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para cambiar la foto de perfil
    $app->post('/cambiarFoto', function ($request, $response) {

        $url = __DIR__ . "/../../frontEnd/src/assets/uploads/";
        $arrayResponse = [
            "status" => '',
            "response" => '',
            "errorCode" => ''
        ];
        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $postImgResponse = $request->getUploadedFiles();
            $image = $postImgResponse['file'];
            $postData = json_decode($postResponse['data']);
            $folderName = $url . $postData->usuario . '/';
            $token = $postData->token;

            if (!file_exists($folderName)) {
                mkdir($folderName, 0777, true);
            }

            //Array con extensiones válidas
            $extensionesValidas = array('png', 'jpeg', 'jpg');

            //Validar extensión
            $extension = pathinfo($folderName . '/' . $image->getClientFilename(), PATHINFO_EXTENSION);
            $extension = strtolower($extension);

            if (in_array($extension, $extensionesValidas)) {
                if ($image->getError() === UPLOAD_ERR_OK) {

                    //Borramos todo lo que haya dentro para que siempre haya un solo archivo
                    $files = glob($folderName . '*');
                    foreach ($files as $file) {
                        if (is_file($file))
                            unlink($file);
                    }

                    //Si se mueve la imágen a la carpeta, se sube a la DB
                    if ($filename = moveUploadedFile($folderName, $image)) {
                        $query = "UPDATE usuarios SET imagen = '$filename' WHERE token = '$token'";
                        $resultadoQuery = $db->prepare($query);
                        $resultadoQuery->execute();

                        if ($resultadoQuery->rowCount() === 1) {
                            $postData->imagen = $filename;
                            $arrayResponse['status'] = true;
                            $arrayResponse['errorCode'] = "";
                            $arrayResponse['response'] = $filename;
                            $arrayResponse['data'] = $postData;
                        } else {
                            $arrayResponse['status'] = false;
                            $arrayResponse['errorCode'] = "1001";
                            $arrayResponse['response'] = 'Ha ocurrido un problema al subir la foto';
                        }
                    }
                } else {
                    $arrayResponse['status'] = false;
                    $arrayResponse['errorCode'] = "1000";
                    $arrayResponse['response'] = 'Error de server';
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1000";
                $arrayResponse['response'] = "Formato de imágen no admitido";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] = false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para eliminar la foto de perfil
    $app->post('/eliminarFoto', function ($request, $response) {

        $url = __DIR__ . "/../../frontEnd/src/assets/uploads/";
        $arrayResponse = [
            "status" => '',
            "response" => '',
            "errorCode" => ''
        ];
        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $image = "";
            $postData = json_decode($postResponse['data']);
            $folderName = $url . $postData->usuario . '/';
            $token = $postData->token;

            if (!file_exists($folderName)) {
                mkdir($folderName, 0777, true);
            }
            //Borramos todo lo que haya dentro para que siempre haya un solo archivo
            $files = glob($folderName . '*');
            foreach ($files as $file) {
                if (is_file($file))
                    unlink($file);
            }

            $query = "UPDATE usuarios SET imagen = '' WHERE token = '$token'";
            $resultadoQuery = $db->prepare($query);
            $resultadoQuery->execute();

            if ($resultadoQuery->rowCount() === 0) {
                $arrayResponse['status'] = false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = 'Error al eliminar la foto!';
            } else {
                $postData->imagen = $image;
                $arrayResponse['status'] = true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = $image;
                $arrayResponse['data'] = $postData;
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  $folderName;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función move file
    function moveUploadedFile($directory, $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        comprimirImagen($directory . DIRECTORY_SEPARATOR . $filename, $directory . DIRECTORY_SEPARATOR . $filename, 40);

        return $filename;
    }

    //Función para comprimir imágenes
    function comprimirImagen($recurso, $destino, $calidad)
    {
        $info = getimagesize($recurso);

        if ($info['mime'] == 'image/jpeg')
            $image = imagecreatefromjpeg($recurso);

        elseif ($info['mime'] == 'image/gif')
            $image = imagecreatefromgif($recurso);

        elseif ($info['mime'] == 'image/png')
            $image = imagecreatefrompng($recurso);

        imagejpeg($image, $destino, $calidad);
    }

    //Función para actualizar los datos extra del usuario
    $app->post('/updateDatosExtra', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "response" => '',
            "errorCode" => '',
            "data" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $postData = json_decode($postResponse['data']);

            $token = filter_var($postData->token, FILTER_SANITIZE_STRING);
            $nombre = filter_var($postData->nombre, FILTER_SANITIZE_STRING);
            $ubicacion = filter_var($postData->ubicacion, FILTER_SANITIZE_STRING);
            $rol = filter_var($postData->rol, FILTER_SANITIZE_STRING);
            // $equipo = filter_var($postData->equipo, FILTER_SANITIZE_STRING);
            $descripcion = filter_var($postData->descripcion, FILTER_SANITIZE_STRING);

            $query = "UPDATE usuarios SET nombre = ?, ubicacion = ?, rol = ?, descripcion = ? WHERE token = '$token'";
            $resultadoQuery = $db->prepare($query);
            $resultadoQuery->bindParam(1, $nombre);
            $resultadoQuery->bindParam(2, $ubicacion);
            $resultadoQuery->bindParam(3, $rol);
            $resultadoQuery->bindParam(4, $descripcion);

            $resultadoQuery->execute();

            if ($resultadoQuery->rowCount() === 0) {
                $arrayResponse['status'] = false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = 'Error al actualizar los datos';
            } else {
                $arrayResponse['status'] = true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = 'Datos actualizados correctamente!';
                $arrayResponse['data'] = $postData;
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para añadir un usuario a una tarea
    $app->post('/getUserNote', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "response" => '',
            "errorCode" => '',
            "data" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $email = filter_var($postResponse['email'], FILTER_SANITIZE_EMAIL);
            $sesionEmail = $postResponse['sesionEmail'];

            $query = "SELECT token, usuario, imagen from usuarios WHERE email = ? AND email != '$sesionEmail'";
            $resultadoQuery = $db->prepare($query);
            $resultadoQuery->bindParam(1, $email);

            $resultadoQuery->execute();

            if ($resultadoQuery->rowCount() === 0) {
                $arrayResponse['status'] = false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = 'No hemos podido encontrar ese email...';
            } else {
                $arrayResponse['status'] = true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = 'Email encontrado!';
                $arrayResponse['data'] = $resultadoQuery->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para crear una tarea
    $app->post('/addTarea', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "response" => '',
            "errorCode" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();

            $token = $postResponse['token'];
            $titulo = filter_var($postResponse['titulo'], FILTER_SANITIZE_STRING);
            $fecha = filter_var($postResponse['fecha'], FILTER_SANITIZE_STRING);
            $descripcion = filter_var($postResponse['descripcion'], FILTER_SANITIZE_STRING);
            $now = date("Y-m-d H:i:s");

            $query = "INSERT INTO tareas (tokenUsuario, titulo, fecha, descripcion, creacion) VALUES (?, ?, ?, ?, ?)";
            $resultadoQuery = $db->prepare($query);
            $resultadoQuery->bindParam(1, $token);
            $resultadoQuery->bindParam(2, $titulo);
            $resultadoQuery->bindParam(3, $fecha);
            $resultadoQuery->bindParam(4, $descripcion);
            $resultadoQuery->bindParam(5, $now);
            $resultadoQuery->execute();

            if ($resultadoQuery->rowCount() === 1) {
                $arrayResponse['status'] = true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = 'Tarea insertada correctamente!';
            } else {
                $arrayResponse['status'] = false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = 'Se ha producido un error al insertar la tarea...';
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para crear una tarea
    $app->post('/obtenerTareas', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => '',
            "todayTasks" => '',
            "todayCount" => '',
            "tomorrowTasks" => '',
            "tomorrowCount" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $userToken = $postResponse['userToken'];

            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime($today . ' + 1 days'));
            //falta añadir fechas pasadas y futuras

            //Get today tasks
            /*  AND tareas.fecha = '$today' AND tareas.completada = 0 ORDER BY creacion DESC */

            $queryAsignedTasks = "";
            $resultadoQuery = $db->prepare($queryAsignedTasks);
            $resultadoQuery->execute();

            //Get tomorrow tasks
            $tomorrow = "SELECT tareas.id, tareas.titulo, tareas.fecha, tareas.descripcion, tareas.creacion, tareas.completada, usuarios.usuario, usuarios.imagen FROM tareas INNER JOIN usuarios WHERE (tareas.tokenUsuario = '$userToken') AND tareas.fecha = '$tomorrow' AND tareas.completada = 0 ORDER BY creacion DESC";
            $resultadoTomorrow = $db->prepare($tomorrow);
            $resultadoTomorrow->execute();

            if ($resultadoQuery->rowCount() > 0) {
                $arrayResponse['status'] = true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = 'Tareas obtenidas correctamente!';
                $arrayResponse['todayTasks'] = $resultadoQuery->fetchAll(PDO::FETCH_ASSOC);
                $arrayResponse['todayCount'] = $resultadoQuery->rowCount();

                $arrayResponse['tomorrowTasks'] = $resultadoTomorrow->fetchAll(PDO::FETCH_ASSOC);
                $arrayResponse['tomorrowCount'] = $resultadoTomorrow->rowCount();
            } else {
                $arrayResponse['status'] = false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = '';
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });


    //Función para eliminar una tarea
    $app->post('/eliminarTarea', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();

            $tokenUsuario = $postResponse['tokenUsuario'];
            $idTarea = $postResponse['idTarea'];

            $query = "DELETE FROM tareas WHERE id = '$idTarea' AND tokenUsuario = '$tokenUsuario'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() == 1) {
                $arrayResponse['status'] =  true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = "Tarea eliminada correctamente!";
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Se ha producido un error al eliminar la tarea";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para cambiar la contraseña desde dentro
    $app->post('/cambiarClave', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();

            $tokenUsuario = $postResponse['tokenUsuario'];
            $claveActual = $postResponse['claveActual'];
            $claveNueva = $postResponse['claveNueva'];

            $actual = crypt($claveActual, 'Sc2dgcK39Zspr7nUKdnV28xfwFUaP2Sx4vnVxHzhyRwzzujVyx');
            $encryptedClave = crypt($claveNueva, 'Sc2dgcK39Zspr7nUKdnV28xfwFUaP2Sx4vnVxHzhyRwzzujVyx');

            $query1 = "SELECT * FROM usuarios WHERE token = '$tokenUsuario' AND clave = '$actual'";
            $resultado1 = $db->prepare($query1);
            $resultado1->execute();

            if ($resultado1->rowCount() == 1) {
                $query2 = "UPDATE usuarios SET clave = '$encryptedClave' WHERE token = '$tokenUsuario' AND clave = '$actual'";
                $resultado2 = $db->prepare($query2);
                $resultado2->execute();
                if ($resultado2->rowCount() == 1) {
                    $arrayResponse['status'] =  true;
                    $arrayResponse['errorCode'] = "";
                    $arrayResponse['response'] = "Contraseña actualizada correctamente!";
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "No se ha podido actualizar tu contraseña";
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "La contraseña actual es incorrecta";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para crear equipos
    $app->post('/crearEquipo', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $url = "default2.jpg";

            $nombre = $postResponse['nombre'];
            $tokenUsuario = $postResponse['tokenUsuario'];

            //Comprobar si tiene equipo
            $queryUserHasTeam = "SELECT usuarios.id, usuarios_equipos.id_equipo FROM usuarios LEFT JOIN usuarios_equipos ON usuarios.id = usuarios_equipos.id_usuario WHERE usuarios.token = '$tokenUsuario'";

            $resultadoUserHasTeam = $db->prepare($queryUserHasTeam);
            $resultadoUserHasTeam->execute();
            $resultado = $resultadoUserHasTeam->fetch(PDO::FETCH_ASSOC);

            if ($resultado['id_equipo'] == NULL) {
                $idUser = $resultado['id'];
                //Comprobar si el nombre existe
                /* $queryExisteEquipo = "SELECT nombre FROM equipos WHERE nombre = '$nombre'";
                $resultadoExisteEquipo = $db->prepare($queryExisteEquipo);
                $resultadoExisteEquipo->execute();

                if ($resultadoExisteEquipo->rowCount() == 1) {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "Ya existe un equipo con ese nombre...";
                } else { */
                $now = date("Y-m-d H:i:s");
                $query = "INSERT INTO equipos (nombre, creador, creacion, imagen) VALUES (?, ?, ?, ?)";
                $resultado = $db->prepare($query);
                $resultado->bindParam(1, $nombre);
                $resultado->bindParam(2, $idUser);
                $resultado->bindParam(3, $now);
                $resultado->bindParam(4, $url);

                $resultado->execute();
                $idEquipo = $db->lastInsertId();

                if ($resultado->rowCount() == 1) {
                    $query = "INSERT INTO usuarios_equipos (id_usuario, id_equipo) VALUES (?, ?)";
                    $resultado = $db->prepare($query);
                    $resultado->bindParam(1, $idUser);
                    $resultado->bindParam(2, $idEquipo);
                    $resultado->execute();
                    if ($resultado->rowCount() == 1) {
                        $arrayResponse['status'] =  true;
                        $arrayResponse['errorCode'] = "";
                        $arrayResponse['response'] = "Equipo creado correctamente!";
                    } else {
                        $arrayResponse['status'] =  false;
                        $arrayResponse['errorCode'] = "1001";
                        $arrayResponse['response'] = "Se ha producido un error al crear el equipo";
                    }
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "Se ha producido un error al crear el equipo";
                }
                /*  } */
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = "Abandona el grupo actual para crear uno nuevo";
                /*  $arrayResponse['dataTeam'] = $resultadoUserHasTeam->fetchAll(PDO::FETCH_ASSOC); */
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para obtener los datos del eequipo
    $app->post('/obtenerDatosEquipo', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $tokenUsuario = $postResponse['tokenUsuario'];

            $queryUserHasTeam = "SELECT usuarios.id, usuarios_equipos.id_equipo FROM usuarios LEFT JOIN usuarios_equipos ON usuarios.id = usuarios_equipos.id_usuario WHERE usuarios.token = '$tokenUsuario'";

            $resultadoUserHasTeam = $db->prepare($queryUserHasTeam);
            $resultadoUserHasTeam->execute();
            $resultado = $resultadoUserHasTeam->fetch(PDO::FETCH_ASSOC);
            if ($resultado['id_equipo'] !== NULL) {
                $idUsuario = $resultado['id'];
                $idEquipo = $resultado['id_equipo'];

                $query = "SELECT equipos.id, equipos.nombre, equipos.creador, equipos.imagen, equipos.descripcion, equipos.creacion FROM equipos INNER JOIN usuarios_equipos ON usuarios_equipos.id_equipo = equipos.id AND usuarios_equipos.id_usuario = '$idUsuario'";
                $resultado = $db->prepare($query);
                $resultado->execute();

                if ($resultado->rowCount() === 1) {
                    $arrayResponse['status'] =  true;
                    $arrayResponse['errorCode'] = "";
                    $arrayResponse['response'] = $resultado->fetch(PDO::FETCH_ASSOC);

                    $query = "SELECT usuarios.imagen, usuarios.usuario, usuarios.email FROM usuarios INNER JOIN usuarios_equipos ON usuarios.id = usuarios_equipos.id_usuario AND usuarios_equipos.id_equipo = '$idEquipo'";
                    $resultado = $db->prepare($query);
                    $resultado->execute();
                    $arrayResponse['response']['usersData'] = $resultado->fetchAll(PDO::FETCH_ASSOC);
                    $arrayResponse['response']['usersCount'] = $resultado->rowCount();
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "Ha ocurrido un problema al obtener los datos del equipo";
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Este usuario no tiene equipo";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para abandonar un grupo
    $app->post('/abandonarEquipo', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $tokenUsuario = $postResponse['tokenUsuario'];

            $queryUserHasTeam = "SELECT usuarios.id, usuarios_equipos.id_equipo FROM usuarios 
            LEFT JOIN usuarios_equipos ON usuarios.id = usuarios_equipos.id_usuario WHERE usuarios.token = '$tokenUsuario'";
            $resultadoUserHasTeam = $db->prepare($queryUserHasTeam);
            $resultadoUserHasTeam->execute();
            $resultado = $resultadoUserHasTeam->fetch(PDO::FETCH_ASSOC);

            if ($resultado['id'] != NULL && $resultado['id_equipo'] != NULL) {
                $idUsuario = $resultado['id'];
                $idEquipo = $resultado['id_equipo'];

                $query = "DELETE FROM usuarios_equipos WHERE id_usuario = '$idUsuario' AND id_equipo = '$idEquipo'";
                $resultado = $db->prepare($query);
                $resultado->execute();

                if ($resultado->rowCount() === 1) {
                    $arrayResponse['status'] =  true;
                    $arrayResponse['errorCode'] = "";
                    $arrayResponse['response'] = "Saliste del grupo";
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "Fallo al salir del grupo";
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Este usuario no tiene equipo";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para abandonar un grupo
    $app->post('/buscarEquipo', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $value = $postResponse['value'];

            $query = "SELECT id, nombre, imagen FROM equipos WHERE nombre LIKE '$value%' OR nombre LIKE '%$value%' OR nombre LIKE '%$value'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() > 0) {
                $arrayResponse['status'] =  true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = $resultado->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "No hemos encontrado ningún equipo...";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para unirse a un grupo
    $app->post('/unirseEquipo', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();

            $token = $postResponse['token'];
            $idGrupo = $postResponse['idGrupo'];
            $nombreGrupo = $postResponse['nombreGrupo'];

            //Comprobar si el equipo existe
            $query = "SELECT * FROM equipos WHERE nombre = '$nombreGrupo' AND id = '$idGrupo'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                //Obtener el id del usuario que queremos insertar
                $queryGetUserId = "SELECT id FROM usuarios WHERE token = '$token'";
                $resultado = $db->prepare($queryGetUserId);
                $resultado->execute();

                if ($resultado->rowCount() === 1) {
                    $resultado = $resultado->fetch(PDO::FETCH_ASSOC);
                    $id = $resultado['id'];
                    $query = "INSERT INTO usuarios_equipos (id_usuario, id_equipo) VALUES ('$id', '$idGrupo')";
                    $resultado = $db->prepare($query);
                    $resultado->execute();
                    if ($resultado->rowCount() === 1) {
                        $arrayResponse['status'] =  true;
                        $arrayResponse['errorCode'] = "";
                        $arrayResponse['response'] = "Te has unido al grupo correctamente!";
                    } else {
                        $arrayResponse['status'] =  false;
                        $arrayResponse['errorCode'] = "1003";
                        $arrayResponse['response'] = "No se ha podido unir al grupo, inténtalo más tarde";
                    }
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "Se ha producido un error";
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "El equipo introducido no existe";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });
};
