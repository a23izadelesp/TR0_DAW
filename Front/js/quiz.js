// URL base para llamar a la API PHP del quiz
const API_BASE_URL = "/quiz.php";

// Variables globales para estado del quiz y temporizador
let quizState = null;
let timer = 0;
let intervalId = null;
let startTime = null;

// Clave para guardar el estado completo en localStorage
const QUIZ_STORAGE_KEY = "quizAppState";

// Referencias a elementos DOM importantes
const timerDiv = document.getElementById('timer');
const resultsDiv = document.getElementById("results");
const quizDiv = document.getElementById("quiz");
const navButtons = document.getElementById("navButtons");
const userNameInput = document.getElementById("userNameInput");
const nameSubmitBtn = document.getElementById("nameSubmitBtn");
const nameScreen = document.getElementById("nameScreen");

// Estado inicial: ocultar quiz, resultados, timer; mostrar pantalla de nombre
resultsDiv.style.display = "none";
quizDiv.style.display = "none";
navButtons.style.display = "none";
timerDiv.style.display = "none";
nameScreen.style.display = "block";

// Evento al hacer clic en el botón para iniciar con el nombre del usuario
nameSubmitBtn.addEventListener("click", () => {
  const nombre = userNameInput.value.trim();
  if (!nombre) {
    alert("Si us plau, introdueix un nom per començar.");
    return;
  }
  // Guarda el nombre en localStorage
  localStorage.setItem("quizUsuari", nombre);
  // Oculta pantalla de nombre y muestra quiz y controles
  nameScreen.style.display = "none";
  quizDiv.style.display = "block";
  navButtons.style.display = "flex";
  timerDiv.style.display = "flex";
  // Carga el quiz desde el servidor
  loadQuiz();
});

// Guarda estado completo (quiz + nombre) en localStorage para persistencia
function saveFullState() {
  if (!quizState) return;
  const fullState = {
    quizState,
    nombre: localStorage.getItem("quizUsuari") || null,
  };
  localStorage.setItem(QUIZ_STORAGE_KEY, JSON.stringify(fullState));
}

// Carga estado completo desde localStorage, o null si no existe o inválido
function loadFullState() {
  const stateStr = localStorage.getItem(QUIZ_STORAGE_KEY);
  if (!stateStr) return null;
  try {
    return JSON.parse(stateStr);
  } catch {
    return null;
  }
}

// Reinicia todo el quiz, limpia DOM y estado, elimina datos de localStorage
function resetQuiz() {
  quizState = null;
  resultsDiv.innerHTML = "";
  resultsDiv.style.display = "none";
  quizDiv.style.display = "none";
  navButtons.style.display = "none";
  timerDiv.style.display = "none";
  stopTimer();

  localStorage.removeItem(QUIZ_STORAGE_KEY);
  localStorage.removeItem("quizUsuari");

  userNameInput.value = "";
  nameScreen.style.display = "block";
}

// Al cargar la página intenta restaurar el estado o resetea si no hay datos
window.addEventListener("load", () => {
  const saved = loadFullState();
  if (saved && saved.quizState && !saved.quizState.finished) {
    quizState = saved.quizState;
    const nombre = saved.nombre || "";
    localStorage.setItem("quizUsuari", nombre);
    nameScreen.style.display = "none";
    quizDiv.style.display = "block";
    navButtons.style.display = "flex";
    timerDiv.style.display = "flex";
    renderQuizAll();
    mostrarPregunta(quizState.currentIndex || 0);
    updateButtons();
    iniciarTimer();
  } else {
    resetQuiz();
  }
});

// Carga preguntas desde API y prepara el quiz para jugar
async function loadQuiz() {
  try {
    const res = await fetch(`${API_BASE_URL}?action=load&num=10`);
    if (!res.ok) throw new Error("HTTP error " + res.status);
    const text = await res.text();
    const data = JSON.parse(text);
    quizState = data;
    quizState.finished = false;
    // Inicializa respuestas si no vienen definidas
    if (!Array.isArray(quizState.userAnswers)) {
      quizState.userAnswers = Array(quizState.questions.length).fill(-1);
    }
    quizState.currentIndex = 0;
    iniciarTimer();
    renderQuizAll();
    mostrarPregunta(0);
    updateButtons();
    resultsDiv.style.display = "none";
  } catch (e) {
    quizDiv.innerText = "Error carregant partida: " + e.message;
  }
}

// Renderiza todas las preguntas y opciones en el DOM, ocultando todas excepto la actual
function renderQuizAll() {
  if (!quizState || !quizState.questions) {
    quizDiv.textContent = "No hi ha preguntes per mostrar.";
    return;
  }
  quizDiv.innerHTML = quizState.questions
    .map(
      (q, idx) => `
    <div class="preguntaBloc${idx === quizState.currentIndex ? "" : " amaga"}" data-idx="${idx}">
      <div class="question-card">
        <div class="bandera-imatge">
          <img src="${q.imatge}" alt="Bandera per a la pregunta ${idx + 1}" width="150" height="100" />
        </div>
        <strong>Pregunta ${idx + 1} de ${quizState.questions.length}:</strong>
        <br>${q.question}
      </div>
      <div class="answers">
        ${q.answers
          .map(
            (ans, i) => `
            <label>
              <input type="radio" name="answer${idx}" value="${i}" ${
              quizState.userAnswers[idx] === i ? "checked" : ""
            } tabindex="0" aria-label="Resposta ${i + 1}: ${ans}" />
              ${ans}
            </label>
          `
          )
          .join("")}
      </div>
    </div>
  `
    )
    .join("");
}

// Escucha cambios en las respuestas del quiz para actualizar el estado y guardarlo
quizDiv.addEventListener("change", async (e) => {
  if (e.target.matches("input[type=radio]")) {
    const bloc = e.target.closest(".preguntaBloc");
    if (!bloc) return;
    const idx = parseInt(bloc.dataset.idx, 10);
    const ans = parseInt(e.target.value, 10);
    if (quizState) {
      quizState.userAnswers[idx] = ans;
      await saveState();
    }
  }
});

// Actualiza visibilidad y texto de botones anterior/siguiente según el estado actual
function updateButtons() {
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");
  if (!quizState) {
    navButtons.style.display = "none";
    return;
  }
  if (quizState.finished) {
    navButtons.style.display = "none";
    return;
  }
  navButtons.style.display = "flex";
  prevBtn.style.display = quizState.currentIndex === 0 ? "none" : "inline-block";
  nextBtn.textContent =
    quizState.currentIndex === quizState.questions.length - 1
      ? "Enviar ✅"
      : "Següent ➡️";
}

// Botón para ir a la pregunta anterior, solo si existe y no está terminado
document.getElementById("prevBtn").addEventListener("click", async () => {
  if (!quizState || quizState.finished) return;
  if (quizState.currentIndex > 0) {
    quizState.currentIndex--;
    await saveState();
    mostrarPregunta(quizState.currentIndex);
  }
});

// Botón para ir a la siguiente pregunta o enviar el quiz si es la última
document.getElementById("nextBtn").addEventListener("click", async () => {
  if (!quizState || quizState.finished) return;
  if (quizState.userAnswers[quizState.currentIndex] === -1) {
    alert("Selecciona una resposta abans de continuar.");
    return;
  }
  if (quizState.currentIndex === quizState.questions.length - 1) {
    openConfirmDialog();
  } else {
    quizState.currentIndex++;
    await saveState();
    mostrarPregunta(quizState.currentIndex);
  }
});

// Botón para reiniciar el quiz, enviando acción de terminar al backend y limpiando estado
document.getElementById("restartBtn").addEventListener("click", async () => {
  await fetch(`${API_BASE_URL}?action=finish`, { method: "POST" });
  resetQuiz();
});

// Función para resetear el estado y la pantalla (reducida también anteriormente para recarga)
function resetQuiz() {
  quizState = null;
  resultsDiv.innerHTML = "";
  resultsDiv.style.display = "none";
  quizDiv.style.display = "none";
  navButtons.style.display = "none";
  timerDiv.style.display = "none";
  stopTimer();

  localStorage.removeItem(QUIZ_STORAGE_KEY);
  localStorage.removeItem("quizUsuari");

  userNameInput.value = "";
  nameScreen.style.display = "block";
}

// Muestra solo la pregunta del índice dado y oculta el resto
function mostrarPregunta(idx) {
  if (!quizState) return;
  quizState.currentIndex = idx;
  document.querySelectorAll(".preguntaBloc").forEach((div, i) => {
    div.classList.toggle("amaga", i !== idx);
  });
  updateButtons();
}

// Navegación con flechas del teclado para anterior y siguiente en el quiz
window.addEventListener("keydown", (e) => {
  if (!quizState || quizState.finished) return;
  if (e.key === "ArrowRight" && quizState.currentIndex < quizState.questions.length - 1) {
    document.getElementById("nextBtn").click();
  }
  if (e.key === "ArrowLeft" && quizState.currentIndex > 0) {
    document.getElementById("prevBtn").click();
  }
});

// Actualiza y guarda el estado actual del quiz al backend y localStorage
async function saveState() {
  if (!quizState) return;
  await fetch(`${API_BASE_URL}?action=updateState`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      userAnswers: quizState.userAnswers,
      currentIndex: quizState.currentIndex,
    }),
  });
  saveFullState();
}

// Abre el diálogo de confirmación para enviar el quiz
function openConfirmDialog() {
  document.getElementById("confirmDialog").style.display = "flex";
}

// Ejecuta acción según la confirmación: enviar el quiz o cancelar
function confirmFinish(send) {
  document.getElementById("confirmDialog").style.display = "none";
  if (send) finishQuiz();
}

// Eventos para botones dentro del diálogo de confirmación
document.getElementById("confirmYes").addEventListener("click", () => confirmFinish(true));
document.getElementById("confirmNo").addEventListener("click", () => confirmFinish(false));

// Envía la señal de fin al backend, oculta quiz y muestra resultados finales
async function finishQuiz() {
  if (!quizState) return;
  quizState.finished = true;
  stopTimer();
  navButtons.style.display = "none";
  quizDiv.style.display = "none";
  timerDiv.style.display = "none";
  const res = await fetch(`${API_BASE_URL}?action=finish`, { method: "POST" });
  const data = await res.json();
  renderFinalResults(data);
}

// Muestra los resultados finales con detalle por pregunta y mensaje personalizado
function renderFinalResults(data) {
  resultsDiv.style.display = "block";
  const nombre = localStorage.getItem("quizUsuari") || "Jugador";
  const totalPreguntas = data.total;
  const acertadas = data.correctes;
  const porcentaje = (acertadas / totalPreguntas) * 100;
  let mensaje = "";
  if (porcentaje === 100) {
    mensaje = "¡Perfecte! Ets un expert en banderes.";
  } else if (porcentaje >= 70) {
    mensaje = "Molt bé! Tens un bon coneixement, però encara pots millorar.";
  } else if (porcentaje >= 40) {
    mensaje = "No està malament, però et recomanem estudiar una mica més les banderes.";
  } else {
    mensaje = "No és el millor resultat, però no et rendeixis, la pràctica fa el mestre!";
  }
  resultsDiv.innerHTML = `
    <h2>Resultat final</h2>
    <p>Hola <strong>${nombre}</strong>, has encertat <strong>${acertadas}</strong> de ${totalPreguntas} preguntes.</p>
    <p>${mensaje}</p>
    <div class="final-list"><table>
      <tr>
        <th>Bandera</th>
        <th>Pregunta</th>
        <th>La teva resposta</th>
        <th>Correcte?</th>
      </tr>
      ${data.results
        .map((r, i) => {
          const ua = r.yourAnswer;
          const correcte = r.correcte;
          const answerText = ua !== -1 ? r.answers[ua] : "<em>No respost</em>";
          return `<tr>
            <td><img src="${r.imatge}" alt="Bandera ${i + 1}" width="40"/></td>
            <td>${r.question}</td>
            <td class="${correcte ? "answer-correct" : "answer-wrong"}">${answerText}</td>
            <td>${correcte ? "✅" : "❌"} (${r.answers[r.correctIndex]})</td>
          </tr>`;
        })
        .join("")}
    </table></div>
    <button id="restartBtnFinal" class="next-final">Jugar un altre cop</button>
  `;
  document.getElementById("restartBtnFinal").onclick = () => {
    resetQuiz();
  };
}

// ----- Temporizador -----

// Inicia el cronómetro desde cero actualizando el tiempo mostrado cada 0.5 segundos
function iniciarTimer() {
  timer = 0;
  startTime = Date.now();
  updateTimerDisplay();
  clearInterval(intervalId);
  intervalId = setInterval(() => {
    timer = Math.floor((Date.now() - startTime) / 1000);
    updateTimerDisplay();
  }, 500);
}

// Actualiza el texto del timer en pantalla
function updateTimerDisplay() {
  timerDiv.textContent = `Temps: ${timer} s`;
}

// Para el cronómetro y limpia el intervalo
function stopTimer() {
  clearInterval(intervalId);
}
