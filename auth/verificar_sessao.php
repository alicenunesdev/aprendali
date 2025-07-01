<?php
session_start();

// Se não estiver logado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    header("Location: login.php");
    exit();
}

// Lista de páginas permitidas para o aluno
$paginas_permitidas_aluno = ['index.php', 'cursos.php', 'ver_materias.php', 'ver_questoes.php', 'perfil.php'];

// Obter nome da página atual
$pagina_atual = basename($_SERVER['PHP_SELF']);

// Se for aluno e a página não estiver na lista, bloqueia
if ($_SESSION['tipo'] == 'aluno' && !in_array($pagina_atual, $paginas_permitidas_aluno)) {
    echo "<script>alert('Acesso negado!'); window.location.href='index.php';</script>";
    exit();
}
