<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
            text-align: left;
        }
        form {
            margin-top: 20px;
        }
        form label {
            display: block;
            margin: 5px 0;
        }
        form input, form select {
            padding: 8px;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <?php
    // Connexion à la base de données
    function getDbConnection() {
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $dbname = 'bubbleratzz';

        $connexion = new mysqli($host, $username, $password, $dbname);

        if ($connexion->connect_error) {
            die("Connection failed: " . $connexion->connect_error);
        }

        return $connexion;
    }

    // Modèle des utilisateurs
    function getAllUsers() {
        $db = getDbConnection();
        $query = "SELECT * FROM users";
        $result = $db->query($query);

        if ($result === false) {
            die('Error: ' . $db->error);
        }

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        return $users;
    }

    function getUserById($id) {
        $db = getDbConnection();
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    function createUser($nom, $prenom, $email, $password, $adresse) {
        $db = getDbConnection();
        $query = "INSERT INTO users (nom, prenom, email, password, adresse) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param('sssss', $nom, $prenom, $email, $hashed_password, $adresse);
        $stmt->execute();
    }
    
    function updateUser($id, $nom, $prenom, $email, $password, $adresse) {
        $db = getDbConnection();
        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET nom = ?, prenom = ?, email = ?, password = ?, adresse = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('sssssi', $nom, $prenom, $email, $hashed_password, $adresse, $id);
        } else {
            $query = "UPDATE users SET nom = ?, prenom = ?, email = ?, adresse = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('ssssi', $nom, $prenom, $email, $adresse, $id);
        }
        $stmt->execute();
    }
    

    function deleteUser($id) {
        $db = getDbConnection();
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    // Inclure le code des fonctions et la connexion à la base de données ici
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérifier quelle action est demandée
        $action = $_POST['action'];
        $id = $_POST['id'] ?? null;
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $password = $_POST['password'] ?? null;
        $adresse = $_POST['adresse'];
    
        // Appeler la fonction appropriée en fonction de l'action
        if ($action === 'create') {
            createUser($nom, $prenom, $email, $password, $adresse);
        } elseif ($action === 'update') {
            updateUser($id, $nom, $prenom, $email, $password, $adresse);
        }
    
        // Redirection vers une autre page ou actualisation de la même page
        header('Location: users.php');
        exit();
    }
    

    $users = getAllUsers();
    ?>
    <!-- <table>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['nom']) ?></td>
                    <td><?= htmlspecialchars($user['prenom']) ?></td>
                    <td><?= htmlspecialchars($user['adresse']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table> -->
    
    <form action="users.php" method="post">
    <input type="hidden" name="action" value="<?= isset($_GET['action']) && $_GET['action'] === 'edit' ? 'update' : 'create' ?>">
    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit'): ?>
        <?php $user = getUserById($_GET['id']); ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
    <?php endif; ?>
    <label for="nom">Nom :</label>
    <input type="text" id="nom" name="nom" value="<?= isset($user) ? htmlspecialchars($user['nom']) : '' ?>" required>
    <label for="prenom">Prénom :</label>
    <input type="text" id="prenom" name="prenom" value="<?= isset($user) ? htmlspecialchars($user['prenom']) : '' ?>" required>
    <label for="adresse">Adresse :</label>
    <input type="text" id="adresse" name="adresse" value="<?= isset($user) ? htmlspecialchars($user['adresse']) : '' ?>" required>
    <label for="email">Email :</label>
    <input type="text" id="email" name="email" value="<?= isset($user) ? htmlspecialchars($user['email']) : '' ?>" required>
    <label for="password">Mot de passe :</label>
    <input type="password" id="password" name="password" required>
    <input type="submit" value="Enregistrer"> <!-- Bouton Enregistrer -->
</form>


