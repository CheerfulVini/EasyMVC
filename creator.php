<?php
ini_set('display_errors',1);
ini_set('display_startup_erros',1);
error_reporting(E_ALL);
class Creator {
    private $con;
    private $servidor ;
    private $banco;
    private $usuario;
    private $senha;
    private $tabelas;

    function __construct() {
        if(isset($_GET['id']))
            $this->buscaBancodeDados();
        else {
            $this->criaDiretorios();
            $this->conectar(1);
            $this->buscaTabelas();
            $this->ClassesModel();
            $this->ClasseConexao();
            $this->ClassesControl();
            $this->classesView();
            $this->ClassesDao();
            $this->buscaTabelas();
            $this->criaIndex();
            $this->compactar();
            header("Location:index.php?msg=2");
        }
    }//fimConsytruct

    function criaIndex(){
        $nomeBanco = $_POST['banco'];
        $tab1 = "";
        $tab2 = "";
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array) $tabela)[0];
            $tab1.="<li><a href='view/{$nomeTabela}.php'>Cadastro de {$nomeTabela}</a></li>\n";
            $tab2.="<li><a href='view/Lista{$nomeTabela}.php'>Relatório de {$nomeTabela}</a></li>\n";
        }
        $conteudo = <<<EOT

<!DOCTYPE html>
<html lang="pt-BR"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    
    <title>Sistema de {$nomeBanco}</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        /* Cabeçalho */
        .cabecalho {
            width: 100%;
            height: 200px;
            background-color: #2c3e50;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
        }

        /* Menu principal */
        .menu {
            width: 100%;
            height: 100px;
            background-color: #34495e;
            display: flex;
            align-items: center;
            padding-left: 20px;
        }

        .menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 40px;
        }

        .menu li {
            position: relative;
        }

        .menu a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            padding: 10px;
            display: block;
        }

        /* Submenu */
        .menu li ul {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #2c3e50;
            list-style: none;
            padding: 0;
            margin: 0;
            min-width: 200px;
        }

        .menu li ul li a {
            padding: 10px;
            font-size: 16px;
        }

        /* Exibir submenu ao passar o mouse */
        .menu li:hover ul {
            display: block;
        }

        /* Conteúdo */
        .conteudo {
            min-height: calc(100vh - 300px); /* altura total - cabeçalho (200) - menu (100) */
            padding: 20px;
            background-color: #ecf0f1;
        }
    </style>
</head>
<body>
<div class="cabecalho">
    Sistema de {$nomeBanco}
</div>

<div class="menu">
    <ul>
        <li>
            <a href="index.php">Cadastros</a>
            <ul>
                {$tab1}
            </ul>
        </li>
        <li>
            <a href="https://ava.ifpr.edu.br/pluginfile.php/638357/mod_assign/intro/index.html#">Relatórios</a>
            <ul>
                {$tab2}
            </ul>
        </li>
    </ul>
</div>

<div class="conteudo">
    <h2>Bem-vindo!</h2>
    <p>Esta é a área de conteúdo do sistema.</p>
</div>


</body></html>

EOT;
    file_put_contents("sistema/index.php", $conteudo);
    }
    function criaDiretorios() {
        $dirs = [
            "sistema",
            "sistema/model",
            "sistema/control",
            "sistema/view",
            "sistema/dao",
            "sistema/css"
        ];

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    header("Location:index.php?msg=0");
                }
            }
        }
        copy('estilos.css','sistema/css/estilos.css');
    }//fimDiretorios
    function conectar($id){
        $this->servidor = $_REQUEST["servidor"];
        $this->usuario = $_REQUEST["usuario"];
        $this->senha = $_REQUEST["senha"];
        if ($id == 1) {
           $this->banco = $_POST["banco"];
        }
        else{
            $this->banco = "mysql";


        }
        try {
            $this->con = new PDO(
                "mysql:host=" . $this->servidor . ";dbname=" . $this->banco,
                $this->usuario,
                $this->senha
            );
        } catch (Exception $e) {

           header("Location:index.php?msg=1");
        }
    }//fimConectar
    function buscaBancodeDados(){
        try {
                $this->conectar(0);
                $sql = "SHOW databases";
                $query = $this->con->query($sql);
                $databases = $query->fetchAll(PDO::FETCH_ASSOC);
                foreach ($databases as $database){
                    echo "<option>".$database["Database"]."</option>";
                }
                $this->con=null;
            }
        catch (Exception $e) {
            header("Location:index.php?msg=3");

        }
    }//uscaBD
    function buscaTabelas(){
       try {
           $sql = "SHOW TABLES";
           $query = $this->con->query($sql);
           $this->tabelas = $query->fetchAll(PDO::FETCH_ASSOC);
       }
       catch (Exception $e) {
           header("Location:index.php?msg=3");
       }
    }//fimBuscaTabelas
    function buscaAtributos($nomeTabela){
        $sql="show columns from ".$nomeTabela;
        $atributos = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
        return $atributos;
    }//fimBuscaAtributos
    function ClassesModel() {
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array) $tabela)[0];
            $atributos=$this->buscaAtributos($nomeTabela);
            $nomeAtributos="";
            $geters_seters="";
            foreach ($atributos as $atributo) {
                $atributo=$atributo->Field;
                $nomeAtributos.="\tprivate \${$atributo};\n";
                $metodo=ucfirst($atributo);
                $geters_seters.="\tfunction get".$metodo."(){\n";
                $geters_seters.="\t\treturn \$this->{$atributo};\n\t}\n";
                $geters_seters.="\tfunction set".$metodo."(\${$atributo}){\n";
                $geters_seters.="\t\t\$this->{$atributo}=\${$atributo};\n\t}\n";
            }
            $nomeTabela=ucfirst($nomeTabela);
            $conteudo = <<<EOT
<?php
class {$nomeTabela} {
{$nomeAtributos}
{$geters_seters}
}
?>
EOT;
            file_put_contents("sistema/model/{$nomeTabela}.php", $conteudo);

        }
    }//fimModel
    function classesView() {
        //formulários
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array) $tabela)[0];
            $atributos=$this->buscaAtributos($nomeTabela);
            $formCampos="";
           foreach ($atributos as $atributo) {
                $atributo=$atributo->Field;
                $formCampos .= "<label for='{$atributo}'>{$atributo}</label>\n";
                $formCampos .= "<input type='text' value='<?php if(isset(\$obj['id'])){print(\$obj['{$atributo}']);}; ?>' name='{$atributo}'><br>\n";
            }
            $conteudo = <<<HTML
<?php
    require_once('../dao/{$nomeTabela}Dao.php');
    \$obj=null;
    if(isset(\$_GET['id'])){
        \$obj=(new {$nomeTabela}Dao())->buscaPorId(\$_GET['id']);
        
    }
    \$acao=\$obj?3:1;
?>
<html>
    <head>
        <title>Cadastro de {$nomeTabela}</title>
        <link rel="stylesheet" href="../css/estilos.css">
    </head>
    <body>
        <form action="../control/{$nomeTabela}Control.php?a=<?php print(\$acao) ?>" method="post">
        <h1>Cadastro de {$nomeTabela}</h1>
            {$formCampos}
             <button type="submit">Enviar</button>
        </form>
    </body>
</html>
HTML;
  file_put_contents("sistema/view/{$nomeTabela}.php", $conteudo); // Exemplo salvando como arquivo
        }
        //Listas
        foreach ($this->tabelas as $tabela) {
             $nomeTabela = array_values((array)$tabela)[0];
             $nomeTabelaUC=ucfirst($nomeTabela);
             $atributos=$this->buscaAtributos($nomeTabela);
             $attr = "";
             foreach($atributos as $atributo){
                $atributo=$atributo->Field;

                $attr.="echo \"<td>{\$dado['{$atributo}']}</td>\";";
              }
            $conteudo="";
            $id = $atributos['id'];
            $conteudo = <<<HTML
            

<html>
    <head>
        <title>Lista de {$nomeTabelaUC}</title>
        <link rel="stylesheet" href="../css/estilos.css">
    </head>
    <body>
        <table border = "1">
      <?php
      require_once("../dao/{$nomeTabelaUC}Dao.php");
   \$dao=new {$nomeTabela}DAO();
   \$dados=\$dao->listaGeral();
    foreach(\$dados as \$dado){
        print("<tr>");
       {$attr};
       print("<td><a href='../control/{$nomeTabela}Control.php?id={\$dado["id"]}&a=2'>excluir</a></td>");
        print("<td><a href='Alterar{$nomeTabela}.php?id={\$dado["id"]}'>alterar</a></td>");
        print("</tr>");
    }
     ?>  
     </table>
    </body>
</html>
HTML;           
  file_put_contents("sistema/view/Lista{$nomeTabela}.php", $conteudo);        
        }

        foreach ($this->tabelas as $tabela) {
             $nomeTabela = array_values((array)$tabela)[0];
             $segundoAttr = array_values((array)$tabela)[1];
             $nomeTabelaUC=ucfirst($nomeTabela);
             $atributos=$this->buscaAtributos($nomeTabela);
             $attr = "";
             foreach($atributos as $atributo){
                $atributo=$atributo->Field;
                $atributoUC = ucfirst($atributo);

                $attr.="\$obj->set{$atributoUC}(\$assoc['{$atributo}']);\n";
              }
            $conteudo="";
            $id = $atributos['id'];
            $conteudo = <<<EOT
<?php
require_once("../model/{$nomeTabela}.php");
require_once("../control/{$nomeTabela}Control.php");
require_once("../dao/{$nomeTabela}Dao.php");

\$dao = new {$nomeTabelaUC}Dao();
\$controller = new {$nomeTabelaUC}Control();

\$msgErro = "";
\$aluno = null;


if(isset(\$_POST['id'])) {
    \$assoc = \$dao->buscaPorId(\$_GET['id']);
    \$obj = new {$nomeTabelaUC}();

    {$attr}

    \$erros = \$controller->alterar(\$obj);
    header("location: Lista{$nomeTabela}.php");
}else {
    
    \$id = 0;
    if(isset(\$_GET["id"]))
        \$id = \$_GET["id"];

    
    \${$nomeTabela} = \$dao->buscaPorId(\$id);

    if(! \${$nomeTabela}) {
        echo "ID do {$nomeTabela} é inválido!<br>";
        echo "<a href='Lista{$nomeTabela}.php'>Voltar</a>";
        exit;
    }
}
include_once("{$nomeTabela}.php");
?>
EOT;
    file_put_contents("sistema/view/Alterar{$nomeTabela}.php", $conteudo);  
        }
    }//fimView
   
function ClasseConexao(){
        $conteudo = <<<EOT

<?php
class Conexao {
    private \$server;
    private \$banco;
    private \$usuario;
    private \$senha;
    function __construct() {
        \$this->server = '{$this->servidor}';
        \$this->banco = '{$this->banco}';
        \$this->usuario = '{$this->usuario}';
        \$this->senha = '{$this->senha}';
    }
    
    function conectar() {
        try {
            \$conn = new PDO(
                "mysql:host=" . \$this->server . ";dbname=" . \$this->banco,\$this->usuario,
                \$this->senha
            );
            return \$conn;
        } catch (Exception \$e) {
            echo "Erro ao conectar com o Banco de dados: " . \$e->getMessage();
        }
    }
}
?>
EOT;
        file_put_contents("sistema/model/conexao.php", $conteudo);
    }//fimConexao
    function ClassesControl(){
        foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array)$tabela)[0];
            $atributos=$this->buscaAtributos($nomeTabela);
            $nomeClasse=ucfirst($nomeTabela);
            $posts="";
            foreach ($atributos as $atributo) {
                $atributo=$atributo->Field;
                $posts.= "\$this->{$nomeTabela}->set".ucFirst($atributo).
                    "(\$_POST['{$atributo}']);\n\t\t";
            }

            $conteudo = <<<EOT
<?php
require_once("../model/{$nomeClasse}.php");
require_once("../dao/{$nomeClasse}Dao.php");
class {$nomeClasse}Control {
    private \${$nomeTabela};
    private \$acao;
    private \$dao;
    public function __construct(){
       \$this->{$nomeTabela}=new {$nomeClasse}();
      \$this->dao=new {$nomeClasse}Dao();
      \$this->acao = "";
      if(isset(\$_GET['a'])){
        \$this->acao=\$_GET["a"];
        }
      \$this->verificaAcao(); 
    }
    function verificaAcao(){
       switch(\$this->acao){
          case 1:
            \$this->inserir();
            header("Location: ../view/Lista{$nomeTabela}.php");
          break;
          case 2:
            \$this->excluir();
            header("Location: ../view/Lista{$nomeTabela}.php");
            break;
        case 3:
            \$this->alterar();
            header("Location: ../view/Lista{$nomeTabela}.php");
            break;
       }
    }
    function inserir(){
        {$posts}
        \$this->dao->inserir(\$this->{$nomeTabela});
    }
    function excluir(){
        \$this->dao->excluir(\$_GET['id']);
    }
    function alterar(){
        {$posts}
        \$this->dao->alterar(\$this->{$nomeTabela});
        header("Location: ../view/Lista{$nomeTabela}.php");
    }
    function buscarId({$nomeClasse} \${$nomeTabela}){}
    function buscaTodos(){}

}
new {$nomeClasse}Control();
?>
EOT;
            file_put_contents("sistema/control/{$nomeTabela}Control.php", $conteudo);
        }

    }//fimControl
    function compactar() {
        $folderToZip = 'sistema';
        $outputZip = 'sistema.zip';
        $zip = new ZipArchive();
        if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return false;
        }
        $folderPath = realpath($folderToZip);  // Corrigido aqui
        if (!is_dir($folderPath)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($folderPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        return $zip->close();
    }//fimCompactar
    
function ClassesDao(){
     foreach ($this->tabelas as $tabela) {
            $nomeTabela = array_values((array)$tabela)[0];
            $nomeClasse = ucfirst($nomeTabela);
            $atributos=$this->buscaAtributos($nomeTabela);
            $atributos = array_map(function($obj) {
             return $obj->Field;
         }, $atributos);
            $sqlCols = implode(', ', $atributos);

            $placeholders = implode(', ', array_fill(0, count($atributos), '?'));
         $vetAtributos=[];
         $AtributosMetodos="";
         $atributosAlterar = "";
         $alterar = "";
         foreach ($atributos as $atributo) {
             $atr=ucfirst($atributo);
             array_push($vetAtributos,"\${$atributo}");
             $AtributosMetodos.="\${$atributo}=\$obj->get{$atr}();\n";
             $atributosAlterar.="\$obj->get{$atr}(), ";
             $alterar.="{$atributo} = ?, ";

         }
         $atributosOk=implode(",",$vetAtributos);
        $alterar = substr($alterar, 0, -2);
         $conteudo = <<<EOT
<?php
require_once("../model/conexao.php");
class {$nomeClasse}Dao {
    private \$con;
    public function __construct(){
       \$this->con=(new Conexao())->conectar();
    }
function inserir(\$obj) {
    \$sql = "INSERT INTO {$nomeTabela} ({$sqlCols}) VALUES ({$placeholders})";
    \$stmt = \$this->con->prepare(\$sql);
    {$AtributosMetodos}
    \$stmt->execute([{$atributosOk}]);
}
function listaGeral(){
    \$sql = "select * from {$nomeTabela}";
    \$query = \$this->con->query(\$sql);
    \$dados = \$query->fetchAll(PDO::FETCH_ASSOC);
    return \$dados;
}
function buscaPorId(\$id){
\$sql = "select * from {$nomeTabela} where id=\$id";
\$query = \$this->con->query(\$sql);
\$dados = \$query->fetch(PDO::FETCH_ASSOC);
return \$dados;
}
function excluir(\$id){
\$sql = "delete from {$nomeTabela} where id=\$id";
\$query = \$this->con->query(\$sql);
\$dados = \$query->fetch(PDO::FETCH_ASSOC);
return NULL;
}
function alterar(\$obj){
    \$sql = "UPDATE {$nomeTabela} SET {$alterar} WHERE id = ?";
    \$stm = \$this->con->prepare(\$sql);
    \$stm->execute([{$atributosAlterar}\$obj->getId()]);
    return NULL;
}
}
?>
EOT;
            file_put_contents("sistema/dao/{$nomeClasse}Dao.php", $conteudo);
        }

    }//fimDao

}
new Creator();
