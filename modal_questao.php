<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Questão</title>
    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 2% auto;
            padding: 0;
            border: none;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-out;
            overflow: hidden;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
        }

        .modal-header h2 {
            margin: 0;
            color: white;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
        }

        .close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-50%) rotate(90deg);
        }

        .modal-body {
            padding: 30px;
            background: white;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        select, input[type="text"], textarea {
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }

        select:focus, input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .alternativas-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .alternativas-grid input {
            margin: 0;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 5px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .radio-option:hover {
            background-color: #f8f9fa;
        }

        .radio-option input[type="radio"] {
            margin: 0;
            accent-color: #667eea;
        }

        button[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
            
            .alternativas-grid {
                grid-template-columns: 1fr;
            }
            
            .radio-group {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div id="modalQuestao" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close" onclick="fecharModal()">&times;</span>
      <h2>Nova Questão</h2>
    </div>
    <div class="modal-body">
      <form method="POST" action="salvar_questao.php">
        <div>
          <label>Curso:</label>
          <select name="curso_id" required>
            <option value="">Selecione um curso</option>
            <?php
            $cursos = $conn->query("SELECT id, nome FROM cursos");
            while ($curso = $cursos->fetch_assoc()) {
              echo "<option value='{$curso['id']}'>{$curso['nome']}</option>";
            }
            ?>
          </select>
        </div>

        <div>
          <label>Matéria:</label>
          <select name="materia_id" required>
            <option value="">Selecione uma matéria</option>
            <?php
            $materias = $conn->query("SELECT id, nome FROM materias ORDER BY nome");
            while ($materia = $materias->fetch_assoc()) {
              echo "<option value='{$materia['id']}'>{$materia['nome']}</option>";
            }
            ?>
          </select>
        </div>

        <div>
          <label>Pergunta:</label>
          <textarea name="pergunta" placeholder="Digite sua pergunta aqui..." required></textarea>
        </div>

        <div>
          <label>Alternativas:</label>
          <div class="alternativas-grid">
            <input type="text" name="resposta_A" placeholder="A) Digite a alternativa A" required>
            <input type="text" name="resposta_B" placeholder="B) Digite a alternativa B" required>
            <input type="text" name="resposta_C" placeholder="C) Digite a alternativa C" required>
            <input type="text" name="resposta_D" placeholder="D) Digite a alternativa D" required>
          </div>
        </div>

        <div>
          <label>Resposta Correta:</label>
          <div class="radio-group">
            <label class="radio-option">
              <input type="radio" name="resposta_correta" value="A" required>
              <span>A</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="resposta_correta" value="B" required>
              <span>B</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="resposta_correta" value="C" required>
              <span>C</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="resposta_correta" value="D" required>
              <span>D</span>
            </label>
          </div>
        </div>

        <div>
          <label>Explicação da Resposta:</label>
          <textarea name="descricao" rows="4" placeholder="Explique por que essa é a resposta correta (opcional)"></textarea>
        </div>
        
        <div>
          <label>Dificuldade:</label>
          <select name="dificuldade">
            <option value="Fácil">Fácil</option>
            <option value="Média" selected>Média</option>
            <option value="Difícil">Difícil</option>
          </select>
        </div>

        <button type="submit">Salvar Questão</button>
      </form>
    </div>
  </div>
</div>

<script>
function fecharModal() {
    document.getElementById('modalQuestao').style.display = 'none';
}

// Fechar modal ao clicar fora dele
window.onclick = function(event) {
    const modal = document.getElementById('modalQuestao');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
function abrirPdfModal(url, titulo) {
    document.getElementById('pdfFrame').src = url.includes('drive.google.com') ? url.replace('/view', '/preview') : url;
    document.getElementById('pdfTitulo').innerText = titulo;
    document.getElementById('pdfModal').style.display = 'block';
}

function fecharModal(id) {
    document.getElementById(id).style.display = 'none';
    if (id === 'pdfModal') {
        document.getElementById('pdfFrame').src = '';
    }
}

</script>
<script>
function carregarMaterias(cursoId, materiaSelecionada = '') {
  const materiaSelect = document.getElementById('materiaSelect');

  fetch('buscar_materias_curso.php?curso_id=' + cursoId)
    .then(response => response.json())
    .then(data => {
      materiaSelect.innerHTML = '<option value="">Selecione a matéria</option>';
      data.materias.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m;
        opt.textContent = m;
        if (m === materiaSelecionada) {
          opt.selected = true;
        }
        materiaSelect.appendChild(opt);
      });
    });
}

// Ao trocar o curso, carregar matérias
document.addEventListener('DOMContentLoaded', () => {
  const cursoSelect = document.getElementById('cursoSelect');
  if (cursoSelect) {
    cursoSelect.addEventListener('change', function () {
      carregarMaterias(this.value);
    });

    // Edição: se já tiver curso selecionado, carrega matérias imediatamente
    if (cursoSelect.value) {
      const materiaAtual = document.getElementById('materiaSelect').getAttribute('data-valor');
      carregarMaterias(cursoSelect.value, materiaAtual);
    }
  }
});
</script>

</body>
</html>