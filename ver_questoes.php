<?php
session_start();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Responder Questões</title>
  <link rel="stylesheet" href="assets/style.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
  <style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #2c3e50;
    line-height: 1.6;
    margin: 0;
    padding-top: 0px;
  }

  .main-container {
    display: flex;
    justify-content: center;
    padding: 20px;
  }

  .popup {
    background: #ffffff;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    max-width: 700px;
    width: 100%;
    animation: slideIn 0.5s ease-out;
    border: 1px solid #e3f2fd;
  }

  @keyframes slideIn {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
  }

  h3 {
    text-align: center;
    margin-bottom: 20px;
    color: #1976d2;
  }

  .filtros-container {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
  }

  .filtro-grupo {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .filtro-grupo label {
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
  }

  .filtro-grupo select {
    padding: 8px 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: white;
    font-size: 0.9rem;
    min-width: 150px;
    transition: border-color 0.3s;
  }

  .filtro-grupo select:focus {
    outline: none;
    border-color: #1976d2;
  }

  .btn-filtrar {
    padding: 8px 16px;
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.3s;
    margin-top: 20px;
  }

  .btn-filtrar:hover {
    background: #1565c0;
  }

  .btn-limpar {
    padding: 8px 16px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.3s;
    margin-top: 20px;
    margin-left: 10px;
  }

  .btn-limpar:hover {
    background: #5a6268;
  }

  #lista-questoes button {
    width: 100%;
    margin: 5px 0;
    padding: 12px;
    border: none;
    border-radius: 10px;
    background: #1e40af;
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    text-align: left;
    position: relative;
  }

  #lista-questoes button:hover {
    background: #1e40af;
    transform: translateY(-1px);
  }

  .questao-info {
    font-size: 0.8rem;
    opacity: 0.8;
    margin-top: 5px;
  }

  .questao-texto {
    margin: 20px 0;
    font-size: 1.1rem;
    font-weight: 500;
  }

  .resposta-btn {
    display: block;
    width: 100%;
    margin: 8px 0;
    padding: 12px;
    border-radius: 10px;
    border: none;
    background: rgba(37, 99, 235, 0.6);
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    text-align: left;
  }

  .resposta-btn:hover {
    background: rgba(37, 99, 235, 0.8);
  }

  .resposta-correta {
    background: #d4edda !important;
    border: 2px solid #28a745 !important;
    color: #155724 !important;
  }

  .resposta-errada {
    background: #f8d7da !important;
    border: 2px solid #dc3545 !important;
    color: #721c24 !important;
  }

  .descricao {
    margin-top: 15px;
    padding: 10px;
    background: #f1f1f1;
    border-left: 4px solid #007bff;
    border-radius: 8px;
    font-size: 0.95rem;
  }

  .loading {
    text-align: center;
    color: #007bff;
    font-weight: 500;
    margin-top: 20px;
  }

  .no-questions {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px dashed #dee2e6;
  }

  .error-message {
    text-align: center;
    color: #dc3545;
    font-weight: 500;
    margin: 20px 0;
    padding: 15px;
    background: #f8d7da;
    border-radius: 10px;
    border: 1px solid #f5c6cb;
  }

  .questao-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e9ecef;
  }

  .questao-meta {
    display: flex;
    gap: 20px;
    font-size: 0.9rem;
    color: #6c757d;
  }

  .meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
  }

  @media (max-width: 600px) {
    .filtros-container {
      flex-direction: column;
      align-items: stretch;
    }
    
    .filtro-grupo {
      width: 100%;
    }
    
    .filtro-grupo select {
      min-width: auto;
      width: 100%;
    }
  }
</style>

  <script>
    let questoes = [];
    let indice = 0;
    let filtros = {};

    function carregarFiltros() {
      fetch('carregar_filtros.php')
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            console.error('Erro ao carregar filtros:', data.error);
            return;
          }
          
          filtros = data;
          montarFiltros();
        })
        .catch(error => {
          console.error('Erro ao carregar filtros:', error);
        });
    }

    function montarFiltros() {
      const selectMateria = document.getElementById('filtro-materia');
      const selectCurso = document.getElementById('filtro-curso');
      
      // Limpar options existentes
      selectMateria.innerHTML = '<option value="">Todas as matérias</option>';
      selectCurso.innerHTML = '<option value="">Todos os cursos</option>';
      
      // Adicionar matérias
      filtros.materias.forEach(materia => {
        const option = document.createElement('option');
        option.value = materia;
        option.textContent = materia;
        selectMateria.appendChild(option);
      });
      
      // Adicionar cursos
      filtros.cursos.forEach(curso => {
        const option = document.createElement('option');
        option.value = curso.id;
        option.textContent = curso.nome;
        selectCurso.appendChild(option);
      });
    }

    function carregarQuestoes() {
      document.querySelector('.loading').style.display = 'block';
      
      const materiaFiltro = document.getElementById('filtro-materia').value;
      const cursoFiltro = document.getElementById('filtro-curso').value;
      
      let url = 'carregar_questoes.php?';
      const params = new URLSearchParams();
      
      if (materiaFiltro) params.append('materia', materiaFiltro);
      if (cursoFiltro) params.append('curso_id', cursoFiltro);
      
      fetch(url + params.toString())
        .then(res => res.json())
        .then(data => {
          document.querySelector('.loading').style.display = 'none';
          
          // Verificar se há erro na resposta
          if (data.error) {
            mostrarErro(data.error);
            return;
          }
          
          questoes = data;
          
          if (questoes.length === 0) {
            mostrarSemQuestoes();
          } else {
            montarListaQuestoes();
          }
        })
        .catch(error => {
          console.error('Erro ao carregar questões:', error);
          document.querySelector('.loading').style.display = 'none';
          mostrarErro('Erro ao carregar questões. Tente novamente.');
        });
    }

    function limparFiltros() {
      document.getElementById('filtro-materia').value = '';
      document.getElementById('filtro-curso').value = '';
      carregarQuestoes();
    }

    function mostrarErro(mensagem) {
      const lista = document.getElementById('lista-questoes');
      lista.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i> ${mensagem}</div>`;
    }

    function mostrarSemQuestoes() {
      const lista = document.getElementById('lista-questoes');
      lista.innerHTML = `
        <div class="no-questions">
          <i class="fas fa-info-circle"></i><br>
          Nenhuma questão encontrada com os filtros aplicados.<br>
          <small>Tente alterar os filtros ou entre em contato com seu professor.</small>
        </div>
      `;
    }

    function montarListaQuestoes() {
      const lista = document.getElementById('lista-questoes');
      lista.innerHTML = '';
      questoes.forEach((q, i) => {
        const botao = document.createElement('button');
        botao.innerHTML = `
          <div>Questão ${i + 1}: ${q.pergunta.slice(0, 60)}...</div>
          <div class="questao-info">
            <i class="fas fa-book"></i> ${q.curso_nome || 'Curso'} 
            ${q.materia ? `| <i class="fas fa-tag"></i> ${q.materia}` : ''} 
            | <i class="fas fa-signal"></i> ${q.dificuldade || 'N/A'}
          </div>
        `;
        botao.onclick = () => {
          indice = i;
          mostrarQuestao();
        };
        lista.appendChild(botao);
      });
    }

    function mostrarQuestao() {
      const q = questoes[indice];
      
      // Mostrar header da questão
      const header = document.getElementById('questao-header');
      header.innerHTML = `
        <div>
          <h4 style="margin: 0; color: #1976d2;">Questão ${indice + 1} de ${questoes.length}</h4>
        </div>
        <div class="questao-meta">
          <div class="meta-item">
            <i class="fas fa-book"></i>
            <span>${q.curso_nome || 'Curso'}</span>
          </div>
          ${q.materia ? `
            <div class="meta-item">
              <i class="fas fa-tag"></i>
              <span>${q.materia}</span>
            </div>
          ` : ''}
          <div class="meta-item">
            <i class="fas fa-signal"></i>
            <span>${q.dificuldade || 'N/A'}</span>
          </div>
        </div>
      `;
      
      document.getElementById("pergunta").textContent = q.pergunta;
      document.getElementById("descricao").textContent = "";

      ['A', 'B', 'C', 'D'].forEach(letra => {
        const btn = document.getElementById(`resposta_${letra}`);
        btn.textContent = `${letra}) ${q[`resposta_${letra}`]}`;
        btn.className = 'resposta-btn';
        btn.onclick = () => verificarResposta(letra, q.resposta_correta, q.descricao);
      });
      
      // Esconder lista de questões e mostrar questão
      document.getElementById('lista-questoes').style.display = 'none';
      document.getElementById('area-questao').style.display = 'block';
      document.getElementById('btn-voltar').style.display = 'inline-block';
    }

    function voltarLista() {
      document.getElementById('lista-questoes').style.display = 'block';
      document.getElementById('area-questao').style.display = 'none';
      document.getElementById('btn-voltar').style.display = 'none';
    }

    function verificarResposta(selecionada, correta, descricao) {
      ['A', 'B', 'C', 'D'].forEach(letra => {
        const btn = document.getElementById(`resposta_${letra}`);
        if (letra === correta.toUpperCase()) {
          btn.classList.add('resposta-correta');
        } else if (letra === selecionada) {
          btn.classList.add('resposta-errada');
        }
        btn.onclick = null;
      });
      document.getElementById("descricao").textContent = descricao;
    }

    document.addEventListener("DOMContentLoaded", () => {
      carregarFiltros();
      carregarQuestoes();
    });
  </script>
</head>
<body>
  <?php include 'menu.php'; ?>
  <div class="main-container">
    <div class="popup">
      <h3>Questões do Curso</h3>

      <!-- Filtros -->
      <div class="filtros-container">
        <div class="filtro-grupo">
          <label for="filtro-materia">Matéria:</label>
          <select id="filtro-materia">
            <option value="">Todas as matérias</option>
          </select>
        </div>
        
        <div class="filtro-grupo">
          <label for="filtro-curso">Curso:</label>
          <select id="filtro-curso">
            <option value="">Todos os cursos</option>
          </select>
        </div>
        
        <div style="display: flex; gap: 10px;">
          <button class="btn-filtrar" onclick="carregarQuestoes()">
            <i class="fas fa-filter"></i> Filtrar
          </button>
          <button class="btn-limpar" onclick="limparFiltros()">
            <i class="fas fa-times"></i> Limpar
          </button>
        </div>
      </div>

      <!-- Lista de questões -->
      <div id="lista-questoes"></div>

      <!-- Área da questão individual -->
      <div id="area-questao" style="display: none;">
        <div id="questao-header" class="questao-header"></div>
        
        <div id="pergunta" class="questao-texto">Selecione uma questão acima.</div>

        <button id="resposta_A" class="resposta-btn">A</button>
        <button id="resposta_B" class="resposta-btn">B</button>
        <button id="resposta_C" class="resposta-btn">C</button>
        <button id="resposta_D" class="resposta-btn">D</button>

        <div class="descricao" id="descricao"></div>
      </div>

      <button id="btn-voltar" class="btn-filtrar" onclick="voltarLista()" style="display: none; margin-top: 20px;">
        <i class="fas fa-arrow-left"></i> Voltar à lista
      </button>

      <div class="loading" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Carregando questões...</div>
    </div>
  </div>
</body>
</html>