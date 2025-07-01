<?php
session_start();
include 'db.php';
include 'menu.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Gerenciar Questões</title>
  <link rel="stylesheet" href="assets/style.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #2d3748;
      min-height: 100vh;
    }
    
    .main-content { 
      padding: 40px; 
      max-width: 1400px; 
      margin: 0 auto; 
    }
    
    .header-section { 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      margin-bottom: 40px; 
      background: #fff; 
      padding: 30px 40px; 
      border-radius: 15px; 
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.12);
      border: 1px solid #e2e8f0;
      position: relative;
    }
    
    .header-section::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 40px;
      right: 40px;
      height: 2px;
      background: linear-gradient(90deg, #3182ce, #2b77cb);
      border-radius: 1px;
    }
    
    .header-section h1 { 
      margin: 0; 
      color: #2d3748; 
      font-size: 2.4em; 
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .header-section h1 i {
      color: #3182ce;
      font-size: 0.9em;
    }
    
    button.nova-questao { 
      background: linear-gradient(135deg, #3182ce 0%, #2b77cb 100%); 
      color: white; 
      border: none; 
      padding: 18px 30px; 
      border-radius: 12px; 
      cursor: pointer; 
      font-weight: 600; 
      font-size: 1.1em; 
      box-shadow: 0 4px 20px rgba(49, 130, 206, 0.3);
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }
    
    button.nova-questao:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 25px rgba(49, 130, 206, 0.4);
      border: 2px solid rgba(49, 130, 206, 0.2);
    }
    
    .cards-container { 
      display: grid; 
      grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); 
      gap: 30px; 
    }
    
    .card { 
      background: #fff; 
      border-radius: 15px; 
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.12); 
      padding: 35px; 
      position: relative; 
      transition: all 0.3s ease;
      border: 1px solid #e2e8f0;
      overflow: hidden;
    }
    
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #3182ce, #2b77cb);
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 40px rgba(31, 38, 135, 0.18);
    }
    
    .card h3 { 
      font-size: 1.4em; 
      font-weight: 700; 
      margin-bottom: 25px;
      color: #2d3748;
      line-height: 1.4;
      padding-bottom: 15px;
      border-bottom: 1px solid #e2e8f0;
    }
    
    .card p { 
      margin: 12px 0; 
      background: #f7fafc; 
      padding: 15px 20px; 
      border-radius: 10px;
      border-left: 3px solid #e2e8f0;
      transition: all 0.3s ease;
      font-size: 1.05em;
      line-height: 1.5;
    }
    
    .card p:hover {
      background: #edf2f7;
      border-left-color: #3182ce;
    }
    
    .correta { 
      background: linear-gradient(135deg, #48bb78 0%, #38a169 100%) !important; 
      color: white !important; 
      font-weight: bold !important; 
      position: relative !important;
      border-left: 3px solid #2f855a !important;
      box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
    }
    
    .correta::after { 
      content: '✓'; 
      position: absolute; 
      right: 20px; 
      top: 50%; 
      transform: translateY(-50%); 
      font-size: 1.4em; 
      font-weight: bold;
      background: rgba(255, 255, 255, 0.2);
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .card small {
      display: block;
      margin-top: 20px;
      padding-top: 15px;
      border-top: 1px solid #e2e8f0;
      color: #4a5568;
      font-size: 0.95em;
    }
    
    .card small strong {
      color: #3182ce;
      font-weight: 600;
    }
    
    .card-actions { 
      margin-top: 25px; 
      display: flex; 
      justify-content: center; 
      gap: 15px;
      padding-top: 20px;
      border-top: 1px solid #e2e8f0;
    }
    
    .btn-action { 
      padding: 12px 20px; 
      border-radius: 10px; 
      text-decoration: none; 
      font-weight: 600; 
      display: flex; 
      align-items: center; 
      gap: 8px;
      transition: all 0.3s ease;
      border: 2px solid transparent;
      font-size: 0.95em;
    }
    
    .btn-editar { 
      background: linear-gradient(135deg, #3182ce 0%, #2b77cb 100%); 
      color: white;
      box-shadow: 0 4px 15px rgba(49, 130, 206, 0.3);
    }
    
    .btn-editar:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(49, 130, 206, 0.4);
      border: 2px solid rgba(49, 130, 206, 0.2);
    }
    
    .btn-excluir { 
      background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%); 
      color: white;
      box-shadow: 0 4px 15px rgba(229, 62, 62, 0.3);
    }
    
    .btn-excluir:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4);
      border: 2px solid rgba(229, 62, 62, 0.2);
    }
    
    /* Modal de confirmação */
    .modal-confirm { 
      display: none; 
      position: fixed; 
      top: 0; 
      left: 0; 
      width: 100%; 
      height: 100%; 
      background: rgba(0,0,0,0.6); 
      justify-content: center; 
      align-items: center;
      z-index: 10000;
      backdrop-filter: blur(5px);
    }
    
    .modal-confirm-content { 
      background: #fff; 
      padding: 40px; 
      border-radius: 15px; 
      text-align: center;
      max-width: 450px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      border: 1px solid #e2e8f0;
      position: relative;
    }
    
    .modal-confirm-content::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #3182ce, #2b77cb);
      border-radius: 15px 15px 0 0;
    }
    
    .modal-confirm-content h3 {
      color: #2d3748;
      margin-bottom: 15px;
      font-size: 1.3em;
    }
    
    .modal-confirm-content p {
      color: #4a5568;
      margin-bottom: 25px;
    }
    
    .toast { 
      position: fixed; 
      top: 30px; 
      right: 30px; 
      padding: 18px 28px; 
      border-radius: 12px; 
      color: white; 
      font-weight: 600; 
      z-index: 9999; 
      animation: fadeInOut 4s ease forwards;
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
      border: 1px solid rgba(255,255,255,0.1);
    }
    
    .toast.success { 
      background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); 
    }
    
    .toast.error { 
      background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%); 
    }
    
    .toast.warning { 
      background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); 
    }
    
    @keyframes fadeInOut {
      0% { opacity: 0; transform: translateY(-30px) scale(0.9); }
      10% { opacity: 1; transform: translateY(0) scale(1); }
      90% { opacity: 1; transform: translateY(0) scale(1); }
      100% { opacity: 0; transform: translateY(-30px) scale(0.9); }
    }

    /* Responsividade */
    @media (max-width: 768px) {
      .main-content { padding: 20px; }
      .header-section { 
        flex-direction: column; 
        gap: 20px; 
        padding: 25px; 
      }
      .header-section h1 { font-size: 1.8em; }
      .cards-container { 
        grid-template-columns: 1fr; 
        gap: 20px; 
      }
      .card { padding: 25px; }
      .card-actions { flex-direction: column; }
    }
  </style>
</head>
<body>
  <main class="main-content">
    <div class="header-section">
      <h1><i class="fas fa-question-circle"></i> Gerenciar Questões</h1>
      <button class="nova-questao" onclick="abrirModal()">
        <i class="fas fa-plus"></i> Nova Questão
      </button>
    </div>

    <?php
    if (isset($_SESSION['mensagem'])) {
      $tipo = $_SESSION['tipo_mensagem'] ?? 'success';
      echo "<div class='toast $tipo'>{$_SESSION['mensagem']}</div>";
      unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);
    }
    ?>

    <section class="cards-container">
      <?php
      $result = $conn->query("SELECT q.*, c.nome AS curso_nome FROM questoes q JOIN cursos c ON q.curso_id = c.id ORDER BY q.criado_em DESC");
      while ($row = $result->fetch_assoc()) {
        echo "<div class='card'>";
        echo "<h3>{$row['pergunta']}</h3>";
        foreach (['A', 'B', 'C', 'D'] as $letra) {
          $resposta = $row["resposta_$letra"];
          $classe = $row['resposta_correta'] === $letra ? 'correta' : '';
          echo "<p class='$classe'><strong>$letra)</strong> $resposta</p>";
        }
        echo "<small>Curso: <strong>{$row['curso_nome']}</strong></small>";
        echo "<div class='card-actions'>";
        echo "<a href='editar_questao.php?id={$row['id']}' class='btn-action btn-editar'><i class='fas fa-edit'></i> Editar</a>";
        echo "<button onclick='confirmarExclusao({$row['id']})' class='btn-action btn-excluir'><i class='fas fa-trash'></i> Excluir</button>";
        echo "</div>";
        echo "</div>";
      }
      ?>
    </section>
  </main>

  <!-- Modal de confirmação -->
  <div id="modalConfirm" class="modal-confirm">
    <div class="modal-confirm-content">
      <h3><i class="fas fa-exclamation-triangle" style="color: #ed8936; margin-right: 10px;"></i>Confirmar Exclusão</h3>
      <p>Deseja realmente excluir esta questão? Essa ação não poderá ser desfeita.</p>
      <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">
        <button onclick="excluirQuestao()" class="btn-action btn-excluir">
          <i class="fas fa-trash"></i> Confirmar Exclusão
        </button>
        <button onclick="fecharModalConfirm()" class="btn-action" style="background: linear-gradient(135deg, #718096 0%, #4a5568 100%); color: white;">
          <i class="fas fa-times"></i> Cancelar
        </button>
      </div>
    </div>
  </div>

  <?php include 'modal_questao.php'; ?>

  <script>
    let questaoParaExcluir = null;

    function confirmarExclusao(id) {
      questaoParaExcluir = id;
      document.getElementById('modalConfirm').style.display = 'flex';
    }

    function excluirQuestao() {
      if (questaoParaExcluir) {
        window.location.href = `excluir_questoes.php?id=${questaoParaExcluir}`;
      }
    }

    function fecharModalConfirm() {
      document.getElementById('modalConfirm').style.display = 'none';
      questaoParaExcluir = null;
    }

    function abrirModal() {
      document.getElementById('modalQuestao').style.display = 'block';
    }

    // Fecha modais clicando fora
    window.onclick = function(e) {
      const modal = document.getElementById('modalConfirm');
      if (e.target === modal) {
        fecharModalConfirm();
      }
    };

    // Animação suave ao carregar
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.card');
      cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
          card.style.transition = 'all 0.6s ease';
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, index * 100);
      });
    });
  </script>
</body>
</html>