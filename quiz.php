<?php
// Configura el nivel de reporte de errores para ignorar E_WARNING y E_NOTICE
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// Oculta la visualización directa de errores en el navegador
ini_set('display_errors', 0);

// Inicia la sesión para gestionar el estado del quiz por usuario
session_start();

// Configura los encabezados para responder en JSON y permitir peticiones CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST');

// Parámetros de conexión a la base de datos
$servername = "localhost"; 
$username = "a23izadelesp_quizdb";
$password = "0473a4LwT1JmkZGr_";
$dbname = "a23izadelesp_quizdb";

// JSON con las preguntas iniciales: cada objeto tiene id, pregunta, respuestas, índice correcto e imagen
$questionsJson = <<<JSON
[ 
    {"id":1,"question":"De quin país és aquesta bandera?","answers":["Països Baixos","França","Rússia","Itàlia"],"correctIndex":1,"imatge":"https://upload.wikimedia.org/wikipedia/en/c/c3/Flag_of_France.svg"},
    {"id":2,"question":"De quin país és aquesta bandera?","answers":["Alemanya","Polònia","Bèlgica","Espanya"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/en/b/ba/Flag_of_Germany.svg"},
    {"id":3,"question":"De quin país és aquesta bandera?","answers":["Irlanda","Itàlia","Bulgària","Hongria"],"correctIndex":1,"imatge":"https://upload.wikimedia.org/wikipedia/en/0/03/Flag_of_Italy.svg"},
    {"id":4,"question":"De quin país és aquesta bandera?","answers":["Andorra","Portugal","Espanya","Romania"],"correctIndex":2,"imatge":"https://upload.wikimedia.org/wikipedia/commons/9/9a/Flag_of_Spain.svg"},
    {"id":5,"question":"De quin país és aquesta bandera?","answers":["Austràlia","Regne Unit","Estats Units","Nova Zelanda"],"correctIndex":1,"imatge":"https://upload.wikimedia.org/wikipedia/en/a/ae/Flag_of_the_United_Kingdom.svg"},
    {"id":6,"question":"De quin país és aquesta bandera?","answers":["Brasil","Itàlia","Portugal","Espanya"],"correctIndex":2,"imatge":"https://upload.wikimedia.org/wikipedia/commons/5/5c/Flag_of_Portugal.svg"},
    {"id":7,"question":"De quin país és aquesta bandera?","answers":["Alemanya","Bèlgica","Suïssa","Països Baixos"],"correctIndex":1,"imatge":"https://upload.wikimedia.org/wikipedia/commons/6/65/Flag_of_Belgium.svg"},
    {"id":8,"question":"De quin país és aquesta bandera?","answers":["França","Països Baixos","Luxemburg","Noruega"],"correctIndex":1,"imatge":"https://upload.wikimedia.org/wikipedia/commons/2/20/Flag_of_the_Netherlands.svg"},
    {"id":9,"question":"De quin país és aquesta bandera?","answers":["Dinamarca","Noruega","Suïssa","Àustria"],"correctIndex":2,"imatge":"https://upload.wikimedia.org/wikipedia/commons/f/f3/Flag_of_Switzerland.svg"},
    {"id":10,"question":"De quin país és aquesta bandera?","answers":["Àustria","Hongria","Polònia","Suïssa"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/4/41/Flag_of_Austria.svg"},
    {"id":11,"question":"De quin país és aquesta bandera?","answers":["Indonèsia","Polònia","Mònaco","Àustria"],"correctIndex":1,"imatge":"https://upload.wikimedia.org/wikipedia/en/1/12/Flag_of_Poland.svg"},
    {"id":12,"question":"De quin país és aquesta bandera?","answers":["Dinamarca","Noruega","Islàndia","Suècia"],"correctIndex":1,"imatge":"https://upload.wikimedia.org/wikipedia/commons/d/d9/Flag_of_Norway.svg"},
    {"id":13,"question":"De quin país és aquesta bandera?","answers":["Finlàndia","Noruega","Islàndia","Suècia"],"correctIndex":3,"imatge":"https://upload.wikimedia.org/wikipedia/en/4/4c/Flag_of_Sweden.svg"},
    {"id":14,"question":"De quin país és aquesta bandera?","answers":["Noruega","Dinamarca","Suècia","Finlàndia"],"correctIndex":1,"imatge":"https://upload.wikimedia.org/wikipedia/commons/9/9c/Flag_of_Denmark.svg"},
    {"id":15,"question":"De quin país és aquesta bandera?","answers":["Finlàndia","Estònia","Noruega","Suècia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/b/bc/Flag_of_Finland.svg"},
    {"id":16,"question":"De quin país és aquesta bandera?","answers":["Islàndia","Noruega","Finlàndia","Suècia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/c/ce/Flag_of_Iceland.svg"},
    {"id":17,"question":"De quin país és aquesta bandera?","answers":["Grècia","Albània","Turquia","Xipre"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/5/5c/Flag_of_Greece.svg"},
    {"id":18,"question":"De quin país és aquesta bandera?","answers":["Turquia","Albània","Grècia","Xipre"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/b/b4/Flag_of_Turkey.svg"},
    {"id":19,"question":"De quin país és aquesta bandera?","answers":["Kosovo","Montenegro","Albània","Sèrbia"],"correctIndex":2,"imatge":"https://upload.wikimedia.org/wikipedia/commons/3/36/Flag_of_Albania.svg"},
    {"id":20,"question":"De quin país és aquesta bandera?","answers":["Malta","Turquia","Grècia","Xipre"],"correctIndex":3,"imatge":"https://upload.wikimedia.org/wikipedia/commons/d/d4/Flag_of_Cyprus.svg"},
    {"id":21,"question":"De quin país és aquesta bandera?","answers":["Malta","Grècia","Xipre","Itàlia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/7/73/Flag_of_Malta.svg"},
    {"id":22,"question":"De quin país és aquesta bandera?","answers":["Romania","Andorra","Txèquia","Moldàvia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/7/73/Flag_of_Romania.svg"},
    {"id":23,"question":"De quin país és aquesta bandera?","answers":["Bulgària","Polònia","Hongria","Itàlia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/9/9a/Flag_of_Bulgaria.svg"},
    {"id":24,"question":"De quin país és aquesta bandera?","answers":["Hongria","Bulgària","Polònia","Itàlia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/c/c1/Flag_of_Hungary.svg"},
    {"id":25,"question":"De quin país és aquesta bandera?","answers":["Eslovàquia","Txèquia","Eslovènia","Croàcia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/e/e6/Flag_of_Slovakia.svg"},
    {"id":26,"question":"De quin país és aquesta bandera?","answers":["Eslovènia","Eslovàquia","Croàcia","Bòsnia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/f/f0/Flag_of_Slovenia.svg"},
    {"id":27,"question":"De quin país és aquesta bandera?","answers":["Croàcia","Eslovènia","Bòsnia","Sèrbia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/1/1b/Flag_of_Croatia.svg"},
    {"id":28,"question":"De quin país és aquesta bandera?","answers":["Montenegro","Sèrbia","Kosovo","Bòsnia"],"correctIndex":1,"imatge":"https://upload.wikimedia.org/wikipedia/commons/f/ff/Flag_of_Serbia.svg"},
    {"id":29,"question":"De quin país és aquesta bandera?","answers":["Montenegro","Kosovo","Sèrbia","Bòsnia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/6/64/Flag_of_Montenegro.svg"},
    {"id":30,"question":"De quin país és aquesta bandera?","answers":["Kosovo","Montenegro","Albània","Sèrbia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/1/1f/Flag_of_Kosovo.svg"},
    {"id":33,"question":"De quin país és aquesta bandera?","answers":["Estònia","Letònia","Finlàndia","Lituània"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/8/8f/Flag_of_Estonia.svg"},
    {"id":34,"question":"De quin país és aquesta bandera?","answers":["Letònia","Estònia","Finlàndia","Lituània"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/8/84/Flag_of_Latvia.svg"},
    {"id":35,"question":"De quin país és aquesta bandera?","answers":["Lituània","Letònia","Estònia","Polònia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/1/11/Flag_of_Lithuania.svg"},
    {"id":36,"question":"De quin país és aquesta bandera?","answers":["Moldàvia","Romania","Ucraïna","Rússia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/2/27/Flag_of_Moldova.svg"},
    {"id":37,"question":"De quin país és aquesta bandera?","answers":["Ucraïna","Rússia","Bielorússia","Polònia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/4/49/Flag_of_Ukraine.svg"},
    {"id":38,"question":"De quin país és aquesta bandera?","answers":["Bielorússia","Rússia","Ucraïna","Polònia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/8/85/Flag_of_Belarus.svg"},
    {"id":39,"question":"De quin país és aquesta bandera?","answers":["Rússia","Bielorússia","Ucraïna","Estònia"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/en/f/f3/Flag_of_Russia.svg"},
    {"id":40,"question":"De quin país és aquesta bandera?","answers":["Andorra","Espanya","França","Portugal"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/1/19/Flag_of_Andorra.svg"},
    {"id":41,"question":"De quin país és aquesta bandera?","answers":["Liechtenstein","Àustria","Suïssa","Alemanya"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/4/47/Flag_of_Liechtenstein.svg"},
    {"id":42,"question":"De quin país és aquesta bandera?","answers":["Luxemburg","Països Baixos","Bèlgica","França"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/d/da/Flag_of_Luxembourg.svg"},
    {"id":43,"question":"De quin país és aquesta bandera?","answers":["Mònaco","França","Itàlia","Espanya"],"correctIndex":0,"imatge":"https://upload.wikimedia.org/wikipedia/commons/e/ea/Flag_of_Monaco.svg"}
]
JSON;

// Decodifica el JSON de preguntas en un array asociativo
$questionsSeed = json_decode($questionsJson, true);

try {
    // Conexión a MySQL con PDO solo para crear la base de datos si no existe
    $pdo = new PDO("mysql:host=$servername", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    // Crea la base de datos con codificación UTF-8 si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Conexión a la base de datos recién creada y fijando modo de obtención asociativo
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Crea la tabla de preguntas si no existe (con los campos necesarios)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer1 VARCHAR(255) NOT NULL,
            answer2 VARCHAR(255) NOT NULL,
            answer3 VARCHAR(255) NOT NULL,
            answer4 VARCHAR(255) NOT NULL,
            correctIndex INT NOT NULL,
            imatge VARCHAR(255) NOT NULL
        )
    ");

    // Comprueba si la tabla está vacía; si es así, inserta las preguntas desde el JSON seed
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
    $row = $stmt->fetch();
    if ($row['total'] == 0) {
        $stmtInsert = $pdo->prepare("INSERT INTO questions (question, answer1, answer2, answer3, answer4, correctIndex, imatge) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($questionsSeed as $q) {
            $stmtInsert->execute([
                $q['question'],
                $q['answers'][0],
                $q['answers'][1],
                $q['answers'][2],
                $q['answers'][3],
                $q['correctIndex'],
                $q['imatge']
            ]);
        }
    }
} catch (PDOException $e) {
    // Si hay error de base de datos devuelve HTTP 500 y error en JSON
    http_response_code(500);
    echo json_encode(["error" => "DB Error: " . $e->getMessage()]);
    exit;
}

// Determina el método HTTP y la acción enviada
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Procesa peticiones GET (leer información)
if ($method === "GET") {
    // Cargar estado del quiz (acción load)
    if ($action === "load") {
        // Si hay estado previo en sesión, lo devuelve
        if (isset($_SESSION['quiz_state'])) {
            echo json_encode($_SESSION['quiz_state']);
            exit;
        }
        // Obtiene número de preguntas (por defecto 10)
        $count = intval($_GET['num'] ?? 10);
        // Selecciona preguntas aleatorias de la base de datos
        $stmt = $pdo->query("SELECT * FROM questions ORDER BY RAND() LIMIT $count");
        $selected = $stmt->fetchAll();
        // Prepara el estado inicial del quiz
        $quizState = [
            "questions" => array_map(fn($q) => [
                "id" => $q["id"],
                "question" => $q["question"],
                "answers" => [$q["answer1"], $q["answer2"], $q["answer3"], $q["answer4"]],
                "correctIndex" => intval($q["correctIndex"]),
                "imatge" => $q["imatge"]
            ], $selected),
            "userAnswers" => array_fill(0, count($selected), -1), // -1 para no respondidas
            "currentIndex" => 0,
            "finished" => false,
        ];
        // Guarda el estado en sesión y lo devuelve
        $_SESSION['quiz_state'] = $quizState;
        echo json_encode($quizState);
        exit;
    }

    // Lista todas las preguntas (acción list)
    if ($action === "list") {
        $stmt = $pdo->query("SELECT * FROM questions ORDER BY id ASC");
        $questions = $stmt->fetchAll();
        echo json_encode(["questions" => array_map(fn($q) => [
            "id" => $q["id"],
            "question" => $q["question"],
            "answers" => [$q["answer1"], $q["answer2"], $q["answer3"], $q["answer4"]],
            "correctIndex" => intval($q["correctIndex"]),
            "imatge" => $q["imatge"]
        ], $questions)]);
        exit;
    }

    // Elimina una pregunta por ID (acción delete)
    if ($action === "delete" && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => "Pregunta eliminada"]);
        } else {
            echo json_encode(["error" => "No se encontró la pregunta con ID $id"]);
        }
        exit;
    }
}

// Procesa peticiones POST (modificaciones o respuestas)
if ($method === "POST") {
    // Actualiza el estado del quiz en la sesión (acción updateState)
    if ($action === "updateState") {
        $input = json_decode(file_get_contents("php://input"), true);
        // Si la sesión ha expirado, devuelve error
        if (!isset($_SESSION['quiz_state'])) {
            http_response_code(400);
            echo json_encode(["error" => "Sessió expirada"]);
            exit;
        }
        // Actualiza respuestas y pregunta actual si se han enviado
        if (isset($input['userAnswers'])) $_SESSION['quiz_state']['userAnswers'] = $input['userAnswers'];
        if (isset($input['currentIndex'])) $_SESSION['quiz_state']['currentIndex'] = $input['currentIndex'];
        echo json_encode($_SESSION['quiz_state']);
        exit;
    }

    // Finaliza el quiz y lo corrige (acción finish)
    if ($action === "finish") {
        if (!isset($_SESSION['quiz_state'])) {
            http_response_code(400);
            echo json_encode(["error" => "Sessió expirada"]);
            exit;
        }
        $state = $_SESSION['quiz_state'];
        $correct = 0;
        $results = [];
        // Corrige cada pregunta y guarda el resultado
        foreach ($state['questions'] as $i => $q) {
            $resposta = intval($state['userAnswers'][$i]);
            $correcte = ($resposta === intval($q['correctIndex']));
            $results[] = [
                "question" => $q['question'],
                "imatge" => $q['imatge'],
                "answers" => $q['answers'],
                "yourAnswer" => $resposta,
                "correctIndex" => intval($q['correctIndex']),
                "correcte" => $correcte
            ];
            if ($correcte) $correct++;
        }
        // Marca el quiz como finalizado y devuelve el resumen
        $_SESSION['quiz_state']['finished'] = true;
        echo json_encode([
            "total" => count($state['questions']),
            "correctes" => $correct,
            "results" => $results
        ]);
        // Destruye la sesión para evitar reinscripción accidental
        session_destroy();
        exit;
    }

    // Gestión de subida de imágenes para las preguntas
    $uploadDir = __DIR__ . '/images/';
    $question = $_POST['question'] ?? '';
    $correctIndex = intval($_POST['correctIndex'] ?? 0);
    $answersRaw = $_POST['answers'] ?? '';
    $answers = json_decode($answersRaw, true);
    $imageUrl = trim($_POST['imatge'] ?? '');
    $finalImageUrl = $imageUrl;

    // Verifica que los datos esenciales de pregunta están completos
    if (empty($question) || !$answers || !is_array($answers) || count($answers) < 4) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incompletos"]);
        exit;
    }

    // Si no se envió URL de imagen pero sí archivo, lo guarda en disco
    if (empty($imageUrl) && isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] == UPLOAD_ERR_OK) {
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $tmp_name = $_FILES['imageFile']['tmp_name'];
        $originalName = basename($_FILES['imageFile']['name']);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $newFileName = uniqid('img_') . '.' . $ext;
        $targetFile = $uploadDir . $newFileName;
        if (move_uploaded_file($tmp_name, $targetFile)) {
            $finalImageUrl = 'http://localhost:8000/images/' . $newFileName;
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error al subir la imagen"]);
            exit;
        }
    }

    // Crea una nueva pregunta (acción create)
    if ($action === "create") {
        $stmt = $pdo->prepare("
            INSERT INTO questions (question, answer1, answer2, answer3, answer4, correctIndex, imatge)
            VALUES (:question, :answer1, :answer2, :answer3, :answer4, :correctIndex, :imatge)
        ");
        $stmt->execute([
            ':question' => $question,
            ':answer1' => $answers[0],
            ':answer2' => $answers[1],
            ':answer3' => $answers[2],
            ':answer4' => $answers[3],
            ':correctIndex' => $correctIndex,
            ':imatge' => $finalImageUrl
        ]);
        echo json_encode(["success" => "Pregunta creada", "imatge" => $finalImageUrl]);
        exit;
    }

    // Actualiza una pregunta existente (acción update)
    if ($action === "update" && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("
            UPDATE questions SET
            question = :question,
            answer1 = :answer1,
            answer2 = :answer2,
            answer3 = :answer3,
            answer4 = :answer4,
            correctIndex = :correctIndex,
            imatge = :imatge
            WHERE id = :id
        ");
        $stmt->execute([
            ':question' => $question,
            ':answer1' => $answers[0],
            ':answer2' => $answers[1],
            ':answer3' => $answers[2],
            ':answer4' => $answers[3],
            ':correctIndex' => $correctIndex,
            ':imatge' => $finalImageUrl,
            ':id' => $id
        ]);
        echo json_encode(["success" => "Pregunta actualizada", "imatge" => $finalImageUrl]);
        exit;
    }
}
?>
