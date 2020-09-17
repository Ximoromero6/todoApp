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
                $arrayResponse['response'] = 'Ups! Parece que el email introducido no existe...';
                $arrayResponse['status'] = 0;
            } else {
                $arrayResponse['data'] = $resultadoExisteEmail->fetch(PDO::FETCH_ASSOC);
                $tokenDate = strtotime("+2 hour");
                $token = base64_encode($arrayResponse['data']['token'] . ";" . $tokenDate);
                $arrayResponse['response'] = 'Email enviado correctamente!';
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
            $extensionesValidas = array('png', 'jpeg', 'jpg', 'svg');

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
            $token = $postResponse['token'];

            //Obtener el id del usuario en sesión
            $queryGetUserId = "SELECT id, email FROM usuarios WHERE token = '$token'";
            $resultado = $db->prepare($queryGetUserId);
            $resultado->execute();

            //Obtener el id del email que buscamos para comprobar si son del mismo equipo
            $queryGetUserId2 = "SELECT id, email FROM usuarios WHERE email = '$email'";
            $resultado2 = $db->prepare($queryGetUserId2);
            $resultado2->execute();

            if ($resultado->rowCount() === 1 && $resultado2->rowCount() === 1) {
                $data = $resultado->fetch(PDO::FETCH_ASSOC);
                $data2 = $resultado2->fetch(PDO::FETCH_ASSOC);

                //Id y email de usuario en sesión
                $id = $data['id'];
                $email = $data['email'];

                //Id del usuario buscado por email, para obtener el id del equipo en el que está
                $id2 = $data2['id'];
                $email2 = $data2['email'];

                //Obtener el id de equipo del usuario en sesión
                $query = "SELECT id_equipo FROM usuarios_equipos WHERE id_usuario = '$id'";
                $resultado = $db->prepare($query);
                $resultado->execute();

                //Obtener el id de equipo del usuario buscado
                $query2 = "SELECT id_equipo FROM usuarios_equipos WHERE id_usuario = '$id2'";
                $resultado2 = $db->prepare($query2);
                $resultado2->execute();

                if ($resultado->rowCount() === 1 && $resultado2->rowCount() === 1) {
                    $resultado = $resultado->fetch(PDO::FETCH_ASSOC);
                    $resultado2 = $resultado2->fetch(PDO::FETCH_ASSOC);

                    //Id equipo usuario sesión
                    $id_equipo = $resultado['id_equipo'];

                    //Id del equipo del usuario buscado
                    $id_equipo2 = $resultado2['id_equipo'];

                    //Comprobamos si son iguales
                    if ($id_equipo === $id_equipo2) {

                        //Si están en el mismo equipo lo puede añadir
                        $query = "SELECT id, token, usuario, imagen from usuarios WHERE email = ? AND email != '$email'";
                        $resultadoQuery = $db->prepare($query);
                        $resultadoQuery->bindParam(1, $email2);
                        $resultadoQuery->execute();

                        if ($resultadoQuery->rowCount() === 0) {
                            $arrayResponse['status'] = false;
                            $arrayResponse['errorCode'] = "1004";
                            $arrayResponse['response'] = 'No hemos podido encontrar ese email...';
                        } else {
                            $arrayResponse['status'] = true;
                            $arrayResponse['errorCode'] = "";
                            $arrayResponse['response'] = 'Email encontrado!';
                            $arrayResponse['data'] = $resultadoQuery->fetch(PDO::FETCH_ASSOC);
                        }
                    } else {
                        $arrayResponse['status'] =  false;
                        $arrayResponse['errorCode'] = "1003";
                        $arrayResponse['response'] = "Este usuario no está en tu equipo";
                    }
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "Ambas personas debéis pertenecer al mismo grupo";
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Se ha producido un error, inténtalo más tarde...";
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
            /*             $usuarios = []; */

            if ($postResponse['usuarios'] == "") {
                $usuarios = array();
            } else {
                $usuarios = explode(",", $postResponse['usuarios']);
            }

            //Obtener el id del usuario
            $query = "SELECT usuarios.id FROM usuarios WHERE usuarios.token = '$token'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() > 0) {
                $resultado = $resultado->fetch(PDO::FETCH_ASSOC);

                //Obtenemos el id del usuario que crea la tarea
                $idUser = $resultado['id'];
                array_push($usuarios, $idUser);

                $query = "INSERT INTO tareas (idUsuario, titulo, fecha, descripcion, creacion) VALUES (?, ?, ?, ?, ?)";
                $resultadoQuery = $db->prepare($query);
                $resultadoQuery->bindParam(1, $idUser);
                $resultadoQuery->bindParam(2, $titulo);
                $resultadoQuery->bindParam(3, $fecha);
                $resultadoQuery->bindParam(4, $descripcion);
                $resultadoQuery->bindParam(5, $now);
                $resultadoQuery->execute();

                $idTarea = $db->lastInsertId();

                if ($resultadoQuery->rowCount() === 1) {

                    foreach ($usuarios as $user) {

                        //Si la tearea se inserta correctamente, insertamos un registro en la tabla usuarios_tareas con el id usuario y el id de la tarea que acabamos de crear
                        $query = "INSERT INTO usuarios_tareas (id_usuario, id_tarea) VALUES (?, ?)";
                        $resultado = $db->prepare($query);
                        $resultado->bindParam(1, $user);
                        $resultado->bindParam(2, $idTarea);
                        $resultado->execute();
                    }

                    if ($resultado->rowCount() == 1) {
                        $arrayResponse['status'] =  true;
                        $arrayResponse['errorCode'] = "";
                        $arrayResponse['response'] = "Tarea insertada correctamente!";
                    } else {
                        $arrayResponse['status'] =  false;
                        $arrayResponse['errorCode'] = "1001";
                        $arrayResponse['response'] = "Se ha producido un error, inténtalo más tarde";
                    }
                } else {
                    $arrayResponse['status'] = false;
                    $arrayResponse['errorCode'] = "1001";
                    $arrayResponse['response'] = 'Se ha producido un error al insertar la tarea...';
                }
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para obtener todas las tareas
    $app->post('/obtenerTareas', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $userToken = $postResponse['userToken'];

            $query = "SELECT usuarios.id FROM usuarios WHERE usuarios.token = '$userToken'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() > 0) {
                $resultado = $resultado->fetch(PDO::FETCH_ASSOC);
                $idUsuario = $resultado['id'];

                //Obtener tareas
                $query = "SELECT usuarios.id AS 'userID', usuarios.usuario, usuarios.imagen, tareas.id AS 'tareasID', tareas.titulo, tareas.fecha, tareas.descripcion, tareas.creacion, tareas.idUsuario 
                FROM tareas INNER JOIN usuarios_tareas ON usuarios_tareas.id_tarea = tareas.id 
                INNER JOIN usuarios ON tareas.idUsuario = usuarios.id 
                WHERE usuarios_tareas.id_usuario = '$idUsuario' AND tareas.completada = 0 ORDER BY creacion DESC";

                $resultado = $db->prepare($query);
                $resultado->execute();
                $resTarea = $resultado->fetchAll(PDO::FETCH_ASSOC);

                if ($resultado->rowCount() > 0) {
                    for ($i = 0; $i < count($resTarea); $i++) {

                        $arrayTasks[$i]['id'] = $resTarea[$i]['tareasID'];
                        $arrayTasks[$i]['titulo'] = $resTarea[$i]['titulo'];
                        $arrayTasks[$i]['fecha'] = $resTarea[$i]['fecha'];
                        $arrayTasks[$i]['descripcion'] = $resTarea[$i]['descripcion'];
                        $arrayTasks[$i]['creacion'] = $resTarea[$i]['creacion'];
                        $arrayTasks[$i]['usuario'] = $resTarea[$i]['usuario'];
                        $arrayTasks[$i]['imagen'] = $resTarea[$i]['imagen'];
                        $arrayTasks[$i]['colaboradores'] = array();
                        $arrayTasks[$i]['comentarios'] = array();

                        //Obtener colaboradores
                        $query = "SELECT usuarios.id, usuarios.usuario, usuarios.imagen 
                        FROM usuarios INNER JOIN usuarios_tareas ON usuarios.id = usuarios_tareas.id_usuario 
                        INNER JOIN tareas ON usuarios_tareas.id_tarea = tareas.id 
                        WHERE usuarios_tareas.id_tarea = " . $resTarea[$i]['tareasID'] . " AND usuarios.id != tareas.idUsuario";

                        $resultado = $db->prepare($query);
                        $resultado->execute();
                        $resColab = $resultado->fetchAll(PDO::FETCH_ASSOC);

                        for ($j = 0; $j < count($resColab); $j++) {

                            $arrayTasks[$i]['colaboradores'][$j]['id'] = $resColab[$j]['id'];
                            $arrayTasks[$i]['colaboradores'][$j]['usuario'] = $resColab[$j]['usuario'];
                            $arrayTasks[$i]['colaboradores'][$j]['imagen'] = $resColab[$j]['imagen'];
                        }

                        //Obtener comentarios
                        $query = "SELECT comentarios.id, comentarios.comentario, comentarios.fecha, usuarios.usuario, usuarios.imagen
                        FROM comentarios INNER JOIN usuarios ON comentarios.id_usuario = usuarios.id
                        WHERE comentarios.id_tarea = " . $resTarea[$i]['tareasID'];

                        $resultado = $db->prepare($query);
                        $resultado->execute();
                        $resCom = $resultado->fetchAll(PDO::FETCH_ASSOC);

                        for ($j = 0; $j < count($resCom); $j++) {

                            $arrayTasks[$i]['comentarios'][$j]['id'] = $resCom[$j]['id'];
                            $arrayTasks[$i]['comentarios'][$j]['comentario'] = $resCom[$j]['comentario'];
                            $arrayTasks[$i]['comentarios'][$j]['fecha'] = $resCom[$j]['fecha'];
                            $arrayTasks[$i]['comentarios'][$j]['usuario'] = $resCom[$j]['usuario'];
                            $arrayTasks[$i]['comentarios'][$j]['imagen'] = $resCom[$j]['imagen'];
                        }
                    }

                    $arrayResponse['status'] =  true;
                    $arrayResponse['errorCode'] = "";
                    $arrayResponse['tasks'] = $arrayTasks;
                    $arrayResponse['todayCount'] = count($arrayTasks);
                } else {
                    $arrayResponse['status'] = false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = '';
                }

                /*  
                $query = "SELECT usuarios.id AS 'userID', usuarios.usuario, usuarios.imagen, tareas.id AS 'tareasID', tareas.titulo, tareas.fecha, 
                tareas.descripcion, tareas.creacion, tareas.idUsuario, comentarios.comentario, comentarios.id AS 'comentarioID', comentarios.fecha AS 'comentariosFecha' 
                FROM tareas INNER JOIN usuarios_tareas ON usuarios_tareas.id_tarea = tareas.id 
                INNER JOIN usuarios ON usuarios_tareas.id_usuario = usuarios.id 
                LEFT JOIN comentarios ON comentarios.id_tarea = tareas.id AND usuarios.id = comentarios.id_usuario 
                WHERE (usuarios_tareas.id_usuario = '$idUsuario' OR usuarios_tareas.id_tarea IN (SELECT tareas.id FROM tareas INNER JOIN usuarios_tareas ON usuarios_tareas.id_tarea = tareas.id WHERE usuarios_tareas.id_usuario = '$idUsuario') ) 
                AND tareas.completada = 0 ORDER BY creacion DESC"; */
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
            $id = $postResponse['id'];

            $query = "DELETE FROM tareas WHERE id = '$id'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
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
            $imagen = "default.jpg";

            $nombre = $postResponse['nombre'];
            $tokenUsuario = $postResponse['tokenUsuario'];

            //Comprobar si tiene equipo
            $queryUserHasTeam = "SELECT usuarios.id, usuarios_equipos.id_equipo FROM usuarios LEFT JOIN usuarios_equipos ON usuarios.id = usuarios_equipos.id_usuario WHERE usuarios.token = '$tokenUsuario'";

            $resultadoUserHasTeam = $db->prepare($queryUserHasTeam);
            $resultadoUserHasTeam->execute();
            $resultado = $resultadoUserHasTeam->fetch(PDO::FETCH_ASSOC);

            if ($resultado['id_equipo'] == NULL) {
                $idUser = $resultado['id'];

                $now = date("Y-m-d H:i:s");
                $query = "INSERT INTO equipos (nombre, creador, creacion, imagen) VALUES (?, ?, ?, ?)";
                $resultado = $db->prepare($query);
                $resultado->bindParam(1, $nombre);
                $resultado->bindParam(2, $idUser);
                $resultado->bindParam(3, $now);
                $resultado->bindParam(4, $imagen);

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
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = "Abandona el grupo actual para crear uno nuevo";
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

                $query = "SELECT equipos.id, equipos.creador, equipos.nombre, equipos.creador, equipos.imagen, equipos.descripcion, equipos.creacion FROM equipos INNER JOIN usuarios_equipos ON usuarios_equipos.id_equipo = equipos.id AND usuarios_equipos.id_usuario = '$idUsuario'";
                $resultado = $db->prepare($query);
                $resultado->execute();

                if ($resultado->rowCount() === 1) {
                    $resultado = $resultado->fetch(PDO::FETCH_ASSOC);
                    $arrayResponse['status'] =  true;
                    $arrayResponse['errorCode'] = "";
                    $arrayResponse['response'] = $resultado;
                    $creadorEquipo = $resultado['creador'];

                    if ($creadorEquipo === $idUsuario) {
                        $arrayResponse['response']['admin'] = true;
                    } else {
                        $arrayResponse['response']['admin'] = false;
                    }

                    $query = "SELECT usuarios.id, usuarios.imagen, usuarios.usuario, usuarios.email FROM usuarios INNER JOIN usuarios_equipos ON usuarios.id = usuarios_equipos.id_usuario AND usuarios_equipos.id_equipo = '$idEquipo'";
                    $resultado = $db->prepare($query);
                    $resultado->execute();

                    $res = $resultado->fetchAll(PDO::FETCH_ASSOC);
                    for ($i = 0; $i < count($res); $i++) {
                        $arrayResponse['response']['usersData'][$i]['id'] = $res[$i]['id'];
                        $arrayResponse['response']['usersData'][$i]['email'] = $res[$i]['email'];
                        $arrayResponse['response']['usersData'][$i]['imagen'] = $res[$i]['imagen'];
                        $arrayResponse['response']['usersData'][$i]['usuario'] = $res[$i]['usuario'];
                        if ($res[$i]['id'] == $creadorEquipo) {
                            $arrayResponse['response']['usersData'][$i]['admin'] = true;
                        } else {
                            $arrayResponse['response']['usersData'][$i]['admin'] = false;
                        }
                    }

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

    //Función para actualizar el grupo
    $app->post('/actualizarDatosEquipo', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();

            $tokenUsuario = $postResponse['token'];
            $idGrupo = $postResponse['idGrupo'];
            $nuevoNombre = trim($postResponse['nuevoNombre']);
            $nuevaDescripcion = trim($postResponse['nuevaDescripcion']);

            //Comprobar si el usuario es el admin
            $query = "SELECT usuarios.id, usuarios_equipos.id_equipo FROM usuarios LEFT JOIN usuarios_equipos ON usuarios.id = usuarios_equipos.id_usuario WHERE usuarios.token = '$tokenUsuario'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $respuesta = $resultado->fetch(PDO::FETCH_ASSOC);
                $idUsuario = $respuesta['id'];

                $query = "SELECT * FROM equipos WHERE creador = '$idUsuario'";
                $resultado = $db->prepare($query);
                $resultado->execute();

                if ($resultado->rowCount() === 1) {
                    $query = "UPDATE equipos SET nombre = '$nuevoNombre', descripcion = '$nuevaDescripcion' WHERE id = '$idGrupo'";
                    $resultado = $db->prepare($query);
                    $resultado->execute();
                    if ($resultado->rowCount() === 1) {
                        $arrayResponse['status'] =  true;
                        $arrayResponse['errorCode'] = "";
                        $arrayResponse['response'] = "Datos de equipo actualizados correctamente!";
                    } else {
                        $arrayResponse['status'] =  false;
                        $arrayResponse['errorCode'] = "1003";
                        $arrayResponse['response'] = "Se ha producido un error al actualizar los datos";
                    }
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "No tienes permisos de administrador";
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

    //Función para actualizar el grupo
    $app->post('/eliminarUsuarioEquipo', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();

            $tokenUsuario = $postResponse['token'];
            $idGrupo = $postResponse['idGrupo'];
            $usuarioABorrar = $postResponse['usuarioABorrar'];

            //Comprobar si el usuario es el admin
            $query = "SELECT usuarios.id, usuarios_equipos.id_equipo FROM usuarios LEFT JOIN usuarios_equipos ON usuarios.id = usuarios_equipos.id_usuario WHERE usuarios.token = '$tokenUsuario'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $respuesta = $resultado->fetch(PDO::FETCH_ASSOC);
                $idUsuario = $respuesta['id'];

                $query = "SELECT * FROM equipos WHERE creador = '$idUsuario'";
                $resultado = $db->prepare($query);
                $resultado->execute();

                if ($resultado->rowCount() === 1) {
                    $query = "DELETE FROM usuarios_equipos WHERE id_equipo = '$idGrupo' AND id_usuario = '$usuarioABorrar'";
                    $resultado = $db->prepare($query);
                    $resultado->execute();
                    if ($resultado->rowCount() === 1) {
                        $arrayResponse['status'] =  true;
                        $arrayResponse['errorCode'] = "";
                        $arrayResponse['response'] = "Usuario eliminado correctamente!";
                    } else {
                        $arrayResponse['status'] =  false;
                        $arrayResponse['errorCode'] = "1003";
                        $arrayResponse['response'] = "Se ha producido un error al eliminar el usuario";
                    }
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "No tienes permisos de administrador";
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Ha ocurrido un problema";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para publicar un comentario en una tarea
    $app->post('/addComentario', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();

            $tokenUsuario = $postResponse['token'];
            $idTarea = $postResponse['idTarea'];
            $comentario = $postResponse['comentario'];

            //Obtener el id del usuario que está en sesión (el que va a publicar el comentario)
            $query = "SELECT id FROM usuarios WHERE token = '$tokenUsuario'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $respuesta = $resultado->fetch(PDO::FETCH_ASSOC);
                $idUsuario = $respuesta['id'];

                //Comprobamos que el usuario esté en la tarea
                $query = "SELECT * FROM usuarios_tareas WHERE id_usuario = '$idUsuario' AND id_tarea = '$idTarea'";
                $resultado = $db->prepare($query);
                $resultado->execute();

                if ($resultado->rowCount() === 1) {
                    $fecha = date("Y-m-d H:i:s");
                    //Si el usuario tiene equipo se inserta el comentario en la tarea
                    $query = "INSERT INTO comentarios (id_usuario, id_tarea, comentario, fecha) VALUES (?, ?, ?, ?)";
                    $resultado = $db->prepare($query);
                    $resultado->bindParam(1, $idUsuario);
                    $resultado->bindParam(2, $idTarea);
                    $resultado->bindParam(3, $comentario);
                    $resultado->bindParam(4, $fecha);
                    $resultado->execute();

                    if ($resultado->rowCount() === 1) {
                        $arrayResponse['status'] =  true;
                        $arrayResponse['errorCode'] = "1002";
                        $arrayResponse['response'] = "Comentario añadido";
                    } else {
                        $arrayResponse['status'] =  false;
                        $arrayResponse['errorCode'] = "1003";
                        $arrayResponse['response'] = "Error al añadir comentario";
                    }
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "Este usuario no está en la tarea";
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Error al obtener datos";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para publicar un comentario en una tarea
    $app->post('/completarTarea', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $idTarea = $postResponse['idTarea'];

            //Obtener el id del usuario que está en sesión (el que va a publicar el comentario)
            $query = "UPDATE tareas SET completada = 1 WHERE id = '$idTarea'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $arrayResponse['status'] =  true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = "Tarea completada!";
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Error al obtener datos";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para agregar un participante extra en una tarea
    $app->post('/agregarParticipante', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();

            $tokenSesion = $postResponse['tokenSesion'];
            $idTarea = $postResponse['idTarea'];
            $emailUsuario = filter_var($postResponse['emailUsuario'], FILTER_SANITIZE_EMAIL);

            //Comprobar si existe el email introducido y si está en el mismo equipo que el usuario en sesión
            $query = "SELECT id, usuario, imagen FROM usuarios WHERE email = '$emailUsuario'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $data = $resultado->fetch(PDO::FETCH_ASSOC);

                //Guardo el id del email introducido
                $newUserID = $data['id'];

                $query2 = "SELECT * usuarios WHERE token = '$tokenSesion'";
                $resultado2 = $db->prepare($query);
                $resultado2->execute();

                if ($resultado2->rowCount() === 1) {
                    $data2 = $resultado2->fetch(PDO::FETCH_ASSOC);

                    //Guardo el id del usuario en sesión
                    $sesionUserID = $data2['id'];

                    //Obtener el id de equipo del usuario en sesión
                    $query = "SELECT id_equipo FROM usuarios_equipos WHERE id_usuario = '$sesionUserID'";
                    $resultado = $db->prepare($query);
                    $resultado->execute();

                    //Obtener el id de equipo del usuario buscado
                    $query2 = "SELECT id_equipo FROM usuarios_equipos WHERE id_usuario = '$newUserID'";
                    $resultado2 = $db->prepare($query2);
                    $resultado2->execute();

                    //Comprobar si están en el mismo equipo
                    if ($resultado->rowCount() === 1 && $resultado2->rowCount() === 1) {
                        $resultado = $resultado->fetch(PDO::FETCH_ASSOC);
                        $resultado2 = $resultado2->fetch(PDO::FETCH_ASSOC);

                        //Id equipo usuario sesión
                        $id_equipo = $resultado['id_equipo'];

                        //Id del equipo del usuario buscado
                        $id_equipo2 = $resultado2['id_equipo'];

                        //Comprobamos si son iguales
                        if ($id_equipo === $id_equipo2) {

                            $query = "SELECT * FROM tareas WHERE id = '$idTarea' AND idUsuario = '$newUserID'";
                            $resultado = $db->prepare($query);
                            $resultado->execute();

                            if ($resultado->rowCount() !== 1) {
                                //Pertenecen al mismo equipo
                                $consulta = "INSERT INTO usuarios_tareas (id_usuario, id_tarea) VALUES (?, ?)";
                                $resultado = $db->prepare($consulta);
                                $resultado->bindParam(1, $newUserID);
                                $resultado->bindParam(2, $idTarea);
                                $resultado->execute();

                                if ($resultado->rowCount() === 1) {
                                    $arrayResponse['status'] =  true;
                                    $arrayResponse['errorCode'] = "";
                                    $arrayResponse['response'] = $data;
                                } else {
                                    $arrayResponse['status'] =  false;
                                    $arrayResponse['errorCode'] = "1004";
                                    $arrayResponse['response'] = "Error al agregar al participante";
                                }
                            } else {
                                $arrayResponse['status'] =  false;
                                $arrayResponse['errorCode'] = "1003";
                                $arrayResponse['response'] = "No se puede agregar al creador";
                            }
                        } else {
                            $arrayResponse['status'] =  false;
                            $arrayResponse['errorCode'] = "1002";
                            $arrayResponse['response'] = "Ambos debeis pertenecer al mismo equipo";
                        }
                    }
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "No hemos encontrado ningún email...";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });


    //Función para eliminar un participante de una tarea
    $app->post('/eliminarParticipanteExtra', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $idTarea = $postResponse['idTarea'];
            $idUsuario = $postResponse['idUsuario'];

            //Obtener el id del usuario que está en sesión (el que va a publicar el comentario)
            $query = "DELETE FROM usuarios_tareas WHERE id_tarea = '$idTarea' AND id_usuario = '$idUsuario'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $arrayResponse['status'] =  true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = "Participante eliminado";
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Error al eliminar participante";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para editar el título de una nota
    $app->post('/editarTitulo', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $idTarea = $postResponse['idTarea'];
            $texto = $postResponse['texto'];

            $query = "UPDATE tareas SET titulo = '$texto' WHERE id = '$idTarea'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $arrayResponse['status'] =  true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = "Título editado";
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Error al actualizar el título";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para editar la descripción de una nota
    $app->post('/editarDescripcion', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $idTarea = $postResponse['idTarea'];
            $texto = $postResponse['texto'];

            $query = "UPDATE tareas SET descripcion = '$texto' WHERE id = '$idTarea'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $arrayResponse['status'] =  true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = "Descripción editada";
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Error al actualizar la descripción";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para eliminar un comentario
    $app->post('/eliminarComentario', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();

            $idTarea = $postResponse['idTarea'];
            $idComentario = $postResponse['idComentario'];

            $query = "DELETE FROM comentarios WHERE id_tarea = $idTarea AND id = $idComentario";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $arrayResponse['status'] =  true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = "Comentario eliminado";
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Error al eliminar el comentario";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para obtener datos equipo
    $app->post('/obtenerDatosEquipo2', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $tokenUsuario = $postResponse['tokenUsuario'];

            //Obtener el id del usuario con el token
            $query = "SELECT id FROM usuarios WHERE token = '$tokenUsuario'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $userData = $resultado->fetch(PDO::FETCH_ASSOC);
                $userID = $userData['id'];

                //Obtener el id del grupo con el id del usuario
                $query2 = "SELECT id_equipo FROM usuarios_equipos WHERE id_usuario = '$userID'";
                $resultado2 = $db->prepare($query2);
                $resultado2->execute();

                if ($resultado2->rowCount() === 1) {
                    $teamData = $resultado2->fetch(PDO::FETCH_ASSOC);
                    $teamID = $teamData['id_equipo'];

                    $query3 = "SELECT usuarios.id, usuarios.imagen, usuarios.usuario, usuarios.email FROM usuarios INNER JOIN usuarios_equipos ON usuarios.id = usuarios_equipos.id_usuario AND usuarios_equipos.id_equipo = '$teamID'";
                    $resultado3 = $db->prepare($query3);
                    $resultado3->execute();

                    if ($resultado3->rowCount() > 0) {
                        $arrayResponse['status'] =  true;
                        $arrayResponse['errorCode'] = "";
                        $arrayResponse['response'] = $resultado3->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $arrayResponse['status'] =  false;
                        $arrayResponse['errorCode'] = "1003";
                        $arrayResponse['response'] = "Error al obtener los datos de los usuarios";
                    }
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "Error al obtener id del grupo";
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Error al obtener id usuario";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para cambiar el creador
    $app->post('/replaceCreator', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();

            $idTarea = $postResponse['idTarea'];
            $oldCreatorName = $postResponse['oldCreatorName'];
            $idSustituto = $postResponse['idSustituto'];

            $query = "UPDATE tareas SET idUsuario = '$idSustituto' WHERE id = '$idTarea'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {

                //Hay que obtener el id del antiguo creador para insertarlo en la tabla de usuarios_tareas y así "asignarlo a la tarea"
                $query2 = "SELECT id from usuarios WHERE usuario = '$oldCreatorName'";
                $resultado2 = $db->prepare($query2);
                $resultado2->execute();

                if ($resultado2->rowCount() === 1) {
                    $dataOld = $resultado2->fetch(PDO::FETCH_ASSOC);
                    $oldCreatorID = $dataOld['id'];

                    //Insertamos el que era antiguo creador
                    $query3 = "DELETE FROM usuarios_tareas WHERE id_usuario = '$oldCreatorID'";
                    $resultado3 = $db->prepare($query3);
                    $resultado3->execute();

                    $query4 = "INSERT INTO usuarios_tareas (id_usuario, id_tarea) VALUES ('$idSustituto', '$idTarea')";
                    $resultado4 = $db->prepare($query4);
                    $resultado4->execute();

                    $arrayResponse['status'] =  true;
                    $arrayResponse['errorCode'] = "";
                    $arrayResponse['response'] = "Creador actualizado correctamente";
                } else {
                    $arrayResponse['status'] =  false;
                    $arrayResponse['errorCode'] = "1002";
                    $arrayResponse['response'] = "Error al crear una entrada nueva en usuarios_tareas";
                }
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Error al actualizar el creador";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para eliminar la fecha de entrega de una tarea
    $app->post('/deleteTaskDate', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $idTarea = $postResponse['idTarea'];


            $query = "UPDATE tareas SET fecha = '0000-00-00' WHERE id = '$idTarea'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $arrayResponse['status'] =  true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = "Fecha de la tarea eliminada";
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Error al eliminar la fecha de la tarea";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para cambiar la fecha de una tarea
    $app->post('/changeTaskDate', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $idTarea = $postResponse['idTarea'];
            $newDate = $postResponse['newDate'];
            $finalDate = date("Y-m-d", strtotime($newDate));

            $query = "UPDATE tareas SET fecha = '$finalDate' WHERE id = '$idTarea'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $arrayResponse['status'] =  true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = "Fecha de la tarea cambiada";
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Error al cambiar la fecha de la tarea";
            }
        } catch (Exception $e) {
            $arrayResponse['status'] =  false;
            $arrayResponse['errorCode'] = "1000";
            $arrayResponse['response'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($arrayResponse));
        return $response;
    });

    //Función para buscar una tarea
    $app->post('/searchTask', function ($request, $response) {

        $arrayResponse = [
            "status" => '',
            "errorCode" => '',
            "response" => ''
        ];

        try {
            $db = $this->get('db');
            $postResponse = $request->getParsedBody();
            $text = $postResponse['text'];

            $query = "SELECT * FROM tareas WHERE titulo LIKE '%$text%' || titulo LIKE '%$text' || titulo LIKE '$text%'";
            $resultado = $db->prepare($query);
            $resultado->execute();

            if ($resultado->rowCount() === 1) {
                $arrayResponse['status'] =  true;
                $arrayResponse['errorCode'] = "";
                $arrayResponse['response'] = $resultado->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $arrayResponse['status'] =  false;
                $arrayResponse['errorCode'] = "1001";
                $arrayResponse['response'] = "Ninguna tarea encontrada";
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
