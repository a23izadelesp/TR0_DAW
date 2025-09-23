<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quizdb";

// Aquí debe ir tu JSON completo de preguntas
$questionsJson = <<<JSON
[ ... TU JSON DE PREGUNTAS ... ]
JSON;

$questionsSeed = json_decode($questionsJson, true);

try {
    $pdo = new PDO("mysql:host=$servername", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

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
    http_response_code(500);
    echo json_encode(["error" => "DB Error: " . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === "GET") {
    if ($action === "load") {
        if (isset($_SESSION['quiz_state'])) {
            echo json_encode($_SESSION['quiz_state']);
            exit;
        }
        $count = intval($_GET['num'] ?? 10);
        $stmt = $pdo->query("SELECT * FROM questions ORDER BY RAND() LIMIT $count");
        $selected = $stmt->fetchAll();
        $quizState = [
            "questions" => array_map(fn($q) => [
                "id" => $q["id"],
                "question" => $q["question"],
                "answers" => [$q["answer1"], $q["answer2"], $q["answer3"], $q["answer4"]],
                "correctIndex" => intval($q["correctIndex"]),
                "imatge" => $q["imatge"]
            ], $selected),
            "userAnswers" => array_fill(0, count($selected), -1),
            "currentIndex" => 0,
            "finished" => false,
        ];
        $_SESSION['quiz_state'] = $quizState;
        echo json_encode($quizState);
        exit;
    } elseif ($action === "list") {
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
} elseif ($method === "POST") {
    // Guardar progreso en sesión: Aquí cambia action de 'update' a 'updateState'
    if ($action === "updateState") {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!isset($_SESSION['quiz_state'])) {
            http_response_code(400);
            echo json_encode(["error" => "Sessió expirada"]);
            exit;
        }
        if (isset($input['userAnswers'])) {
            $_SESSION['quiz_state']['userAnswers'] = $input['userAnswers'];
        }
        if (isset($input['currentIndex'])) {
            $_SESSION['quiz_state']['currentIndex'] = $input['currentIndex'];
        }
        echo json_encode($_SESSION['quiz_state']);
        exit;
    }
    // Fin de quiz
    elseif ($action === "finish") {
        if (!isset($_SESSION['quiz_state'])) {
            http_response_code(400);
            echo json_encode(["error" => "Sessió expirada"]);
            exit;
        }
        $state = $_SESSION['quiz_state'];
        $correct = 0;
        $results = [];
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
        $_SESSION['quiz_state']['finished'] = true;
        echo json_encode([
            "total" => count($state['questions']),
            "correctes" => $correct,
            "results" => $results
        ]);
        session_destroy();
        exit;
    }
    // Crear pregunta
    elseif ($action === "create") {
        $input = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("INSERT INTO questions (question, answer1, answer2, answer3, answer4, correctIndex, imatge) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['question'],
            $input['answers'][0],
            $input['answers'][1],
            $input['answers'][2],
            $input['answers'][3],
            intval($input['correctIndex']),
            $input['imatge']
        ]);
        echo json_encode(["success" => "Pregunta creada"]);
        exit;
    }
    // Actualizar pregunta en base de datos
    elseif ($action === "update" && isset($_GET['id'])) {
        $input = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("UPDATE questions SET question = :question, answer1 = :answer1, answer2 = :answer2, answer3 = :answer3, answer4 = :answer4, correctIndex = :correctIndex, imatge = :imatge WHERE id = :id");
        $stmt->execute([
            ':question' => $input['question'],
            ':answer1' => $input['answers'][0],
            ':answer2' => $input['answers'][1],
            ':answer3' => $input['answers'][2],
            ':answer4' => $input['answers'][3],
            ':correctIndex' => intval($input['correctIndex']),
            ':imatge' => $input['imatge'],
            ':id' => intval($_GET['id'])
        ]);
        echo json_encode(["success" => "Pregunta actualitzada"]);
        exit;
    }
    // Borrar pregunta
    elseif ($action === "delete" && isset($_GET['id'])) {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        echo json_encode(["success" => "Pregunta eliminada"]);
        exit;
    }
}
?>
