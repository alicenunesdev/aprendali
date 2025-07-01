<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.top-nav {
  background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
  padding: 18px 32px;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 
    0 1px 3px rgba(0, 0, 0, 0.06),
    0 8px 24px rgba(0, 0, 0, 0.04);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  position: sticky;
  top: 0;
  z-index: 1000;
  backdrop-filter: blur(12px) saturate(180%);
  position: relative;
}

.logo {
  font-size: 1.75rem;
  font-weight: 700;
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 50%, #1d4ed8 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  letter-spacing: -0.8px;
  text-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
  position: relative;
}

.logo::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 50%;
  transform: translateX(-50%) scaleX(0);
  width: 100%;
  height: 2px;
  background: linear-gradient(90deg, #2563eb, #1d4ed8);
  border-radius: 1px;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.logo:hover::after {
  transform: translateX(-50%) scaleX(1);
}

.logo:hover {
  transform: scale(1.03) translateY(-1px);
  filter: brightness(1.1);
}

.menu-toggle {
  position: absolute;
  left: 32px;
  top: 50%;
  transform: translateY(-50%);
  width: 36px;
  height: 36px;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1001;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background: rgba(248, 250, 252, 0.8);
  border: 1px solid rgba(226, 232, 240, 0.6);
  backdrop-filter: blur(8px);
}

.menu-toggle:hover {
  background: rgba(241, 245, 249, 0.9);
  border-color: rgba(203, 213, 225, 0.8);
  transform: translateY(-50%) scale(1.05);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.menu-toggle:active {
  transform: translateY(-50%) scale(0.98);
}

.hamburger-line {
  width: 18px;
  height: 2px;
  background: linear-gradient(90deg, #475569, #334155);
  margin: 2px 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 2px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.menu-toggle:hover .hamburger-line {
  background: linear-gradient(90deg, #334155, #1e293b);
  transform: scaleX(1.1);
}

.menu-toggle.active .hamburger-line:nth-child(1) {
  transform: rotate(45deg) translate(5px, 5px);
  background: linear-gradient(90deg, #dc2626, #b91c1c);
}

.menu-toggle.active .hamburger-line:nth-child(2) {
  opacity: 0;
  transform: scale(0);
}

.menu-toggle.active .hamburger-line:nth-child(3) {
  transform: rotate(-45deg) translate(7px, -6px);
  background: linear-gradient(90deg, #dc2626, #b91c1c);
}

.nav-links {
  position: fixed;
  top: 0;
  left: -320px;
  width: 300px;
  height: 100vh;
  background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
  backdrop-filter: blur(24px) saturate(180%);
  flex-direction: column;
  gap: 0;
  padding: 90px 24px 24px;
  transition: left 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  box-shadow: 
    4px 0 24px rgba(0, 0, 0, 0.08),
    0 0 0 1px rgba(226, 232, 240, 0.6);
  z-index: 1000;
  display: flex;
  overflow-y: auto;
}

.nav-links::-webkit-scrollbar {
  width: 4px;
}

.nav-links::-webkit-scrollbar-track {
  background: transparent;
}

.nav-links::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.3);
  border-radius: 2px;
}

.nav-links::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.5);
}

.nav-links.active {
  left: 0;
}

.nav-links a {
  text-decoration: none;
  color: #475569;
  font-weight: 500;
  font-size: 0.95rem;
  padding: 16px 20px;
  border-radius: 12px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  border: 1px solid transparent;
  background: rgba(255, 255, 255, 0.5);
  text-align: left;
  display: block;
  letter-spacing: 0.1px;
  backdrop-filter: blur(8px);
}

.nav-links a::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
  transition: left 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: -1;
}

.nav-links a:hover::before {
  left: 100%;
}

.nav-links a:hover {
  color: #1e40af;
  background: rgba(37, 99, 235, 0.08);
  border-color: rgba(37, 99, 235, 0.2);
  transform: translateX(6px) scale(1.02);
  box-shadow: 
    0 4px 12px rgba(37, 99, 235, 0.15),
    0 2px 4px rgba(0, 0, 0, 0.05);
}

.nav-links a:active {
  transform: translateX(3px) scale(0.98);
  background: rgba(37, 99, 235, 0.12);
}

/* Seções da navegação */
.nav-section {
  margin-bottom: 24px;
}

.nav-section-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 8px;
  padding: 0 20px;
  position: relative;
}

.nav-section-title::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 20px;
  right: 20px;
  height: 1px;
  background: linear-gradient(90deg, #2563eb, rgba(37, 99, 235, 0.3), transparent);
}

.nav-section a {
  margin-bottom: 4px;
}

/* Footer da navegação */
.nav-footer {
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid rgba(226, 232, 240, 0.5);
}

.nav-credits {
  font-size: 0.7rem;
  color: #64748b;
  text-align: center;
  line-height: 1.4;
  padding: 8px 12px;
  background: rgba(248, 250, 252, 0.6);
  border-radius: 8px;
  border: 1px solid rgba(226, 232, 240, 0.4);
}

.nav-credits strong {
  color: #2563eb;
  font-weight: 600;
}

.page-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(6px) saturate(120%);
  z-index: 999;
  opacity: 0;
  visibility: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-overlay.active {
  opacity: 1;
  visibility: visible;
}

@media (max-width: 768px) {
  .top-nav {
    padding: 16px 24px;
  }

  .menu-toggle {
    left: 24px;
    width: 34px;
    height: 34px;
  }

  .nav-links {
    width: 280px;
    padding: 80px 20px 32px;
  }

  .nav-links a {
    padding: 14px 18px;
    font-size: 0.9rem;
  }

  .logo {
    font-size: 1.6rem;
  }
}

@keyframes slideInFromLeft {
  from {
    opacity: 0;
    transform: translateX(-30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateX(0) scale(1);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.nav-links.active .nav-section {
  animation: slideInFromLeft 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.nav-links.active .nav-section:nth-child(1) { animation-delay: 0.05s; }
.nav-links.active .nav-section:nth-child(2) { animation-delay: 0.15s; }
.nav-links.active .nav-section:nth-child(3) { animation-delay: 0.25s; }

.nav-links.active .nav-footer {
  animation: fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
  animation-delay: 0.35s;
}

/* Efeito de brilho no logo */
@keyframes shimmer {
  0% { background-position: -200% center; }
  100% { background-position: 200% center; }
}

.logo:hover {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 25%, #60a5fa 50%, #3b82f6 75%, #1e40af 100%);
  background-size: 200% auto;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: shimmer 2s linear infinite;
}

/* Indicador visual para links ativos */
.nav-links a.active {
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.15) 0%, rgba(29, 78, 216, 0.1) 100%);
  color: #1e40af;
  border-color: rgba(37, 99, 235, 0.3);
  font-weight: 600;
}

.nav-links a.active::after {
  content: '';
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 4px;
  background: #2563eb;
  border-radius: 50%;
  box-shadow: 0 0 8px rgba(37, 99, 235, 0.6);
}
</style>

<header class="top-nav">
  <button class="menu-toggle" id="menuToggle" onclick="toggleMenu()">
    <div class="hamburger-line"></div>
    <div class="hamburger-line"></div>
    <div class="hamburger-line"></div>
  </button>
  <div class="logo">AprendaAli</div>

  <nav class="nav-links" id="navLinks">
    <div class="nav-section">
      <div class="nav-section-title">Área Principal</div>
      <a href="index.php">Início</a>
      <a href="cursos.php">Cursos</a>
      <a href="ver_questoes.php">Simulados</a>
    </div>

    <?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'admin'): ?>
      <div class="nav-section">
        <div class="nav-section-title">Área de Gerenciamento</div>
        <a href="adicionar_materia.php">Gerenciar Matérias</a>
        <a href="gerenciar_questoes.php">Gerenciar Questões</a>
        <a href="ver_materias.php">Matérias</a>
        <a href="dashboard_aluno.php">Dashboard Alunos</a>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'professor'): ?>
      <div class="nav-section">
        <div class="nav-section-title">Área do Professor</div>
        <a href="adicionar_materia.php">Gerenciar Matérias</a>
        <a href="gerenciar_questoes.php">Gerenciar Questões</a>
        <a href="ver_materias.php">Matérias</a>
        <a href="dashboard_aluno.php">Dashboard Alunos</a>
      </div>
    <?php endif; ?>

    <div class="nav-section">
      <div class="nav-section-title">Área de Configuração</div>
      <a href="perfil.php">Perfil</a>
      <a href="auth/logout.php">Sair</a>
    </div>

    <div class="nav-footer">
      <div class="nav-credits">
        Criado por <strong>Alice Nunes</strong> e <strong>Vilcimar Santos</strong>
      </div>
    </div>
  </nav>
</header>

<div class="page-overlay" id="pageOverlay" onclick="closeMenu()"></div>

<script>
function toggleMenu() {
  const navLinks = document.getElementById('navLinks');
  const pageOverlay = document.getElementById('pageOverlay');
  const menuToggle = document.getElementById('menuToggle');
  
  navLinks.classList.toggle('active');
  pageOverlay.classList.toggle('active');
  menuToggle.classList.toggle('active');
  
  document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : 'auto';
}

function closeMenu() {
  const navLinks = document.getElementById('navLinks');
  const pageOverlay = document.getElementById('pageOverlay');
  const menuToggle = document.getElementById('menuToggle');
  
  navLinks.classList.remove('active');
  pageOverlay.classList.remove('active');
  menuToggle.classList.remove('active');
  document.body.style.overflow = 'auto';
}

document.addEventListener('click', function(event) {
  const navLinks = document.getElementById('navLinks');
  const menuToggle = document.getElementById('menuToggle');
  
  if (!navLinks.contains(event.target) && !menuToggle.contains(event.target) && navLinks.classList.contains('active')) {
    closeMenu();
  }
});

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    const navLinks = document.getElementById('navLinks');
    if (navLinks.classList.contains('active')) {
      closeMenu();
    }
  }
});

window.addEventListener('resize', function() {
  if (window.innerWidth > 768) {
    closeMenu();
  }
});

// Função para destacar link ativo baseado na URL atual
document.addEventListener('DOMContentLoaded', function() {
  const currentPage = window.location.pathname.split('/').pop();
  const navLinks = document.querySelectorAll('.nav-links a');
  
  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (href === currentPage || (currentPage === '' && href === 'index.php')) {
      link.classList.add('active');
    }
  });
});
</script>