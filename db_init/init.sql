CREATE TABLE IF NOT EXISTS preguntas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  answers JSON NOT NULL,
  correctIndex INT NOT NULL,
  imatge VARCHAR(255)
);

-- Puedes añadir más inserts aquí si lo necesitas
