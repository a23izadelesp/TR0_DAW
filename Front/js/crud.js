const urlBase = "/quiz.php";

const imageUrlInput = document.getElementById("imatge");
const imageFileInput = document.getElementById("imageFile");

// Control exclusivo URL o archivo
imageFileInput.addEventListener("change", () => {
  if (imageFileInput.files.length > 0) {
    imageUrlInput.disabled = true;
    imageUrlInput.value = "";
  } else {
    imageUrlInput.disabled = false;
  }
});

imageUrlInput.addEventListener("input", () => {
  if (imageUrlInput.value.trim().length > 0) {
    imageFileInput.value = "";  
    imageFileInput.disabled = true;
  } else {
    imageFileInput.disabled = false;
  }
});

async function listarPreguntas() {
  const res = await fetch(urlBase + "?action=list");
  const data = await res.json();
  const tbody = document.getElementById("tablaPreguntas");
  tbody.innerHTML = "";
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

function resetForm() {
  document.getElementById("formPregunta").reset();
  document.getElementById("questionId").value = "";
  imageUrlInput.disabled = false;
  imageFileInput.disabled = false;
}

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

document.getElementById("formPregunta").addEventListener("submit", async e => {
  e.preventDefault();

  const id = document.getElementById("questionId").value;
  const formElement = document.getElementById("formPregunta");
  const formData = new FormData(formElement);

  const answers = [
    document.getElementById("answer1").value,
    document.getElementById("answer2").value,
    document.getElementById("answer3").value,
    document.getElementById("answer4").value
  ];

  const urlValue = document.getElementById("imatge").value.trim();
  const fileValue = document.getElementById("imageFile").files.length > 0;

  if (urlValue && fileValue) {
    alert("Debes elegir entre introducir una URL o subir una imagen, no ambas.");
    return;
  }

  formData.delete("answers");
  formData.append("answers", JSON.stringify(answers));
  formData.set("correctIndex", document.getElementById("correctIndex").value);

  let url = urlBase + "?action=create";
  if (id) url = urlBase + "?action=update&id=" + id;

  try {
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

document.getElementById("resetForm").addEventListener("click", e => {
  e.preventDefault();
  resetForm();
});

listarPreguntas();
