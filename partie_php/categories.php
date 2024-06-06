<?php
include 'BDD.php';

// Initialisation des variables
$id = null;
$nom = '';
$description = '';
$image = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $description = $_POST['description'];
    $image = $_FILES['image'];

    if ($image['size'] > 2 * 1024 * 1024) {
        echo "L'image ne doit pas dépasser 2Mo.";
        exit;
    }

    $imageName = time() . '_' . $image['name'];
    move_uploaded_file($image['tmp_name'], 'uploads/' . $imageName);

    if (isset($_POST['id']) && $_POST['id'] != '') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE categories SET nom = ?, description = ?, image = ? WHERE id = ?");
        $stmt->execute([$nom, $description, $imageName, $id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (nom, description, image) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $description, $imageName]);
    }

    header('Location: categories.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $categorie = $stmt->fetch();
    $nom = $categorie['nom'];
    $description = $categorie['description'];
    $image = $categorie['image'];
}

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Mettre à jour les produits associés à cette catégorie
    $stmt = $conn->prepare("UPDATE produits SET categorie_id = NULL WHERE categorie_id = ?");
    $stmt->execute([$id]);

    // Supprimer la catégorie
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: categories.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM categories");
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestion des catégories</title>
</head>
<body>
    <h1>Liste des catégories</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Description</th>
            <th>Image</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($categories as $categorie): ?>
            <tr>
                <td><?= $categorie['id'] ?></td>
                <td><?= htmlspecialchars($categorie['nom']) ?></td>
                <td><?= htmlspecialchars($categorie['description']) ?></td>
                <td><img src="uploads/<?= htmlspecialchars($categorie['image']) ?>" width="100"></td>
                <td>
                    <a href="categories.php?id=<?= $categorie['id'] ?>">Modifier</a>
                    <a href="categories.php?delete_id=<?= $categorie['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h1><?= $id ? 'Modifier' : 'Ajouter' ?> une catégorie</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $id ?>">
        <label>Nom:</label>
        <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" required><br>
        <label>Description:</label>
        <textarea name="description"><?= htmlspecialchars($description) ?></textarea><br>
        <label>Image:</label>
        <input type="file" name="image" accept="image/jpeg, image/png, image/gif" <?= $id ? '' : 'required' ?>><br>
        <button type="submit"><?= $id ? 'Modifier' : 'Ajouter' ?></button>
    </form>
</body>
</html>
