<?php
// Mostrar todos los errores para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión para guardar estado
session_start();

// CORS y cabeceras JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST');

// Configuración conexión a MySQL en XAMPP
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quizdb";

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
            correctIndex INT NOT NULL
        )
    ");

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
    $row = $stmt->fetch();
    if ($row['total'] == 0) {
        $questions = [
            ["What is the scientific name of a butterfly?", "Apis", "Coleoptera", "Formicidae", "Rhopalocera", 3],
            ["How hot is the surface of the sun?", "1,233 K", "5,778 K", "12,130 K", "101,300 K", 1],
            ["Who are the actors in The Internship?", "Ben Stiller, Jonah Hill", "Courteney Cox, Matt LeBlanc", "Kaley Cuoco, Jim Parsons", "Vince Vaughn, Owen Wilson", 3],
            ["What is the capital of Spain?", "Berlin", "Buenos Aires", "Madrid", "San Juan", 2],
            ["What are the school colors of the University of Texas at Austin?", "Black, Red", "Blue, Orange", "White, Burnt Orange", "White, Old gold, Gold", 2],
            ["What is 70 degrees Fahrenheit in Celsius?", "18.8889", "20", "21.1111", "158", 2],
            ["When was Mahatma Gandhi born?", "October 2, 1869", "December 15, 1872", "July 18, 1918", "January 15, 1929", 0],
            ["How far is the moon from Earth?", "7,918 miles (12,742 km)", "86,881 miles (139,822 km)", "238,400 miles (384,400 km)", "35,980,000 miles (57,910,000 km)", 2],
            ["What is 65 times 52?", "117", "3120", "3380", "3520", 2],
            ["How tall is Mount Everest?", "6,683 ft (2,037 m)", "7,918 ft (2,413 m)", "19,341 ft (5,895 m)", "29,029 ft (8,847 m)", 3],
            ["When did The Avengers come out?", "May 2, 2008", "May 4, 2012", "May 3, 2013", "April 4, 2014", 1],
            ["What is 48,879 in hexidecimal?", "0x18C1", "0xBEEF", "0xDEAD", "0x12D591", 1],
        ];
        $stmtInsert = $pdo->prepare("INSERT INTO questions (question, answer1, answer2, answer3, answer4, correctIndex) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($questions as $q) {
            $stmtInsert->execute($q);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en base de datos: " . $e->getMessage()]);
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
            ], $selected),
            "userAnswers" => array_fill(0, count($selected), -1),
            "currentIndex" => 0,
            "finished" => false,
        ];

        $_SESSION['quiz_state'] = $quizState;
        echo json_encode($quizState);
        exit;
    }
    elseif ($action === "list") {
        $stmt = $pdo->query("SELECT * FROM questions ORDER BY id ASC");
        $questions = $stmt->fetchAll();
        echo json_encode(["questions" => $questions]);
        exit;
    }
} elseif ($method === "POST") {
    if ($action === "update" && isset($_GET['id']) && $_GET['id'] != "") {
        $input = json_decode(file_get_contents("php://input"), true);
        error_log("Debug Update ID: " . $_GET['id']);
        error_log("Input data: " . print_r($input, true));
        if(
            !isset($input['question']) ||
            !isset($input['answer1']) ||
            !isset($input['answer2']) ||
            !isset($input['answer3']) ||
            !isset($input['answer4']) ||
            !isset($input['correctIndex'])
        ){
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE questions SET question = :question, answer1 = :answer1, answer2 = :answer2, answer3 = :answer3, answer4 = :answer4, correctIndex = :correctIndex WHERE id = :id");
        try {
            $stmt->execute([
                ':question' => $input['question'],
                ':answer1' => $input['answer1'],
                ':answer2' => $input['answer2'],
                ':answer3' => $input['answer3'],
                ':answer4' => $input['answer4'],
                ':correctIndex' => intval($input['correctIndex']),
                ':id' => intval($_GET['id'])
            ]);
            echo json_encode(["success" => "Pregunta actualizada"]);
        } catch (Exception $e) {
            error_log("Error al actualizar pregunta: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Error al actualizar pregunta"]);
        }
        exit;
    }
    elseif ($action === "update") {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!isset($_SESSION['quiz_state'])) {
            http_response_code(400);
            echo json_encode(["error" => "Sesión expirada"]);
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
    elseif ($action === "finish") {
        if (!isset($_SESSION['quiz_state'])) {
            http_response_code(400);
            echo json_encode(["error" => "Sesión expirada"]);
            exit;
        }
        $state = $_SESSION['quiz_state'];
        $ids = array_column($state['questions'], 'id');
        $inClause = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, correctIndex FROM questions WHERE id IN ($inClause)");
        $stmt->execute($ids);
        $corrections = $stmt->fetchAll();

        $lookup = [];
        foreach ($corrections as $c) {
            $lookup[$c['id']] = intval($c['correctIndex']);
        }

        $corrects = 0;
        foreach ($state['questions'] as $i => $q) {
            $qid = $q['id'];
            $ua = intval($state['userAnswers'][$i]);
            if ($ua === $lookup[$qid]) {
                $corrects++;
            }
        }

        $_SESSION['quiz_state']['finished'] = true;

        echo json_encode([
            "total" => count($state['questions']),
            "correctes" => $corrects,
        ]);
        session_destroy();
        exit;
    }
    elseif ($action === "create") {
        $input = json_decode(file_get_contents("php://input"), true);
        if(
            !isset($input['question']) ||
            !isset($input['answer1']) ||
            !isset($input['answer2']) ||
            !isset($input['answer3']) ||
            !isset($input['answer4']) ||
            !isset($input['correctIndex'])
        ){
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO questions (question, answer1, answer2, answer3, answer4, correctIndex) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([
                $input['question'],
                $input['answer1'],
                $input['answer2'],
                $input['answer3'],
                $input['answer4'],
                intval($input['correctIndex'])
            ]);
            echo json_encode(["success" => "Pregunta creada"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error al crear pregunta"]);
        }
        exit;
    }
    elseif ($action === "delete" && isset($_GET['id']) && $_GET['id'] != "") {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        try {
            $stmt->execute([intval($_GET['id'])]);
            echo json_encode(["success" => "Pregunta eliminada"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error al eliminar pregunta"]);
        }
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Acción no válida"]);
}
?>
