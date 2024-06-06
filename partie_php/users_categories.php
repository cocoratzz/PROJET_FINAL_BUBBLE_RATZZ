<?php
// Connexion à la base de données
$connexion = new mysqli("localhost", "root", "", "bubbleratzz");

// Vérifier la connexion
if ($connexion->connect_error) {
    die("Échec de la connexion à la base de données : " . $connexion->connect_error);
}

// Requête SQL pour récupérer les IDs des utilisateurs
$sql = "SELECT users.id AS user_id FROM users_categories JOIN users ON users_categories.user_id = users.id";
echo "$sql";
$resultat = $connexion->query($sql);

if (!$resultat) {
    echo "Problème de résultat : " . $connexion->error;
} else {
    if ($resultat->num_rows > 0) {
        // Afficher les IDs des utilisateurs
        echo "<ul>";
        while ($row = $resultat->fetch_assoc()) {
            echo "<li>User ID: " . $row['user_id'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "Aucun résultat trouvé";
    }
}

// Fermer la connexion
$connexion->close();
?>