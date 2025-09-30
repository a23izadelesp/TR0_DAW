// URL base para llamar a la API PHP del quiz
const urlBase = "/quiz.php";

// Referencias a los inputs para la URL de la imagen y la subida de archivo
const imageUrlInput = document.getElementById("imatge");
const imageFileInput = document.getElementById("imageFile");

// Control para que solo se pueda usar URL o archivo, no ambos a la vez
imageFileInput.addEventListener("change", () => {
  if (imageFileInput.files.length > 0) {
    // Si hay archivo seleccionado, deshabilita la URL y la limpia
    imageUrlInput.disabled = true;
    imageUrlInput.value = "";
  } else {
    // Si no hay archivo, habilita el input URL
    imageUrlInput.disabled = false;
  }
});

imageUrlInput.addEventListener("input", () => {
  if (imageUrlInput.value.trim().length > 0) {
    // Si hay texto en URL, limpia y deshabilita el input de archivo
    imageFileInput.value = "";  
    imageFileInput.disabled = true;
  } else {
    // Si no hay texto, habilita el input de archivo
    imageFileInput.disabled = false;
  }
});

// Función para listar todas las preguntas desde la API y mostrarlas en la tabla
async function listarPreguntas() {
  const res = await fetch(urlBase + "?action=list");
  const data = await res.json();
  const tbody = document.getElementById("tablaPreguntas");
  tbody.innerHTML = ""; // Limpia la tabla antes de añadir datos nuevos
  // Por cada pregunta crea una fila con datos y botones para editar/borrar
  data.questions.forEach(q => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${q.id}</td>
      <td>${q.question}</td>
      <td>${q.answers.map(a => `<div>${a}</div>`).join('')}</td>
      <td>${q.correctIndex}</td>
      <td><img class="bandera" src="${q.imatge}" alt="Bandera" /></td>
      <td>
        <button class="update" onclick="editarPregunta(${q.id})">Editar</button>
        <button class="delete" onclick="borrarPregunta(${q.id})">Borrar</button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

// Resetea el formulario a su estado inicial
function resetForm() {
  document.getElementById("formPregunta").reset();
  document.getElementById("questionId").value = "";
  // Habilita ambos inputs de imagen por si el usuario quiere volver a elegir
  imageUrlInput.disabled = false;
  imageFileInput.disabled = false;
}

// Carga los datos de una pregunta para editar, llenando el formulario
async function editarPregunta(id) {
  const res = await fetch(urlBase + "?action=list");
  const data = await res.json();
  const pregunta = data.questions.find(q => q.id == id);
  if (!pregunta) return alert("Pregunta no trobada");
  document.getElementById("questionId").value = pregunta.id;
  document.getElementById("questionText").value = pregunta.question;
  document.getElementById("answer1").value = pregunta.answers[0];
  document.getElementById("answer2").value = pregunta.answers[1];
  document.getElementById("answer3").value = pregunta.answers[2];
  document.getElementById("answer4").value = pregunta.answers[3];
  document.getElementById("correctIndex").value = pregunta.correctIndex;
  imageUrlInput.value = pregunta.imatge;
  imageUrlInput.disabled = false;
  imageFileInput.value = "";
  imageFileInput.disabled = false;
}

// Borra una pregunta tras confirmación y refresca la lista
async function borrarPregunta(id) {
  if (!confirm("Eliminar aquesta pregunta?")) return;
  const res = await fetch(urlBase + `?action=delete&id=${id}`, { method: "GET" });
  const data = await res.json();
  if (data.error) {
    alert("Error: " + data.error);
  } else {
    alert(data.success);
    listarPreguntas();
    resetForm();
  }
}

// Maneja el envío del formulario para crear o actualizar preguntas
document.getElementById("formPregunta").addEventListener("submit", async e => {
  e.preventDefault();

  const id = document.getElementById("questionId").value;
  const formElement = document.getElementById("formPregunta");
  const formData = new FormData(formElement);

  // Recolecta las respuestas en un array para enviarlas como JSON
  const answers = [
    document.getElementById("answer1").value,
    document.getElementById("answer2").value,
    document.getElementById("answer3").value,
    document.getElementById("answer4").value
  ];

  // Obtiene valores para controlar exclusividad URL/archivo
  const urlValue = document.getElementById("imatge").value.trim();
  const fileValue = document.getElementById("imageFile").files.length > 0;

  // Valida que no se elijan ambos inputs a la vez
  if (urlValue && fileValue) {
    alert("Debes elegir entre introducir una URL o subir una imagen, no ambas.");
    return;
  }

  // Reemplaza campo answers por el JSON stringificado
  formData.delete("answers");
  formData.append("answers", JSON.stringify(answers));
  formData.set("correctIndex", document.getElementById("correctIndex").value);

  // Define la URL dependiendo si es nueva pregunta o actualización
  let url = urlBase + "?action=create";
  if (id) url = urlBase + "?action=update&id=" + id;

  try {
    // Envía la solicitud POST con el formulario
    const res = await fetch(url, {
      method: "POST",
      body: formData
    });

    const text = await res.text();
    let resp = null;
    try {
      resp = JSON.parse(text);
    } catch (err) {
      alert("Error inesperado del servidor. Consulta consola.");
      console.error("Error parseando JSON:", err);
      return;
    }

    if (resp.error) {
      alert("Error: " + resp.error);
      console.error("Detalles error:", resp);
    } else {
      // Si servidor devuelve URL de imagen nueva, actualiza el input URL
      if (resp.imatge) {
        imageUrlInput.value = resp.imatge;
      }
      alert("Pregunta " + (id ? "actualitzada" : "creada") + " correctament");
      listarPreguntas();
      resetForm();
    }
  } catch (err) {
    alert("Error enviando la solicitud: " + err.message);
  }
});

// Evento para el botón de reset que limpia el formulario
document.getElementById("resetForm").addEventListener("click", e => {
  e.preventDefault();
  resetForm();
});

// Inicialmente lista las preguntas al cargar la página
listarPreguntas();
