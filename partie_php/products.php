<?php
include 'BDD.php';

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$nom = '';
$description = '';
$prix = '';
$quantite = '';
$actif = 0;
$categories = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form'] == 'produit') {
        $nom = $_POST['nom'];
        $description = $_POST['description'];
        $prix = $_POST['prix'];
        $quantite = $_POST['quantite'];
        $actif = isset($_POST['actif']) ? 1 : 0;
        $categories = $_POST['categories'] ?? [];

        if ($id) {
            $stmt = $conn->prepare("UPDATE produits SET nom = ?, description = ?, prix = ?, quantite = ?, actif = ? WHERE id = ?");
            $stmt->execute([$nom, $description, $prix, $quantite, $actif, $id]);
        } else {
            $stmt = $conn->prepare("INSERT INTO produits (nom, description, prix, quantite, actif) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $description, $prix, $quantite, $actif]);
            $id = $conn->lastInsertId();
        }

        // Mettre à jour les catégories du produit
        $stmt = $conn->prepare("DELETE FROM produit_categories WHERE produit_id = ?");
        $stmt->execute([$id]);
        foreach ($categories as $categorie_id) {
            $stmt = $conn->prepare("INSERT INTO produit_categories (produit_id, categorie_id) VALUES (?, ?)");
            $stmt->execute([$id, $categorie_id]);
        }

        header('Location: products.php');
        exit;
    }

    if ($_POST['form'] == 'image') {
        $produit_id = $_POST['produit_id'];
        $alt = $_POST['alt'];
        $principal = isset($_POST['principal']) ? 1 : 0;
        $actif = $_POST['actif'];

        if (!empty($_FILES['image']['name'])) {
            $image = $_FILES['image'];
            if ($image['size'] > 2 * 1024 * 1024) {
                echo "L'image ne doit pas dépasser 2Mo.";
                exit;
            }

            $imageName = time() . '_' . $image['name'];
            move_uploaded_file($image['tmp_name'], 'uploads/' . $imageName);

            $stmt = $conn->prepare("INSERT INTO produit_images (produit_id, chemin_image, alt, principal, actif) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$produit_id, $imageName, $alt, $principal, $actif]);
            
            if ($principal) {
                // Assurer qu'il n'y a qu'une seule image principale par produit
                $stmt = $conn->prepare("UPDATE produit_images SET principal = 0 WHERE produit_id = ? AND id != ?");
                $stmt->execute([$produit_id, $conn->lastInsertId()]);
            }
        }
        
        header("Location: products.php?action=edit_images&id=$produit_id");
        exit;
    }
}

if ($action == 'delete') {
    $stmt = $conn->prepare("DELETE FROM produits WHERE id = ?");
    $stmt->execute([$id]);

    // Supprimer les images liées au produit
    $stmt = $conn->prepare("SELECT chemin_image FROM produit_images WHERE produit_id = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
    foreach ($images as $image) {
        unlink('uploads/' . $image['chemin_image']);
    }

    $stmt = $conn->prepare("DELETE FROM produit_images WHERE produit_id = ?");
    $stmt->execute([$id]);

    header('Location: products.php');
    exit;
}

if ($action == 'delete_image') {
    $image_id = $_GET['image_id'];
    $produit_id = $_GET['produit_id'];

    // Supprimer le fichier image
    $stmt = $conn->prepare("SELECT chemin_image FROM produit_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch();
    unlink('uploads/' . $image['chemin_image']);

    // Supprimer l'image de la base de données
    $stmt = $conn->prepare("DELETE FROM produit_images WHERE id = ?");
    $stmt->execute([$image_id]);

    header("Location: products.php?action=edit_images&id=$produit_id");
    exit;
}

$produits = $conn->query("
    SELECT p.id, p.nom, GROUP_CONCAT(c.nom SEPARATOR ', ') as categories, p.actif 
    FROM produits p
    LEFT JOIN produit_categories pc ON p.id = pc.produit_id
    LEFT JOIN categories c ON pc.categorie_id = c.id
    GROUP BY p.id
")->fetchAll();

$toutes_categories = $conn->query("SELECT * FROM categories")->fetchAll();
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
    <title>Administration des produits</title>
</head>
<body>
    <h1>Administration des produits</h1>
    <a href="products.php">Liste des produits</a> | 
    <a href="products.php?action=add">Ajouter un nouveau produit</a>
    
    <?php if ($action == 'add' || $action == 'edit' || $action == 'edit_images'): ?>
        <?php if ($action == 'add' || $action == 'edit'): ?>
            <h2><?= $id ? 'Modifier' : 'Ajouter' ?> un produit</h2>
            <?php
                if ($id) {
                    $stmt = $conn->prepare("SELECT * FROM produits WHERE id = ?");
                    $stmt->execute([$id]);
                    $produit = $stmt->fetch();
                    $nom = $produit['nom'];
                    $description = $produit['description'];
                    $prix = $produit['prix'];
                    $quantite = $produit['quantite'];
                    $actif = $produit['actif'];
                    $stmt = $conn->prepare("SELECT categorie_id FROM produit_categories WHERE produit_id = ?");
                    $stmt->execute([$id]);
                    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
            ?>
            <form method="POST">
                <input type="hidden" name="form" value="produit">
                <input type="hidden" name="id" value="<?= $id ?>">
                <label>Nom:</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" required><br>
                <label>Description:</label>
                <textarea name="description" required><?= htmlspecialchars($description) ?></textarea><br>
                <label>Prix:</label>
                <input type="text" name="prix" value="<?= htmlspecialchars($prix) ?>" required><br>
                <label>Quantité:</label>
                <input type="number" name="quantite" value="<?= htmlspecialchars($quantite) ?>" required><br>
                <label>Catégories:</label>
                <select name="categories[]" multiple required>
                    <?php foreach ($toutes_categories as $categorie): ?>
                        <option value="<?= $categorie['id'] ?>" <?= in_array($categorie['id'], $categories) ? 'selected' : '' ?>><?= htmlspecialchars($categorie['nom']) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <label>Activé:</label>
                <input type="checkbox" name="actif" <?= $actif ? 'checked' : '' ?>><br>
                <button type="submit"><?= $id ? 'Modifier' : 'Ajouter' ?></button>
            </form>
        <?php elseif ($action == 'edit_images'): ?>
            <h2>Images du produit</h2>
            <table border="1">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Alt</th>
                    <th>Principal</th>
                    <th>Actif</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($images as $image): ?>
                    <tr>
                        <td><?= $image['id'] ?></td>
                        <td><img src="uploads/<?= htmlspecialchars($image['chemin_image']) ?>" width="100" alt="<?= htmlspecialchars($image['alt']) ?>"></td>
                        <td><?= htmlspecialchars($image['alt']) ?></td>
                        <td><?= $image['principal'] ? 'Oui' : 'Non' ?></td>
                        <td><?= $image['actif'] ? 'Oui' : 'Non' ?></td>
                        <td>
                            <a href="products.php?action=delete_image&image_id=<?= $image['id'] ?>&produit_id=<?= $id ?>" onclick="return confirm('Voulez-vous vraiment supprimer cette image ?');">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <h3>Ajouter une nouvelle image</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form" value="image">
                <input type="hidden" name="produit_id" value="<?= $id ?>">
                <label>Image:</label>
                <input type="file" name="image" required><br>
                <label>Alt:</label>
                <input type="text" name="alt" required><br>
                <label>Principal:</label>
                <input type="checkbox" name="principal"><br>
                <label>Actif:</label>
                <select name="actif">
                    <option value="1">Oui</option>
                    <option value="0">Non</option>
                </select><br>
                <button type="submit">Ajouter</button>
            </form>
            <a href="products.php">Retour à la liste des produits</a>
        <?php endif; ?>
    <?php else: ?>
        <h2>Liste des produits</h2>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Catégories</th>
                <th>Actif</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($produits as $produit): ?>
                <tr>
                    <td><?= $produit['id'] ?></td>
                    <td><?= htmlspecialchars($produit['nom']) ?></td>
                    <td><?= htmlspecialchars($produit['categories']) ?></td>
                    <td><?= $produit['actif'] ? 'Oui' : 'Non' ?></td>
                    <td>
                        <a href="products.php?action=edit&id=<?= $produit['id'] ?>">Modifier</a> |
                        <a href="products.php?action=edit_images&id=<?= $produit['id'] ?>">Images</a> |
                        <a href="products.php?action=delete&id=<?= $produit['id'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer ce produit ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
