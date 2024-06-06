<?php
include 'BDD.php';

// Récupérer toutes les commandes
$orders = $conn->query("
    SELECT o.id, u.nom, u.prenom, o.total, o.created_at 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Liste des commandes</title>
</head>
<body>
    <h1>Liste des commandes</h1>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom / Prénom</th>
                <th>Total</th>
                <th>Date</th>
                <th>Détails</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['nom'] . ' ' . $order['prenom']) ?></td>
                    <td><?= $order['total'] ?></td>
                    <td><?= $order['created_at'] ?></td>
                    <td><a href="orders_products.php?id=<?= $order['id'] ?>">Voir détails</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
