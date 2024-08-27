<?php
/**
 * Autor: Kevin Alencar
 * Email: kevinalencar2019@gmail.com
 */

include("conexao.php");

// Verifica se há uma solicitação de exclusão de arquivo
if(isset($_GET['deletar'])) {
    $id = intval($_GET['deletar']); // Converte o ID do arquivo para um número inteiro
    $sql_query = $mysqli->query("SELECT * FROM arquivos WHERE id = '$id'") or die($mysqli->error); // Seleciona o arquivo no banco de dados pelo ID
    $arquivo = $sql_query->fetch_assoc(); // Obtém os dados do arquivo

    // Se o arquivo for excluído do sistema de arquivos
    if(unlink($arquivo['path'])) {
        $deu_certo = $mysqli->query("DELETE FROM arquivos WHERE id = '$id'") or die($mysqli->error); // Exclui o registro do arquivo no banco de dados
        if($deu_certo) {
            echo "<p>Arquivo excluído com sucesso!</p>"; // Exibe mensagem de sucesso
        }
    }
}

// Função para enviar um arquivo
function enviarArquivo($error, $size, $name, $tmp_name) {
    include("conexao.php"); // Inclui a conexão com o banco de dados

    if($error) // Verifica se houve erro no envio do arquivo
        die("Falha ao enviar arquivo");

    if($size > 2097152) // Verifica se o arquivo é maior que 2MB
        die("Arquivo muito grande!! Max: 2MB");

    $pasta = "arquivos/"; // Define o diretório de upload
    $nomeDoArquivo = $name; // Obtém o nome original do arquivo
    $novoNomeDoArquivo = uniqid(); // Gera um novo nome único para o arquivo
    $extensao = strtolower(pathinfo($nomeDoArquivo, PATHINFO_EXTENSION)); // Obtém a extensão do arquivo

    // Verifica se o tipo de arquivo é aceito
    if($extensao != "pdf" && $extensao != "txt")
        die("Tipo de arquivo não aceito");

    $path = $pasta . $novoNomeDoArquivo . "." . $extensao; // Cria o caminho completo do arquivo
    $deu_certo = move_uploaded_file($tmp_name, $path); // Move o arquivo para o diretório de destino

    if ($deu_certo) {
        // Insere os dados do arquivo no banco de dados
        $mysqli->query("INSERT INTO arquivos (nome, path) VALUES('$nomeDoArquivo', '$path')") or die($mysqli->error);
        return true; // Retorna sucesso
    } else {
        return false; // Retorna falha
    }
}

// Verifica se há arquivos enviados pelo formulário
if(isset($_FILES['arquivos'])) {
    $arquivos = $_FILES['arquivos']; // Obtém os arquivos enviados
    $tudo_certo = true; // Flag para verificar se todos os arquivos foram enviados com sucesso

    // Percorre todos os arquivos enviados
    foreach($arquivos['name'] as $index => $arq) {
        $deu_certo =  enviarArquivo(
            $arquivos['error'][$index], 
            $arquivos['size'][$index], 
            $arquivos['name'][$index], 
            $arquivos["tmp_name"][$index]
        );

        if (!$deu_certo) {
            $tudo_certo = false; // Se algum arquivo falhar, marca como falso
        }
    }

    // Exibe mensagem conforme o resultado do upload
    if($tudo_certo)
        echo "Todos os arquivos foram enviados com sucesso!";
    else
        echo "Falha ao enviar alguns arquivos.";
}

// Consulta os arquivos já armazenados no banco de dados
$sql_query = $mysqli->query("SELECT * FROM arquivos") or die ($mysqli->error);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar uploads</title>
</head>
<body>
    <!-- Formulário para envio de arquivos -->
    <form method="POST" enctype="multipart/form-data" action="">
        <label for="arquivo">Selecione o arquivo</label>
        <input multiple type="file" id="arquivo" name="arquivos[]" ><p></p>
        <button name="upload" type="submit">Enviar</button>
    </form>

    <!-- Tabela que exibe a lista de arquivos já enviados -->
    <h2>Lista de arquivos</h2>
    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>Preview</th>
                <th>Arquivo</th>
                <th>Data de envio</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
        <?php
            // Loop para exibir os arquivos na tabela
            while($arquivo = $sql_query->fetch_assoc()){
        ?>
            <tr>
                <td>
                    <?php 
                    $extensao = pathinfo($arquivo['path'], PATHINFO_EXTENSION); // Obtém a extensão do arquivo
                    echo strtoupper($extensao); // Exibe a extensão do arquivo
                    ?>
                </td>
                <td><a target="_blank" href="<?php echo $arquivo['path']; ?>"><?php echo $arquivo['nome']; ?></a></td>
                <td><?php echo date("d/m/Y H:i", strtotime($arquivo['data_upload'])); ?></td>
                <td><a href="index.php?deletar=<?php echo $arquivo['id']; ?>">Deletar</a></td>
            </tr>
        <?php
            }
        ?>
        </tbody>
    </table>
</body>
</html>
