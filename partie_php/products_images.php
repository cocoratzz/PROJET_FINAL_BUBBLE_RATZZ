<?php
include 'BDD.php';

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logique de gestion des images associées à un produit
    // ...
}

$images = [];
if ($action == 'edit_images' && $id) {
    $images = $conn->prepare("SELECT * FROM produit_images WHERE produit_id = ?");
    $images->execute([$id]);
    $images = $images->fetchAll();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestion des images de produit</title>
</head>
<body>
    <h1>Gestion des images de produit</h1>
    <a href="produit_admin.php">Retour à la liste des produits</a>
    
    <?php if ($action == 'edit_images'): ?>
        <h2>Images du produit</h2>
        <?php
            // Code pour afficher les images associées à un produit spécifique
            // ...
        ?>
    <?php endif; ?>
</body>
</html>
