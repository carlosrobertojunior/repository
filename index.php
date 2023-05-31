

<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cooperativa";

// Conecta ao banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Função para calcular o saldo total do cooperado
function calcularSaldoTotal($cooperado_id) {
    global $conn;
    $saldo_total = 0;

    // Calcula o saldo total considerando depósitos
    $sql_depositos = "SELECT SUM(valor) AS total_depositos FROM transacoes WHERE cooperado_id = '$cooperado_id' AND tipo = 'Depósito'";
    $result_depositos = $conn->query($sql_depositos);
    $row_depositos = $result_depositos->fetch_assoc();
    $saldo_total += $row_depositos["total_depositos"];

    // Calcula o saldo total considerando saques
    $sql_saques = "SELECT SUM(valor) AS total_saques FROM transacoes WHERE cooperado_id = '$cooperado_id' AND tipo = 'Saque'";
    $result_saques = $conn->query($sql_saques);
    $row_saques = $result_saques->fetch_assoc();
    $saldo_total -= $row_saques["total_saques"];

    return $saldo_total;
}

// Cria a tabela "cooperados" caso não exista
$sql_create_table_cooperados = "CREATE TABLE IF NOT EXISTS cooperados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL UNIQUE,
    saldo_total DECIMAL(10, 2) NOT NULL DEFAULT 0
)";
$conn->query($sql_create_table_cooperados);

// Cria a tabela "transacoes" caso não exista
$sql_create_table_transacoes = "CREATE TABLE IF NOT EXISTS transacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cooperado_id INT NOT NULL,
    tipo VARCHAR(10) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data DATE NOT NULL,
    FOREIGN KEY (cooperado_id) REFERENCES cooperados(id)
)";
$conn->query($sql_create_table_transacoes);

// Operação de cadastro de cooperado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cadastro_cooperado"])) {
    $nome_cooperado = $_POST["nome_cooperado"];

    // Verifica se já existe um cooperado com o mesmo nome
    $sql_verifica_cooperado = "SELECT id FROM cooperados WHERE nome = '$nome_cooperado'";
    $result_verifica_cooperado = $conn->query($sql_verifica_cooperado);

    if ($result_verifica_cooperado->num_rows > 0) {
        echo "Já existe um cooperado com o mesmo nome.";
    } else {
        // Insere o cooperado no banco de dados
        $sql_cadastro_cooperado = "INSERT INTO cooperados (nome) VALUES ('$nome_cooperado')";

        if ($conn->query($sql_cadastro_cooperado) === true) {
            echo "Cooperado cadastrado com sucesso!";
        } else {
            echo "Erro ao cadastrar cooperado: " . $conn->error;
        }
    }
}

// Operação de edição de dados do cooperado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["editar_dados"])) {
    $cooperado_id = $_POST["cooperado_id"];
    $novo_nome_cooperado = $_POST["novo_nome_cooperado"];

    // Verifica se já existe um cooperado com o mesmo nome
    $sql_verifica_cooperado = "SELECT id FROM cooperados WHERE nome = '$novo_nome_cooperado' AND id != '$cooperado_id'";
    $result_verifica_cooperado = $conn->query($sql_verifica_cooperado);

    if ($result_verifica_cooperado->num_rows > 0) {
        echo "Já existe um cooperado com o mesmo nome.";
    } else {
        // Atualiza o nome do cooperado no banco de dados
        $sql_atualizar_dados = "UPDATE cooperados SET nome = '$novo_nome_cooperado' WHERE id = '$cooperado_id'";

        if ($conn->query($sql_atualizar_dados) === true) {
            echo "Dados do cooperado atualizados com sucesso!";
        } else {
            echo "Erro ao atualizar dados do cooperado: " . $conn->error;
        }
    }
}

// Operação de lançamento de depósito
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["lancar_deposito"])) {
    $cooperado_id = $_POST["cooperado_id"];
    $valor_deposito = $_POST["valor_deposito"];
    $data_deposito = $_POST["data_deposito"];

    // Insere o depósito no banco de dados
    $sql_lancar_deposito = "INSERT INTO transacoes (cooperado_id, tipo, valor, data) VALUES ('$cooperado_id', 'Depósito', '$valor_deposito', '$data_deposito')";

    if ($conn->query($sql_lancar_deposito) === true) {
        echo "Depósito lançado com sucesso!";
    } else {
        echo "Erro ao lançar depósito: " . $conn->error;
    }
}

// Operação de lançamento de saque
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["lancar_saque"])) {
    $cooperado_id = $_POST["cooperado_id"];
    $valor_saque = $_POST["valor_saque"];
    $data_saque = $_POST["data_saque"];

    // Verifica se o cooperado possui saldo suficiente para o saque
    $saldo_total = calcularSaldoTotal($cooperado_id);

    if ($saldo_total >= $valor_saque) {
        // Insere o saque no banco de dados
        $sql_lancar_saque = "INSERT INTO transacoes (cooperado_id, tipo, valor, data) VALUES ('$cooperado_id', 'Saque', '$valor_saque', '$data_saque')";

        if ($conn->query($sql_lancar_saque) === true) {
            echo "Saque lançado com sucesso!";
        } else {
            echo "Erro ao lançar saque: " . $conn->error;
        }
    } else {
        echo "Saldo insuficiente para o saque.";
    }
}

// Operação de exclusão da conta do cooperado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["excluir_conta"])) {
    $cooperado_id = $_POST["cooperado_id"];

    // Exclui o cooperado do banco de dados
    $sql_excluir_cooperado = "DELETE FROM cooperados WHERE id = '$cooperado_id'";

    if ($conn->query($sql_excluir_cooperado) === true) {
        echo "Conta do cooperado excluída com sucesso!";
    } else {
        echo "Erro ao excluir conta do cooperado: " . $conn->error;
    }
}

// Operação de emissão do extrato
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["emitir_extrato"])) {
    $cooperado_id = $_POST["cooperado_id"];

    // Obtém as transações do cooperado do banco de dados
    $sql_extrato = "SELECT * FROM transacoes WHERE cooperado_id = '$cooperado_id' ORDER BY data";
    $result_extrato = $conn->query($sql_extrato);

    if ($result_extrato->num_rows > 0) {
        echo "Extrato do Cooperado:<br>";
        echo "--------------------------------<br>";

        while ($row = $result_extrato->fetch_assoc()) {
            echo "Tipo: " . $row["tipo"] . "<br>";
            echo "Valor: " . $row["valor"] . "<br>";
            echo "Data: " . $row["data"] . "<br>";
            echo "--------------------------------<br>";
        }

        // Calcula o saldo total
        $saldo_total = calcularSaldoTotal($cooperado_id);
        echo "Saldo Total: " . $saldo_total . "<br>";
    } else {
        echo "Não há transações para exibir.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Controle de Finanças - Cooperativa</title>
</head>
<body>
<h2>Cadastro de Cooperado</h2>
    <form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
        <input type="text" name="nome_cooperado" placeholder="Nome do Cooperado" required>
        <input type="submit" name="cadastro_cooperado" value="Cadastrar">
    </form>

    <h2>Edição de Dados</h2>
    <form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
        <select name="cooperado_id" required>
            <option value="" disabled selected>Selecione o Cooperado</option>
          
        </select>
        <input type="text" name="novo_nome_cooperado" placeholder="Novo Nome" required>
        <input type="submit" name="editar_dados" value="Editar">
    </form>

    <h2>Lançamento de Depósito</h2>
    <form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
        <select name="cooperado_id" required>
            <option value="" disabled selected>Selecione o Cooperado</option>
            <?php
            $sql_cooperados = "SELECT * FROM cooperados";
            $result_cooperados = $conn->query($sql_cooperados);

            if ($result_cooperados->num_rows > 0) {
                while ($row = $result_cooperados->fetch_assoc()) {
                    echo "<option value='" . $row["id"] . "'>" . $row["nome"] . "</option>";
                }
            }
            ?>
        </select>
        <input type="text" name="valor_deposito" placeholder="Valor do Depósito" required>
        <input type="date" name="data_deposito" required>
        <input type="submit" name="lancar_deposito" value="Lançar Depósito">
    </form>

    <h2>Lançamento de Saque</h2>
    <form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
        <select name="cooperado_id" required>
            <option value="" disabled selected>Selecione o Cooperado</option>
            <?php
            $sql_cooperados = "SELECT * FROM cooperados";
            $result_cooperados = $conn->query($sql_cooperados);

            if ($result_cooperados->num_rows > 0) {
                while ($row = $result_cooperados->fetch_assoc()) {
                    echo "<option value='" . $row["id"] . "'>" . $row["nome"] . "</option>";
                }
            }
            ?>
        </select>
        <input type="text" name="valor_saque" placeholder="Valor do Saque" required>
        <input type="date" name="data_saque" required>
        <input type="submit" name="lancar_saque" value="Lançar Saque">
    </form>

    <h2>Exclusão de Conta</h2>
    <form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
        <select name="cooperado_id" required>
            <option value="" disabled selected>Selecione o Cooperado</option>
            <?php
            $sql_cooperados = "SELECT * FROM cooperados";
            $result_cooperados = $conn->query($sql_cooperados);

            if ($result_cooperados->num_rows > 0) {
                while ($row = $result_cooperados->fetch_assoc()) {
                    echo "<option value='" . $row["id"] . "'>" . $row["nome"] . "</option>";
                }
            }
            ?>
        </select>
        <input type="submit" name="excluir_conta" value="Excluir Conta">
    </form>

    <h2>Emissão do Extrato</h2>
    <form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
        <select name="cooperado_id" required>
            <option value="" disabled selected>Selecione o Cooperado</option>
            <?php
            $sql_cooperados = "SELECT * FROM cooperados";
            $result_cooperados = $conn->query($sql_cooperados);

            if ($result_cooperados->num_rows > 0) {
                while ($row = $result_cooperados->fetch_assoc()) {
                    echo "<option value='" . $row["id"] . "'>" . $row["nome"] . "</option>";
                }
            }
            ?>
        </select>
        <input type="submit" name="emitir_extrato" value="Emitir Extrato">
    </form>
</body>
</html>


