<?php
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/App.php';
require __DIR__ . '/Auth.php';
$app = AppFactory::create();
// Defina o token válido que será utilizado pelo middleware
$bearerTokenMiddleware = new BearerTokenMiddleware();
// Configurações do Google Drive API
$credentialsPath = __DIR__ . '/credentials.json'; // Caminho para o arquivo credentials.json
$tokenPath = __DIR__ . '/token.json'; // Caminho para salvar o token gerado
$driveAPI = new GoogleDriveAPI($credentialsPath, $tokenPath);
// Rotas sem middleware (exceções)
$app->get('/', function ($request, $response) use ($driveAPI, $tokenPath) {
    if (!file_exists($tokenPath)) {
        $authUrl = $driveAPI->getAuthUrl();
        $htmlContent = file_get_contents(__DIR__ . '/public/auth.html');
        $htmlContent = str_replace('{{ authUrl }}', $authUrl, $htmlContent);
        $response->getBody()->write($htmlContent);
    } else {
        $response->getBody()->write("Google Drive API is ready to use.");
    }
    return $response;
});

$app->get('/callback', function ($request, $response, $args) use ($driveAPI) {
    $queryParams = $request->getQueryParams();
    if (isset($queryParams['code'])) {
        $driveAPI->authenticate($queryParams['code']);
        $response->getBody()->write("Authorization successful. You can now use the API.");
    } else {
        $response->getBody()->write("Authorization failed.");
    }
    return $response;
});

$app->group('/api', function (RouteCollectorProxy $group) use ($driveAPI) {

    // Rota para listar arquivos em uma pasta específica
    $group->get('/files/{folderId}', function ($request, $response, $args) use ($driveAPI) {
        $folderId = $args['folderId'];
        try {
            $files = $driveAPI->listFilesInFolder($folderId);
            $response->getBody()->write(json_encode($files));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                "status" => 'error',
                "message" => 'Não foi possivel listar os arquivos'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    });

    // Rota para listar todas as pastas
    $group->get('/folders', function ($request, $response) use ($driveAPI) {
        $folders = $driveAPI->listAllFolders();
        $response->getBody()->write(json_encode($folders));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Rota para upload de arquivo para uma pasta específica
    $group->post('/upload/{folderId}', function ($request, $response, $args) use ($driveAPI) {
        $folderId = $args['folderId'];
        $data = $request->getParsedBody(); // Obtém o corpo da requisição JSON
        try {
            // Verifica se os campos 'content' e 'filename' estão presentes no JSON
            if (isset($data['content']) && isset($data['filename'])) {
                $fileContentBase64 = $data['content']; // Conteúdo do arquivo em base64
                $fileName = $data['filename']; // Nome do arquivo com a extensão

                // Decodifica o conteúdo em base64
                $fileContent = base64_decode($fileContentBase64);
                if ($fileContent === false) {
                    $response->getBody()->write(json_encode(["error" => "Falha ao decodificar o conteúdo do arquivo."]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }

                // Faz o upload diretamente para o Google Drive
                $fileId = $driveAPI->uploadFileToFolder($fileContent, $fileName, $folderId);

                $response->getBody()->write(json_encode(["message" => "Arquivo enviado com sucesso.", "fileId" => $fileId]));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $response->getBody()->write(json_encode(["error" => "Dados inválidos. Certifique-se de que 'content' e 'filename' estão presentes."]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                "status" => 'error',
                "message" => 'Não foi realizar upload do arquivo'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    });

    // Rota para deletar um arquivo pelo seu ID
    $group->get('/delete/{fileId}', function ($request, $response, $args) use ($driveAPI) {
        $fileId = $args['fileId'];
        try {
            $driveAPI->deleteFile($fileId);
            $response->getBody()->write(json_encode(["message" => "Arquivo deletado com sucesso."]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                "status" => 'error',
                "message" => 'Não foi possivel deletar o arquivo'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    });

})->add($bearerTokenMiddleware); // Aplica o middleware ao grupo de rotas com o prefixo /api
// Middleware de erro
$app->addBodyParsingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, false);
$app->run();


