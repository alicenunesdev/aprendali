<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Matérias | Aprendali</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <header>
    <h1>Gerenciar Matérias</h1>
  </header>

  <section class="form-section">
    <form action="" method="POST">
      <select name="curso_id" required>
        <option value="">Selecione o Curso</option>
        <?php
        $cursos = $conn->query("SELECT id, nome FROM cursos");
        while ($curso = $cursos->fetch_assoc()):
        ?>
          <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nome']) ?></option>
        <?php endwhile; ?>
      </select>
      <input type="text" name="nome" placeholder="Nome da Matéria" required>
      <button type="submit">Cadastrar</button>
    </form>
  </section>

  <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $nome = $conn->real_escape_string($_POST['nome']);
      $curso_id = intval($_POST['curso_id']);
      $conn->query("INSERT INTO materias (curso_id, nome) VALUES ($curso_id, '$nome')");
    }

    $result = $conn->query("SELECT m.nome AS materia, c.nome AS curso
                            FROM materias m
                            JOIN cursos c ON m.curso_id = c.id");
  ?>

  <section class="list-section">
    <h2>Matérias Cadastradas</h2>
    <ul>
      <?php while ($row = $result->fetch_assoc()): ?>
        <li><?= htmlspecialchars($row['materia']) ?> (Curso: <?= htmlspecialchars($row['curso']) ?>)</li>
      <?php endwhile; ?>
    </ul>
  </section>
</body>
</html>
