<?php
include 'BDD.php';

// Récupérer l'ID de la commande
$order_id = $_GET['id'];

// Récupérer les détails de la commande
$stmt = $conn->prepare("
    SELECT o.*, u.nom AS user_nom, u.prenom AS user_prenom, u.adresse, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Récupérer les produits de la commande
$stmt = $conn->prepare("
    SELECT op.*
    FROM order_products op
    WHERE op.order_id = ?
");
$stmt->execute([$order_id]);
$order_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Détails de la commande</title>
</head>
<body>
    <h1>Détails de la commande</h1>
    <p><strong>ID:</strong> <?= $order['id'] ?></p>
    <p><strong>Nom:</strong> <?= htmlspecialchars($order['user_nom']) ?></p>
    <p><strong>Prénom:</strong> <?= htmlspecialchars($order['user_prenom']) ?></p>
    <p><strong>Adresse:</strong> <?= htmlspecialchars($order['adresse']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
    <h2>Produits</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Nom du produit</th>
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order_products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                    <td><?= $product['quantity'] ?></td>
                    <td><?= $product['unit_price'] ?></td>
                    <td><?= $product['quantity'] * $product['unit_price'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Total de la commande: <?= $order['total'] ?></h3>
    <p><a href="order.php">Retour à la liste des commandes</a></p>
</body>
</html>
