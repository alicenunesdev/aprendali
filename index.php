<?php
session_start();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>AprendaAli - Plataforma Educacional</title>
  <link rel="stylesheet" href="assets/style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      color: #2c3e50;
      line-height: 1.6;
      margin: 0;
      min-height: 100vh;
    }

    .main-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 40px 30px;
      display: flex;
      flex-direction: column;
      gap: 30px;
    }

    /* Header Section */
    .header-section {
      text-align: center;
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      padding: 50px 40px;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
      margin-bottom: 20px;
    }

    .header-section h1 {
      font-size: 2.5rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 15px;
      letter-spacing: -0.5px;
    }

    .header-section p {
      font-size: 1.1rem;
      color: #6c757d;
      max-width: 600px;
      margin: 0 auto;
      line-height: 1.7;
    }

    /* Stats Section */
    .stats-section {
      background: #ffffff;
      border-radius: 16px;
      padding: 35px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
      border: 1px solid #e9ecef;
      display: flex;
      flex-direction: column;
      gap: 25px;
    }

    .stats-section h2 {
      font-size: 1.8rem;
      font-weight: 600;
      color: #2c3e50;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .stats-section h2::before {
      content: '';
      width: 4px;
      height: 24px;
      background: linear-gradient(135deg, #007bff, #0056b3);
      border-radius: 2px;
    }

    .stats-card {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
      padding: 25px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
      border: none;
    }

    .stats-number {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 8px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .stats-label {
      font-size: 0.95rem;
      opacity: 0.9;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .action-button {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: white;
      border: none;
      padding: 14px 28px;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      text-align: center;
      box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
      letter-spacing: 0.3px;
    }

    .action-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
      background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
    }

    .action-button:active {
      transform: translateY(0);
      box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
    }

    /* Courses Section */
    .courses-section {
      background: #ffffff;
      border-radius: 16px;
      padding: 35px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
      border: 1px solid #e9ecef;
    }

    .courses-section h2 {
      font-size: 1.8rem;
      font-weight: 600;
      color: #2c3e50;
      margin: 0 0 25px 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .courses-section h2::before {
      content: '';
      width: 4px;
      height: 24px;
      background: linear-gradient(135deg, #6f42c1, #5a32a3);
      border-radius: 2px;
    }

    .courses-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .course-item {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      padding: 18px 22px;
      border-radius: 10px;
      border-left: 4px solid #6f42c1;
      transition: all 0.3s ease;
      font-weight: 500;
      color: #495057;
      position: relative;
      overflow: hidden;
    }

    .course-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(111, 66, 193, 0.05), transparent);
      transform: translateX(-100%);
      transition: transform 0.6s ease;
    }

    .course-item:hover::before {
      transform: translateX(100%);
    }

    .course-item:hover {
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      border-left-color: #5a32a3;
      transform: translateX(8px);
      box-shadow: 0 4px 15px rgba(111, 66, 193, 0.1);
    }

    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: #6c757d;
      font-style: italic;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border-radius: 10px;
      border: 2px dashed #dee2e6;
    }

    .empty-state::before {
      content: '📚';
      font-size: 3rem;
      display: block;
      margin-bottom: 15px;
      opacity: 0.5;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .main-content {
        padding: 20px 15px;
        gap: 20px;
      }

      .header-section {
        padding: 30px 20px;
      }

      .header-section h1 {
        font-size: 2rem;
      }

      .stats-section, .courses-section {
        padding: 25px 20px;
      }

      .stats-section h2, .courses-section h2 {
        font-size: 1.5rem;
      }

      .stats-number {
        font-size: 1.8rem;
      }

      .action-button {
        padding: 12px 24px;
        font-size: 0.95rem;
      }

      .course-item {
        padding: 15px 18px;
      }
    }

    @media (max-width: 480px) {
      .header-section h1 {
        font-size: 1.8rem;
      }

      .header-section p {
        font-size: 1rem;
      }

      .stats-section, .courses-section {
        padding: 20px 15px;
      }

      .action-button {
        width: 100%;
        padding: 14px;
      }
    }

    /* Loading Animation */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .main-content > * {
      animation: fadeInUp 0.6s ease-out forwards;
    }

    .main-content > *:nth-child(1) { animation-delay: 0.1s; }
    .main-content > *:nth-child(2) { animation-delay: 0.2s; }
    .main-content > *:nth-child(3) { animation-delay: 0.3s; }
  </style>
</head>
<body>

  <?php include 'menu.php'; ?>

  <main class="main-content">
    <!-- Header Section -->
    <section class="header-section">
      <h1>Plataforma Educacional AprendaAli</h1>
      <p>Transforme conhecimento em oportunidades. Gerencie cursos, organize conteúdos e acompanhe o progresso educacional de forma profissional e eficiente.</p>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
      <h2>Painel de Controle</h2>
      
      <?php
        $cursos = $conn->query("SELECT * FROM cursos");
        $total = $cursos->num_rows;
      ?>
      
      <div class="stats-card">
        <div class="stats-number"><?= $total ?></div>
        <div class="stats-label"><?= $total == 1 ? 'Curso Cadastrado' : 'Cursos Cadastrados' ?></div>
      </div>

    </section>

    <!-- Courses List Section -->
    <section class="courses-section">
      <h2>Cursos Disponíveis</h2>
      
      <?php if ($total > 0): ?>
        <ul class="courses-list">
          <?php 
            // Reset the result pointer
            $cursos->data_seek(0);
            while ($curso = $cursos->fetch_assoc()): 
          ?>
            <li class="course-item">
              <?= htmlspecialchars($curso['nome']) ?>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <div class="empty-state">
          <p>Nenhum curso cadastrado ainda.<br>Comece criando seu primeiro curso!</p>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <script>
    // Script do botão hamburguer
    document.addEventListener("DOMContentLoaded", function() {
      const menuBtn = document.getElementById("menu-toggle");
      const navLinks = document.getElementById("navLinks");

      if (menuBtn && navLinks) {
        menuBtn.addEventListener("click", function() {
          navLinks.classList.toggle("show");
        });
      }

      // Add smooth scroll behavior
      document.documentElement.style.scrollBehavior = 'smooth';
    });
  </script>
</body>
</html>